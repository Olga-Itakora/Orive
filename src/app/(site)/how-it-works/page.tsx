import Link from "next/link";
import { ProsePage } from "@/components/site/prose-page";
import { ButtonLink } from "@/components/ui/button";

export const metadata = { title: "Cómo funciona" };

export default function HowPage() {
  return (
    <ProsePage
      eyebrow="Cómo funciona"
      title="De tu proceso creativo a una declaración verificable"
      lead="Un recorrido guiado, sin tecnicismos. Puedes guardar un borrador, volver atrás y corregir tus respuestas en cualquier momento antes de emitir."
      aside={
        <div className="rounded-[var(--radius-lg)] border border-line bg-surface p-5">
          <p className="font-medium">¿Empezamos?</p>
          <p className="mt-1 text-sm text-muted">Crear una cuenta es gratuito. Todo es privado hasta que decidas publicarlo.</p>
          <ButtonLink href="/register" className="mt-4 w-full">
            Crear cuenta
          </ButtonLink>
        </div>
      }
    >
      <h2>1. Identifica la obra</h2>
      <p>Título, modalidad, estado, idioma, editorial e identificadores (ISBN, DOI…). Si es una obra híbrida, como un libro ilustrado o un videojuego, divídela en componentes: texto, ilustraciones, audio, código…</p>
      <h2>2. Indica quién declara y qué cubre la declaración</h2>
      <p>Tu nombre profesional, tu rol y el alcance: la obra completa, componentes concretos o solo tu contribución. En una obra colectiva solo declaras lo que conoces; tu declaración no se atribuye a los demás participantes.</p>
      <h2>3. Responde si se utilizó IA</h2>
      <p>No, sí, no puedo determinarlo con certeza, o solo puedo declarar sobre mi contribución. Si no se utilizó IA, puedes emitir una declaración negativa. Si hay incertidumbre, se registra tal cual, sin inventar datos.</p>
      <h2>4. Registra las herramientas y cada uso concreto</h2>
      <p>ORIVA no se limita a una lista de herramientas: registra <em>usos individuales</em>. Para cada uso indicas la parte de la obra, la herramienta, las fases, para qué se utilizó la IA, qué se incorporó a la obra y qué hiciste tú con ello.</p>
      <h2>5. Obtén tu clasificación ORIVA</h2>
      <p>
        Un motor de reglas deterministas calcula el código de cada uso y el perfil de la obra. Ves el resultado mientras respondes. No puedes editar el código directamente: si no te parece correcto, revisa las respuestas. <Link href="/code">Qué significa cada dimensión</Link>.
      </p>
      <h2>6. Consulta la orientación sobre el artículo 50</h2>
      <p>Unas preguntas opcionales te ayudan a reconocer si podría existir una obligación específica de transparencia según el Reglamento europeo de IA. Es una orientación, separada del código, y no una certificación.</p>
      <h2>7. Revisa, acepta y emite</h2>
      <p>Revisas todo, aceptas expresamente la declaración responsable y la emites. Se registra la fecha y hora del servidor, la versión del texto aceptado, la versión de la taxonomía y una huella SHA-256. Recibes un identificador único, un PDF completo, un resumen de una página y una exportación JSON.</p>
      <h2>8. Decide qué publicar</h2>
      <p>Emitir y publicar son acciones distintas. Puedes publicar solo el código, la obra, el resumen, tu nombre o la declaración completa, y retirar la publicación cuando quieras. Las evidencias nunca se publican sin tu consentimiento específico.</p>
      <h2>9. Actualiza con nuevas versiones</h2>
      <p>Si necesitas corregir algo, creas una nueva versión. La anterior se conserva intacta, enlazada y verificable, marcada como sustituida.</p>
    </ProsePage>
  );
}
