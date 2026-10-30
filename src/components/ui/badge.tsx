import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

const badge = cva("inline-flex items-center gap-1 rounded-[var(--radius-sm)] border px-2 py-1 text-xs font-medium", {
  variants: {
    tone: {
      neutral: "border-line bg-paper text-muted",
      petrol: "border-petrol-200 bg-petrol-50 text-petrol-800",
      ok: "border-transparent bg-ok-soft text-ok",
      warn: "border-transparent bg-warn-soft text-warn",
      danger: "border-transparent bg-danger-soft text-danger",
    },
  },
  defaultVariants: { tone: "neutral" },
});

export function Badge({ className, tone, ...props }: React.HTMLAttributes<HTMLSpanElement> & VariantProps<typeof badge>) {
  return <span className={cn(badge({ tone }), className)} {...props} />;
}
