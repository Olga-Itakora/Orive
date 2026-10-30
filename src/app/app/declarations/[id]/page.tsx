import Link from "next/link";
import { notFound } from "next/navigation";
import { Download, ExternalLink, FilePlus2, Pencil } from "lucide-react";
import { newVersionAction, publishAction, revokeAction, withdrawAction } from "@/app/actions/declarations";
import { PageHeader } from "@/components/app/page-header";
import { DocStatusBadge, VisibilityBadge } from "@/components/app/status-badges";
import { PublishForm, RevokeDialog } from "@/components/declaration/owner-forms";
import { SnapshotView } from "@/components/declaration/snapshot-view";
import { CopyButton } from "@/components/oriva/copy-button";
import { Alert } from "@/components/ui/alert";
import { ButtonAnchor, ButtonLink } from "@/components/ui/button";
import { Card, CardBody, CardHeader, CardTitle } from "@/components/ui/card";
import { SubmitButton } from "@/components/ui/submit-button";
import { requireUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { currentPublication, DomainError, getOwnedVersion } from "@/lib/declarations/service";
import { appUrl } from "@/lib/env";
import type { FullSnapshot } from "@/lib/integrity/disclosure";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Declaración" };

export default async function DeclarationPage({ params, searchParams }: { params: Promise<{ id: string }>; searchParams: Promise<{ issued?: string; tab?: string }> }) {
  const user = await requireUser();
  const { id } = await params;
  const sp = await searchParams;
  let v;
  try {
    v = await getOwnedVersion(user.id, id);
  } catch (e) {
    if (e instanceof DomainError) notFound();
    throw e;
  }
  const work = v.declaration.work;
  const versions = await prisma.declarationVersion.findMany({
    where: { declarationId: v.declarationId },
    select: { id: true, versionNumber: true, status: true, visibility: true, publicId: true, issuedAt: true, updatedAt: true },
    orderBy: { versionNumber: "desc" },
  });

  if (v.status === "DRAFT") {
    return (
      <>
        <PageHeader title={`Borrador · ${work.title}`} back={{ href: `/app/works/${work.id}`, label: work.title }} description={`Versión ${v.versionNumber}. Aún no se ha emitido.`} />
        <ButtonLink href={`/app/declarations/${id}/edit/${v.lastStep ?? "work"}`}>
          <Pencil className="h-4 w-4" aria-hidden /> Continuar la declaración
        </ButtonLink>
      </>
    );
  }

  const snapshot = v.snapshot as unknown as FullSnapshot;
  const sections = Object.fromEntries(Object.entries(snapshot.sections).map(([k, s]) => [k, s.data]));
  const pub = await currentPublication(id);
  const verifyUrl = appUrl(`/verify/${v.publicId}`);
  const latest = versions[0];
  const hasDraft = latest.status === "DRAFT";
  const canNewVersion = v.status === "ISSUED" || hasDraft;
  const flags = pub && pub.action !== "WITHDRAW" ? pub : { showName: false, showWork: true, showCode: true, showSummary: true, showFull: false, showEvidence: false };
  const badgeUrl = appUrl(`/badge/${v.publicId}.svg`);
  const embed = `<a href="${verifyUrl}" rel="noopener"><img src="${badgeUrl}" alt="ORIVA ${(sections.classification as { code?: string })?.code ?? "Declaration"} — ${v.publicId}" height="28"></a>`;

  const tab = sp.tab === "publication" || sp.tab === "versions" || sp.tab === "process" ? sp.tab : "declaration";
  const tabs = [
    { key: "declaration", label: "Declaración" },
    { key: "process", label: "Proceso declarado" },
    { key: "publication", label: "Publicación y distintivo" },
    { key: "versions", label: "Integridad y versiones" },
  ];

  return (
    <>
      <PageHeader
        title={work.title}
        description={
          <span className="flex flex-wrap items-center gap-2">
            <DocStatusBadge status={v.status} /> <VisibilityBadge visibility={v.visibility} />
            <span className="font-mono">{v.publicId}</span> · versión {v.versionNumber} · emitida {formatDate(v.issuedAt, true)}
          </span>
        }
        back={{ href: `/app/works/${work.id}`, label: work.title }}
        actions={
          <>
            <ButtonAnchor href={`/app/declarations/${id}/pdf?type=full`} variant="secondary" size="sm" download>
              <Download className="h-4 w-4" aria-hidden /> Descargar PDF completo
            </ButtonAnchor>
            <ButtonAnchor href={`/app/declarations/${id}/pdf?type=summary`} variant="secondary" size="sm" download>
              <Download className="h-4 w-4" aria-hidden /> Descargar resumen
            </ButtonAnchor>
            <ButtonAnchor href={`/app/declarations/${id}/json`} variant="secondary" size="sm" download>
              <Download className="h-4 w-4" aria-hidden /> Descargar JSON
            </ButtonAnchor>
          </>
        }
      />

      {sp.issued && (
        <Alert tone="ok" className="mb-6" title="Declaración emitida">
          Se ha registrado con el identificador <span className="font-mono">{v.publicId}</span>. Es privada: si quieres que otras personas puedan consultarla,{" "}
          <Link href={`/app/declarations/${id}?tab=publication`} className="underline">
            elige qué publicar
          </Link>
          .
        </Alert>
      )}
      {v.status === "SUPERSEDED" && v.supersededBy && (
        <Alert tone="info" className="mb-6">
          Esta versión ha sido sustituida por la{" "}
          <Link className="underline" href={`/app/declarations/${v.supersededBy.id}`}>
            versión {v.supersededBy.versionNumber}
          </Link>
          . Se conserva sin cambios.
        </Alert>
      )}
      {v.status === "REVOKED" && (
        <Alert tone="danger" className="mb-6" title="Declaración revocada">
          {formatDate(v.revokedAt, true)} · Motivo: {v.revocationReason}
        </Alert>
      )}

      <nav aria-label="Secciones de la declaración" className="mb-6 relative overflow-x-auto border-b border-line">
        <ul className="flex min-w-max gap-1">
          {tabs.map((t) => (
            <li key={t.key}>
              <Link
                href={`/app/declarations/${id}${t.key === "declaration" ? "" : `?tab=${t.key}`}`}
                aria-current={tab === t.key ? "page" : undefined}
                className="-mb-px inline-flex min-h-11 items-center border-b-2 border-transparent px-4 text-sm whitespace-nowrap text-muted hover:text-petrol-800 aria-[current=page]:border-petrol-800 aria-[current=page]:font-medium aria-[current=page]:text-petrol-900"
              >
                {t.label}
              </Link>
            </li>
          ))}
        </ul>
      </nav>

      {tab === "declaration" && (
        <div className="max-w-4xl min-w-0">
          <SnapshotView sections={sections} verifyUrl={v.visibility === "PUBLIC" ? verifyUrl : undefined} render={["meta", "work", "declarant", "scope", "classification", "summary"]} />
        </div>
      )}

      {tab === "process" && (
        <div className="max-w-4xl min-w-0">
          <SnapshotView sections={sections} showCodeCard={false} render={["process", "article50", "statement", "evidence"]} />
        </div>
      )}

      {tab === "publication" && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card className="min-w-0">
            <CardHeader>
              <CardTitle>Qué se puede consultar públicamente</CardTitle>
            </CardHeader>
            <CardBody className="space-y-4">
              {v.visibility === "PUBLIC" && (
                <div className="space-y-2 rounded-md bg-petrol-50 p-3 text-sm">
                  <p className="font-medium text-petrol-900">Publicada en</p>
                  <a href={`/verify/${v.publicId}`} className="inline-flex min-h-11 items-center gap-1 break-all text-petrol-700 underline" target="_blank">
                    {verifyUrl} <ExternalLink className="h-4 w-4 shrink-0" aria-hidden />
                  </a>
                  <div className="flex flex-wrap gap-2">
                    <CopyButton value={verifyUrl} label="Copiar enlace" />
                  </div>
                </div>
              )}
              {v.visibility === "WITHDRAWN" && <p className="text-sm text-muted">Publicación retirada. La declaración sigue emitida y verificable por quien tenga el documento.</p>}
              <PublishForm action={publishAction.bind(null, id)} initial={flags} isPublic={v.visibility === "PUBLIC"} hasEvidence={v.evidence.length > 0} />
              {v.visibility === "PUBLIC" && (
                <form action={withdrawAction.bind(null, id)} className="border-t border-line pt-4">
                  <SubmitButton variant="secondary" size="sm">
                    Retirar publicación
                  </SubmitButton>
                  <p className="mt-2 text-sm text-muted">Deja de ser consultable públicamente. No revoca ni elimina la declaración, y puedes volver a publicarla.</p>
                </form>
              )}
            </CardBody>
          </Card>
          {v.visibility === "PUBLIC" ? (
            <Card className="min-w-0">
              <CardHeader>
                <CardTitle>Compartir y distintivo</CardTitle>
              </CardHeader>
              <CardBody className="space-y-4 text-sm">
                <p className="text-muted">Código QR de verificación (también incluido en los PDF):</p>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={`/verify/${v.publicId}/qr.svg`} alt={`Código QR que enlaza con ${verifyUrl}`} className="h-36 w-36 rounded border border-line bg-white p-2" />
                <p className="text-muted">Distintivo para webs. Enlaza con esta declaración; no es un sello de certificación.</p>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={`/badge/${v.publicId}.svg`} alt="Distintivo ORIVA" className="h-7" />
                <pre className="rounded border border-line bg-paper p-3 font-mono text-xs break-all whitespace-pre-wrap">{embed}</pre>
                <CopyButton value={embed} label="Copiar código HTML" />
              </CardBody>
            </Card>
          ) : (
            <Card className="min-w-0">
              <CardBody className="text-sm text-muted">
                Cuando la publiques, aquí tendrás el enlace, el código QR y un distintivo para incrustar en tu web o en la página de la obra.
              </CardBody>
            </Card>
          )}
        </div>
      )}

      {tab === "versions" && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card className="min-w-0">
            <CardHeader>
              <CardTitle>Integridad</CardTitle>
            </CardHeader>
            <CardBody className="space-y-2 text-sm">
              <p className="text-muted">Huella SHA-256 del contenido registrado:</p>
              <p className="font-mono text-xs break-all">{v.contentHash}</p>
              <p className="text-muted">Acredita que el contenido no ha cambiado desde su emisión. No acredita identidad, veracidad, originalidad, titularidad ni cumplimiento legal.</p>
            </CardBody>
          </Card>
          <Card className="min-w-0">
            <CardHeader>
              <CardTitle>Versiones</CardTitle>
            </CardHeader>
            <CardBody className="space-y-4">
              <ul className="divide-y divide-line text-sm">
                {versions.map((x) => (
                  <li key={x.id} className="flex items-center justify-between gap-2 py-2">
                    <Link href={x.status === "DRAFT" ? `/app/declarations/${x.id}/edit/declarant` : `/app/declarations/${x.id}?tab=versions`} className={`inline-flex min-h-11 flex-col justify-center ${x.id === id ? "font-semibold" : "text-petrol-700 hover:text-petrol-900"}`}>
                      Versión {x.versionNumber}
                      {x.publicId && <span className="font-mono text-xs font-normal text-muted">{x.publicId}</span>}
                    </Link>
                    <DocStatusBadge status={x.status} />
                  </li>
                ))}
              </ul>
              {canNewVersion && (
                <form action={newVersionAction.bind(null, v.declarationId)}>
                  <SubmitButton variant="secondary" size="sm">
                    <FilePlus2 className="h-4 w-4" aria-hidden /> {hasDraft ? "Continuar la nueva versión" : "Crear nueva versión"}
                  </SubmitButton>
                  <p className="mt-2 text-sm text-muted">La versión actual se conservará intacta y quedará enlazada a la nueva.</p>
                </form>
              )}
              {(v.status === "ISSUED" || v.status === "SUPERSEDED") && (
                <div className="border-t border-line pt-4">
                  <RevokeDialog action={revokeAction.bind(null, id)} entity={`«${work.title}» (${v.publicId}, versión ${v.versionNumber})`} />
                </div>
              )}
            </CardBody>
          </Card>
        </div>
      )}
    </>
  );
}
