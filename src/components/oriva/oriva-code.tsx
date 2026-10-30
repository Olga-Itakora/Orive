"use client";
import { useId, useState } from "react";
import Link from "next/link";
import { DIMENSIONS, getValue, type DimensionKey } from "@/lib/oriva-code/taxonomy";
import { cn } from "@/lib/utils";
import { CopyButton } from "./copy-button";

export interface OrivaCodeUse {
  ref: string;
  title?: string | null;
  code: string | null;
  notation: string;
  status: string;
}

export interface OrivaCodeProps {
  /** Código compacto (A#B#C#D#) si existe. */
  code: string | null;
  /** Perfil compuesto "A.. | B.. | C.. | D.." si no hay código compacto. */
  compositeNotation?: string | null;
  composite?: { A: string[]; B: string[]; C: string[]; D: string } | null;
  status: string;
  schemeVersion: string;
  uses?: OrivaCodeUse[];
  publicId?: string | null;
  verifyUrl?: string | null;
  scopeText?: string | null;
  variant?: "full" | "compact";
  className?: string;
}

const STATUS_TEXT: Record<string, string> = {
  COMPLETE: "Clasificación completa",
  PARTIAL: "Clasificación parcial: algún uso con información insuficiente",
  UNDETERMINED: "El declarante no puede determinar si se utilizó IA",
  PENDING: "Clasificación pendiente: faltan respuestas esenciales",
  INSUFFICIENT: "Información insuficiente",
};

function segmentsOf(code: string) {
  return (code.match(/[ABCD]\d/g) ?? []).map((c) => ({ code: c, dim: c[0] as DimensionKey }));
}

/** Código compacto con segmentos seleccionables y explicación accesible. */
function InteractiveCode({ code, dark }: { code: string; dark?: boolean }) {
  const segs = segmentsOf(code);
  const [active, setActive] = useState<string>(segs[0]?.code ?? "");
  const id = useId();
  const value = getValue(active);
  const dim = active ? DIMENSIONS[active[0] as DimensionKey] : null;
  return (
    <div>
      <div role="tablist" aria-label="Segmentos del código ORIVA" className="flex flex-wrap items-center gap-2">
        <span className={cn("mr-1 text-sm font-semibold tracking-[0.2em]", dark ? "text-petrol-100" : "text-petrol-700")}>ORIVA</span>
        {segs.map((s) => (
          <button
            key={s.code}
            role="tab"
            type="button"
            id={`${id}-${s.code}`}
            aria-selected={active === s.code}
            aria-controls={`${id}-panel`}
            onClick={() => setActive(s.code)}
            onKeyDown={(e) => {
              const i = segs.findIndex((x) => x.code === active);
              if (e.key === "ArrowRight") setActive(segs[(i + 1) % segs.length].code);
              if (e.key === "ArrowLeft") setActive(segs[(i - 1 + segs.length) % segs.length].code);
            }}
            className={cn(
              "min-h-11 rounded-md px-3 py-1 font-mono text-2xl font-medium sm:text-3xl",
              dark
                ? active === s.code
                  ? "bg-accent text-white"
                  : "text-white hover:bg-white/10"
                : active === s.code
                  ? "bg-petrol-800 text-white"
                  : "text-petrol-800 hover:bg-petrol-50",
            )}
          >
            {s.code}
          </button>
        ))}
      </div>
      {value && dim && (
        <div id={`${id}-panel`} role="tabpanel" aria-labelledby={`${id}-${active}`} className={cn("mt-3 rounded-md p-3 text-sm", dark ? "bg-white/5 text-petrol-50" : "bg-petrol-50 text-petrol-900")}>
          <p className={cn("text-xs font-semibold tracking-wide uppercase", dark ? "text-petrol-200" : "text-petrol-600")}>
            Dimensión {dim.key} · {dim.name.es}
          </p>
          <p className="mt-1 font-semibold">
            {value.code} — {value.label.es}
          </p>
          <p className="mt-1 leading-relaxed opacity-90">{value.description.es}</p>
          <p className="mt-2 text-xs leading-relaxed opacity-75">{dim.notMeaning.es}</p>
        </div>
      )}
    </div>
  );
}

export function OrivaCode(props: OrivaCodeProps) {
  const { code, compositeNotation, composite, status, schemeVersion, uses = [], publicId, verifyUrl, scopeText, variant = "full", className } = props;

  if (variant === "compact") {
    return (
      <div className={cn("inline-flex flex-wrap items-center gap-x-3 gap-y-1 rounded-[var(--radius-md)] border border-petrol-200 bg-surface px-3 py-2", className)}>
        <span className="text-xs font-semibold tracking-[0.2em] text-petrol-800">ORIVA</span>
        <span className="font-mono text-sm text-petrol-800">{code ?? compositeNotation ?? STATUS_TEXT[status] ?? status}</span>
        <span className="text-xs text-subtle">v{schemeVersion}</span>
        {publicId && (
          <Link href={`/verify/${publicId}`} className="inline-flex min-h-11 items-center text-xs text-petrol-700 underline underline-offset-2">
            {publicId}
          </Link>
        )}
      </div>
    );
  }

  return (
    <section aria-label="Código ORIVA" className={cn("overflow-hidden rounded-[var(--radius-lg)] bg-petrol-800 text-white", className)}>
      <div className="space-y-4 p-5 sm:p-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <p className="text-xs font-medium tracking-wider text-petrol-200 uppercase">ORIVA Code v{schemeVersion}</p>
          <div className="flex gap-2">
            {(code || compositeNotation) && <CopyButton tone="dark" value={code ? `ORIVA ${code}` : `ORIVA ${compositeNotation}`} label="Copiar código" />}
          </div>
        </div>

        {code ? (
          <InteractiveCode code={code} dark />
        ) : compositeNotation ? (
          <div>
            <p className="font-mono text-xl break-words sm:text-2xl">{compositeNotation}</p>
            <p className="mt-2 text-sm leading-relaxed text-petrol-100">
              Perfil ORIVA compuesto: síntesis de los valores presentes en la obra. No es una secuencia de parejas B–C; las relaciones originales se conservan en el desglose por usos.
            </p>
          </div>
        ) : (
          <p className="text-lg font-medium">{STATUS_TEXT[status] ?? status}</p>
        )}

        {composite && !code && (
          <dl className="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
            {(["A", "B", "C"] as const).map((k) => (
              <div key={k} className="rounded-md bg-white/5 p-3">
                <dt className="text-xs text-petrol-200">{DIMENSIONS[k].name.es}</dt>
                <dd className="mt-1 space-y-1">
                  {composite[k].length === 0 ? "—" : composite[k].map((c) => <span key={c} className="block"><span className="font-mono">{c}</span> {getValue(c)?.label.es}</span>)}
                </dd>
              </div>
            ))}
            <div className="rounded-md bg-white/5 p-3">
              <dt className="text-xs text-petrol-200">{DIMENSIONS.D.name.es}</dt>
              <dd className="mt-1"><span className="font-mono">{composite.D}</span> {getValue(composite.D)?.label.es}</dd>
            </div>
          </dl>
        )}

        {status !== "COMPLETE" && (code || compositeNotation) && <p className="text-sm text-petrol-100">{STATUS_TEXT[status]}</p>}
        {scopeText && <p className="border-t border-white/10 pt-3 text-sm leading-relaxed text-petrol-100">{scopeText}</p>}
      </div>

      {uses.length > 0 && (
        <div className="border-t border-white/10 bg-petrol-900/50 px-5 py-4 sm:px-6">
          <h3 className="mb-2 text-xs font-medium tracking-wider text-petrol-200 uppercase">Desglose por usos</h3>
          <ul className="space-y-2 text-sm">
            {uses.map((u) => (
              <li key={u.ref} className="flex flex-wrap items-baseline gap-x-3">
                <span className="font-mono text-petrol-200">{u.ref}</span>
                <span className="font-mono">{u.code ?? u.notation}</span>
                {u.title && <span className="text-petrol-100">{u.title}</span>}
              </li>
            ))}
          </ul>
        </div>
      )}

      <div className="flex flex-wrap items-center justify-between gap-2 border-t border-white/10 px-5 py-3 text-xs text-petrol-200 sm:px-6">
        <span>Clasificación descriptiva. No es un porcentaje de autoría ni una certificación.</span>
        <span className="flex gap-3">
          <Link href="/code" className="inline-flex min-h-11 items-center underline underline-offset-2 hover:text-white">
            Qué significa
          </Link>
          {verifyUrl && (
            <a href={verifyUrl} className="inline-flex min-h-11 items-center underline underline-offset-2 hover:text-white">
              Ver declaración
            </a>
          )}
        </span>
      </div>
    </section>
  );
}

/** Ejemplo interactivo para páginas informativas. */
export function CodeExplorer({ code = "A5B3C4D2" }: { code?: string }) {
  return (
    <div className="rounded-[var(--radius-lg)] border border-line bg-surface p-5 sm:p-6">
      <InteractiveCode code={code} />
    </div>
  );
}
