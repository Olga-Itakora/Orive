/** Etiquetas legibles (es) para valores enumerados del dominio. */

export const COMPONENT_KIND_LABEL: Record<string, string> = {
  TEXT: "Texto",
  IMAGE: "Imagen / ilustración",
  AUDIO: "Audio / música",
  VIDEO: "Vídeo / animación",
  CODE: "Código",
  INTERACTIVE: "Diseño interactivo",
  OTHER: "Otro",
};

export const WORK_STATUS_LABEL: Record<string, string> = {
  IN_PROGRESS: "En proceso",
  FINISHED: "Terminada (inédita)",
  PUBLISHED: "Publicada",
};

export const SCOPE_LABEL: Record<string, string> = {
  WHOLE_WORK: "La obra completa",
  COMPONENTS: "Componentes concretos de la obra",
  OWN_CONTRIBUTION: "Solo mi contribución",
};

export const AI_USAGE_LABEL: Record<string, string> = {
  NO: "No se utilizó IA",
  YES: "Sí se utilizó IA",
  UNCERTAIN: "No puede determinarse con certeza",
  OWN_CONTRIBUTION_ONLY: "Solo puede declararse sobre la propia contribución",
};

export const INCORPORATION_LABEL: Record<string, string> = {
  NONE: "No se incorporó nada a la obra",
  INCORPORATED: "Se incorporó algo a la obra",
  UNKNOWN: "No se puede precisar",
};

export const INTERVENTION_ANSWER_LABEL: Record<string, string> = {
  ACCEPTED: "Lo seleccioné o acepté sin cambios significativos",
  MINOR_EDITS: "Hice ajustes menores",
  TRANSFORMED: "Lo transformé",
  OWN_EXPRESSION: "Tomé la idea o referencia y realicé yo la expresión final",
  UNSURE: "No puedo precisarlo",
};

export const TRI_LABEL: Record<string, string> = { YES: "Sí", NO: "No", UNSURE: "No lo sé" };

export const EVIDENCE_KIND_LABEL: Record<string, string> = {
  VERSION_HISTORY: "Historial de versiones",
  CONVERSATION_LOG: "Registro de conversación con la IA",
  WORK_MATERIAL: "Material de trabajo",
  SCREENSHOT: "Captura de pantalla",
  OTHER: "Otra",
};

export const DOC_STATUS_LABEL: Record<string, string> = {
  DRAFT: "Borrador",
  ISSUED: "Emitida",
  SUPERSEDED: "Sustituida",
  REVOKED: "Revocada",
};

export const VISIBILITY_LABEL: Record<string, string> = {
  PRIVATE: "Privada",
  PUBLIC: "Pública",
  WITHDRAWN: "Retirada de publicación",
};

export const PROFILE_STATUS_LABEL: Record<string, string> = {
  COMPLETE: "Clasificación completa",
  PARTIAL: "Clasificación parcial (información insuficiente en algún uso)",
  INSUFFICIENT: "Información insuficiente",
  UNDETERMINED: "Uso de IA no determinado",
  PENDING: "Clasificación pendiente",
};

export const SYSTEM_TYPES = [
  "Modelo de lenguaje / asistente conversacional",
  "Generador de imágenes",
  "Generador de música o audio",
  "Generador de vídeo",
  "Traducción automática",
  "Corrector o asistente de escritura",
  "Herramienta de edición con funciones de IA",
  "Asistente de código",
  "Otro / no lo sé",
];

export const ROLE_SUGGESTIONS = [
  "Autor/a",
  "Coautor/a",
  "Ilustrador/a",
  "Fotógrafo/a",
  "Compositor/a",
  "Guionista",
  "Director/a",
  "Traductor/a",
  "Editor/a",
  "Investigador/a",
  "Desarrollador/a",
  "Diseñador/a",
];
