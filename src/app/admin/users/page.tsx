import { setUserRoleAction, toggleUserDisabledAction } from "@/app/actions/admin";
import { AdminTable, td } from "@/components/app/admin-table";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { ListSearch, Pagination } from "@/components/ui/list-toolbar";
import { requireAdmin } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export default async function UsersPage({ searchParams }: { searchParams: Promise<{ q?: string; page?: string }> }) {
  const admin = await requireAdmin();
  const { q, page: rawPage } = await searchParams;
  const page = Math.max(1, Number(rawPage) || 1);
  const PAGE = 50;
  const where = q ? { OR: [{ email: { contains: q, mode: "insensitive" as const } }, { profile: { displayName: { contains: q, mode: "insensitive" as const } } }] } : {};
  const total = await prisma.user.count({ where });
  const users = await prisma.user.findMany({
    where: q ? { OR: [{ email: { contains: q, mode: "insensitive" } }, { profile: { displayName: { contains: q, mode: "insensitive" } } }] } : {},
    include: { profile: true, _count: { select: { works: true, declarations: true } } },
    orderBy: { createdAt: "desc" },
    take: PAGE,
    skip: (page - 1) * PAGE,
  });
  return (
    <>
      <PageHeader title="Usuarios y permisos" />
      <div className="mb-4">
        <ListSearch q={q} label="Buscar usuarios" hint="Por correo o nombre profesional." />
      </div>
      <AdminTable caption="Usuarios" head={["Usuario", "Alta", "Obras / declaraciones", "Rol", "Estado"]}>
        {users.map((u) => (
          <tr key={u.id}>
            <td className={td}>
              <p className="font-medium">{u.profile?.displayName ?? "—"}</p>
              <p className="text-xs text-muted">{u.email}</p>
            </td>
            <td className={td}>{formatDate(u.createdAt)}</td>
            <td className={td}>
              {u._count.works} / {u._count.declarations}
            </td>
            <td className={td}>
              <form action={setUserRoleAction.bind(null, u.id)} className="flex items-center gap-2">
                <label htmlFor={`role-${u.id}`} className="sr-only md:not-sr-only md:text-xs md:text-muted">Rol</label>
                <select id={`role-${u.id}`} name="role" defaultValue={u.role} disabled={u.id === admin.id} className="h-11 rounded border border-line-strong bg-surface px-2 text-sm">
                  <option value="AUTHOR">Autor</option>
                  <option value="ADMIN">Administrador</option>
                </select>
                {u.id !== admin.id && <Button size="sm" variant="secondary">Cambiar rol</Button>}
              </form>
            </td>
            <td className={td}>
              {u.disabledAt ? <Badge tone="danger">Desactivado</Badge> : <Badge tone="ok">Activo</Badge>}
              {u.id !== admin.id && (
                u.disabledAt ? (
                  <form action={toggleUserDisabledAction.bind(null, u.id)} className="mt-1">
                    <Button size="sm" variant="link">Reactivar cuenta</Button>
                  </form>
                ) : (
                  <ConfirmDialog
                    trigger="text"
                    triggerLabel="Desactivar cuenta"
                    title={`¿Desactivar la cuenta de «${u.email}»?`}
                    body="Se cerrarán sus sesiones y no podrá acceder hasta que se reactive. Sus declaraciones emitidas no cambian."
                    confirmLabel="Desactivar cuenta"
                    action={toggleUserDisabledAction.bind(null, u.id)}
                  />
                )
              )}
            </td>
          </tr>
        ))}
      </AdminTable>
      <Pagination page={page} pages={Math.ceil(total / PAGE)} href={(p) => `/admin/users?${new URLSearchParams({ ...(q ? { q } : {}), page: String(p) })}`} />
    </>
  );
}
