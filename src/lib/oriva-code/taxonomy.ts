/**
 * ORIVA Code v1.0 — definición de la taxonomía.
 *
 * Este fichero es la fuente de verdad de la versión 1.0 del esquema. Se replica
 * en base de datos (TransparencyScheme / Dimension / Value / Rule) mediante el
 * seed para su consulta y gobernanza, pero el motor de clasificación de cada
 * versión vive en código y nunca se modifica una vez publicada: una nueva
 * versión del esquema implica un nuevo módulo y un nuevo registro.
 *
 * IMPORTANTE: los números son identificadores categóricos. No son
 * puntuaciones, porcentajes ni magnitudes comparables.
 */

export const SCHEME_ID = "oriva-code";
export const SCHEME_VERSION = "1.0";

export type FunctionCode = "A0" | "A1" | "A2" | "A3" | "A4" | "A5";
export type UseFunctionCode = Exclude<FunctionCode, "A0">;
export type ContributionCode = "B0" | "B1" | "B2" | "B3";
export type IncorporatedContributionCode = Exclude<ContributionCode, "B0">;
export type InterventionCode = "C0" | "C1" | "C2" | "C3" | "C4" | "C5";
export type DocumentationCode = "D0" | "D1" | "D2" | "D3";

export type DimensionKey = "A" | "B" | "C" | "D";

export interface Localized {
  es: string;
  en: string;
}

export interface TaxonomyValue {
  code: string;
  label: Localized;
  /** Descripción breve para lectores del código. */
  description: Localized;
  /** Ejemplo ilustrativo. */
  example?: Localized;
}

export interface TaxonomyDimension {
  key: DimensionKey;
  name: Localized;
  question: Localized;
  /** Qué NO describe esta dimensión (para evitar lecturas erróneas). */
  notMeaning: Localized;
  values: TaxonomyValue[];
}

export const DIMENSIONS: Record<DimensionKey, TaxonomyDimension> = {
  A: {
    key: "A",
    name: { es: "Función de la IA", en: "Function of AI" },
    question: { es: "¿Para qué se utilizó la IA?", en: "What was AI used for?" },
    notMeaning: {
      es: "No mide intensidad de uso: A5 no es «más uso» que A3; son funciones distintas.",
      en: "It does not measure intensity: A5 is not “more use” than A3; they are different functions.",
    },
    values: [
      {
        code: "A0",
        label: { es: "No utilización de IA", en: "No use of AI" },
        description: {
          es: "El declarante afirma que no se utilizaron sistemas de IA en el ámbito declarado.",
          en: "The declarant states that no AI systems were used within the declared scope.",
        },
      },
      {
        code: "A1",
        label: { es: "Consulta, documentación o exploración", en: "Consultation, research or exploration" },
        description: {
          es: "La IA se usó para consultar, documentarse o explorar información.",
          en: "AI was used to consult, research or explore information.",
        },
        example: { es: "Preguntar a un asistente por el contexto histórico de una época.", en: "Asking an assistant about the historical context of a period." },
      },
      {
        code: "A2",
        label: { es: "Generación de ideas, sugerencias o alternativas", en: "Generation of ideas, suggestions or alternatives" },
        description: {
          es: "La IA propuso ideas, sugerencias o alternativas.",
          en: "AI proposed ideas, suggestions or alternatives.",
        },
        example: { es: "Pedir posibles giros argumentales.", en: "Asking for possible plot twists." },
      },
      {
        code: "A3",
        label: { es: "Revisión, crítica o evaluación", en: "Review, critique or evaluation" },
        description: {
          es: "La IA revisó, criticó o evaluó material existente.",
          en: "AI reviewed, critiqued or evaluated existing material.",
        },
        example: { es: "Solicitar una lectura crítica de un ensayo.", en: "Requesting a critical reading of an essay." },
      },
      {
        code: "A4",
        label: { es: "Corrección, edición o transformación", en: "Correction, editing or transformation" },
        description: {
          es: "La IA corrigió, editó, tradujo o transformó material existente.",
          en: "AI corrected, edited, translated or transformed existing material.",
        },
        example: { es: "Corrección ortográfica; traducción; limpieza de audio.", en: "Spell-checking; translation; audio clean-up." },
      },
      {
        code: "A5",
        label: { es: "Generación de contenido creativo", en: "Generation of creative content" },
        description: {
          es: "La IA generó contenido: texto, imagen, música, sonido, vídeo, código u otros elementos.",
          en: "AI generated content: text, images, music, sound, video, code or other elements.",
        },
        example: { es: "Generar una ilustración a partir de una descripción.", en: "Generating an illustration from a description." },
      },
    ],
  },
  B: {
    key: "B",
    name: { es: "Tipo de aportación incorporada", en: "Type of incorporated contribution" },
    question: {
      es: "¿Qué tipo de aportación procedente de IA está presente en la obra final?",
      en: "What kind of AI-originated contribution is present in the final work?",
    },
    notMeaning: {
      es: "No describe cuánto modificó la persona esa aportación (eso es la dimensión C).",
      en: "It does not describe how much the person modified it (that is dimension C).",
    },
    values: [
      {
        code: "B0",
        label: { es: "Ninguna aportación incorporada", en: "No contribution incorporated" },
        description: {
          es: "La IA pudo usarse en el proceso, pero ninguna de sus propuestas o resultados se incorporó a la obra.",
          en: "AI may have been used, but none of its proposals or outputs were incorporated into the work.",
        },
        example: { es: "Se descartaron todas las observaciones de una revisión.", en: "All the observations from a review were discarded." },
      },
      {
        code: "B1",
        label: { es: "Aportación conceptual", en: "Conceptual contribution" },
        description: {
          es: "Se incorporaron ideas, sugerencias, estructuras o conceptos procedentes de IA, desarrollados después por la persona.",
          en: "Ideas, suggestions, structures or concepts from AI were incorporated and then developed by the person.",
        },
        example: { es: "La IA propone una estructura narrativa y el autor redacta la novela.", en: "AI proposes a narrative structure and the author writes the novel." },
      },
      {
        code: "B2",
        label: { es: "Aportación formal", en: "Formal contribution" },
        description: {
          es: "Se incorporaron correcciones, reformulaciones, traducciones, cambios de estilo u otras modificaciones formales hechas con IA.",
          en: "Corrections, rephrasings, translations, style changes or other formal modifications made with AI were incorporated.",
        },
        example: { es: "Se aceptan correcciones gramaticales sugeridas.", en: "Suggested grammar corrections are accepted." },
      },
      {
        code: "B3",
        label: { es: "Aportación de contenido expresivo", en: "Expressive content contribution" },
        description: {
          es: "Se incorporaron textos, imágenes, música, sonidos, vídeos, código creativo u otros elementos expresivos generados con IA.",
          en: "Text, images, music, sounds, video, creative code or other expressive elements generated with AI were incorporated.",
        },
        example: { es: "Una ilustración generada con IA aparece en el libro.", en: "An AI-generated illustration appears in the book." },
      },
    ],
  },
  C: {
    key: "C",
    name: { es: "Modalidad de intervención humana", en: "Mode of human intervention" },
    question: {
      es: "¿Qué hizo la persona con la aportación de IA?",
      en: "What did the person do with the AI contribution?",
    },
    notMeaning: {
      es: "No mide mérito artístico, originalidad, esfuerzo ni porcentaje de autoría. C5 no es «más autoría» que C4.",
      en: "It does not measure artistic merit, originality, effort or share of authorship. C5 is not “more authorship” than C4.",
    },
    values: [
      {
        code: "C0",
        label: { es: "No aplicable", en: "Not applicable" },
        description: {
          es: "No se incorporaron resultados de IA que requieran describir una intervención posterior.",
          en: "No AI outputs were incorporated that would require describing a subsequent intervention.",
        },
      },
      {
        code: "C1",
        label: { es: "Selección o aceptación", en: "Selection or acceptance" },
        description: {
          es: "La persona seleccionó o aceptó un resultado sin modificarlo significativamente. Seleccionar puede ser una decisión creativa relevante.",
          en: "The person selected or accepted an output without significant modification. Selecting can be a relevant creative decision.",
        },
        example: { es: "Se incorpora una ilustración generada sin cambios.", en: "A generated illustration is included unchanged." },
      },
      {
        code: "C2",
        label: { es: "Edición puntual", en: "Minor editing" },
        description: {
          es: "La persona hizo ajustes menores sobre el resultado.",
          en: "The person made minor adjustments to the output.",
        },
        example: { es: "Retocar colores o cambiar algunas palabras.", en: "Adjusting colours or changing a few words." },
      },
      {
        code: "C3",
        label: { es: "Transformación sustancial", en: "Substantial transformation" },
        description: {
          es: "La persona modificó considerablemente el resultado, aunque se mantienen elementos reconocibles de la propuesta original.",
          en: "The person modified the output considerably, though recognisable elements of the original remain.",
        },
      },
      {
        code: "C4",
        label: { es: "Reelaboración creativa", en: "Creative reworking" },
        description: {
          es: "La persona usó el resultado como punto de partida y desarrolló una expresión considerablemente diferente.",
          en: "The person used the output as a starting point and developed a considerably different expression.",
        },
      },
      {
        code: "C5",
        label: { es: "Creación propia a partir de referencias", en: "Own creation from references" },
        description: {
          es: "La persona tomó ideas o referencias ofrecidas por la IA y realizó por sí misma la expresión final.",
          en: "The person took ideas or references offered by AI and produced the final expression themselves.",
        },
        example: { es: "Escribir una novela a partir de una idea surgida en una conversación con IA.", en: "Writing a novel from an idea that arose in a conversation with AI." },
      },
    ],
  },
  D: {
    key: "D",
    name: { es: "Documentación y trazabilidad", en: "Documentation and traceability" },
    question: {
      es: "¿Qué información complementaria se aportó sobre el proceso?",
      en: "What complementary information was provided about the process?",
    },
    notMeaning: {
      es: "No clasifica la veracidad. D3 no implica evidencias auditadas ni auténticas; D0 no implica una declaración falsa ni de menor validez.",
      en: "It does not rate truthfulness. D3 does not mean audited or authentic evidence; D0 does not mean a false or less valid declaration.",
    },
    values: [
      {
        code: "D0",
        label: { es: "Declaración básica", en: "Basic declaration" },
        description: {
          es: "Existe una declaración responsable sin documentación adicional significativa.",
          en: "A responsible declaration exists without significant additional documentation.",
        },
      },
      {
        code: "D1",
        label: { es: "Herramientas y usos identificados", en: "Tools and uses identified" },
        description: {
          es: "Se identifican las herramientas, las funciones y los usos.",
          en: "Tools, functions and uses are identified.",
        },
      },
      {
        code: "D2",
        label: { es: "Proceso creativo descrito en detalle", en: "Creative process described in detail" },
        description: {
          es: "Se documentan además las fases, los resultados, las decisiones y las intervenciones humanas.",
          en: "Phases, results, decisions and human interventions are also documented.",
        },
      },
      {
        code: "D3",
        label: { es: "Evidencias complementarias registradas", en: "Complementary evidence registered" },
        description: {
          es: "Se han registrado evidencias complementarias (versiones, historiales, materiales). No han sido auditadas por ORIVA.",
          en: "Complementary evidence (versions, histories, materials) has been registered. It has not been audited by ORIVA.",
        },
      },
    ],
  },
};

export function getValue(code: string): TaxonomyValue | undefined {
  const dim = DIMENSIONS[code.charAt(0) as DimensionKey];
  return dim?.values.find((v) => v.code === code);
}

/** Fases creativas (v1.0). */
export const CREATIVE_PHASES = [
  "RESEARCH",
  "IDEATION",
  "PLANNING",
  "STRUCTURING",
  "DRAFTING",
  "CONTENT_GENERATION",
  "EVALUATION",
  "CORRECTION",
  "EDITING",
  "TRANSFORMATION",
  "TRANSLATION",
  "POSTPRODUCTION",
  "PUBLICATION",
  "OTHER",
] as const;
export type CreativePhase = (typeof CREATIVE_PHASES)[number];

export const PHASE_LABELS: Record<CreativePhase, Localized> = {
  RESEARCH: { es: "Documentación", en: "Research" },
  IDEATION: { es: "Generación de ideas", en: "Ideation" },
  PLANNING: { es: "Planificación", en: "Planning" },
  STRUCTURING: { es: "Estructuración", en: "Structuring" },
  DRAFTING: { es: "Creación de borradores", en: "Drafting" },
  CONTENT_GENERATION: { es: "Generación de contenido", en: "Content generation" },
  EVALUATION: { es: "Evaluación y crítica", en: "Evaluation and critique" },
  CORRECTION: { es: "Corrección", en: "Correction" },
  EDITING: { es: "Edición", en: "Editing" },
  TRANSFORMATION: { es: "Transformación", en: "Transformation" },
  TRANSLATION: { es: "Traducción", en: "Translation" },
  POSTPRODUCTION: { es: "Posproducción", en: "Post-production" },
  PUBLICATION: { es: "Publicación", en: "Publication" },
  OTHER: { es: "Otras", en: "Other" },
};
