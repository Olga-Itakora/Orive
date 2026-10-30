import { createCategoryAction, toggleCategoryAction } from "@/app/actions/admin";
import { AdminTable, td } from "@/components/app/admin-table";
import { CategoryForm } from "@/components/app/admin-forms";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { prisma } from "@/lib/db";

export default async function CategoriesPage() {
  const cats = await prisma.workCategory.findMany({ include: { _count: { select: { works: true } } }, orderBy: { sortOrder: "asc" } });
  return (
    <>
      <PageHeader title="Modalidades de obra" description="Las modalidades desactivadas dejan de ofrecerse, pero se conservan para las obras existentes." />
      <div className="mb-6 rounded-[var(--radius-lg)] border border-line bg-surface p-4">
        <CategoryForm action={createCategoryAction} />
      </div>
      <AdminTable caption="Modalidades" head={["Clave", "Etiqueta", "Grupo", "Obras", "Estado"]}>
        {cats.map((c) => (
          <tr key={c.key}>
            <td className={`${td} font-mono text-xs`}>{c.key}</td>
            <td className={td}>{c.labelEs} <span className="text-xs text-muted">/ {c.labelEn}</span></td>
            <td className={td}>{c.group}</td>
            <td className={td}>{c._count.works}</td>
            <td className={td}>
              <form action={toggleCategoryAction.bind(null, c.key)} className="flex items-center gap-2">
                {c.active ? <Badge tone="ok">Activa</Badge> : <Badge>Inactiva</Badge>}
                <Button size="sm" variant="link">{c.active ? "Desactivar" : "Activar"}</Button>
              </form>
            </td>
          </tr>
        ))}
      </AdminTable>
    </>
  );
}
