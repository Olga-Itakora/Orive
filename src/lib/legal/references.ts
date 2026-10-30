/**
 * Referencias normativas usadas por la orientación del artículo 50.
 * Se replican en la tabla LegalReference mediante el seed y pueden gestionarse
 * desde administración. Todas requieren revisión jurídica profesional.
 *
 * Estado de verificación: el texto citado procede del Reglamento (UE) 2024/1689
 * tal como fue publicado en el DOUE (L, 12.7.2024). Las referencias al
 * Reglamento (UE) 2026/1744 («Digital Omnibus on AI») se basan en fuentes
 * secundarias consultadas el 30/10/2026 y DEBEN contrastarse con el DOUE y con
 * la versión consolidada vigente en EUR-Lex antes de su uso en producción.
 */
export interface LegalReferenceSeed {
  key: string;
  title: string;
  citation: string;
  url: string;
  applicableFrom?: string;
  summary: string;
  verificationNote: string;
}

const AI_ACT_URL = "https://eur-lex.europa.eu/eli/reg/2024/1689/oj";

export const LEGAL_REFERENCES: LegalReferenceSeed[] = [
  {
    key: "AIA-3-3",
    title: "Definición de «proveedor»",
    citation: "Reglamento (UE) 2024/1689, art. 3, punto 3",
    url: AI_ACT_URL,
    summary: "Persona física o jurídica, autoridad u organismo que desarrolle un sistema de IA o lo haga desarrollar y lo introduzca en el mercado o lo ponga en servicio con su propio nombre o marca.",
    verificationNote: "Texto original de 2024. Revisar versión consolidada.",
  },
  {
    key: "AIA-3-4",
    title: "Definición de «responsable del despliegue»",
    citation: "Reglamento (UE) 2024/1689, art. 3, punto 4",
    url: AI_ACT_URL,
    summary: "Persona física o jurídica, autoridad u organismo que utilice un sistema de IA bajo su propia autoridad, salvo cuando su uso se enmarque en una actividad personal de carácter no profesional.",
    verificationNote: "Texto original de 2024. Revisar versión consolidada.",
  },
  {
    key: "AIA-3-60",
    title: "Definición de «ultrasuplantación» (deep fake)",
    citation: "Reglamento (UE) 2024/1689, art. 3, punto 60",
    url: AI_ACT_URL,
    summary: "Contenido de imagen, audio o vídeo generado o manipulado por una IA que se asemeja a personas, objetos, lugares, entidades o sucesos reales y que puede inducir a una persona a pensar erróneamente que son auténticos o verídicos.",
    verificationNote: "Texto original de 2024. Revisar versión consolidada.",
  },
  {
    key: "AIA-50-2",
    title: "Marcado de contenidos sintéticos (proveedores)",
    citation: "Reglamento (UE) 2024/1689, art. 50, apartado 2",
    url: AI_ACT_URL,
    applicableFrom: "2026-08-02",
    summary: "Obligación de los proveedores de sistemas que generan audio, imagen, vídeo o texto sintéticos de marcar los resultados en formato legible por máquina. No se aplica en la medida en que el sistema desempeñe una función de asistencia para la edición estándar o no altere sustancialmente los datos de entrada.",
    verificationNote: "Según fuentes secundarias, el Reglamento (UE) 2026/1744 establece un periodo transitorio hasta el 2/12/2026 para sistemas introducidos en el mercado antes del 2/8/2026. Verificar en el DOUE.",
  },
  {
    key: "AIA-50-4-1",
    title: "Ultrasuplantaciones: obligación de revelar (responsables del despliegue)",
    citation: "Reglamento (UE) 2024/1689, art. 50, apartado 4, párrafo primero",
    url: AI_ACT_URL,
    applicableFrom: "2026-08-02",
    summary: "Los responsables del despliegue de un sistema que genere o manipule imagen, audio o vídeo que constituya una ultrasuplantación harán público que el contenido ha sido generado o manipulado de manera artificial. Si forma parte de una obra o programa manifiestamente artístico, creativo, satírico, de ficción o análogo, la obligación se limita a revelar la existencia de dicho contenido de forma adecuada que no dificulte la exhibición o el disfrute de la obra.",
    verificationNote: "Texto original de 2024; según fuentes secundarias no fue aplazado por el Reglamento (UE) 2026/1744. Verificar.",
  },
  {
    key: "AIA-50-4-2",
    title: "Texto publicado para informar sobre asuntos de interés público",
    citation: "Reglamento (UE) 2024/1689, art. 50, apartado 4, párrafo segundo",
    url: AI_ACT_URL,
    applicableFrom: "2026-08-02",
    summary: "Los responsables del despliegue de un sistema que genere o manipule texto publicado con el fin de informar al público sobre asuntos de interés público harán público que ha sido generado o manipulado artificialmente, salvo que haya sido sometido a revisión humana o control editorial y una persona física o jurídica tenga la responsabilidad editorial de la publicación.",
    verificationNote: "Texto original de 2024. Revisar versión consolidada y directrices de la Comisión.",
  },
  {
    key: "AIA-50-5",
    title: "Forma y momento de la información",
    citation: "Reglamento (UE) 2024/1689, art. 50, apartado 5",
    url: AI_ACT_URL,
    applicableFrom: "2026-08-02",
    summary: "La información se facilitará de manera clara y distinguible a más tardar con ocasión de la primera interacción o exposición, conforme a los requisitos de accesibilidad aplicables.",
    verificationNote: "Texto original de 2024.",
  },
  {
    key: "AIA-113",
    title: "Entrada en vigor y aplicación",
    citation: "Reglamento (UE) 2024/1689, art. 113",
    url: AI_ACT_URL,
    summary: "El Reglamento se aplica, con carácter general, a partir del 2 de agosto de 2026, con excepciones para determinados capítulos.",
    verificationNote: "Modificado en parte por el Reglamento (UE) 2026/1744 (calendario de alto riesgo). Verificar en la versión consolidada.",
  },
  {
    key: "EC-ART50-GUIDELINES",
    title: "Directrices de la Comisión sobre las obligaciones de transparencia del art. 50",
    citation: "Comisión Europea — Guidelines on transparency obligations for providers and deployers of AI systems",
    url: "https://digital-strategy.ec.europa.eu/en/library/guidelines-transparency-obligations-providers-and-deployers-ai-systems",
    summary: "Orientaciones interpretativas no vinculantes sobre el ámbito del art. 50.",
    verificationNote: "Fecha de publicación no verificada directamente desde este entorno (fuentes secundarias indican julio de 2026).",
  },
  {
    key: "EC-ART50-FAQ",
    title: "Preguntas frecuentes de la Comisión sobre el art. 50",
    citation: "Comisión Europea — Transparency obligations under Article 50 of the AI Act (FAQ)",
    url: "https://digital-strategy.ec.europa.eu/en/faqs/transparency-obligations-under-article-50-ai-act",
    summary: "Respuestas de la Comisión sobre el ámbito y la aplicación del art. 50.",
    verificationNote: "No accesible desde el entorno de desarrollo; enlace a revisar.",
  },
  {
    key: "EC-COP-AIGC",
    title: "Código de prácticas sobre marcado y etiquetado de contenido generado por IA",
    citation: "Comisión Europea — Code of Practice on Transparency of AI-generated Content",
    url: "https://digital-strategy.ec.europa.eu/en/policies/code-practice-ai-generated-content",
    summary: "Código voluntario que orienta el cumplimiento del art. 50 (sección 1: proveedores; sección 2: responsables del despliegue). Adhesión voluntaria.",
    verificationNote: "Versión final publicada en junio de 2026 según fuentes secundarias. Verificar.",
  },
  {
    key: "AIA-SERVICE-DESK-50",
    title: "AI Act Service Desk — artículo 50",
    citation: "Comisión Europea — AI Act Service Desk",
    url: "https://ai-act-service-desk.ec.europa.eu/en/ai-act/article-50",
    summary: "Texto del artículo 50 con explicaciones del servicio de asistencia de la Comisión.",
    verificationNote: "Enlace facilitado en el encargo; no consultado desde el entorno.",
  },
  {
    key: "EU-2026-1744",
    title: "Reglamento (UE) 2026/1744 («Digital Omnibus on AI»)",
    citation: "Reglamento (UE) 2026/1744, por el que se modifica, entre otros, el Reglamento (UE) 2024/1689",
    url: "https://eur-lex.europa.eu/eli/reg/2026/1744/oj",
    summary: "Según fuentes secundarias: publicado en el DOUE el 24/7/2026, en vigor el 27/7/2026; aplaza obligaciones de alto riesgo y prevé un periodo transitorio para el art. 50.2. No aplaza el resto del art. 50.",
    verificationNote: "NO verificado contra el DOUE desde este entorno. Existe una corrección de errores (septiembre de 2026) según fuentes secundarias.",
  },
];
