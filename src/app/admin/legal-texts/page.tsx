import { createLegalTextAction, publishLegalTextAction } from "@/app/actions/admin";
import { AdminTable, td } from "@/components/app/admin-table";
import { LegalTextForm } from "@/components/app/admin-forms";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export default async function LegalTextsPage() {
  const texts = await prisma.legalText.findMany({ include: { _count: { select: { acceptances: true } } }, orderBy: [{ key: "asc" }, { createdAt: "desc" }] });
  return (
    <>
      <PageHeader title="Textos legales" description="Las versiones publicadas son inmutables (protegidas en base de datos). Cada aceptación registra la versión exacta y su huella. Publicar una versión la convierte en la activa para su tipo." />
      <AdminTable caption="Textos legales" head={["Texto", "Versión", "Publicada", "Aceptaciones", "Estado"]}>
        {texts.map((t) => (
          <tr key={t.id}>
            <td className={td}>
              {t.title}
              <span className="block font-mono text-xs text-muted">{t.key} · {t.contentHash.slice(0, 12)}…</span>
            </td>
            <td className={`${td} font-mono`}>{t.version}</td>
            <td className={td}>{formatDate(t.publishedAt)}</td>
            <td className={td}>{t._count.acceptances}</td>
            <td className={td}>
              <span className="flex flex-wrap items-center gap-2">
                {t.active ? <Badge tone="ok">Activo</Badge> : t.publishedAt ? <Badge>Histórico</Badge> : <Badge tone="warn">Borrador</Badge>}
                {t.pendingLegalReview && <Badge tone="warn">Pendiente de revisión jurídica</Badge>}
              </span>
              {!t.active && (
                <form action={publishLegalTextAction.bind(null, t.id)} className="mt-1">
                  <Button size="sm" variant="link">{t.publishedAt ? "Reactivar" : "Publicar y activar"}</Button>
                </form>
              )}
            </td>
          </tr>
        ))}
      </AdminTable>
      <section className="mt-8 rounded-[var(--radius-lg)] border border-line bg-surface p-5">
        <h2 className="mb-3 font-semibold">Nueva versión</h2>
        <LegalTextForm action={createLegalTextAction} />
      </section>
    </>
  );
}
