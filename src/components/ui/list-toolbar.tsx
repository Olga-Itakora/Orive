import Link from "next/link";
import { Search } from "lucide-react";
import { SlashFocus } from "./slash-focus";

/** Búsqueda reconocible a simple vista (icono + etiqueta visible). «/» la enfoca. */
export function ListSearch({ q, label = "Buscar", hint, action, extra }: { q?: string; label?: string; hint?: string; action?: string; extra?: Record<string, string> }) {
  return (
    <form role="search" action={action} className="flex flex-wrap items-end gap-3">
      <div className="min-w-0 flex-1 basis-64">
        <label htmlFor="list-q" className="mb-2 flex items-center gap-2 text-sm font-medium text-ink">
          <Search className="h-4 w-4 text-muted" aria-hidden /> {label}
          <kbd className="ml-1 rounded border border-line bg-paper px-1 font-mono text-xs text-muted" aria-hidden>
            /
          </kbd>
        </label>
        <input id="list-q" name="q" defaultValue={q} maxLength={100} aria-describedby={hint ? "list-q-hint" : undefined} className="h-11 w-full rounded-[var(--radius-md)] border border-line-strong bg-surface px-3 text-base sm:text-sm" />
        {hint && (
          <p id="list-q-hint" className="mt-1 text-xs text-muted">
            {hint}
          </p>
        )}
      </div>
      {extra && Object.entries(extra).map(([k, v]) => <input key={k} type="hidden" name={k} value={v} />)}
      <button className="inline-flex h-11 items-center rounded-[var(--radius-md)] bg-petrol-800 px-4 text-sm font-medium text-white hover:bg-petrol-700">Buscar</button>
      <SlashFocus targetId="list-q" />
    </form>
  );
}

/** Paginación: siempre visible; los controles desactivados conservan su etiqueta. */
export function Pagination({ page, pages, href }: { page: number; pages: number; href: (p: number) => string }) {
  const btn = "inline-flex h-11 items-center rounded-[var(--radius-md)] border px-4 text-sm";
  return (
    <nav aria-label="Paginación" className="flex items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm">
      {page > 1 ? (
        <Link href={href(page - 1)} className={`${btn} border-line-strong bg-surface text-ink hover:bg-petrol-50`}>
          Página anterior
        </Link>
      ) : (
        <span aria-disabled className={`${btn} border-line text-muted`}>
          Página anterior
        </span>
      )}
      <span className="text-muted">
        Página {page} de {Math.max(1, pages)}
      </span>
      {page < pages ? (
        <Link href={href(page + 1)} className={`${btn} border-line-strong bg-surface text-ink hover:bg-petrol-50`}>
          Página siguiente
        </Link>
      ) : (
        <span aria-disabled className={`${btn} border-line text-muted`}>
          Página siguiente
        </span>
      )}
    </nav>
  );
}

/** Marco L1 de una tabla (borde + filetes). Los estados vacíos van dentro. */
export function TableFrame({ children }: { children: React.ReactNode }) {
  return <div className="overflow-hidden rounded-[var(--radius-lg)] border border-line bg-surface">{children}</div>;
}
