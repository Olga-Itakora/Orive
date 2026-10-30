import { ProsePage } from "@/components/site/prose-page";
import { Alert } from "@/components/ui/alert";
import { prisma } from "@/lib/db";

export const metadata = { title: "Marco normativo" };

export default async function LegalFrameworkPage() {
  const refs = await prisma.legalReference.findMany({ where: { active: true }, orderBy: { key: "asc" } });
  return (
    <ProsePage
      eyebrow="Marco normativo"
      title="ORIVA y el Reglamento europeo de inteligencia artificial"
      lead="El Reglamento (UE) 2024/1689 establece obligaciones específicas de transparencia. ORIVA ayuda a documentar procesos creativos, pero no certifica el cumplimiento normativo."
    >
      <Alert tone="warn" className="not-prose mb-6" title="Contenido informativo pendiente de revisión jurídica">
        Esta página resume el marco aplicable a fecha de 30 de octubre de 2026 y no constituye asesoramiento jurídico. Las referencias deben contrastarse con el Diario Oficial de la UE y la versión consolidada vigente en EUR-Lex.
      </Alert>
      <h2>No todos los autores están obligados a declarar su uso de IA</h2>
      <p>El Reglamento no impone a toda persona que utiliza una herramienta de IA la obligación de declararlo. Conviene distinguir:</p>
      <ul>
        <li>
          <strong>Obligaciones específicas de transparencia</strong> previstas en el artículo 50 para proveedores y responsables del despliegue de determinados sistemas.
        </li>
        <li>
          <strong>Requisitos de editoriales, concursos, revistas o instituciones</strong>, que pueden exigir declarar el uso de IA aunque la ley no lo haga.
        </li>
        <li>
          <strong>Obligaciones contractuales o sectoriales</strong> que puedan resultar aplicables.
        </li>
        <li>
          <strong>Buenas prácticas voluntarias</strong> de transparencia, que es el ámbito principal de ORIVA.
        </li>
      </ul>
      <h2>Proveedores y responsables del despliegue</h2>
      <p>
        El <em>proveedor</em> es quien desarrolla un sistema de IA y lo introduce en el mercado o lo pone en servicio con su nombre o marca. El <em>responsable del despliegue</em> es quien utiliza un sistema de IA bajo su propia autoridad, salvo en el marco de una actividad personal de carácter no profesional. No toda persona que usa una herramienta es responsable del despliegue: cuando se trabaja por cuenta de una editorial o una empresa, esa organización puede serlo.
      </p>
      <h2>El artículo 50</h2>
      <p>Aplicable desde el 2 de agosto de 2026. Entre otras cuestiones:</p>
      <ul>
        <li>Los proveedores de sistemas que generan audio, imagen, vídeo o texto sintéticos deben marcar los resultados en un formato legible por máquina (art. 50.2), salvo cuando el sistema realiza una función de asistencia para la edición estándar o no altera sustancialmente los datos de entrada.</li>
        <li>
          Los responsables del despliegue de sistemas que generan o manipulan imagen, audio o vídeo que constituya una <em>ultrasuplantación</em> (deep fake) deben revelarlo (art. 50.4). Una ultrasuplantación es un contenido que se asemeja a personas, objetos, lugares, entidades o sucesos reales y que puede parecer falsamente auténtico: <strong>no toda imagen generada con IA lo es</strong>.
        </li>
        <li>Cuando ese contenido forma parte de una obra manifiestamente artística, creativa, satírica, de ficción o análoga, la obligación se limita a revelar su existencia de forma adecuada que no dificulte la exhibición o el disfrute de la obra.</li>
        <li>El texto generado o manipulado publicado para informar al público sobre asuntos de interés público debe revelarse, salvo que haya sido sometido a revisión humana o control editorial y una persona asuma la responsabilidad editorial.</li>
      </ul>
      <h2>Cambios recientes</h2>
      <p>
        Según fuentes secundarias consultadas, el Reglamento (UE) 2026/1744 («Digital Omnibus on AI»), publicado en julio de 2026, aplazó parte de las obligaciones de los sistemas de alto riesgo y estableció un periodo transitorio hasta el 2 de diciembre de 2026 para el marcado del art. 50.2 de los sistemas ya comercializados antes del 2 de agosto de 2026, sin aplazar el resto del artículo 50. La Comisión publicó en 2026 un Código de prácticas sobre el marcado y etiquetado de contenido generado por IA y directrices sobre el artículo 50. <strong>Estos extremos deben verificarse en las fuentes oficiales.</strong>
      </p>
      <h2>Autoría y derechos de autor</h2>
      <p>Que una obra se haya elaborado con asistencia de IA no significa por sí mismo que deje de tener autoría humana ni protección por derechos de autor. ORIVA no se pronuncia sobre la autoría ni sobre la titularidad de derechos.</p>
      <h2>Qué hace ORIVA</h2>
      <ul>
        <li>Ofrece una orientación sobre el artículo 50, separada del código ORIVA y con referencias a las disposiciones.</li>
        <li>No convierte esas respuestas en una certificación de cumplimiento.</li>
        <li>No incorpora ninguna puntuación de cumplimiento en el código.</li>
        <li>No es un servicio de la Unión Europea ni utiliza sus emblemas.</li>
      </ul>
      <h2>Referencias</h2>
      <ul>
        {refs.map((r) => (
          <li key={r.key}>
            <a href={r.url} rel="noopener noreferrer" target="_blank">
              {r.citation}
            </a>{" "}
            — {r.title}.{r.verificationNote && <span className="text-sm text-muted"> {r.verificationNote}</span>}
          </li>
        ))}
      </ul>
    </ProsePage>
  );
}
