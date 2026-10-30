import { AdminTable, td } from "@/components/app/admin-table";
import { PageHeader } from "@/components/app/page-header";
import { ButtonLink } from "@/components/ui/button";
import { ListSearch, Pagination } from "@/components/ui/list-toolbar";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export default async function AuditPage({ searchParams }: { searchParams: Promise<{ q?: string; page?: string }> }) {
  const { q: action, page: rawPage } = await searchParams;
  const page = Math.max(1, Number(rawPage) || 1);
  const PAGE = 100;
  const where = action ? { action: { startsWith: action } } : {};
  const total = await prisma.auditLog.count({ where });
  const logs = await prisma.auditLog.findMany({ where: action ? { action: { startsWith: action } } : {}, include: { actor: { select: { email: true } } }, orderBy: { createdAt: "desc" }, take: PAGE, skip: (page - 1) * PAGE });
  return (
    <>
      <PageHeader title="Registro de auditoría" description="Registro de solo inserción (protegido en base de datos). Las IP se guardan como huellas con clave, nunca en claro." />
      <div className="mb-4">
        <ListSearch q={action} label="Filtrar por acción" hint="Prefijo de la acción, p. ej. declaration. o admin." />
      </div>
      {logs.length === 0 && (
        <div className="mb-4 rounded-[var(--radius-lg)] border border-line bg-surface p-8 text-center">
          <p className="font-medium">Ningún registro coincide con «{action}»</p>
          <ButtonLink href="/admin/audit" variant="secondary" className="mt-3">Borrar el filtro</ButtonLink>
        </div>
      )}
      <AdminTable caption="Auditoría" head={["Fecha (UTC)", "Acción", "Actor", "Entidad", "Datos"]}>
        {logs.map((l) => (
          <tr key={l.id}>
            <td className={`${td} text-xs whitespace-nowrap`}>{formatDate(l.createdAt, true)}</td>
            <td className={`${td} font-mono text-xs`}>{l.action}</td>
            <td className={`${td} text-xs`}>{l.actor?.email ?? "—"}</td>
            <td className={`${td} font-mono text-xs`}>{l.entityType}{l.entityId ? `:${l.entityId.slice(0, 10)}` : ""}</td>
            <td className={`${td} max-w-xs font-mono text-xs break-all text-muted`}>{JSON.stringify(l.metadata)}</td>
          </tr>
        ))}
      </AdminTable>
      <Pagination page={page} pages={Math.ceil(total / PAGE)} href={(p) => `/admin/audit?${new URLSearchParams({ ...(action ? { q: action } : {}), page: String(p) })}`} />
    </>
  );
}
