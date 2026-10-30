import Link from "next/link";
import { notFound } from "next/navigation";
import {
  addEvidenceAction,
  deleteDraftAction,
  deleteEvidenceAction,
  deleteToolAction,
  issueAction,
  saveArticle50Action,
  saveDeclarantAction,
  saveObservationsAction,
  saveToolAction,
  saveUsageAction,
} from "@/app/actions/declarations";
import { updateWorkAction } from "@/app/actions/works";
import { WorkForm } from "@/components/app/work-form";
import { Article50Findings, SnapshotView } from "@/components/declaration/snapshot-view";
import { Alert } from "@/components/ui/alert";
import { ButtonLink } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Card, CardBody, CardHeader, CardTitle } from "@/components/ui/card";
import { Article50Form, DeclarantForm, EditableTool, EvidenceForm, IssueForm, ObservationsForm, ToolForm, UsageForm } from "@/components/wizard/forms";
import { loadDraftForPage } from "@/components/wizard/load";
import { nextStep, prevStep, STEPS, type StepKey } from "@/components/wizard/steps";
import { WizardShell } from "@/components/wizard/wizard-shell";
import { prisma } from "@/lib/db";
import { article50Of } from "@/lib/declarations/load";
import { buildSnapshot } from "@/lib/declarations/snapshot";
import { activeStatement, issuanceReadiness } from "@/lib/declarations/service";
import { assessArticle50 } from "@/lib/legal/article50";
import { COMPONENT_KIND_LABEL, EVIDENCE_KIND_LABEL } from "@/lib/labels";

export const metadata = { title: "Declaración" };

const STEP_COPY: Record<StepKey, { title: string; intro?: string }> = {
  work: { title: "Identificación de la obra", intro: "Revisa los datos de la obra. Se congelarán en la declaración en el momento de emitirla." },
  declarant: { title: "Declarante y alcance", intro: "Quién declara, con qué rol y qué parte de la obra cubre la declaración." },
  usage: { title: "Utilización general de IA", intro: "Responde con lo que sabes. Si no puedes determinar algo, dilo: es preferible registrar la incertidumbre que inventar datos." },
  tools: { title: "Herramientas de IA", intro: "Registra los sistemas utilizados. No necesitas recordar el modelo exacto; también puedes registrar herramientas desconocidas." },
  uses: { title: "Usos de IA" },
  evidence: { title: "Evidencias complementarias (opcional)", intro: "Versiones, historiales o materiales de trabajo que respaldan tu descripción. Son privadas por defecto y ORIVA no las audita." },
  article50: {
    title: "Orientación sobre el artículo 50",
    intro: "Preguntas orientativas para reconocer si podría existir una obligación específica de transparencia según el Reglamento (UE) 2024/1689. No afecta a tu código ORIVA ni constituye asesoramiento jurídico.",
  },
  review: { title: "Revisión y emisión", intro: "Comprueba toda la información. Puedes volver a cualquier paso para corregirla. Una vez emitida, esta versión será inmutable." },
};

export default async function StepPage({ params, searchParams }: { params: Promise<{ id: string; step: string }>; searchParams: Promise<{ saved?: string }> }) {
  const { id, step: rawStep } = await params;
  const sp = await searchParams;
  if (!STEPS.some((s) => s.key === rawStep) || rawStep === "uses") notFound();
  const step = rawStep as StepKey;
  const { user, v, eff } = await loadDraftForPage(id);
  const work = v.declaration.work;
  const prev = prevStep(step, eff);
  const next = nextStep(step, eff);
  const back = prev ? `/app/declarations/${id}/edit/${prev}` : undefined;
  if ((step === "tools") && eff !== "YES") notFound();

  let body: React.ReactNode = null;

  if (step === "work") {
    const categories = await prisma.workCategory.findMany({ where: { OR: [{ active: true }, { key: work.categoryKey }] }, orderBy: { sortOrder: "asc" } });
    body = (
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1.5fr_1fr]">
        <Card>
          <CardBody className="py-6">
            <WorkForm
              action={updateWorkAction.bind(null, work.id)}
                draftKey={`work:${work.id}`}
              categories={categories}
              values={{ ...work, publicationDate: work.publicationDate?.toISOString().slice(0, 10) ?? null }}
              submitLabel="Guardar y continuar"
              next={`/app/declarations/${id}/edit/declarant`}
            />
          </CardBody>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Componentes de la obra</CardTitle>
          </CardHeader>
          <CardBody className="space-y-3 text-sm">
            {work.components.length === 0 ? (
              <p className="text-muted">Sin componentes. Si tu obra combina partes con procesos distintos (texto, imágenes, audio…), añádelos para describir cada una.</p>
            ) : (
              <ul className="space-y-1">
                {work.components.map((c) => (
                  <li key={c.id}>
                    <span className="font-medium">{c.name}</span> <span className="text-muted">· {COMPONENT_KIND_LABEL[c.kind]}</span>
                  </li>
                ))}
              </ul>
            )}
            <Link href={`/app/works/${work.id}`} className="inline-flex min-h-11 items-center text-petrol-700 underline underline-offset-4">
              Gestionar componentes y participantes
            </Link>
          </CardBody>
        </Card>
      </div>
    );
  }

  if (step === "declarant") {
    body = (
      <Card className="max-w-2xl">
        <CardBody className="py-6">
          {sp.saved && <Alert tone="ok" className="mb-4">Borrador guardado.</Alert>}
          <DeclarantForm
            action={saveDeclarantAction.bind(null, id)}
            email={user.email}
            components={work.components}
            back={back!}
            isCollective={work.isCollective || work.contributors.length > 0}
            values={{
              declarantName: v.declarantName ?? "",
              declarantArtisticName: v.declarantArtisticName ?? "",
              declarantOrganization: v.declarantOrganization ?? "",
              declarantRole: v.declarantRole ?? "",
              scope: v.scope ?? "",
              scopeComponentIds: v.scopeComponentIds,
              responsibilityNote: v.responsibilityNote ?? "",
            }}
          />
        </CardBody>
      </Card>
    );
  }

  if (step === "usage") {
    body = (
      <Card className="max-w-2xl">
        <CardBody className="py-6">
          {sp.saved && <Alert tone="ok" className="mb-4">Borrador guardado.</Alert>}
          <UsageForm
            action={saveUsageAction.bind(null, id)}
            back={back!}
            values={{ aiUsage: v.aiUsage ?? "", ownContributionAiUsage: v.ownContributionAiUsage ?? "", uncertaintyNote: v.uncertaintyNote ?? "", processNarrative: v.processNarrative ?? "" }}
          />
        </CardBody>
      </Card>
    );
  }

  if (step === "tools") {
    body = (
      <div className="space-y-6">
        {v.tools.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Herramientas registradas</CardTitle>
            </CardHeader>
            <CardBody>
              <ul className="divide-y divide-line">
                {v.tools.map((t) => (
                  <li key={t.id} className="flex items-start justify-between gap-3 py-3">
                    <EditableTool
                      action={saveToolAction.bind(null, id, t.id)}
                      values={{ identified: t.identified, name: t.name, provider: t.provider ?? "", modelVersion: t.modelVersion ?? "", systemType: t.systemType ?? "", purpose: t.purpose ?? "", usagePeriod: t.usagePeriod ?? "" }}
                    >
                      <p className="text-sm font-medium">{t.identified ? t.name : <em>Herramienta no identificada{t.name !== "Herramienta no identificada" ? ` (${t.name})` : ""}</em>}</p>
                      <p className="text-xs text-muted">{[t.provider, t.modelVersion, t.systemType, t.usagePeriod].filter(Boolean).join(" · ") || "Sin más datos"}</p>
                    </EditableTool>
                    <ConfirmDialog
                      trigger="icon"
                      triggerLabel={`Eliminar herramienta «${t.name}»`}
                      title={`¿Eliminar la herramienta «${t.name}»?`}
                      body="Se quitará de este borrador. Los usos que la tenían asociada quedarán sin herramienta y tendrás que asignarles otra."
                      confirmLabel="Eliminar herramienta"
                      action={deleteToolAction.bind(null, id, t.id)}
                    />
                  </li>
                ))}
              </ul>
            </CardBody>
          </Card>
        )}
        <Card>
          <CardHeader>
            <CardTitle>{v.tools.length ? "Añadir otra herramienta" : "Añadir herramienta"}</CardTitle>
          </CardHeader>
          <CardBody>
            <ToolForm action={saveToolAction.bind(null, id, null)} submitLabel="Añadir herramienta" />
          </CardBody>
        </Card>
        <div className="flex justify-between border-t border-line pt-5">
          <Link href={back!} className="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">
            ← Paso anterior
          </Link>
          <ButtonLink href={`/app/declarations/${id}/edit/uses`}>Continuar con los usos</ButtonLink>
        </div>
      </div>
    );
  }

  if (step === "evidence") {
    body = (
      <div className="space-y-6">
        {v.evidence.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Evidencias registradas</CardTitle>
            </CardHeader>
            <CardBody>
              <ul className="divide-y divide-line">
                {v.evidence.map((e) => (
                  <li key={e.id} className="flex items-start justify-between gap-3 py-3 text-sm">
                    <div className="min-w-0">
                      <p className="font-medium">{EVIDENCE_KIND_LABEL[e.kind]}</p>
                      <p className="text-muted">{e.description}</p>
                      {e.fileName && (
                        <p className="mt-1 text-xs text-muted">
                          <a href={`/app/declarations/${id}/evidence/${e.id}`} className="underline" download>
                            {e.fileName}
                          </a>{" "}
                          · {Math.ceil((e.sizeBytes ?? 0) / 1024)} KB · <span className="font-mono break-all">SHA-256 {e.sha256?.slice(0, 16)}…</span>
                        </p>
                      )}
                      <p className="text-xs text-subtle">{e.useId ? `Uso: ${v.uses.find((u) => u.id === e.useId)?.title}` : "Toda la declaración"}</p>
                    </div>
                    <ConfirmDialog
                      trigger="icon"
                      triggerLabel={`Eliminar evidencia «${e.fileName ?? EVIDENCE_KIND_LABEL[e.kind]}»`}
                      title={`¿Eliminar la evidencia «${e.fileName ?? EVIDENCE_KIND_LABEL[e.kind]}»?`}
                      body="Se quitará de este borrador. Las declaraciones ya emitidas que la incluyen no cambian."
                      confirmLabel="Eliminar evidencia"
                      action={deleteEvidenceAction.bind(null, id, e.id)}
                    />
                  </li>
                ))}
              </ul>
            </CardBody>
          </Card>
        )}
        <Card>
          <CardHeader>
            <CardTitle>Registrar evidencia</CardTitle>
          </CardHeader>
          <CardBody>
            <Alert tone="info" className="mb-4">
              No subas prompts o conversaciones que contengan datos personales de terceros o información confidencial si no es necesario. Las evidencias nunca se publican sin tu consentimiento específico, y aun así solo se publican sus metadatos.
            </Alert>
            <EvidenceForm action={addEvidenceAction.bind(null, id)} uses={v.uses.map((u) => ({ id: u.id, title: u.title }))} />
          </CardBody>
        </Card>
        <div className="flex justify-between border-t border-line pt-5">
          <Link href={back!} className="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">
            ← Paso anterior
          </Link>
          <ButtonLink href={`/app/declarations/${id}/edit/${next}`}>Continuar</ButtonLink>
        </div>
      </div>
    );
  }

  if (step === "article50") {
    const answers = article50Of(v);
    const result = assessArticle50(answers);
    const hasText = work.components.some((c) => c.kind === "TEXT") || ["essay", "academic", "outreach", "novel", "short_story", "poetry", "screenplay", "other", "multimedia"].includes(work.categoryKey);
    body = (
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1.4fr_1fr]">
        <Card>
          <CardBody className="py-6">
            <Article50Form action={saveArticle50Action.bind(null, id)} back={back!} showText={hasText} values={answers as unknown as Record<string, string | null>} />
          </CardBody>
        </Card>
        <div>
          <Card className="lg:sticky lg:top-6">
            <CardHeader>
              <CardTitle>Orientación con tus respuestas guardadas</CardTitle>
            </CardHeader>
            <CardBody>
              {sp.saved && <Alert tone="ok" className="mb-3">Respuestas guardadas.</Alert>}
              {result.answered ? <Article50Findings data={result} /> : <p className="text-sm text-muted">Responde a las preguntas y guarda para ver la orientación. Este paso es opcional.</p>}
            </CardBody>
          </Card>
        </div>
      </div>
    );
  }

  if (step === "review") {
    const statement = await activeStatement();
    const { profile, validation, completeness, canIssue } = issuanceReadiness(v);
    const preview = buildSnapshot(v, profile, {
      publicId: "ORV-····-····-····",
      issuedAt: new Date(),
      verifyUrl: "",
      statement: { key: statement.key, version: statement.version, locale: statement.locale, text: statement.content, textHash: statement.contentHash },
    });
    const sections = Object.fromEntries(Object.entries(preview.snapshot.sections).map(([k, s]) => [k, s.data]));
    const useTitle = (ref: string) => v.uses.find((u) => u.id === ref)?.title;
    const stepOf = (field: string) => (field.startsWith("use") || field.startsWith("contribution") || field === "functions" || field === "incorporation" || field === "primaryFunction" ? "uses" : field === "aiUsage" ? "usage" : "uses");
    body = (
      <div className="space-y-8">
        {!canIssue && (
          <Alert tone="warn" title="Falta información para emitir la declaración">
            <ul className="mt-1 list-disc space-y-1 pl-5">
              {completeness.map((c) => (
                <li key={c.field}>
                  {c.message}{" "}
                  <Link className="underline" href={c.step === "uses" ? `/app/declarations/${id}/edit/uses` : `/app/declarations/${id}/edit/${c.step}`}>
                    Corregir
                  </Link>
                </li>
              ))}
              {validation.errors.map((e, i) => {
                const useRef = profile.uses.find((u) => u.issues.includes(e))?.ref;
                return (
                  <li key={i}>
                    {useRef ? <strong>{useTitle(useRef)}: </strong> : null}
                    {e.message}{" "}
                    <Link className="underline" href={useRef ? `/app/declarations/${id}/edit/uses/${useRef}` : `/app/declarations/${id}/edit/${stepOf(e.field)}`}>
                      Corregir
                    </Link>
                  </li>
                );
              })}
            </ul>
          </Alert>
        )}
        {validation.warnings.length > 0 && (
          <Alert tone="info" title="Avisos de coherencia (no impiden emitir)">
            <ul className="mt-1 list-disc space-y-1 pl-5">
              {validation.warnings.map((w, i) => (
                <li key={i}>{w.message}</li>
              ))}
            </ul>
          </Alert>
        )}
        <SnapshotView sections={sections} />
        <Card>
          <CardBody className="py-5">
            <ObservationsForm action={saveObservationsAction.bind(null, id)} value={v.observations ?? ""} />
          </CardBody>
        </Card>
        <Card className="border-petrol-200">
          <CardHeader>
            <CardTitle>Declaración responsable</CardTitle>
          </CardHeader>
          <CardBody>
            <IssueForm action={issueAction.bind(null, id)} statement={statement.content} statementHash={statement.contentHash} statementVersion={statement.version} />
          </CardBody>
        </Card>
        <div className="text-right">
          <ConfirmDialog
            trigger="text"
            triggerLabel="Descartar este borrador"
            title={`¿Descartar el borrador de la versión ${v.versionNumber} de «${work.title}»?`}
            body="Se eliminarán sus respuestas, herramientas, usos y evidencias. Las versiones ya emitidas no se ven afectadas. Esta acción no se puede deshacer."
            confirmLabel="Descartar borrador"
            action={deleteDraftAction.bind(null, id)}
          />
        </div>
      </div>
    );
  }

  return (
    <WizardShell versionId={id} current={step} effectiveUsage={eff} workTitle={work.title} versionNumber={v.versionNumber} title={STEP_COPY[step].title} intro={STEP_COPY[step].intro}>
      {body}
    </WizardShell>
  );
}
