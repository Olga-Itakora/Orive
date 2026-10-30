import Link from "next/link";
import { ChevronRight } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { ButtonLink } from "@/components/ui/button";
import { ListSearch, Pagination, TableFrame } from "@/components/ui/list-toolbar";
import { listPublicDeclarations } from "@/lib/declarations/public";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Declaraciones públicas" };

const PAGE = 20;

/** Patrón: Lista / Índice. */
export default async function PublicDeclarationsPage({ searchParams }: { searchParams: Promise<{ q?: string; page?: string }> }) {
  const sp = await searchParams;
  const q = sp.q?.trim().slice(0, 100) || undefined;
  const page = Math.max(1, Number(sp.page) || 1);
  const { total, items } = await listPublicDeclarations({ q, take: PAGE, skip: (page - 1) * PAGE });
  const pages = Math.ceil(total / PAGE);
  const href = (p: number) => `/declarations?${new URLSearchParams({ ...(q ? { q } : {}), page: String(p) })}`;
  return (
    <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      <h1 className="text-3xl font-semibold text-petrol-900">Declaraciones públicas</h1>
      <p className="mt-3 max-w-[70ch] text-muted">Solo se muestran las declaraciones que sus autores han decidido publicar, y únicamente los datos que han autorizado.</p>
      <div className="mt-8 mb-4">
        <ListSearch q={q} label="Buscar declaraciones" hint="Por título, autor, identificador (ORV-…) o código (p. ej. A4B2C1D2)." />
      </div>
      <p className="mb-3 text-sm text-muted" aria-live="polite">
        {total} declaraci{total === 1 ? "ón" : "ones"}
        {q && ` para «${q}»`}
      </p>
      <TableFrame>
        {items.length === 0 ? (
          <div className="p-10 text-center">
            {q ? (
              <>
                <h2 className="font-medium">Ninguna declaración pública coincide con «{q}»</h2>
                <p className="mt-1 text-sm text-muted">Comprueba el identificador o prueba con parte del título.</p>
                <ButtonLink href="/declarations" variant="secondary" className="mt-4">
                  Borrar la búsqueda
                </ButtonLink>
              </>
            ) : (
              <>
                <h2 className="font-medium">Todavía no hay declaraciones públicas</h2>
                <p className="mt-1 text-sm text-muted">Cuando las autoras y los autores publiquen sus declaraciones, podrás consultarlas aquí.</p>
                <ButtonLink href="/app/works/new" className="mt-4">
                  Crear una declaración
                </ButtonLink>
              </>
            )}
          </div>
        ) : (
          <div className="relative overflow-x-auto">
            <table className="w-full min-w-[720px] text-sm">
              <caption className="sr-only">Declaraciones públicas, de la más reciente a la más antigua</caption>
              <thead className="border-b border-line bg-paper text-left text-xs text-muted">
                <tr>
                  <th scope="col" className="px-4 py-3 font-medium">Obra</th>
                  <th scope="col" className="px-4 py-3 font-medium">Declarante</th>
                  <th scope="col" className="px-4 py-3 font-medium">Código o perfil ORIVA</th>
                  <th scope="col" className="px-4 py-3 font-medium" aria-sort="descending">Emitida</th>
                  <th scope="col" className="px-4 py-3 font-medium">Estado</th>
                  <th scope="col" className="w-10 px-2 py-3"><span className="sr-only">Abrir</span></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {items.map((i) => (
                  <tr key={i.publicId} className="hover:bg-petrol-50/50">
                    <td className="px-4 py-3">
                      <Link href={`/verify/${i.publicId}`} className="font-medium text-petrol-700 underline-offset-4 hover:underline">
                        {i.title ?? "Obra no publicada"}
                      </Link>
                      <span className="block text-muted">{i.category ?? "—"} · <span className="font-mono">{i.publicId}</span></span>
                    </td>
                    <td className="px-4 py-3">{i.declarant ?? <span className="text-muted">No publicado</span>}</td>
                    <td className="px-4 py-3 font-mono text-petrol-800">{i.code ?? i.compositeNotation ?? "—"}</td>
                    <td className="px-4 py-3 text-muted">{formatDate(i.issuedAt)}</td>
                    <td className="px-4 py-3">{i.status === "ISSUED" ? "Vigente" : <Badge tone={i.status === "REVOKED" ? "danger" : "neutral"}>{i.status === "REVOKED" ? "Revocada" : "Sustituida"}</Badge>}</td>
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
    </div>
  );
}
