/**
 * Orientación sobre el artículo 50 del Reglamento (UE) 2024/1689.
 *
 * Es una ayuda para reconocer situaciones; NO es una certificación de
 * cumplimiento ni asesoramiento jurídico. Es independiente del código ORIVA:
 * ORIVA Code describe prácticas creativas y no incorpora ninguna puntuación de
 * cumplimiento.
 */
import { z } from "zod";

const tri = z.enum(["YES", "NO", "UNSURE"]);

export const article50AnswersSchema = z.object({
  role: z.enum(["OWN_AUTHORITY", "FOR_ORGANIZATION", "PROVIDER", "UNSURE"]).nullable().default(null),
  organizationName: z.string().trim().max(200).default(""),
  context: z.enum(["PROFESSIONAL", "PERSONAL_NON_PROFESSIONAL", "UNSURE"]).nullable().default(null),
  syntheticMedia: tri.nullable().default(null),
  realisticResemblance: tri.nullable().default(null),
  evidentlyCreative: tri.nullable().default(null),
  publicInterestText: tri.nullable().default(null),
  humanReview: tri.nullable().default(null),
  editorialResponsibility: tri.nullable().default(null),
});
export type Article50Answers = z.infer<typeof article50AnswersSchema>;

export const EMPTY_ARTICLE50: Article50Answers = article50AnswersSchema.parse({});

export type FindingLevel =
  | "POSSIBLE_OBLIGATION"
  | "LIMITED_OBLIGATION"
  | "EXCEPTION_MAY_APPLY"
  | "UNCERTAIN"
  | "INFO"
  | "NOT_IDENTIFIED";

export interface Article50Finding {
  id: string;
  level: FindingLevel;
  title: string;
  text: string;
  references: string[];
}

export interface Article50Result {
  answered: boolean;
  findings: Article50Finding[];
  disclaimer: string;
}

export const ARTICLE50_DISCLAIMER =
  "Esta orientación es meramente informativa, se basa únicamente en las respuestas facilitadas y no constituye asesoramiento jurídico ni una certificación de cumplimiento legal. El artículo 50 se aplica desde el 2 de agosto de 2026. Además de las obligaciones legales pueden existir requisitos de editoriales, concursos, instituciones o contratos, y prácticas voluntarias de transparencia. Consulta a un profesional para tu caso concreto.";

export function assessArticle50(a: Article50Answers): Article50Result {
  const findings: Article50Finding[] = [];
  const answered = Object.entries(a).some(([k, v]) => k !== "organizationName" && v !== null);
  if (!answered) return { answered, findings, disclaimer: ARTICLE50_DISCLAIMER };

  const personal = a.context === "PERSONAL_NON_PROFESSIONAL";
  const mayBeDeployer = !personal && a.role !== "PROVIDER";

  if (a.role === "PROVIDER") {
    findings.push({
      id: "PROVIDER",
      level: "INFO",
      title: "Has indicado que eres proveedor de un sistema de IA",
      text: "Las obligaciones de los proveedores (por ejemplo, el marcado legible por máquina de los resultados sintéticos del art. 50.2) recaen sobre el sistema que desarrollas o comercializas, no sobre una obra concreta. ORIVA no evalúa esas obligaciones.",
      references: ["AIA-3-3", "AIA-50-2"],
    });
  }

  if (a.role === "FOR_ORGANIZATION") {
    findings.push({
      id: "ORG_AUTHORITY",
      level: "INFO",
      title: "El sistema se utilizó bajo la autoridad de una organización",
      text: `Cuando una persona utiliza un sistema de IA por cuenta de una organización${a.organizationName ? ` (${a.organizationName})` : ""}, el responsable del despliegue a efectos del Reglamento puede ser esa organización y no la persona que lo maneja. Conviene que la organización revise sus obligaciones.`,
      references: ["AIA-3-4"],
    });
  }

  if (personal) {
    findings.push({
      id: "PERSONAL_USE",
      level: "INFO",
      title: "Actividad personal de carácter no profesional",
      text: "La definición de «responsable del despliegue» excluye el uso en el marco de una actividad personal de carácter no profesional, por lo que las obligaciones del art. 50.4 dirigidas a los responsables del despliegue podrían no resultar aplicables. Pueden aplicarse, no obstante, requisitos de plataformas, editoriales o concursos.",
      references: ["AIA-3-4"],
    });
  }
  if (a.context === "UNSURE" || a.role === "UNSURE") {
    findings.push({
      id: "ROLE_UNCERTAIN",
      level: "UNCERTAIN",
      title: "No está claro tu papel o el contexto de uso",
      text: "La aplicación del art. 50.4 depende de quién utiliza el sistema bajo su propia autoridad y de si se trata de una actividad profesional. Se recomienda aclararlo.",
      references: ["AIA-3-4"],
    });
  }

  // --- Ultrasuplantaciones (imagen, audio, vídeo) ---
  if (a.syntheticMedia === "YES") {
    if (a.realisticResemblance === "YES") {
      if (mayBeDeployer) {
        if (a.evidentlyCreative === "YES") {
          findings.push({
            id: "DEEPFAKE_CREATIVE",
            level: "LIMITED_OBLIGATION",
            title: "Posible ultrasuplantación en una obra manifiestamente creativa",
            text: "El uso declarado podría estar sujeto a una obligación específica de transparencia del art. 50.4. Para obras manifiestamente artísticas, creativas, satíricas o de ficción, la obligación se limita a revelar la existencia del contenido generado o manipulado de forma adecuada que no dificulte la exhibición o el disfrute de la obra. Se recomienda revisar las condiciones aplicables.",
            references: ["AIA-3-60", "AIA-50-4-1", "AIA-50-5", "EC-COP-AIGC"],
          });
        } else {
          findings.push({
            id: "DEEPFAKE",
            level: a.evidentlyCreative === "UNSURE" ? "UNCERTAIN" : "POSSIBLE_OBLIGATION",
            title: "Posible ultrasuplantación (deep fake)",
            text: "El uso declarado podría estar sujeto a una obligación específica de transparencia del art. 50.4: revelar que el contenido ha sido generado o manipulado artificialmente. Se recomienda revisar las condiciones aplicables.",
            references: ["AIA-3-60", "AIA-50-4-1", "AIA-50-5", "EC-COP-AIGC"],
          });
        }
      }
    } else if (a.realisticResemblance === "NO") {
      findings.push({
        id: "SYNTHETIC_NOT_DEEPFAKE",
        level: "INFO",
        title: "Contenido sintético que no parece una ultrasuplantación",
        text: "No toda imagen, audio o vídeo generado con IA es una ultrasuplantación: la definición exige que se asemeje a personas, objetos, lugares, entidades o sucesos reales y pueda parecer falsamente auténtico. El marcado técnico de los resultados (art. 50.2) corresponde a los proveedores de las herramientas.",
        references: ["AIA-3-60", "AIA-50-2"],
      });
    } else if (a.realisticResemblance === "UNSURE") {
      findings.push({
        id: "RESEMBLANCE_UNCERTAIN",
        level: "UNCERTAIN",
        title: "No está claro si el contenido podría parecer auténtico",
        text: "Si el contenido se asemeja a personas, lugares o sucesos reales y puede inducir a pensar que es auténtico, podría tratarse de una ultrasuplantación sujeta al art. 50.4. Se recomienda revisarlo.",
        references: ["AIA-3-60", "AIA-50-4-1"],
      });
    }
  }

  // --- Texto de interés público ---
  if (a.publicInterestText === "YES" && mayBeDeployer) {
    if (a.humanReview === "YES" && a.editorialResponsibility === "YES") {
      findings.push({
        id: "PUBLIC_TEXT_EXCEPTION",
        level: "EXCEPTION_MAY_APPLY",
        title: "Texto de interés público con revisión humana y responsabilidad editorial",
        text: "La obligación de revelar textos generados o manipulados que informan sobre asuntos de interés público no se aplica cuando el contenido ha sido sometido a revisión humana o control editorial y una persona física o jurídica tiene la responsabilidad editorial. Según tus respuestas, esta excepción podría resultar aplicable.",
        references: ["AIA-50-4-2"],
      });
    } else if (a.humanReview === "UNSURE" || a.editorialResponsibility === "UNSURE" || a.humanReview === null || a.editorialResponsibility === null) {
      findings.push({
        id: "PUBLIC_TEXT_UNCERTAIN",
        level: "UNCERTAIN",
        title: "Texto de interés público: falta información sobre el control editorial",
        text: "Para valorar la excepción del art. 50.4, párrafo segundo, es necesario saber si hubo revisión humana o control editorial y si alguien asume la responsabilidad editorial.",
        references: ["AIA-50-4-2"],
      });
    } else {
      findings.push({
        id: "PUBLIC_TEXT",
        level: "POSSIBLE_OBLIGATION",
        title: "Texto publicado para informar sobre asuntos de interés público",
        text: "El uso declarado podría estar sujeto a una obligación específica de transparencia del art. 50.4: revelar que el texto ha sido generado o manipulado artificialmente. Se recomienda revisar las condiciones aplicables.",
        references: ["AIA-50-4-2", "AIA-50-5", "EC-COP-AIGC"],
      });
    }
  }

  const hasSpecific = findings.some((f) => f.level === "POSSIBLE_OBLIGATION" || f.level === "LIMITED_OBLIGATION" || f.level === "UNCERTAIN");
  if (!hasSpecific) {
    findings.push({
      id: "NOT_IDENTIFIED",
      level: "NOT_IDENTIFIED",
      title: "No se ha identificado uno de los supuestos específicos analizados",
      text: "Con la información facilitada no se ha identificado uno de los supuestos específicos analizados. Esto no equivale a una certificación de cumplimiento legal.",
      references: [],
    });
  }
  return { answered, findings, disclaimer: ARTICLE50_DISCLAIMER };
}

export const FINDING_LEVEL_LABEL: Record<FindingLevel, string> = {
  POSSIBLE_OBLIGATION: "Posible obligación específica",
  LIMITED_OBLIGATION: "Posible obligación limitada",
  EXCEPTION_MAY_APPLY: "Posible excepción",
  UNCERTAIN: "Requiere aclaración",
  INFO: "Información",
  NOT_IDENTIFIED: "Supuesto no identificado",
};
