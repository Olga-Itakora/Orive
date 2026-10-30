import Link from "next/link";
import { ProsePage } from "@/components/site/prose-page";

export const metadata = { title: "Qué es ORIVA" };

export default function AboutPage() {
  return (
    <ProsePage eyebrow="Qué es ORIVA" title="Un lenguaje común para explicar cómo se ha creado una obra" lead="ORIVA es una plataforma de transparencia creativa. Permite a autoras y autores describir de forma estructurada qué uso han hecho de la inteligencia artificial y emitir una declaración responsable verificable.">
      <p>
        <strong>ORIVA no pretende determinar quién merece ser considerado autor</strong>, sino ofrecer una forma transparente, estructurada y comprensible de explicar cómo se ha creado una obra.
      </p>
      <h2>Cinco principios</h2>
      <ul>
        <li>
          <strong>Transparencia.</strong> Describir qué sistemas de IA se utilizaron, para qué, en qué fases y con qué resultados.
        </li>
        <li>
          <strong>Neutralidad.</strong> El uso de IA no es automáticamente positivo ni negativo. ORIVA no penaliza su uso ni presupone que una obra sin IA sea mejor.
        </li>
        <li>
          <strong>Responsabilidad.</strong> Cada declaración identifica quién declara, qué parte de la obra cubre y quién asume las decisiones finales.
        </li>
        <li>
          <strong>Trazabilidad.</strong> Las declaraciones emitidas, sus versiones, las reglas de clasificación aplicadas y sus huellas criptográficas se conservan sin alteraciones.
        </li>
        <li>
          <strong>Proporcionalidad.</strong> No se piden datos innecesarios: ni prompts completos, ni conversaciones privadas, ni documentación confidencial.
        </li>
      </ul>
      <h2>Qué es y qué no es</h2>
      <p>
        ORIVA <strong>es</strong> un sistema de clasificación descriptiva (ORIVA Code), un cuestionario guiado y un registro técnico que acredita que una declaración concreta se emitió con un contenido determinado y no ha sido alterada.
      </p>
      <p>
        ORIVA <strong>no es</strong> una certificación de originalidad, de autoría ni de cumplimiento legal; no es un servicio de las instituciones europeas; no detecta automáticamente el uso de IA; y no puntúa obras ni calcula porcentajes de participación humana.
      </p>
      <h2>Identidad</h2>
      <ul>
        <li>Plataforma: ORIVA.</li>
        <li>Sistema de clasificación: ORIVA Code (versión actual v1.0).</li>
        <li>Documentos: ORIVA Declaration.</li>
        <li>Lema: «Every creation has a process. Make it visible.» — «Toda creación tiene un proceso. Hazlo visible.»</li>
      </ul>
      <p>
        ORIVA se diseña con vocación internacional y con la intención de evolucionar hacia una especificación abierta de transparencia creativa. <Link href="/code">Consulta la especificación de ORIVA Code</Link>.
      </p>
    </ProsePage>
  );
}
