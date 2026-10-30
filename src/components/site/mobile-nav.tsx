"use client";
import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu, X } from "lucide-react";

export function MobileNav({ links, authed, labels }: { links: { href: string; label: string }[]; authed: boolean; labels: { login: string; register: string; dashboard: string } }) {
  const [open, setOpen] = useState(false);
  const pathname = usePathname();
  const btn = useRef<HTMLButtonElement>(null);
  useEffect(() => setOpen(false), [pathname]);
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        setOpen(false);
        btn.current?.focus();
      }
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [open]);
  return (
    <div className="lg:hidden">
      <button ref={btn} type="button" aria-expanded={open} aria-controls="mobile-menu" onClick={() => setOpen((o) => !o)} className="inline-flex h-11 items-center justify-center gap-2 rounded-md px-3 text-sm font-medium text-petrol-800 hover:bg-petrol-50">
        {open ? <X className="h-5 w-5" aria-hidden /> : <Menu className="h-5 w-5" aria-hidden />}
        <span>Menú</span>
      </button>
      {open && (
        <nav id="mobile-menu" aria-label="Principal (móvil)" className="absolute inset-x-0 top-16 border-b border-line bg-paper px-4 pb-4">
          <ul className="space-y-1 py-2">
            {links.map((l) => (
              <li key={l.href}>
                <Link href={l.href} className="flex min-h-11 items-center rounded-md px-3 text-sm text-ink hover:bg-petrol-50">
                  {l.label}
                </Link>
              </li>
            ))}
          </ul>
          <div className="flex gap-2 border-t border-line pt-3">
            {authed ? (
              <Link href="/app" className="flex min-h-11 flex-1 items-center justify-center rounded-md bg-petrol-800 px-3 text-center text-sm font-medium text-white">
                {labels.dashboard}
              </Link>
            ) : (
              <>
                <Link href="/login" className="flex min-h-11 flex-1 items-center justify-center rounded-md border border-line-strong px-3 text-sm">
                  {labels.login}
                </Link>
                <Link href="/register" className="flex min-h-11 flex-1 items-center justify-center rounded-md bg-petrol-800 px-3 text-center text-sm font-medium text-white">
                  {labels.register}
                </Link>
              </>
            )}
          </div>
        </nav>
      )}
    </div>
  );
}
