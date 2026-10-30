"use client";
import { useState } from "react";
import { Check, Copy } from "lucide-react";
import { cn } from "@/lib/utils";

export function CopyButton({ value, label = "Copiar", className, tone = "light" }: { value: string; label?: string; className?: string; tone?: "light" | "dark" }) {
  const [done, setDone] = useState(false);
  return (
    <button
      type="button"
      onClick={async () => {
        try {
          await navigator.clipboard.writeText(value);
          setDone(true);
          setTimeout(() => setDone(false), 1800);
        } catch {
          /* el portapapeles puede no estar disponible */
        }
      }}
      className={cn(
        "inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm font-medium",
        tone === "dark" ? "bg-white/10 text-white hover:bg-white/20" : "border border-line-strong bg-surface text-ink hover:bg-petrol-50",
        className,
      )}
    >
      {done ? <Check className="h-3.5 w-3.5" aria-hidden /> : <Copy className="h-3.5 w-3.5" aria-hidden />}
      <span aria-live="polite">{done ? "Copiado" : label}</span>
    </button>
  );
}
