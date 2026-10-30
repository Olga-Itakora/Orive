import { cn } from "@/lib/utils";

/** Marca ORIVA: un trazo circular abierto (el proceso) con un punto que lo hace visible. */
export function LogoMark({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 32 32" aria-hidden className={cn("h-7 w-7", className)}>
      <path d="M26.5 10.5A12 12 0 1 0 28 16" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
      <circle cx="26.9" cy="10.9" r="3.1" fill="var(--color-accent)" />
      <circle cx="16" cy="16" r="3.2" fill="currentColor" />
    </svg>
  );
}

export function Logo({ className, withDescriptor = false }: { className?: string; withDescriptor?: boolean }) {
  return (
    <span className={cn("inline-flex items-center gap-3 text-petrol-800", className)}>
      <LogoMark />
      <span className="leading-none">
        <span className="block text-lg font-semibold tracking-[0.22em]">ORIVA</span>
        {withDescriptor && <span className="mt-1 block text-xs font-medium tracking-[0.12em] text-muted uppercase">Creative AI Transparency</span>}
      </span>
    </span>
  );
}
