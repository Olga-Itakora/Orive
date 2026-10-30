import { redirect } from "next/navigation";
import { FileCheck } from "@/components/oriva/integrity-check";
import { LookupForm } from "@/components/oriva/lookup-form";
import { Alert } from "@/components/ui/alert";
import { normalizePublicId } from "@/lib/ids";
import { CODE_REGEX } from "@/lib/oriva-code";

export const metadata = { title: "Verificar una declaración", description: "Consulta una declaración ORIVA pública o comprueba la integridad de un documento." };

export default async function VerifyPage({ searchParams }: { searchParams: Promise<{ q?: string }> }) {
  const { q } = await searchParams;
  let notRecognized = false;
  if (q) {
    const id = normalizePublicId(q);
    if (id) redirect(`/verify/${id}`);
    const code = q.trim().toUpperCase().replace(/^ORIVA\s+/, "");
    if (CODE_REGEX.test(code)) redirect(`/code?c=${code}`);
    notRecognized = true;
  }
  return (
    <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
      <h1 className="text-3xl font-semibold text-petrol-900">Verificar</h1>
      <p className="mt-3 leading-relaxed text-muted">
        Consulta una declaración pública mediante su identificador, o comprueba si un documento ORIVA que has recibido coincide exactamente con el registrado.
      </p>
      {notRecognized && (
        <Alert tone="warn" className="mt-6">
          No reconocemos «{q}». Un identificador tiene la forma ORV-XXXX-XXXX-XXXX y un código, la forma A5B3C4D2.
        </Alert>
      )}
      <div className="mt-8 space-y-8">
        <LookupForm />
        <section className="rounded-[var(--radius-lg)] border border-line bg-surface p-5">
          <h2 className="font-semibold">Comprobar un documento</h2>
          <p className="mt-1 mb-4 text-sm text-muted">Funciona también con declaraciones no publicadas: solo confirma si el documento que ya tienes coincide con el registro, sin revelar nada más.</p>
          <FileCheck />
        </section>
        <section className="text-sm leading-relaxed text-muted">
          <h2 className="mb-2 font-semibold text-ink">Qué se verifica y qué no</h2>
          <p>
            ORIVA comprueba que una declaración fue registrada con un contenido determinado en una fecha determinada y que ese contenido no ha sido alterado (huella SHA-256 sobre una representación canónica). No verifica la identidad del declarante, la veracidad de lo declarado, la originalidad de la obra, la titularidad de derechos ni el cumplimiento de obligaciones legales.
          </p>
        </section>
      </div>
    </div>
  );
}
