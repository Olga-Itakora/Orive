import Link from "next/link";
import { ArrowRight, FilePlus2 } from "lucide-react";
import { PageHeader } from "@/components/app/page-header";
import { DocStatusBadge, VisibilityBadge } from "@/components/app/status-badges";
import { Alert } from "@/components/ui/alert";
import { ButtonLink } from "@/components/ui/button";
import { Card, CardBody, CardHeader, CardTitle } from "@/components/ui/card";
import { requireUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Mi espacio" };

export default async function Dashboard({ searchParams }: { searchParams: Promise<{ welcome?: string }> }) {
  const user = await requireUser();
  const sp = await searchParams;
  const [works, drafts, issued] = await Promise.all([
    prisma.work.count({ where: { ownerId: user.id } }),
    prisma.declarationVersion.findMany({
      where: { status: "DRAFT", declaration: { ownerId: user.id } },
      include: { declaration: { include: { work: { select: { title: true } } } } },
      orderBy: { updatedAt: "desc" },
      take: 5,
    }),
    prisma.declarationVersion.findMany({
      where: { status: { not: "DRAFT" }, declaration: { ownerId: user.id } },
      include: { declaration: { include: { work: { select: { title: true } } } } },
      orderBy: { issuedAt: "desc" },
      take: 5,
    }),
  ]);
  return (
    <>
      <PageHeader
        title={`Hola${user.displayName ? `, ${user.displayName}` : ""}`}
        description="Documenta tu proceso creativo, obtén tu código ORIVA y emite declaraciones verificables."
        actions={
          <ButtonLink href="/app/works/new">
            <FilePlus2 className="h-4 w-4" aria-hidden /> Nueva declaración
          </ButtonLink>
        }
      />
      {sp.welcome && (
        <Alert tone="ok" className="mb-6" title="Cuenta creada">
          Empieza registrando una obra. Todo lo que hagas es privado hasta que decidas publicarlo.
        </Alert>
      )}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader className="flex items-center justify-between">
            <CardTitle>Borradores en curso</CardTitle>
          </CardHeader>
          <CardBody>
            {drafts.length === 0 ? (
              <div>
                <h3 className="text-sm font-medium">Aquí verás las declaraciones que estés preparando</h3>
                <p className="mt-1 text-sm text-muted">Un borrador se guarda paso a paso y es privado hasta que lo emitas.</p>
                <ButtonLink href={works === 0 ? "/app/works/new" : "/app/works"} size="sm" className="mt-3">
                  {works === 0 ? "Registrar mi primera obra" : "Elegir una obra para declarar"}
                </ButtonLink>
              </div>
            ) : (
              <ul className="divide-y divide-line">
                {drafts.map((d) => (
                  <li key={d.id} className="flex items-center justify-between gap-3 py-3">
                    <div className="min-w-0">
                      <p className="truncate font-medium">{d.declaration.work.title}</p>
                      <p className="text-xs text-muted">Versión {d.versionNumber} · actualizado {formatDate(d.updatedAt)}</p>
                    </div>
                    <Link href={`/app/declarations/${d.id}/edit/${d.lastStep ?? "work"}`} className="inline-flex min-h-11 shrink-0 items-center gap-1 text-sm font-medium text-petrol-700">
                      Continuar <ArrowRight className="h-3.5 w-3.5" aria-hidden />
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </CardBody>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Declaraciones emitidas</CardTitle>
          </CardHeader>
          <CardBody>
            {issued.length === 0 ? (
              <div>
                <h3 className="text-sm font-medium">Aquí aparecerán tus declaraciones emitidas</h3>
                <p className="mt-1 text-sm text-muted">Al emitir obtienes un identificador, un PDF y una huella verificable que puedes compartir.</p>
                <ButtonLink href="/app/works/new" variant="secondary" size="sm" className="mt-3">
                  Empezar una declaración
                </ButtonLink>
              </div>
            ) : (
              <ul className="divide-y divide-line">
                {issued.map((d) => (
                  <li key={d.id} className="flex items-center justify-between gap-3 py-3">
                    <div className="min-w-0">
                      <Link href={`/app/declarations/${d.id}`} className="flex min-h-11 items-center truncate font-medium text-petrol-700 underline-offset-4 hover:underline">
                        {d.declaration.work.title}
                      </Link>
                      <p className="font-mono text-xs text-muted">{d.publicId}</p>
                    </div>
                    <div className="flex shrink-0 gap-2">
                      <DocStatusBadge status={d.status} />
                      <VisibilityBadge visibility={d.visibility} />
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </CardBody>
        </Card>
      </div>
      <p className="mt-6 text-sm text-muted">
        Tienes {works} obra{works === 1 ? "" : "s"} registrada{works === 1 ? "" : "s"}.{" "}
        <Link href="/app/works" className="inline-flex min-h-11 items-center text-petrol-700 underline underline-offset-4">
          Ver mis obras
        </Link>
      </p>
    </>
  );
}
