"use client";
import Link from "next/link";
import { CopyButton } from "@/components/oriva/copy-button";

export default function ErrorPage({ reset, error }: { reset: () => void; error: Error & { digest?: string } }) {
  const details = `ORIVA · error ${error.digest ?? "sin referencia"} · ${new Date().toISOString()} · ${typeof window !== "undefined" ? window.location.pathname : ""}`;
  return (
    <main id="main" className="mx-auto flex min-h-[60vh] max-w-lg flex-col items-start justify-center gap-4 px-6">
      <h1 className="text-2xl font-semibold text-petrol-900">No se ha podido completar la operación</h1>
      <p className="text-muted">Ha fallado algo en el servidor. Lo que ya habías guardado está a salvo; vuelve a intentarlo y, si persiste, envíanos los detalles.</p>
      <div className="flex flex-wrap gap-3">
        <button onClick={reset} className="inline-flex h-11 items-center rounded-[var(--radius-md)] bg-petrol-800 px-4 text-sm font-medium text-white">
          Volver a intentarlo
        </button>
        <CopyButton value={details} label="Copiar detalles del error" />
        <Link href="/contact" className="inline-flex min-h-11 items-center px-2 text-sm text-petrol-700 underline">
          Contactar con soporte
        </Link>
      </div>
    </main>
  );
}
