/* eslint-disable @typescript-eslint/no-explicit-any */
import { OrivaCode } from "@/components/oriva/oriva-code";
import { Badge } from "@/components/ui/badge";
import { DIMENSIONS, labelOf } from "@/lib/oriva-code";
import { FINDING_LEVEL_LABEL, type FindingLevel } from "@/lib/legal/article50";
import { LEGAL_REFERENCES } from "@/lib/legal/references";
import { EVIDENCE_KIND_LABEL, INCORPORATION_LABEL, INTERVENTION_ANSWER_LABEL, PROFILE_STATUS_LABEL, TRI_LABEL, WORK_STATUS_LABEL } from "@/lib/labels";
import type { SectionName } from "@/lib/integrity/disclosure";

type Sections = Partial<Record<SectionName, any>>;

function Row({ label, value }: { label: string; value: React.ReactNode }) {
  if (value === null || value === undefined || value === "") return null;
  return (
    <div className="grid grid-cols-1 gap-1 py-2 sm:grid-cols-[14rem_1fr] sm:gap-4">
      <dt className="text-sm text-muted">{label}</dt>
      <dd className="text-sm break-words text-ink">{value}</dd>
    </div>
  );
}

function Section({ title, children, id }: { title: string; children: React.ReactNode; id?: string }) {
  return (
    <section aria-labelledby={id} className="rounded-[var(--radius-lg)] border border-line bg-surface">
      <h2 id={id} className="border-b border-line px-5 py-3 text-sm font-semibold tracking-wide text-petrol-800 uppercase">
        {title}
      </h2>
      <div className="px-5 py-3">{children}</div>
    </section>
  );
}

const levelTone: Record<string, "warn" | "petrol" | "ok" | "neutral" | "danger"> = {
  POSSIBLE_OBLIGATION: "warn",
  LIMITED_OBLIGATION: "warn",
  EXCEPTION_MAY_APPLY: "petrol",
  UNCERTAIN: "warn",
  INFO: "neutral",
  NOT_IDENTIFIED: "ok",
};

export function Article50Findings({ data }: { data: { findings: any[]; disclaimer: string } }) {
  return (
    <div className="space-y-3">
      {data.findings.map((f) => (
        <div key={f.id} className="rounded-md border border-line p-3">
          <div className="flex flex-wrap items-center gap-2">
            <Badge tone={levelTone[f.level]}>{FINDING_LEVEL_LABEL[f.level as FindingLevel]}</Badge>
            <p className="text-sm font-medium">{f.title}</p>
          </div>
          <p className="mt-2 text-sm leading-relaxed text-ink">{f.text}</p>
          {f.references.length > 0 && (
            <p className="mt-2 text-xs text-muted">
              Referencias:{" "}
              {f.references.map((k: string, i: number) => {
                const r = LEGAL_REFERENCES.find((x) => x.key === k);
                return (
                  <span key={k}>
                    {i > 0 && "; "}
                    {r ? (
                      <a href={r.url} className="underline underline-offset-2" rel="noopener noreferrer" target="_blank">
                        {r.citation}
                      </a>
                    ) : (
                      k
                    )}
                  </span>
                );
              })}
            </p>
          )}
        </div>
      ))}
      <p className="text-xs leading-relaxed text-muted">{data.disclaimer}</p>
      <p className="text-xs leading-relaxed text-muted">Esta orientación es independiente del código ORIVA, que describe prácticas creativas y no incorpora ninguna valoración de cumplimiento.</p>
    </div>
  );
}

export function SnapshotView({ sections, verifyUrl, showCodeCard = true, render }: { sections: Sections; verifyUrl?: string; showCodeCard?: boolean; render?: SectionName[] }) {
  const { meta, work, declarant, scope, classification: cls, summary, process: proc, article50, statement, evidence } = sections;
  const show = (n: SectionName) => !render || render.includes(n);
  const compName = (ref: string | null) => (ref ? work?.components?.find((c: any) => c.ref === ref)?.name ?? ref : null);
  return (
    <div className="space-y-6">
      {cls && showCodeCard && show("classification") && (
        <OrivaCode
          code={cls.code}
          compositeNotation={cls.compositeNotation}
          composite={cls.composite}
          status={cls.status}
          schemeVersion={cls.schemeVersion}
          publicId={meta?.publicId}
          verifyUrl={verifyUrl}
          scopeText={scope?.coverageStatement}
          uses={cls.uses.map((u: any) => ({ ref: u.ref, code: u.code, notation: u.notation, status: u.status, title: proc?.uses?.find((x: any) => x.ref === u.ref)?.title ?? null }))}
        />
      )}

      {summary && show("summary") && (
        <Section title="Resumen" id="s-summary">
          <p className="py-1 text-sm leading-relaxed">{summary.text}</p>
        </Section>
      )}

      {work && show("work") && (
        <Section title="Obra" id="s-work">
          <dl className="divide-y divide-line">
            <Row label="Título" value={`${work.title}${work.subtitle ? `. ${work.subtitle}` : ""}`} />
            <Row label="Tipo de obra" value={work.category.label} />
            <Row label="Estado" value={WORK_STATUS_LABEL[work.status]} />
            <Row label="Descripción" value={work.description} />
            <Row label="Periodo de creación" value={work.creationPeriod} />
            <Row label="Fecha de publicación" value={work.publicationDate} />
            <Row label="Idioma" value={work.language} />
            <Row label="Versión" value={work.workVersion} />
            <Row label="Editorial o institución" value={work.publisher} />
            <Row label={work.identifierType ?? "Identificador"} value={work.identifier} />
            <Row label="URL" value={work.url ? <a href={work.url} rel="noopener noreferrer nofollow" target="_blank" className="text-petrol-700 underline">{work.url}</a> : null} />
            <Row label="Componentes" value={work.components.length ? work.components.map((c: any) => `${c.ref} · ${c.name} (${c.kindLabel})`).join(" — ") : null} />
            <Row label="Obra colectiva" value={work.isCollective ? `Sí${work.contributorRoles.length ? ` · otros roles: ${work.contributorRoles.map((r: any) => r.role).join(", ")}` : ""}` : null} />
          </dl>
        </Section>
      )}

      {(declarant || scope) && show("declarant") && (
        <Section title="Declarante y alcance" id="s-declarant">
          <dl className="divide-y divide-line">
            {declarant ? (
              <>
                <Row label="Declarante" value={`${declarant.name}${declarant.artisticName ? ` («${declarant.artisticName}»)` : ""}`} />
                <Row label="Organización" value={declarant.organization} />
                <Row label="Rol en la obra" value={declarant.role} />
              </>
            ) : (
              <Row label="Declarante" value={<span className="text-muted italic">No publicado por decisión del declarante</span>} />
            )}
            {scope && (
              <>
                <Row label="Alcance" value={scope.scopeLabel} />
                <Row label="Cobertura" value={scope.coverageStatement} />
                <Row label="Ámbito de responsabilidad" value={scope.responsibilityNote} />
                <Row label="Utilización de IA" value={scope.effectiveAiUsageLabel} />
                <Row label="Incertidumbre declarada" value={scope.uncertaintyNote} />
              </>
            )}
          </dl>
        </Section>
      )}

      {cls && show("classification") && (
        <Section title="Clasificación ORIVA" id="s-class">
          <dl className="divide-y divide-line">
            <Row label="Estado" value={PROFILE_STATUS_LABEL[cls.status] ?? cls.status} />
            <Row label="Código compacto" value={cls.code ? <span className="font-mono">{cls.code}</span> : "No aplicable: ver perfil compuesto y desglose"} />
            <Row label="Perfil compuesto" value={cls.compositeNotation ? <span className="font-mono">{cls.compositeNotation}</span> : null} />
            {cls.composite &&
              (["A", "B", "C"] as const).map((k) => <Row key={k} label={`${k} · ${DIMENSIONS[k].name.es}`} value={cls.composite[k].map((c: string) => `${c} ${labelOf(c)}`).join("; ") || "—"} />)}
            <Row label={`D · ${DIMENSIONS.D.name.es}`} value={`${cls.documentation} ${labelOf(cls.documentation)}`} />
            <Row label="Esquema" value={`ORIVA Code v${cls.schemeVersion}`} />
            <Row label="Reglas aplicadas" value={<span className="font-mono text-xs">{cls.rulesApplied.join(" · ")}</span>} />
          </dl>
        </Section>
      )}

      {proc && show("process") && (
        <Section title="Proceso declarado" id="s-process">
          {proc.tools.length > 0 && (
            <div className="py-2">
              <h3 className="mb-2 text-sm font-semibold">Herramientas</h3>
              <ul className="space-y-1 text-sm">
                {proc.tools.map((t: any) => (
                  <li key={t.ref}>
                    <span className="font-mono text-xs text-muted">{t.ref}</span> {t.identified ? <strong className="font-medium">{t.name}</strong> : <em>Herramienta no identificada{t.name && t.name !== "Herramienta no identificada" ? ` (${t.name})` : ""}</em>}
                    {[t.provider, t.modelVersion, t.systemType, t.usagePeriod].filter(Boolean).length > 0 && <span className="text-muted"> · {[t.provider, t.modelVersion, t.systemType, t.usagePeriod].filter(Boolean).join(" · ")}</span>}
                    {t.purpose && <span className="block text-xs text-muted">Finalidad: {t.purpose}</span>}
                  </li>
                ))}
              </ul>
            </div>
          )}
          {proc.uses.length > 0 && (
            <div className="space-y-4 py-2">
              <h3 className="text-sm font-semibold">Usos de IA</h3>
              {proc.uses.map((u: any) => {
                const c = cls?.uses?.find((x: any) => x.ref === u.ref);
                return (
                  <article key={u.ref} className="rounded-md border border-line p-4">
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                      <h4 className="font-medium">
                        <span className="font-mono text-xs text-muted">{u.ref}</span> {u.title}
                      </h4>
                      {c && <span className="font-mono text-sm text-petrol-800">{c.code ?? c.notation}</span>}
                    </div>
                    <dl className="mt-2 divide-y divide-line">
                      <Row label="Componente" value={compName(u.componentRef) ?? "Obra en general"} />
                      <Row label="Herramienta" value={u.toolRef ? proc.tools.find((t: any) => t.ref === u.toolRef)?.name : "No indicada"} />
                      <Row label="Fases" value={u.phaseLabels.join(", ")} />
                      <Row label="Funciones (A)" value={u.functions.map((f: string) => `${f} ${labelOf(f)}`).join("; ") + (u.primaryFunction && u.primaryFunction !== "NONE" && u.functions.length > 1 ? ` — principal: ${u.primaryFunction}` : "")} />
                      <Row label="Objetivo" value={u.objective} />
                      <Row label="Actividad" value={u.activityDescription} />
                      <Row label="Resultado obtenido" value={u.resultDescription} />
                      <Row label="Incorporación" value={u.incorporation ? INCORPORATION_LABEL[u.incorporation] : null} />
                      {u.contributions.map((k: any) => (
                        <Row
                          key={k.ref}
                          label={`Aportación ${k.type}`}
                          value={
                            <>
                              <span className="font-medium">{labelOf(k.type)}</span> → {k.interventionCode ? `${k.interventionCode} ${labelOf(k.interventionCode)}` : "intervención no precisada"}
                              <span className="block text-xs text-muted">
                                Respuesta: {INTERVENTION_ANSWER_LABEL[k.interventionAnswer] ?? "—"}
                                {k.retainsRecognizableElements ? ` · ¿elementos reconocibles del original? ${TRI_LABEL[k.retainsRecognizableElements]}` : ""}
                              </span>
                              {k.interventionDescription && <span className="mt-1 block">{k.interventionDescription}</span>}
                            </>
                          }
                        />
                      ))}
                      {c && <Row label="Documentación (D)" value={`${c.documentation} ${labelOf(c.documentation)}`} />}
                      {u.evidenceRefs.length > 0 && <Row label="Evidencias" value={u.evidenceRefs.join(", ")} />}
                    </dl>
                  </article>
                );
              })}
            </div>
          )}
          {(proc.processNarrative || proc.observations) && (
            <dl className="divide-y divide-line">
              <Row label="Descripción del proceso" value={proc.processNarrative} />
              <Row label="Observaciones" value={proc.observations} />
            </dl>
          )}
          {!proc.tools.length && !proc.uses.length && !proc.processNarrative && !proc.observations && <p className="py-2 text-sm text-muted">Sin información adicional sobre el proceso.</p>}
        </Section>
      )}

      {evidence && show("evidence") && (
        <Section title="Evidencias complementarias" id="s-evidence">
          {evidence.items.length === 0 ? (
            <p className="py-2 text-sm text-muted">No se han registrado evidencias.</p>
          ) : (
            <ul className="space-y-2 py-2 text-sm">
              {evidence.items.map((e: any) => (
                <li key={e.ref}>
                  <span className="font-mono text-xs text-muted">{e.ref}</span> {EVIDENCE_KIND_LABEL[e.kind]} — {e.description}
                  {e.sha256 && <span className="block font-mono text-xs break-all text-muted">SHA-256 {e.sha256}</span>}
                </li>
              ))}
            </ul>
          )}
          <p className="pb-2 text-xs text-muted">{evidence.note}</p>
        </Section>
      )}

      {article50?.answered && show("article50") && (
        <Section title="Orientación sobre el artículo 50 (Reglamento UE 2024/1689)" id="s-a50">
          <div className="py-2">
            <Article50Findings data={article50} />
          </div>
        </Section>
      )}

      {statement && show("statement") && (
        <Section title="Declaración responsable" id="s-statement">
          <blockquote className="my-2 border-l-4 border-accent pl-4 text-sm leading-relaxed whitespace-pre-line">{statement.text}</blockquote>
          <dl className="divide-y divide-line">
            <Row label="Texto aceptado" value={`${statement.legalTextKey} v${statement.legalTextVersion}`} />
            <Row label="Aceptación" value={statement.acceptanceMethod} />
          </dl>
        </Section>
      )}
    </div>
  );
}
