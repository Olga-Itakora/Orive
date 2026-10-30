import Link from "next/link";
import { getDictionary } from "@/i18n/server";
import { Logo } from "./logo";
import { LocaleSwitcher } from "./locale-switcher";

export async function SiteFooter() {
  const { t, locale } = await getDictionary();
  const cols = [
    { title: "ORIVA", links: [["/about", t.footer.about], ["/how-it-works", t.nav.how], ["/code", t.nav.code], ["/faq", t.nav.faq]] },
    { title: locale === "es" ? "Consulta" : "Lookup", links: [["/declarations", t.nav.declarations], ["/verify", t.nav.verify], ["/legal-framework", t.nav.legal]] },
    { title: "Legal", links: [["/privacy", t.footer.privacy], ["/terms", t.footer.terms], ["/legal-notice", t.footer.legalNotice], ["/contact", t.footer.contact]] },
  ];
  return (
    <footer className="mt-24 border-t border-line bg-surface">
      <div className="mx-auto grid grid-cols-1 max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div className="space-y-3">
          <Logo withDescriptor />
          <p className="max-w-xs text-sm text-muted">{t.brand.tagline}</p>
          <LocaleSwitcher current={locale} label={t.common.language} />
        </div>
        {cols.map((c) => (
          <nav key={c.title} aria-label={c.title}>
            <h2 className="mb-3 text-xs font-semibold tracking-wider text-subtle uppercase">{c.title}</h2>
            <ul>
              {c.links.map(([href, label]) => (
                <li key={href}>
                  <Link href={href} className="inline-flex min-h-11 items-center text-sm text-muted hover:text-petrol-800">
                    {label}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>
        ))}
      </div>
      <div className="border-t border-line">
        <p className="mx-auto max-w-6xl px-4 py-5 text-xs leading-relaxed text-subtle sm:px-6">
          © {new Date().getUTCFullYear()} ORIVA · {t.footer.disclaimer}
        </p>
      </div>
    </footer>
  );
}
