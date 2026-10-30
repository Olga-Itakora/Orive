import * as React from "react";
import { AlertTriangle, CheckCircle2, Info, XCircle } from "lucide-react";
import { cn } from "@/lib/utils";

const tones = {
  info: { cls: "border-petrol-200 bg-petrol-50 text-petrol-900", Icon: Info },
  warn: { cls: "border-[#f0dcae] bg-warn-soft text-[#5c3d00]", Icon: AlertTriangle },
  danger: { cls: "border-[#f1c4c1] bg-danger-soft text-danger", Icon: XCircle },
  ok: { cls: "border-[#bfe0cc] bg-ok-soft text-ok", Icon: CheckCircle2 },
};

export function Alert({ tone = "info", title, children, className, role }: { tone?: keyof typeof tones; title?: React.ReactNode; children?: React.ReactNode; className?: string; role?: string }) {
  const { cls, Icon } = tones[tone];
  return (
    <div role={role ?? (tone === "danger" ? "alert" : undefined)} className={cn("flex gap-3 rounded-[var(--radius-md)] border p-4 text-sm", cls, className)}>
      <Icon aria-hidden className="mt-1 h-4 w-4 shrink-0" />
      <div className="space-y-1 leading-relaxed">
        {title && <p className="font-semibold">{title}</p>}
        {children && <div>{children}</div>}
      </div>
    </div>
  );
}
