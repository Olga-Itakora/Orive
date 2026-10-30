import { ProsePage } from "@/components/site/prose-page";

export const metadata = { title: "Preguntas frecuentes" };

const FAQ: [string, string][] = [
  ["¿ORIVA certifica que mi obra es original o que la he hecho yo?", "No. ORIVA registra tu declaración responsable y acredita técnicamente que no ha sido alterada. No certifica autoría, originalidad, titularidad de derechos ni veracidad."],
  ["¿Estoy obligado por ley a declarar el uso de IA?", "No con carácter general. El artículo 50 del Reglamento europeo de IA establece obligaciones específicas en determinados supuestos. Editoriales, concursos o instituciones pueden tener sus propios requisitos. ORIVA incluye una orientación, pero no sustituye el asesoramiento jurídico."],
  ["¿Un código con números más altos significa más uso de IA o menos mérito?", "No. Los números identifican categorías. A5 (generación) no es «más» que A3 (revisión), y C5 no implica más autoría que C4. No se suman ni se promedian."],
  ["¿Qué pasa si no recuerdo qué herramienta usé?", "Puedes registrarla como desconocida. La clasificación lo reflejará (por ejemplo, en la dimensión D) sin inventar datos."],
  ["¿Y si no sé si una herramienta incorporaba IA?", "Elige «No puedo determinarlo con certeza» y explica la situación. La incertidumbre se registra tal cual."],
  ["Mi obra tiene texto e ilustraciones con procesos distintos. ¿Cómo lo declaro?", "Define componentes en la obra y asocia cada uso de IA a su componente. ORIVA conserva el código de cada uso, el perfil de cada componente y un perfil compuesto de la obra."],
  ["Participo en una obra colectiva. ¿Puedo declarar solo mi parte?", "Sí. Elige el alcance «Solo mi contribución». La declaración indicará que no abarca necesariamente las aportaciones de los demás participantes."],
  ["¿Tengo que subir mis prompts o conversaciones?", "No. Basta con describir lo esencial. Si aportas evidencias, son privadas por defecto y nunca se publican sin tu consentimiento específico."],
  ["¿Puedo cambiar una declaración emitida?", "Una versión emitida no se modifica. Puedes crear una nueva versión; la anterior se conserva, enlazada y marcada como sustituida. También puedes revocarla."],
  ["¿Qué diferencia hay entre retirar la publicación y revocar?", "Retirar la publicación hace que deje de ser consultable públicamente, pero la declaración sigue vigente. Revocar indica que ya no es válida; se conserva y sigue siendo verificable, marcada como revocada."],
  ["¿Qué demuestra la huella criptográfica?", "Que el contenido registrado no ha cambiado desde su emisión. No demuestra la identidad del declarante ni la verdad de lo declarado."],
  ["¿Es la aceptación una firma electrónica?", "No es una firma electrónica cualificada. Es una aceptación expresa realizada mediante una acción autenticada en tu cuenta, que queda registrada con la versión exacta del texto aceptado."],
];

export default function FaqPage() {
  return (
    <ProsePage eyebrow="Preguntas frecuentes" title="Preguntas frecuentes">
      <div className="not-prose space-y-3">
        {FAQ.map(([q, a]) => (
          <details key={q} className="group rounded-[var(--radius-md)] border border-line bg-surface open:border-petrol-200">
            <summary className="flex min-h-11 cursor-pointer list-none items-center p-4 font-medium text-ink marker:hidden">
              <span className="mr-2 inline-block text-muted transition-transform duration-150 ease-out group-open:rotate-90" aria-hidden>›</span>
              {q}
            </summary>
            <p className="px-4 pb-4 pl-9 text-sm text-muted">{a}</p>
          </details>
        ))}
      </div>
    </ProsePage>
  );
}
