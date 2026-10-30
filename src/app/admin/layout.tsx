import Link from "next/link";
import { Logo } from "@/components/site/logo";
import { requireAdmin } from "@/lib/auth/session";

export const metadata = { title: "Administración", robots: { index: false } };

const NAV = [
  ["/admin", "Resumen"],
  ["/admin/users", "Usuarios"],
  ["/admin/taxonomy", "Taxonomía"],
  ["/admin/questionnaires", "Cuestionarios"],
  ["/admin/categories", "Modalidades"],
  ["/admin/legal-texts", "Textos legales"],
  ["/admin/legal-references", "Referencias normativas"],
  ["/admin/incidents", "Incidencias"],
  ["/admin/audit", "Auditoría"],
];

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const admin = await requireAdmin();
  return (
    <div className="min-h-dvh">
      <header className="border-b border-line bg-petrol-900 text-white">
        <div className="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6">
          <Link href="/admin" className="flex min-h-11 items-center gap-3">
            <Logo className="text-white" />
            <span className="rounded bg-white/10 px-2 py-1 text-xs">Administración</span>
          </Link>
          <span className="flex items-center gap-4 text-sm text-petrol-100">
            <span className="hidden sm:inline">{admin.email}</span>
            <Link href="/app" className="inline-flex min-h-11 items-center underline underline-offset-4">
              Salir del panel
            </Link>
          </span>
        </div>
      </header>
      <div className="mx-auto grid grid-cols-1 max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[13rem_1fr]">
        <nav aria-label="Administración" className="min-w-0">
          <ul className="flex gap-1 relative overflow-x-auto lg:flex-col">
            {NAV.map(([href, label]) => (
              <li key={href}>
                <Link href={href} className="flex min-h-11 items-center rounded-md px-3 text-sm whitespace-nowrap text-muted hover:bg-petrol-50 hover:text-petrol-800">
                  {label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>
        <main id="main" className="min-w-0">
          {children}
        </main>
      </div>
    </div>
  );
}
