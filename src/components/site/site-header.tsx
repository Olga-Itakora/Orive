import Link from "next/link";
import { getCurrentUser } from "@/lib/auth/session";
import { getDictionary } from "@/i18n/server";
import { Logo } from "./logo";
import { ButtonLink } from "@/components/ui/button";
import { MobileNav } from "./mobile-nav";

export async function SiteHeader() {
  const [user, { t }] = await Promise.all([getCurrentUser(), getDictionary()]);
  const links = [
    { href: "/how-it-works", label: t.nav.how },
    { href: "/code", label: t.nav.code },
    { href: "/declarations", label: t.nav.declarations },
    { href: "/verify", label: t.nav.verify },
    { href: "/legal-framework", label: t.nav.legal },
  ];
  return (
    <header className="sticky top-0 z-30 border-b border-line bg-paper">
      <a href="#main" className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded focus:bg-surface focus:px-3 focus:py-2">
        {t.nav.skip}
      </a>
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
        <Link href="/" aria-label="ORIVA — inicio" className="inline-flex min-h-11 items-center">
          <Logo />
        </Link>
        <nav aria-label="Principal" className="hidden items-center gap-1 lg:flex">
          {links.map((l) => (
            <Link key={l.href} href={l.href} className="inline-flex min-h-11 items-center rounded-md px-3 text-sm text-muted hover:bg-petrol-50 hover:text-petrol-800">
              {l.label}
            </Link>
          ))}
        </nav>
        <div className="hidden items-center gap-2 lg:flex">
          {user ? (
            <ButtonLink href="/app" size="sm">
              {t.nav.dashboard}
            </ButtonLink>
          ) : (
            <>
              <ButtonLink href="/login" variant="ghost" size="sm">
                {t.nav.login}
              </ButtonLink>
              <ButtonLink href="/register" size="sm">
                {t.nav.register}
              </ButtonLink>
            </>
          )}
        </div>
        <MobileNav links={links} authed={!!user} labels={{ login: t.nav.login, register: t.nav.register, dashboard: t.nav.dashboard }} />
      </div>
    </header>
  );
}
