import Link from "next/link";
import { FileText, FolderOpen, LayoutDashboard, LogOut, Shield, User } from "lucide-react";
import { logoutAction } from "@/app/actions/auth";
import { Logo } from "@/components/site/logo";
import { requireUser } from "@/lib/auth/session";

export const metadata = { robots: { index: false } };

export default async function AppLayout({ children }: { children: React.ReactNode }) {
  const user = await requireUser();
  const nav = [
    { href: "/app", label: "Inicio", icon: LayoutDashboard },
    { href: "/app/works", label: "Mis obras", icon: FolderOpen },
    { href: "/app/declarations", label: "Mis declaraciones", icon: FileText },
    { href: "/app/profile", label: "Perfil y datos", icon: User },
    ...(user.role === "ADMIN" ? [{ href: "/admin", label: "Administración", icon: Shield }] : []),
  ];
  return (
    <div className="min-h-dvh">
      <a href="#main" className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded focus:bg-surface focus:px-3 focus:py-2">
        Saltar al contenido
      </a>
      <header className="border-b border-line bg-surface">
        <div className="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
          <Link href="/" aria-label="ORIVA — inicio" className="inline-flex min-h-11 shrink-0 items-center">
            <Logo />
          </Link>
          <div className="flex min-w-0 items-center gap-3 text-sm">
            <span className="hidden max-w-[16rem] truncate text-muted sm:inline" title={user.email}>
              {user.displayName ?? user.email}
            </span>
            <form action={logoutAction}>
              <button className="inline-flex min-h-11 items-center gap-2 rounded-md px-3 whitespace-nowrap text-muted hover:bg-petrol-50 hover:text-petrol-800">
                <LogOut className="h-4 w-4" aria-hidden /> <span>Cerrar sesión</span>
              </button>
            </form>
          </div>
        </div>
        <nav aria-label="Área de autor" className="mx-auto max-w-6xl relative overflow-x-auto px-2 sm:px-4">
          <ul className="flex gap-1">
            {nav.map(({ href, label, icon: Icon }) => (
              <li key={href}>
                <Link href={href} className="inline-flex min-h-11 items-center gap-2 border-b-2 border-transparent px-3 text-sm whitespace-nowrap text-muted hover:border-line-strong hover:text-petrol-800">
                  <Icon className="h-4 w-4" aria-hidden /> {label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </header>
      <main id="main" className="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        {children}
      </main>
    </div>
  );
}
