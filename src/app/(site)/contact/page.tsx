import { ContactForm } from "@/components/site/contact-form";
import { ProsePage } from "@/components/site/prose-page";
import { getCurrentUser } from "@/lib/auth/session";

export const metadata = { title: "Contacto" };

export default async function ContactPage() {
  const user = await getCurrentUser();
  return (
    <ProsePage eyebrow="Contacto" title="Contacto y solicitudes de derechos" lead="Escríbenos para consultas, incidencias técnicas, comunicaciones sobre declaraciones públicas o para ejercer tus derechos de protección de datos.">
      <div className="not-prose max-w-2xl rounded-[var(--radius-lg)] border border-line bg-surface p-6">
        <ContactForm defaultEmail={user?.email} />
      </div>
      <p className="mt-6 text-sm text-muted">
        Si tienes cuenta, también puedes descargar tus datos y solicitar la supresión de tu cuenta desde «Perfil y datos». Retirar la publicación de una declaración es distinto de eliminar datos personales: las declaraciones emitidas se conservan de forma inalterable mientras exista una base legítima para ello.
      </p>
    </ProsePage>
  );
}
