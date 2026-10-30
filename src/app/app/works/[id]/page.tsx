import Link from "next/link";
import { notFound } from "next/navigation";
import { FilePlus2 } from "lucide-react";
import { addComponentAction, addContributorAction, deleteComponentAction, deleteContributorAction, deleteWorkAction, updateWorkAction } from "@/app/actions/works";
import { startDeclarationAction } from "@/app/actions/declarations";
import { PageHeader } from "@/components/app/page-header";
import { DocStatusBadge, VisibilityBadge } from "@/components/app/status-badges";
import { AddComponentForm, AddContributorForm } from "@/components/app/work-parts";
import { WorkForm } from "@/components/app/work-form";
import { Alert } from "@/components/ui/alert";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Card, CardBody, CardHeader, CardTitle } from "@/components/ui/card";
import { SubmitButton } from "@/components/ui/submit-button";
import { requireUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { COMPONENT_KIND_LABEL } from "@/lib/labels";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Obra" };

const ERRORS: Record<string, string> = {
  "has-declarations": "No se puede eliminar una obra que tiene declaraciones. Las declaraciones emitidas se conservan de forma inalterable.",
  "component-in-use": "Este componente está vinculado a usos de IA de alguna declaración. Desvincúlalo antes de eliminarlo.",
};

export default async function WorkPage({ params, searchParams }: { params: Promise<{ id: string }>; searchParams: Promise<{ declare?: string; error?: string }> }) {
  const user = await requireUser();
  const { id } = await params;
  const sp = await searchParams;
  const work = await prisma.work.findFirst({
    where: { id, ownerId: user.id },
    include: {
      components: { orderBy: [{ sortOrder: "asc" }, { createdAt: "asc" }] },
      contributors: { orderBy: { createdAt: "asc" } },
      category: true,
      declarations: { include: { versions: { orderBy: { versionNumber: "desc" } } }, orderBy: { createdAt: "desc" } },
    },
  });
  if (!work) notFound();
  const categories = await prisma.workCategory.findMany({ where: { OR: [{ active: true }, { key: work.categoryKey }] }, orderBy: { sortOrder: "asc" } });
  const start = startDeclarationAction.bind(null, work.id);

  return (
    <>
      <PageHeader
        title={work.title}
        description={`${work.category.labelEs}${work.subtitle ? ` · ${work.subtitle}` : ""}`}
        back={{ href: "/app/works", label: "Mis obras" }}
        actions={
          <form action={start}>
            <SubmitButton pendingText="Creando…">
              <FilePlus2 className="h-4 w-4" aria-hidden /> Nueva declaración
            </SubmitButton>
          </form>
        }
      />
      {sp.error && ERRORS[sp.error] && <Alert tone="danger" className="mb-6">{ERRORS[sp.error]}</Alert>}
      {sp.declare && work.declarations.length === 0 && (
        <Alert tone="info" className="mb-6" title="Obra guardada">
          Si tu obra tiene partes con procesos distintos (por ejemplo, texto e ilustraciones), añádelas como componentes. Después pulsa «Nueva declaración».
        </Alert>
      )}

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Declaraciones</CardTitle>
            </CardHeader>
            <CardBody>
              {work.declarations.length === 0 ? (
                <p className="text-sm text-muted">Esta obra aún no tiene declaraciones.</p>
              ) : (
                <ul className="space-y-4">
                  {work.declarations.map((d) => (
                    <li key={d.id} className="rounded-md border border-line p-3">
                      <ul className="divide-y divide-line">
                        {d.versions.map((v) => (
                          <li key={v.id} className="flex flex-wrap items-center justify-between gap-2 py-2">
                            <Link href={v.status === "DRAFT" ? `/app/declarations/${v.id}/edit/${v.lastStep ?? "work"}` : `/app/declarations/${v.id}`} className="inline-flex min-h-11 items-center text-sm font-medium text-petrol-700 underline-offset-4 hover:underline">
                              Versión {v.versionNumber} {v.publicId && <span className="font-mono text-xs text-muted">· {v.publicId}</span>}
                            </Link>
                            <span className="flex items-center gap-2">
                              <span className="text-xs text-muted">{formatDate(v.issuedAt ?? v.updatedAt)}</span>
                              <DocStatusBadge status={v.status} />
                              {v.status !== "DRAFT" && <VisibilityBadge visibility={v.visibility} />}
                            </span>
                          </li>
                        ))}
                      </ul>
                    </li>
                  ))}
                </ul>
              )}
            </CardBody>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Datos de la obra</CardTitle>
              <p className="mt-1 text-xs text-muted">Los cambios no afectan a las declaraciones ya emitidas, que conservan los datos del momento de emisión.</p>
            </CardHeader>
            <CardBody>
              <WorkForm
                action={updateWorkAction.bind(null, work.id)}
                draftKey={`work:${work.id}`}
                categories={categories}
                submitLabel="Guardar cambios"
                values={{ ...work, publicationDate: work.publicationDate?.toISOString().slice(0, 10) ?? null }}
              />
            </CardBody>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Componentes</CardTitle>
              <p className="mt-1 text-xs text-muted">Partes de una obra híbrida que pueden tener procesos distintos.</p>
            </CardHeader>
            <CardBody className="space-y-4">
              {work.components.length > 0 && (
                <ul className="divide-y divide-line">
                  {work.components.map((c) => (
                    <li key={c.id} className="flex items-center justify-between gap-2 py-2 text-sm">
                      <span>
                        <span className="font-medium">{c.name}</span> <span className="text-muted">· {COMPONENT_KIND_LABEL[c.kind]}</span>
                      </span>
                      <ConfirmDialog
                        trigger="icon"
                        triggerLabel={`Eliminar componente «${c.name}»`}
                        title={`¿Eliminar el componente «${c.name}»?`}
                        body="Se quitará de la obra. Si algún uso de IA de un borrador lo utiliza, primero tendrás que desvincularlo. Las declaraciones emitidas no cambian."
                        confirmLabel="Eliminar componente"
                        action={deleteComponentAction.bind(null, work.id, c.id)}
                      />
                    </li>
                  ))}
                </ul>
              )}
              <AddComponentForm action={addComponentAction.bind(null, work.id)} />
            </CardBody>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Otros participantes</CardTitle>
              <p className="mt-1 text-xs text-muted">Solo para obras colectivas. Tu declaración no cubrirá sus aportaciones salvo que tú las conozcas y declares.</p>
            </CardHeader>
            <CardBody className="space-y-4">
              {work.contributors.length > 0 && (
                <ul className="divide-y divide-line">
                  {work.contributors.map((c) => (
                    <li key={c.id} className="flex items-center justify-between gap-2 py-2 text-sm">
                      <span>
                        <span className="font-medium">{c.name}</span> <span className="text-muted">· {c.role}</span>
                      </span>
                      <ConfirmDialog
                        trigger="icon"
                        triggerLabel={`Eliminar participante «${c.name}»`}
                        title={`¿Eliminar al participante «${c.name}»?`}
                        body="Se quitará de la lista de participantes de la obra. Las declaraciones emitidas no cambian."
                        confirmLabel="Eliminar participante"
                        action={deleteContributorAction.bind(null, work.id, c.id)}
                      />
                    </li>
                  ))}
                </ul>
              )}
              <AddContributorForm action={addContributorAction.bind(null, work.id)} components={work.components} />
            </CardBody>
          </Card>

          {work.declarations.length === 0 && (
            <ConfirmDialog
              trigger="text"
              triggerLabel="Eliminar esta obra"
              title={`¿Eliminar la obra «${work.title}»?`}
              body="Se eliminarán la obra, sus componentes y sus participantes. Esta acción no se puede deshacer."
              confirmLabel="Eliminar obra"
              action={deleteWorkAction.bind(null, work.id)}
            />
          )}
        </div>
      </div>
    </>
  );
}
