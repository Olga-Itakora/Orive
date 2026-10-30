import Link from "next/link";
import type { Prisma } from "@prisma/client";
import { ChevronRight, Plus } from "lucide-react";
import { PageHeader } from "@/components/app/page-header";
import { ButtonLink } from "@/components/ui/button";
import { ListSearch, Pagination, TableFrame } from "@/components/ui/list-toolbar";
import { requireUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { WORK_STATUS_LABEL } from "@/lib/labels";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Mis obras" };
const PAGE = 20;

/** Patrón: Lista / Índice. */
export default async function WorksPage({ searchParams }: { searchParams: Promise<{ q?: string; page?: string }> }) {
  const user = await requireUser();
  const sp = await searchParams;
  const q = sp.q?.trim().slice(0, 100) || undefined;
  const page = Math.max(1, Number(sp.page) || 1);
  const where: Prisma.WorkWhereInput = { ownerId: user.id, ...(q ? { OR: [{ title: { contains: q, mode: "insensitive" } }, { subtitle: { contains: q, mode: "insensitive" } }] } : {}) };
  const [total, works, any] = await Promise.all([
    prisma.work.count({ where }),
    prisma.work.findMany({ where, include: { category: true, _count: { select: { components: true, declarations: true } } }, orderBy: { updatedAt: "desc" }, take: PAGE, skip: (page - 1) * PAGE }),
    prisma.work.count({ where: { ownerId: user.id } }),
  ]);
  const pages = Math.ceil(total / PAGE);
  const href = (p: number) => `/app/works?${new URLSearchParams({ ...(q ? { q } : {}), page: String(p) })}`;
  return (
    <>
      <PageHeader
        title="Mis obras"
        description="Cada obra puede tener componentes (texto, imágenes, audio…) y una o varias declaraciones con sus versiones."
        actions={
          <ButtonLink href="/app/works/new">
            <Plus className="h-4 w-4" aria-hidden /> Registrar obra
          </ButtonLink>
        }
      />
      <div className="mb-4">
        <ListSearch q={q} label="Buscar obras" />
      </div>
      <TableFrame>
        {works.length === 0 ? (
          any === 0 ? (
            <div className="p-10 text-center">
              <h2 className="font-medium">Aquí aparecerán tus obras</h2>
              <p className="mt-1 text-sm text-muted">Registra una obra para describir su proceso creativo y emitir su declaración de transparencia.</p>
              <ButtonLink href="/app/works/new" className="mt-4">
                Registrar mi primera obra
              </ButtonLink>
            </div>
          ) : (
            <div className="p-10 text-center">
              <h2 className="font-medium">Ninguna obra coincide con «{q}»</h2>
              <p className="mt-1 text-sm text-muted">Prueba con otra palabra del título o del subtítulo.</p>
              <ButtonLink href="/app/works" variant="secondary" className="mt-4">
                Borrar la búsqueda
              </ButtonLink>
            </div>
          )
        ) : (
          <div className="relative overflow-x-auto">
            <table className="w-full min-w-[640px] text-sm">
              <caption className="sr-only">Obras, ordenadas por última modificación</caption>
              <thead className="border-b border-line bg-paper text-left text-xs text-muted">
                <tr>
                  <th scope="col" className="px-4 py-3 font-medium">Obra</th>
                  <th scope="col" className="px-4 py-3 font-medium">Tipo</th>
                  <th scope="col" className="px-4 py-3 font-medium">Estado</th>
                  <th scope="col" className="px-4 py-3 font-medium">Componentes</th>
                  <th scope="col" className="px-4 py-3 font-medium">Declaraciones</th>
                  <th scope="col" className="px-4 py-3 font-medium" aria-sort="descending">Modificada</th>
                  <th scope="col" className="w-10 px-2 py-3"><span className="sr-only">Abrir</span></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {works.map((w) => (
                  <tr key={w.id} className="hover:bg-petrol-50/50">
                    <td className="px-4 py-3">
                      <Link href={`/app/works/${w.id}`} className="font-medium text-petrol-700 underline-offset-4 hover:underline">
                        {w.title}
                      </Link>
                      {w.subtitle && <span className="block text-muted">{w.subtitle}</span>}
                    </td>
                    <td className="px-4 py-3">{w.category.labelEs}</td>
                    <td className="px-4 py-3">{WORK_STATUS_LABEL[w.status]}</td>
                    <td className="px-4 py-3">{w._count.components}</td>
                    <td className="px-4 py-3">{w._count.declarations}</td>
                    <td className="px-4 py-3 text-muted">{formatDate(w.updatedAt)}</td>
                    <td className="px-2 py-3 text-muted">
                      <ChevronRight className="h-4 w-4" aria-hidden />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <Pagination page={page} pages={pages} href={href} />
      </TableFrame>
    </>
  );
}
