import * as React from "react";
import Link from "next/link";
import { cn } from "@/lib/utils";

/**
 * Botón de icono cuadrado (44×44) con tooltip real visible al pasar el ratón y
 * al recibir el foco. El nombre accesible (aria-label) coincide con el tooltip.
 */
function Tip({ label, id }: { label: string; id: string }) {
  return (
    <span
      id={id}
      role="tooltip"
      className="pointer-events-none absolute right-0 bottom-full z-20 mb-2 rounded-[var(--radius-sm)] bg-ink px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition-opacity duration-150 ease-out group-focus-within:opacity-100 group-hover:opacity-100"
    >
      {label}
    </span>
  );
}

const base =
  "inline-flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] text-petrol-800 hover:bg-petrol-50 disabled:opacity-50";

export const IconButton = React.forwardRef<HTMLButtonElement, React.ButtonHTMLAttributes<HTMLButtonElement> & { label: string; tone?: "default" | "danger" }>(
  ({ label, tone = "default", className, children, ...props }, ref) => {
    const id = React.useId();
    return (
      <span className="group relative inline-flex">
        <button ref={ref} type="button" aria-label={label} aria-describedby={id} className={cn(base, tone === "danger" && "text-danger hover:bg-danger-soft", className)} {...props}>
          {children}
        </button>
        <Tip label={label} id={id} />
      </span>
    );
  },
);
IconButton.displayName = "IconButton";

export function IconLink({ href, label, children, className }: { href: string; label: string; children: React.ReactNode; className?: string }) {
  const id = React.useId();
  return (
    <span className="group relative inline-flex">
      <Link href={href} aria-label={label} aria-describedby={id} className={cn(base, className)}>
        {children}
      </Link>
      <Tip label={label} id={id} />
    </span>
  );
}
