import Link from "next/link";
import { Check } from "lucide-react";
import { cn } from "@/lib/utils";
import { applicableSteps, STEPS, type StepKey } from "./steps";

export function WizardShell({
  versionId,
  current,
  effectiveUsage,
  workTitle,
  versionNumber,
  children,
  title,
  intro,
}: {
  versionId: string;
  current: StepKey;
  effectiveUsage: string | null;
  workTitle: string;
  versionNumber: number;
  title: string;
  intro?: React.ReactNode;
  children: React.ReactNode;
}) {
  const steps = applicableSteps(effectiveUsage);
  const idx = steps.indexOf(current);
  const pct = Math.round(((idx + 1) / steps.length) * 100);
  return (
    <div>
      <div className="mb-6 flex flex-wrap items-baseline justify-between gap-2">
        <p className="text-sm text-muted">
          Declaración sobre <span className="font-medium text-ink">{workTitle}</span> · versión {versionNumber} · <span className="text-warn">borrador</span>
        </p>
        <Link href={`/app/declarations/${versionId}`} className="inline-flex min-h-11 items-center text-sm text-petrol-700 underline underline-offset-4">
          Resumen del borrador
        </Link>
      </div>
      <nav aria-label="Pasos de la declaración" className="mb-8">
        <div className="mb-3 h-1.5 overflow-hidden rounded-full bg-line" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100} aria-label={`Paso ${idx + 1} de ${steps.length}`}>
          <div className="h-full rounded-full bg-accent" style={{ width: `${pct}%` }} />
        </div>
        <ol className="flex flex-wrap gap-2">
          {STEPS.filter((s) => steps.includes(s.key)).map((s, i) => {
            const state = i < idx ? "done" : i === idx ? "current" : "todo";
            return (
              <li key={s.key}>
                <Link
                  href={`/app/declarations/${versionId}/edit/${s.key}`}
                  aria-current={state === "current" ? "step" : undefined}
                  className={cn(
                    "inline-flex min-h-11 items-center gap-2 rounded-[var(--radius-md)] border px-3 text-sm",
                    state === "current" && "border-petrol-800 bg-petrol-800 text-white",
                    state === "done" && "border-petrol-200 bg-petrol-50 text-petrol-800",
                    state === "todo" && "border-line bg-surface text-muted hover:border-line-strong",
                  )}
                >
                  {state === "done" ? <Check className="h-3 w-3" aria-hidden /> : <span className="font-mono">{i + 1}</span>}
                  {s.label}
                </Link>
              </li>
            );
          })}
        </ol>
      </nav>
      <h1 className="text-2xl font-semibold text-petrol-900">{title}</h1>
      {intro && <div className="mt-2 max-w-[70ch] text-sm text-muted">{intro}</div>}
      <div className="mt-6">{children}</div>
    </div>
  );
}
