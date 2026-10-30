/**
 * Construcción de la instantánea emitida (contenido congelado de una versión).
 *
 * - No incluye correo electrónico, datos de autenticación ni identificadores internos.
 * - Los nombres de colaboradores (terceros) no se incluyen: solo sus roles.
 * - Las referencias internas se sustituyen por referencias estables (K1, T1, U1, E1…).
 */
import { assessArticle50, type Article50Result } from "@/lib/legal/article50";
import { labelOf, PHASE_LABELS, SCHEME_VERSION, type CreativePhase, type DeclarationProfile } from "@/lib/oriva-code";
import { canonicalize, type JsonValue } from "@/lib/integrity/canonical";
import { randomSalt, SNAPSHOT_FORMAT, SECTION_NAMES, type FullSnapshot, type SectionName } from "@/lib/integrity/disclosure";
import { AI_USAGE_LABEL, COMPONENT_KIND_LABEL, SCOPE_LABEL } from "@/lib/labels";
import { article50Of, effectiveAiUsage, type VersionFull } from "./load";

export interface SnapshotContext {
  publicId: string;
  issuedAt: Date;
  verifyUrl: string;
  statement: { key: string; version: string; locale: string; text: string; textHash: string };
}

export interface RefMaps {
  component: Map<string, string>;
  tool: Map<string, string>;
  use: Map<string, string>;
  contribution: Map<string, string>;
  evidence: Map<string, string>;
}

export function buildRefMaps(v: VersionFull): RefMaps {
  const component = new Map(v.declaration.work.components.map((c, i) => [c.id, `K${i + 1}`]));
  const tool = new Map(v.tools.map((t, i) => [t.id, `T${i + 1}`]));
  const use = new Map(v.uses.map((u, i) => [u.id, `U${i + 1}`]));
  const contribution = new Map<string, string>();
  for (const u of v.uses) for (const c of u.contributions) contribution.set(c.id, `${use.get(u.id)}-${c.contributionType}`);
  const evidence = new Map(v.evidence.map((e, i) => [e.id, `E${i + 1}`]));
  return { component, tool, use, contribution, evidence };
}

const s = (x: string | null | undefined): string | null => (x && x.trim() ? x.trim() : null);
const iso = (d: Date | null | undefined): string | null => (d ? d.toISOString() : null);
const dateOnly = (d: Date | null | undefined): string | null => (d ? d.toISOString().slice(0, 10) : null);

/** Texto de cobertura: deja claro qué abarca y qué no abarca la declaración. */
export function coverageStatement(v: VersionFull, refs: RefMaps): string {
  const work = v.declaration.work;
  const names = v.scopeComponentIds
    .map((id) => work.components.find((c) => c.id === id))
    .filter(Boolean)
    .map((c) => `«${c!.name}»`);
  const others = work.isCollective || work.contributors.length > 0;
  switch (v.scope) {
    case "WHOLE_WORK":
      return others
        ? "La declaración se refiere a la obra completa, dentro del ámbito de conocimiento y responsabilidad del declarante. En una obra colectiva no acredita por sí misma el proceso seguido por los demás participantes."
        : "La declaración se refiere a la obra completa.";
    case "COMPONENTS":
      return `La declaración se limita a: ${names.join(", ") || "los componentes indicados"}. No abarca necesariamente otros componentes de la obra${others ? " ni las aportaciones de los demás participantes" : ""}.`;
    case "OWN_CONTRIBUTION":
      return `La declaración se limita a la contribución del declarante${v.declarantRole ? ` (${v.declarantRole})` : ""}${names.length ? ` en ${names.join(", ")}` : ""}. No abarca necesariamente otras partes de la obra ni las aportaciones de los demás participantes.`;
    default:
      void refs;
      return "Alcance no indicado.";
  }
}

/** Resumen sintético determinista (sin modelos generativos). */
export function buildSummaryText(v: VersionFull, profile: DeclarationProfile, refs: RefMaps): string {
  const w = v.declaration.work;
  const head = `Obra: «${w.title}» (${w.category.labelEs}).`;
  const eff = effectiveAiUsage(v);
  if (eff === "NO") return `${head} El declarante afirma que no se utilizaron sistemas de inteligencia artificial en el ámbito declarado. ${coverageStatement(v, refs)}`;
  if (eff === "UNCERTAIN")
    return `${head} El declarante no puede determinar con certeza si se utilizaron sistemas de inteligencia artificial.${v.uncertaintyNote ? ` Motivo indicado: ${v.uncertaintyNote.trim()}` : ""} ${coverageStatement(v, refs)}`;
  const lines = profile.uses.map((u) => {
    const use = v.uses.find((x) => x.id === u.ref)!;
    const fn = u.functions.map((f) => labelOf(f).toLowerCase()).join(", ");
    const pairs = u.pairs
      .map((p) => (p.b === "B0" ? "sin incorporación a la obra" : `${labelOf(p.b).toLowerCase()} con ${p.c ? labelOf(p.c).toLowerCase() : "intervención no precisada"}`))
      .join("; ");
    return `${refs.use.get(u.ref)} «${use.title}»: ${fn || "función no indicada"}; ${pairs || "incorporación no precisada"}.`;
  });
  return `${head} Se declaran ${profile.uses.length} uso${profile.uses.length === 1 ? "" : "s"} de IA. ${lines.join(" ")} ${coverageStatement(v, refs)}`;
}

export function buildSnapshot(v: VersionFull, profile: DeclarationProfile, ctx: SnapshotContext): { snapshot: FullSnapshot; refs: RefMaps; article50: Article50Result } {
  const refs = buildRefMaps(v);
  const w = v.declaration.work;
  const art50Answers = article50Of(v);
  const article50 = assessArticle50(art50Answers);

  const data: Record<SectionName, JsonValue> = {
    meta: {
      platform: "ORIVA",
      documentType: "ORIVA Declaration",
      publicId: ctx.publicId,
      versionNumber: v.versionNumber,
      issuedAt: ctx.issuedAt.toISOString(),
      schemeVersion: v.scheme.version,
      questionnaireVersion: v.questionnaireVersion.version,
      supersedesPublicId: v.supersedes?.publicId ?? null,
      verifyUrl: ctx.verifyUrl,
    },
    work: {
      title: w.title,
      subtitle: s(w.subtitle),
      category: { key: w.categoryKey, label: w.category.labelEs },
      description: s(w.description),
      creationPeriod: s(w.creationPeriod),
      status: w.status,
      publicationDate: dateOnly(w.publicationDate),
      language: s(w.language),
      workVersion: s(w.workVersion),
      publisher: s(w.publisher),
      identifierType: s(w.identifierType),
      identifier: s(w.identifier),
      url: s(w.url),
      isCollective: w.isCollective,
      components: w.components.map((c) => ({ ref: refs.component.get(c.id)!, name: c.name, kind: c.kind, kindLabel: COMPONENT_KIND_LABEL[c.kind], description: s(c.description) })),
      contributorRoles: w.contributors.map((c) => ({ role: c.role, componentRefs: c.componentIds.map((id) => refs.component.get(id)).filter((x): x is string => !!x) })),
    },
    declarant: {
      name: v.declarantName ?? "",
      artisticName: s(v.declarantArtisticName),
      organization: s(v.declarantOrganization),
      role: s(v.declarantRole),
    },
    scope: {
      scope: v.scope ?? null,
      scopeLabel: v.scope ? SCOPE_LABEL[v.scope] : null,
      componentRefs: v.scopeComponentIds.map((id) => refs.component.get(id)).filter((x): x is string => !!x),
      responsibilityNote: s(v.responsibilityNote),
      aiUsage: v.aiUsage ?? null,
      ownContributionAiUsage: v.ownContributionAiUsage ?? null,
      effectiveAiUsage: effectiveAiUsage(v),
      effectiveAiUsageLabel: effectiveAiUsage(v) ? AI_USAGE_LABEL[effectiveAiUsage(v)!] : null,
      uncertaintyNote: s(v.uncertaintyNote),
      coverageStatement: coverageStatement(v, refs),
    },
    classification: {
      scheme: "ORIVA Code",
      schemeVersion: SCHEME_VERSION,
      computedAt: ctx.issuedAt.toISOString(),
      status: profile.status,
      code: profile.code,
      compositeNotation: profile.compositeNotation,
      composite: profile.composite ? { A: profile.composite.A, B: profile.composite.B, C: profile.composite.C, D: profile.composite.D } : null,
      documentation: profile.documentation,
      insufficientUseCount: profile.insufficientUseCount,
      rulesApplied: profile.rulesApplied,
      uses: profile.uses.map((u) => ({
        ref: refs.use.get(u.ref)!,
        componentRef: u.componentRef ? refs.component.get(u.componentRef) ?? null : null,
        status: u.status,
        code: u.code,
        notation: u.notation,
        functions: u.functions,
        compactFunction: u.compactFunction,
        pairs: u.pairs.map((p) => ({ contributionRef: p.contributionRef ? refs.contribution.get(p.contributionRef) ?? null : null, b: p.b, c: p.c })),
        documentation: u.documentation,
        rulesApplied: u.rulesApplied,
      })),
      components: profile.components.map((c) => ({
        componentRef: c.componentRef ? refs.component.get(c.componentRef) ?? null : null,
        status: c.status,
        code: c.code,
        compositeNotation: c.compositeNotation,
        useRefs: c.useRefs.map((r) => refs.use.get(r)!),
      })),
    },
    summary: { text: buildSummaryText(v, profile, refs) },
    process: {
      processNarrative: s(v.processNarrative),
      observations: s(v.observations),
      tools: v.tools.map((t) => ({
        ref: refs.tool.get(t.id)!,
        identified: t.identified,
        name: t.name,
        provider: s(t.provider),
        modelVersion: s(t.modelVersion),
        systemType: s(t.systemType),
        purpose: s(t.purpose),
        usagePeriod: s(t.usagePeriod),
      })),
      uses: v.uses.map((u) => ({
        ref: refs.use.get(u.id)!,
        title: u.title,
        componentRef: u.componentId ? refs.component.get(u.componentId) ?? null : null,
        toolRef: u.toolId ? refs.tool.get(u.toolId) ?? null : null,
        parentRef: u.parentUseId ? refs.use.get(u.parentUseId) ?? null : null,
        phases: u.phases.map((p) => p.phase).sort(),
        phaseLabels: u.phases.map((p) => p.phase).sort().map((p) => PHASE_LABELS[p as CreativePhase]?.es ?? p),
        functions: [...u.functions].sort(),
        primaryFunction: u.primaryFunction ?? null,
        objective: s(u.objective),
        activityDescription: s(u.activityDescription),
        resultDescription: s(u.resultDescription),
        incorporation: u.incorporation ?? null,
        contributions: u.contributions.map((c) => ({
          ref: refs.contribution.get(c.id)!,
          type: c.contributionType,
          interventionAnswer: c.interventionAnswer ?? null,
          retainsRecognizableElements: c.retainsRecognizableElements ?? null,
          interventionCode: profile.uses.find((p) => p.ref === u.id)?.pairs.find((p) => p.contributionRef === c.id)?.c ?? null,
          interventionDescription: s(c.interventionDescription),
        })),
        evidenceRefs: u.evidence.map((e) => refs.evidence.get(e.id)!),
      })),
    },
    article50: {
      answers: art50Answers as unknown as JsonValue,
      answered: article50.answered,
      findings: article50.findings.map((f) => ({ id: f.id, level: f.level, title: f.title, text: f.text, references: f.references })),
      disclaimer: article50.disclaimer,
    },
    statement: {
      legalTextKey: ctx.statement.key,
      legalTextVersion: ctx.statement.version,
      locale: ctx.statement.locale,
      text: ctx.statement.text,
      textSha256: ctx.statement.textHash,
      acceptedAt: ctx.issuedAt.toISOString(),
      acceptanceMethod: "Aceptación expresa mediante acción autenticada en la cuenta del declarante (no constituye firma electrónica cualificada).",
    },
    evidence: {
      note: "Las evidencias no han sido auditadas por ORIVA. Su registro no acredita su autenticidad.",
      items: v.evidence.map((e) => ({
        ref: refs.evidence.get(e.id)!,
        kind: e.kind,
        description: e.description,
        useRef: e.useId ? refs.use.get(e.useId) ?? null : null,
        fileName: e.fileName ?? null,
        mimeType: e.mimeType ?? null,
        sizeBytes: e.sizeBytes ?? null,
        sha256: e.sha256 ?? null,
      })),
    },
  };

  // Normaliza: elimina undefined y valida que todo sea serializable canónicamente.
  const sections = {} as FullSnapshot["sections"];
  for (const name of SECTION_NAMES) {
    const normalized = JSON.parse(JSON.stringify(data[name])) as JsonValue;
    canonicalize(normalized);
    sections[name] = { salt: randomSalt(), data: normalized };
  }
  return { snapshot: { format: SNAPSHOT_FORMAT, sections }, refs, article50 };
}

export { iso };
