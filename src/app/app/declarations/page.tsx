import Link from "next/link";
import type { Prisma } from "@prisma/client";
import { ButtonLink } from "@/components/ui/button";
import { ListSearch, Pagination, TableFrame } from "@/components/ui/list-toolbar";
import { PageHeader } from "@/components/app/page-header";
import { DocStatusBadge, VisibilityBadge } from "@/components/app/status-badges";
import { requireUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Mis declaraciones" };

const PAGE = 25;

/** Patrón: Lista / Índice. */
export default async function DeclarationsPage({ searchParams }: { searchParams: Promise<{ q?: string; page?: string }> }) {
  const user = await requireUser();
  const sp = await searchParams;
  const q = sp.q?.trim().slice(0, 100) || undefined;
  const page = Math.max(1, Number(sp.page) || 1);
  const where: Prisma.DeclarationVersionWhereInput = {
    declaration: { ownerId: user.id },
    ...(q ? { OR: [{ publicId: { contains: q.toUpperCase() } }, { declaration: { work: { title: { contains: q, mode: "insensitive" } } } }] } : {}),
  };
  const [total, versions] = await Promise.all([
    prisma.declarationVersion.count({ where }),
    prisma.declarationVersion.findMany({
      where,
      include: { declaration: { include: { work: { select: { title: true, id: true } } } } },
      orderBy: [{ updatedAt: "desc" }],
      take: PAGE,
      skip: (page - 1) * PAGE,
    }),
  ]);
  const pages = Math.ceil(total / PAGE);
  const href = (p: number) => `/app/declarations?${new URLSearchParams({ ...(q ? { q } : {}), page: String(p) })}`;
  return (
    <>
      <PageHeader title="Mis declaraciones" description="Todas las versiones, emitidas y en borrador. El estado documental (emitida, sustituida, revocada) es independiente de la visibilidad (privada, pública, retirada)." />
      <div className="mb-4">
        <ListSearch q={q} label="Buscar declaraciones" hint="Por título de la obra o identificador ORV-…" />
      </div>
      <TableFrame>
        {versions.length === 0 ? (
          <div className="p-10 text-center">
            {q ? (
              <>
                <h2 className="font-medium">Ninguna declaración coincide con «{q}»</h2>
                <p className="mt-1 text-sm text-muted">Prueba con el título de la obra o con el identificador completo.</p>
                <ButtonLink href="/app/declarations" variant="secondary" className="mt-4">
                  Borrar la búsqueda
                </ButtonLink>
              </>
            ) : (
              <>
                <h2 className="font-medium">Aquí aparecerán tus declaraciones y sus versiones</h2>
                <p className="mt-1 text-sm text-muted">Una declaración describe cómo se usó la IA en una obra y puede verificarla cualquiera.</p>
                <ButtonLink href="/app/works" className="mt-4">
                  Elegir una obra para declarar
                </ButtonLink>
              </>
            )}
          </div>
        ) : (
          <div className="relative overflow-x-auto">
            <table className="w-full min-w-[640px] text-sm">
              <caption className="sr-only">Declaraciones, ordenadas por última modificación</caption>
              <thead className="border-b border-line bg-paper text-left text-xs text-muted">
                <tr>
                  <th scope="col" className="px-4 py-3 font-medium">Obra</th>
                  <th scope="col" className="px-4 py-3 font-medium">Versión</th>
                  <th scope="col" className="px-4 py-3 font-medium">Identificador</th>
                  <th scope="col" className="px-4 py-3 font-medium" aria-sort="descending">Fecha</th>
                  <th scope="col" className="px-4 py-3 font-medium">Estado</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {versions.map((v) => (
                  <tr key={v.id} className="hover:bg-petrol-50/50">
                    <td className="px-4 py-3">
                      <Link href={v.status === "DRAFT" ? `/app/declarations/${v.id}/edit/${v.lastStep ?? "work"}` : `/app/declarations/${v.id}`} className="font-medium text-petrol-700 underline-offset-4 hover:underline">
                        {v.declaration.work.title}
                      </Link>
                    </td>
                    <td className="px-4 py-3">{v.versionNumber}</td>
                    <td className="px-4 py-3 font-mono">{v.publicId ?? "—"}</td>
                    <td className="px-4 py-3 text-muted">{formatDate(v.issuedAt ?? v.updatedAt)}</td>
                    <td className="px-4 py-3">
                      <span className="flex gap-2">
                        <DocStatusBadge status={v.status} />
                        {v.status !== "DRAFT" && <VisibilityBadge visibility={v.visibility} />}
                      </span>
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
