import type { Metadata } from "next";
import Link from "next/link";
import { Download } from "lucide-react";
import { SnapshotView } from "@/components/declaration/snapshot-view";
import { BrowserIntegrityCheck, FileCheck } from "@/components/oriva/integrity-check";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { ButtonAnchor, ButtonLink } from "@/components/ui/button";
import { getPublicDeclaration } from "@/lib/declarations/public";
import { appUrl } from "@/lib/env";
import { SECTION_NAMES } from "@/lib/integrity/disclosure";
import { normalizePublicId } from "@/lib/ids";
import { formatDate } from "@/lib/utils";

const SECTION_LABEL: Record<string, string> = {
  meta: "Datos del registro",
  work: "Obra",
  declarant: "Declarante",
  scope: "Alcance",
  classification: "Clasificación ORIVA",
  summary: "Resumen",
  process: "Proceso declarado",
  article50: "Orientación art. 50",
  statement: "Declaración responsable",
  evidence: "Evidencias",
};

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }): Promise<Metadata> {
  const { id } = await params;
  const d = await getPublicDeclaration(id);
  if (!d) return { title: "Declaración no disponible", robots: { index: false } };
  const work = d.view.sections.work?.data as { title?: string } | undefined;
  const cls = d.view.sections.classification?.data as { code?: string } | undefined;
  return { title: `${work?.title ?? d.publicId} — ORIVA Declaration`, description: `Declaración ORIVA ${d.publicId}${cls?.code ? ` · ${cls.code}` : ""}` };
}

export default async function PublicDeclarationPage({ params, searchParams }: { params: Promise<{ id: string }>; searchParams: Promise<{ tab?: string }> }) {
  const { id } = await params;
  const { tab: rawTab } = await searchParams;
  const d = await getPublicDeclaration(id);
  const normalized = normalizePublicId(id);

  if (!d) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        <h1 className="text-2xl font-semibold text-petrol-900">Declaración no disponible</h1>
        <p className="mt-3 leading-relaxed text-muted">
          No hay ninguna declaración pública con el identificador <span className="font-mono">{normalized ?? id}</span>. Puede que no exista, que sea privada o que su autor haya retirado la publicación.
        </p>
        {normalized && (
          <section className="mt-8 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
            <h2 className="font-semibold">¿Tienes el documento?</h2>
            <p className="mt-1 mb-4 text-sm text-muted">Puedes comprobar si un PDF o JSON que has recibido coincide con el registro de esta declaración, aunque no sea pública.</p>
            <FileCheck publicId={normalized} />
          </section>
        )}
      </div>
    );
  }

  const sections = Object.fromEntries(Object.entries(d.view.sections).map(([k, s]) => [k, s!.data]));
  const work = sections.work as { title?: string } | undefined;
  const p = d.publication;
  const canSummary = p.showName && (p.showFull || (p.showWork && p.showCode && p.showSummary));
  const canFull = p.showName && p.showFull;
  const hasProcess = ["process", "article50", "statement", "evidence"].some((k) => d.visible.includes(k as never));
  const tabs = [
    { key: "declaration", label: "Declaración" },
    ...(hasProcess ? [{ key: "process", label: "Proceso declarado" }] : []),
    { key: "integrity", label: "Integridad y descargas" },
  ];
  const tab = tabs.some((t) => t.key === rawTab) ? rawTab! : "declaration";
  const pick = (keys: string[]) => Object.fromEntries(keys.filter((k) => k in sections).map((k) => [k, sections[k]]));
  const statusTone = d.status === "ISSUED" ? "ok" : d.status === "REVOKED" ? "danger" : "neutral";
  const statusText = d.status === "ISSUED" ? "Vigente" : d.status === "REVOKED" ? "Revocada" : "Sustituida";

  return (
    <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
      <p className="text-xs font-semibold tracking-[0.2em] text-accent uppercase">ORIVA Declaration</p>
      <h1 className="mt-2 text-3xl font-semibold text-petrol-900">{work?.title ?? "Declaración ORIVA"}</h1>
      <div className="mt-3 flex flex-wrap items-center gap-2 text-sm text-muted">
        <span className="font-mono text-ink">{d.publicId}</span>
        <span>· emitida el {formatDate(d.issuedAt, true)}</span>
        <Badge tone={statusTone}>{statusText}</Badge>
      </div>

      <div className="mt-6 space-y-3">
        {d.status === "SUPERSEDED" && (
          <Alert tone="info" title="Esta versión ha sido sustituida">
            Existe una versión posterior de esta declaración
            {d.supersededBy?.isPublic ? (
              <>
                :{" "}
                <Link href={`/verify/${d.supersededBy.publicId}`} className="font-mono underline">
                  {d.supersededBy.publicId}
                </Link>
              </>
            ) : (
              " (no publicada)"
            )}
            . Esta versión se conserva sin cambios.
          </Alert>
        )}
        {d.status === "REVOKED" && (
          <Alert tone="danger" title="Declaración revocada por su autor">
            {d.revokedAt && `Revocada el ${formatDate(d.revokedAt, true)}.`} {d.revocationReason && `Motivo: ${d.revocationReason}`} El contenido se conserva para su verificación, pero el declarante indica que ya no es válida.
          </Alert>
        )}
        {d.supersedes?.isPublic && (
          <p className="text-sm text-muted">
            Sustituye a la versión{" "}
            <Link href={`/verify/${d.supersedes.publicId}`} className="font-mono underline">
              {d.supersedes.publicId}
            </Link>
            .
          </p>
        )}
      </div>

      <nav aria-label="Secciones de la declaración" className="mt-8 relative overflow-x-auto border-b border-line">
        <ul className="flex min-w-max gap-1">
          {tabs.map((t) => (
            <li key={t.key}>
              <Link
                href={`/verify/${d.publicId}${t.key === "declaration" ? "" : `?tab=${t.key}`}`}
                aria-current={tab === t.key ? "page" : undefined}
                className="-mb-px inline-flex min-h-11 items-center border-b-2 border-transparent px-4 text-sm whitespace-nowrap text-muted hover:text-petrol-800 aria-[current=page]:border-petrol-800 aria-[current=page]:font-medium aria-[current=page]:text-petrol-900"
              >
                {t.label}
              </Link>
            </li>
          ))}
        </ul>
      </nav>

      <div className="mt-8">
        {tab === "declaration" && (
          <div className="max-w-4xl min-w-0">
            <SnapshotView sections={pick(["meta", "work", "declarant", "scope", "classification", "summary"])} />
          </div>
        )}
        {tab === "process" && (
          <div className="max-w-4xl min-w-0">
            <SnapshotView sections={pick(["meta", "work", "classification", "process", "article50", "statement", "evidence"])} showCodeCard={false} render={["process", "article50", "statement", "evidence"]} />
          </div>
        )}
        {tab === "integrity" && <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
          <section className="rounded-[var(--radius-lg)] border border-line bg-surface p-5">
            <h2 className="font-semibold">Integridad</h2>
            <p className="mt-2 text-xs text-muted">Huella SHA-256 registrada:</p>
            <p className="font-mono text-xs break-all">{d.contentHash}</p>
            <div className="mt-4">
              <BrowserIntegrityCheck publicId={d.publicId} expectedHash={d.contentHash} />
            </div>
            <p className="mt-4 text-xs leading-relaxed text-muted">
              Las secciones no publicadas por el autor se representan solo por su resumen criptográfico, de modo que la huella puede comprobarse sin revelarlas.
            </p>
            <ul className="mt-3 space-y-1 text-xs">
              {SECTION_NAMES.map((s) => (
                <li key={s} className="flex justify-between gap-2">
                  <span>{SECTION_LABEL[s]}</span>
                  <span className={d.visible.includes(s) ? "text-ok" : "text-subtle"}>{d.visible.includes(s) ? "publicada" : "no publicada"}</span>
                </li>
              ))}
            </ul>
          </section>

          <section className="rounded-[var(--radius-lg)] border border-line bg-surface p-5">
            <h2 className="font-semibold">Comprobar un documento</h2>
            <p className="mt-1 mb-3 text-xs text-muted">¿Has recibido el PDF o el JSON de esta declaración? Comprueba que es idéntico al registrado.</p>
            <FileCheck publicId={d.publicId} />
          </section>

          <section className="space-y-2 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
            <h2 className="font-semibold">Descargas</h2>
            <ButtonAnchor href={`/api/v1/declarations/${d.publicId}`} variant="secondary" size="sm" className="w-full">
              <Download className="h-4 w-4" aria-hidden /> Descargar datos publicados (JSON)
            </ButtonAnchor>
            {canSummary && (
              <ButtonAnchor href={`/verify/${d.publicId}/pdf?type=summary`} variant="secondary" size="sm" className="w-full">
                <Download className="h-4 w-4" aria-hidden /> Descargar resumen (PDF)
              </ButtonAnchor>
            )}
            {canFull && (
              <ButtonAnchor href={`/verify/${d.publicId}/pdf?type=full`} variant="secondary" size="sm" className="w-full">
                <Download className="h-4 w-4" aria-hidden /> Descargar declaración completa (PDF)
              </ButtonAnchor>
            )}
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={`/verify/${d.publicId}/qr.svg`} alt={`Código QR de ${appUrl(`/verify/${d.publicId}`)}`} className="mt-3 h-32 w-32 rounded border border-line bg-white p-2" />
          </section>

          {d.otherPublicVersions.length > 0 && (
            <section className="rounded-[var(--radius-lg)] border border-line bg-surface p-5 text-sm">
              <h2 className="font-semibold">Otras versiones públicas</h2>
              <ul className="mt-2 space-y-1">
                {d.otherPublicVersions.map((o) => (
                  <li key={o.publicId}>
                    <Link href={`/verify/${o.publicId}`} className="font-mono text-xs underline">
                      {o.publicId}
                    </Link>{" "}
                    · versión {o.versionNumber}
                  </li>
                ))}
              </ul>
            </section>
          )}

          <p className="text-xs leading-relaxed text-muted">
            Esta es una declaración responsable de su autor. ORIVA acredita que fue registrada con este contenido y que no ha sido alterada; no acredita la identidad del declarante, la veracidad de lo declarado, la originalidad, la titularidad de derechos ni el cumplimiento legal.{" "}
            <Link href="/code" className="underline">
              Cómo leer el código ORIVA
            </Link>
          </p>
        </div>}
      </div>
    </div>
  );
}
