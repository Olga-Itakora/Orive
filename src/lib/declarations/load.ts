import type { Prisma } from "@prisma/client";
import type {
  AIUseInput,
  DeclarationInput,
  IncorporatedContributionCode,
  InterventionAnswer,
  TriState,
  UseFunctionCode,
} from "@/lib/oriva-code";
import { article50AnswersSchema, type Article50Answers } from "@/lib/legal/article50";

export const versionFullInclude = {
  declaration: { include: { work: { include: { category: true, components: { orderBy: [{ sortOrder: "asc" }, { createdAt: "asc" }] }, contributors: true } } } },
  tools: { orderBy: [{ sortOrder: "asc" }, { createdAt: "asc" }] },
  uses: {
    orderBy: [{ sortOrder: "asc" }, { createdAt: "asc" }],
    include: { phases: true, contributions: { orderBy: { contributionType: "asc" } }, evidence: true, tool: true, component: true },
  },
  evidence: { orderBy: { createdAt: "asc" } },
  questionnaireVersion: true,
  scheme: true,
  acceptance: { include: { legalText: true } },
  supersedes: { select: { id: true, publicId: true, versionNumber: true } },
  supersededBy: { select: { id: true, publicId: true, versionNumber: true, status: true } },
} satisfies Prisma.DeclarationVersionInclude;

export type VersionFull = Prisma.DeclarationVersionGetPayload<{ include: typeof versionFullInclude }>;

/** Respuesta efectiva sobre el uso de IA (resuelve "solo puedo declarar sobre mi contribución"). */
export function effectiveAiUsage(v: Pick<VersionFull, "aiUsage" | "ownContributionAiUsage">): "NO" | "YES" | "UNCERTAIN" | null {
  if (v.aiUsage === "OWN_CONTRIBUTION_ONLY") {
    const own = v.ownContributionAiUsage;
    return own === "NO" || own === "YES" || own === "UNCERTAIN" ? own : null;
  }
  return v.aiUsage ?? null;
}

export function toDeclarationInput(v: VersionFull): DeclarationInput {
  const declarationEvidenceCount = v.evidence.filter((e) => !e.useId).length;
  const uses: AIUseInput[] = v.uses.map((u) => ({
    ref: u.id,
    componentRef: u.componentId,
    toolIdentified: u.tool ? u.tool.identified : null,
    phases: u.phases.map((p) => p.phase),
    functions: u.functions as UseFunctionCode[],
    primaryFunction: (u.primaryFunction as UseFunctionCode | "NONE" | null) ?? null,
    objective: u.objective ?? "",
    activityDescription: u.activityDescription ?? "",
    resultDescription: u.resultDescription ?? "",
    incorporation: u.incorporation ?? null,
    contributions: u.contributions.map((c) => ({
      ref: c.id,
      type: c.contributionType as IncorporatedContributionCode,
      interventionAnswer: (c.interventionAnswer as InterventionAnswer | null) ?? null,
      retainsRecognizableElements: (c.retainsRecognizableElements as TriState | null) ?? null,
      interventionDescription: c.interventionDescription ?? "",
    })),
    evidenceCount: u.evidence.length,
  }));
  return {
    aiUsage: effectiveAiUsage(v),
    processNarrative: v.processNarrative ?? "",
    uses,
    declarationEvidenceCount,
  };
}

export function article50Of(v: Pick<VersionFull, "article50Answers">): Article50Answers {
  const parsed = article50AnswersSchema.safeParse(v.article50Answers ?? {});
  return parsed.success ? parsed.data : article50AnswersSchema.parse({});
}

/** Comprobaciones de completitud de datos no relacionadas con la clasificación. */
export function completenessIssues(v: VersionFull): { field: string; message: string; step: string }[] {
  const out: { field: string; message: string; step: string }[] = [];
  const w = v.declaration.work;
  if (!w.title.trim()) out.push({ field: "title", message: "La obra necesita un título.", step: "work" });
  if (!v.declarantName?.trim()) out.push({ field: "declarantName", message: "Indica el nombre del declarante.", step: "declarant" });
  if (!v.declarantRole?.trim()) out.push({ field: "declarantRole", message: "Indica tu rol en la obra.", step: "declarant" });
  if (!v.scope) out.push({ field: "scope", message: "Indica el alcance de la declaración.", step: "declarant" });
  if (v.scope === "COMPONENTS" && v.scopeComponentIds.length === 0)
    out.push({ field: "scopeComponentIds", message: "Selecciona los componentes que cubre la declaración.", step: "declarant" });
  if (!v.aiUsage) out.push({ field: "aiUsage", message: "Indica si se utilizó IA.", step: "usage" });
  if (v.aiUsage === "OWN_CONTRIBUTION_ONLY" && !v.ownContributionAiUsage)
    out.push({ field: "ownContributionAiUsage", message: "Indica si se utilizó IA en tu contribución.", step: "usage" });
  const eff = effectiveAiUsage(v);
  if (eff === "YES") {
    for (const u of v.uses) {
      if (!u.toolId) out.push({ field: `use.${u.id}.tool`, message: `El uso «${u.title}» no tiene herramienta asociada (puedes registrarla como desconocida).`, step: "uses" });
      if (!u.activityDescription?.trim()) out.push({ field: `use.${u.id}.activity`, message: `Describe la actividad del uso «${u.title}».`, step: "uses" });
    }
  }
  return out;
}
