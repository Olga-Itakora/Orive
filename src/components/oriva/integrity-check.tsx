"use client";
import { useState } from "react";
import { CheckCircle2, XCircle } from "lucide-react";
import { verifyDisclosed, type DisclosedSnapshot } from "@/lib/integrity/disclosure";
import { Button } from "@/components/ui/button";

/** Recalcula la huella en el navegador del visitante a partir de la vista divulgada (WebCrypto). */
export function BrowserIntegrityCheck({ publicId, expectedHash }: { publicId: string; expectedHash: string }) {
  const [state, setState] = useState<{ ok: boolean; computed: string } | null>(null);
  const [busy, setBusy] = useState(false);
  return (
    <div className="space-y-3">
      <Button
        type="button"
        variant="secondary"
        size="sm"
        disabled={busy}
        onClick={async () => {
          setBusy(true);
          try {
            const res = await fetch(`/api/v1/declarations/${publicId}`, { cache: "no-store" });
            const json = (await res.json()) as { view: DisclosedSnapshot };
            const r = await verifyDisclosed(json.view, expectedHash);
            setState({ ok: r.ok, computed: r.computedHash });
          } catch {
            setState({ ok: false, computed: "" });
          } finally {
            setBusy(false);
          }
        }}
      >
        {busy ? "Calculando…" : "Recalcular la huella en mi navegador"}
      </Button>
      {state && (
        <div role="status" className={`flex gap-2 rounded-md p-3 text-sm ${state.ok ? "bg-ok-soft text-ok" : "bg-danger-soft text-danger"}`}>
          {state.ok ? <CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden /> : <XCircle className="h-4 w-4 shrink-0" aria-hidden />}
          <div>
            <p className="font-medium">{state.ok ? "La huella recalculada coincide con la registrada." : "La huella recalculada no coincide."}</p>
            {state.computed && <p className="mt-1 font-mono text-xs break-all">{state.computed}</p>}
          </div>
        </div>
      )}
    </div>
  );
}

const KIND_TEXT: Record<string, string> = {
  "pdf-full": "El fichero es idéntico al PDF completo registrado para esta declaración.",
  "pdf-summary": "El fichero es idéntico al Resumen ORIVA registrado para esta declaración.",
  json: "El JSON exportado reproduce exactamente la huella registrada.",
};
const STATUS_TEXT: Record<string, string> = { ISSUED: "vigente", SUPERSEDED: "sustituida por una versión posterior", REVOKED: "revocada" };

/** Comprueba un fichero que posee el verificador (PDF o JSON) contra el registro. */
export function FileCheck({ publicId: initialId }: { publicId?: string }) {
  const [id, setId] = useState(initialId ?? "");
  const [result, setResult] = useState<{ match: boolean; kind: string; status?: string } | null>(null);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  return (
    <form
      className="space-y-3"
      onSubmit={async (e) => {
        e.preventDefault();
        setErr(null);
        setResult(null);
        const input = (e.currentTarget.elements.namedItem("file") as HTMLInputElement).files?.[0];
        if (!input) return setErr("Selecciona un fichero.");
        if (input.size > 15 * 1024 * 1024) return setErr("El fichero supera 15 MB.");
        setBusy(true);
        try {
          const res = await fetch(`/api/v1/declarations/${encodeURIComponent(id.trim())}/check`, { method: "POST", body: await input.arrayBuffer() });
          if (res.status === 429) setErr("Demasiadas comprobaciones. Espera unos minutos.");
          else setResult(await res.json());
        } catch {
          setErr("No se pudo completar la comprobación. Inténtalo de nuevo.");
        } finally {
          setBusy(false);
        }
      }}
    >
      {!initialId && (
        <div className="space-y-2">
          <label htmlFor="fc-id" className="text-sm font-medium">
            Identificador de la declaración
          </label>
          <input id="fc-id" value={id} onChange={(e) => setId(e.target.value)} required placeholder="ORV-XXXX-XXXX-XXXX" className="h-11 w-full rounded-[var(--radius-md)] border border-line-strong bg-surface px-3 font-mono text-sm" />
        </div>
      )}
      <div className="space-y-2">
        <label htmlFor="fc-file" className="text-sm font-medium">
          Documento recibido (PDF o JSON de ORIVA)
        </label>
        <input id="fc-file" name="file" type="file" accept=".pdf,.json,application/pdf,application/json" className="block min-h-11 w-full py-2 text-sm" />
        <p className="text-sm text-muted">El fichero se envía solo para calcular su huella; no se almacena.</p>
      </div>
      <Button type="submit" variant="secondary" size="sm" disabled={busy}>
        {busy ? "Comprobando…" : "Comprobar documento"}
      </Button>
      {err && <p role="alert" className="text-sm text-danger">{err}</p>}
      {result && (
        <div role="status" className={`flex gap-2 rounded-md p-3 text-sm ${result.match ? "bg-ok-soft text-ok" : "bg-danger-soft text-danger"}`}>
          {result.match ? <CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden /> : <XCircle className="h-4 w-4 shrink-0" aria-hidden />}
          <p>
            {result.match
              ? `${KIND_TEXT[result.kind]} Estado de la declaración: ${STATUS_TEXT[result.status ?? ""] ?? result.status}.`
              : "El documento no coincide con ninguna declaración registrada con ese identificador. Puede haber sido modificado, o el identificador no es correcto."}
          </p>
        </div>
      )}
    </form>
  );
}
