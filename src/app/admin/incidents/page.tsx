import Link from "next/link";
import { adminWithdrawAction, updateIncidentAction } from "@/app/actions/admin";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

const TYPE: Record<string, string> = {
  CONTACT: "Consulta", CONTENT_REPORT: "Comunicación de contenido", TECHNICAL: "Técnica",
  DATA_ACCESS: "RGPD · acceso", DATA_RECTIFICATION: "RGPD · rectificación", DATA_ERASURE: "RGPD · supresión", DATA_OBJECTION: "RGPD · oposición", DATA_PORTABILITY: "RGPD · portabilidad", DATA_RESTRICTION: "RGPD · limitación",
};

export default async function IncidentsPage({ searchParams }: { searchParams: Promise<{ all?: string }> }) {
  const { all } = await searchParams;
  const items = await prisma.incident.findMany({ where: all ? {} : { status: { in: ["OPEN", "IN_PROGRESS"] } }, orderBy: { createdAt: "desc" }, take: 200 });
  return (
    <>
      <PageHeader
        title="Incidencias y solicitudes"
        description="Incluye solicitudes de derechos de protección de datos (plazo legal: un mes). Ver PRIVACY.md para el procedimiento de supresión."
        actions={<Link href={all ? "/admin/incidents" : "/admin/incidents?all=1"} className="inline-flex min-h-11 items-center text-sm underline">{all ? "Ver solo abiertas" : "Ver todas"}</Link>}
      />
      {items.length === 0 && <p className="text-sm text-muted">No hay incidencias.</p>}
      <ul className="space-y-4">
        {items.map((i) => (
          <li key={i.id} className="rounded-[var(--radius-lg)] border border-line bg-surface p-4">
            <div className="flex flex-wrap items-center gap-2">
              <Badge tone={i.type.startsWith("DATA_") ? "warn" : "petrol"}>{TYPE[i.type]}</Badge>
              <Badge>{i.status}</Badge>
              <span className="text-xs text-muted">{formatDate(i.createdAt, true)} · {i.email}{i.name ? ` · ${i.name}` : ""}{i.userId ? " · usuario registrado" : ""}</span>
            </div>
            {i.publicId && <p className="mt-1 text-sm">Declaración: <Link href={`/verify/${i.publicId}`} className="font-mono underline">{i.publicId}</Link></p>}
            <p className="mt-2 text-sm whitespace-pre-line">{i.message}</p>
            <form action={updateIncidentAction.bind(null, i.id)} className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-[10rem_1fr_auto] sm:items-end">
              <label className="flex flex-col gap-1 text-sm font-medium">
                Estado
              <select name="status" defaultValue={i.status} className="h-11 rounded border border-line-strong bg-surface px-2 text-sm font-normal">
                <option value="OPEN">Abierta</option>
                <option value="IN_PROGRESS">En curso</option>
                <option value="RESOLVED">Resuelta</option>
                <option value="REJECTED">Rechazada</option>
              </select>
              </label>
              <label className="flex flex-col gap-1 text-sm font-medium">
                Resolución o notas
                <input name="resolution" defaultValue={i.resolution ?? ""} className="h-11 rounded border border-line-strong bg-surface px-2 text-sm font-normal" />
              </label>
              <Button variant="secondary">Actualizar incidencia</Button>
            </form>
            {i.type === "CONTENT_REPORT" && i.publicId && (
              <div className="mt-2">
                <ConfirmDialog
                  trigger="text"
                  triggerLabel="Retirar publicación"
                  title={`¿Retirar la publicación de la declaración ${i.publicId}?`}
                  body="Dejará de ser consultable públicamente. No se modifica ni se revoca su contenido, y la acción queda auditada."
                  confirmLabel="Retirar publicación"
                  action={adminWithdrawAction.bind(null, i.publicId)}
                >
                  <label className="flex flex-col gap-1 text-sm font-medium">
                    Motivo de la retirada
                    <textarea name="reason" rows={2} maxLength={1000} className="rounded border border-line-strong bg-surface p-2 text-sm font-normal" />
                  </label>
                </ConfirmDialog>
              </div>
            )}
          </li>
        ))}
      </ul>
    </>
  );
}
