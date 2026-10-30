import Link from "next/link";
import { notFound } from "next/navigation";
import { Pencil, Plus } from "lucide-react";
import { deleteUseAction } from "@/app/actions/declarations";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { ButtonLink } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { IconLink } from "@/components/ui/icon-button";
import { loadDraftForPage } from "@/components/wizard/load";
import { prevStep } from "@/components/wizard/steps";
import { WizardShell } from "@/components/wizard/wizard-shell";
import { classifyVersion } from "@/lib/declarations/service";
import { PROFILE_STATUS_LABEL } from "@/lib/labels";

export const metadata = { title: "Usos de IA" };

export default async function UsesPage({ params, searchParams }: { params: Promise<{ id: string }>; searchParams: Promise<{ saved?: string }> }) {
  const { id } = await params;
  const sp = await searchParams;
  const { v, eff } = await loadDraftForPage(id);
  if (eff !== "YES") notFound();
  const profile = classifyVersion(v);
  const comp = (cid: string | null) => v.declaration.work.components.find((c) => c.id === cid)?.name ?? "Obra en general";
  return (
    <WizardShell
      versionId={id}
      current="uses"
      effectiveUsage={eff}
      workTitle={v.declaration.work.title}
      versionNumber={v.versionNumber}
      title="Usos de IA"
      intro="Registra cada uso concreto por separado: por ejemplo, una revisión crítica, una traducción o la generación de una ilustración. Cada uso recibe su propia clasificación ORIVA y se conserva en el perfil de la obra."
    >
      {sp.saved && <Alert tone="ok" className="mb-4">Uso guardado.</Alert>}
      {v.tools.length === 0 && (
        <Alert tone="warn" className="mb-4">
          Aún no has registrado herramientas.{" "}
          <Link href={`/app/declarations/${id}/edit/tools`} className="underline">
            Regístralas primero
          </Link>{" "}
          (puedes registrar una herramienta como «desconocida»).
        </Alert>
      )}
      {v.uses.length === 0 ? (
        <div className="rounded-[var(--radius-lg)] border border-dashed border-line-strong bg-surface p-10 text-center">
          <h2 className="font-medium">Aquí aparecerán los usos de IA de tu obra</h2>
          <p className="mt-1 text-sm text-muted">Cada uso recibe su propia clasificación ORIVA. Empieza por el más relevante; podrás añadir tantos como necesites.</p>
          <ButtonLink href={`/app/declarations/${id}/edit/uses/new`} className="mt-4">
            <Plus className="h-4 w-4" aria-hidden /> Añadir el primer uso
          </ButtonLink>
        </div>
      ) : (
        <ul className="space-y-3">
          {v.uses.map((u) => {
            const p = profile.uses.find((x) => x.ref === u.id)!;
            return (
              <li key={u.id} className="flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-lg)] border border-line bg-surface p-4">
                <div className="min-w-0">
                  <p className="font-medium">
                    {u.parentUseId && <span className="text-muted">↳ </span>}
                    {u.title}
                  </p>
                  <p className="text-xs text-muted">
                    {comp(u.componentId)} · {u.tool ? (u.tool.identified ? u.tool.name : "Herramienta no identificada") : "Sin herramienta"}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <span className="font-mono text-sm text-petrol-800">{p.code ?? p.notation}</span>
                  {p.status !== "COMPLETE" && <Badge tone="warn">{PROFILE_STATUS_LABEL[p.status]}</Badge>}
                  <IconLink href={`/app/declarations/${id}/edit/uses/${u.id}`} label={`Editar uso «${u.title}»`}>
                    <Pencil className="h-4 w-4" aria-hidden />
                  </IconLink>
                  <ConfirmDialog
                    trigger="icon"
                    triggerLabel={`Eliminar uso «${u.title}»`}
                    title={`¿Eliminar el uso «${u.title}»?`}
                    body="Se eliminarán sus fases, aportaciones y la vinculación de sus evidencias en este borrador. Esta acción no se puede deshacer."
                    confirmLabel="Eliminar uso"
                    action={deleteUseAction.bind(null, id, u.id)}
                  />
                </div>
              </li>
            );
          })}
        </ul>
      )}
      {profile.compositeNotation && v.uses.length > 1 && (
        <p className="mt-4 text-sm text-muted">
          Perfil compuesto provisional: <span className="font-mono text-petrol-800">{profile.code ?? profile.compositeNotation}</span>
        </p>
      )}
      <div className="mt-6 flex flex-wrap items-center gap-3 border-t border-line pt-5">
        <Link href={`/app/declarations/${id}/edit/${prevStep("uses", eff)}`} className="mr-auto inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">
          ← Paso anterior
        </Link>
        <ButtonLink href={`/app/declarations/${id}/edit/uses/new`} variant="secondary">
          <Plus className="h-4 w-4" aria-hidden /> Añadir uso
        </ButtonLink>
        <ButtonLink href={`/app/declarations/${id}/edit/evidence`}>Continuar</ButtonLink>
      </div>
    </WizardShell>
  );
}
