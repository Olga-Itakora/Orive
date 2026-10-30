/** Modalidades de obra iniciales (gestionables desde administración). */
export interface CategorySeed {
  key: string;
  labelEs: string;
  labelEn: string;
  group: string;
  phaseHints: Record<string, string>;
}

const literary = {
  RESEARCH: "Buscar contexto histórico o datos para la trama",
  IDEATION: "Pedir posibles giros argumentales o títulos",
  STRUCTURING: "Ordenar capítulos o proponer una estructura",
  DRAFTING: "Redactar un primer borrador de un pasaje",
  EVALUATION: "Pedir una lectura crítica del manuscrito",
  CORRECTION: "Corrección ortográfica y gramatical",
  TRANSLATION: "Traducir el texto a otro idioma",
};
const visual = {
  RESEARCH: "Buscar referencias visuales",
  IDEATION: "Explorar composiciones o paletas",
  CONTENT_GENERATION: "Generar una imagen a partir de una descripción",
  TRANSFORMATION: "Ampliar, rellenar o modificar una imagen con IA",
  POSTPRODUCTION: "Eliminar ruido, reencuadrar o retocar",
};
const sound = {
  IDEATION: "Explorar progresiones o motivos",
  CONTENT_GENERATION: "Generar una pista, voz o efecto sonoro",
  POSTPRODUCTION: "Separar pistas, masterizar o limpiar audio",
  TRANSFORMATION: "Transformar timbres o voces",
};
const av = {
  IDEATION: "Desarrollar ideas de guion o storyboard",
  CONTENT_GENERATION: "Generar planos, animaciones o voces",
  POSTPRODUCTION: "Subtitulado, doblaje, corrección de color o efectos",
  TRANSLATION: "Subtítulos o doblaje en otros idiomas",
};
const academic = {
  RESEARCH: "Búsqueda y síntesis de bibliografía",
  STRUCTURING: "Propuesta de estructura del artículo",
  EVALUATION: "Revisión crítica de argumentos",
  CORRECTION: "Corrección lingüística",
  TRANSLATION: "Traducción del resumen o del artículo",
};
const code = {
  IDEATION: "Explorar mecánicas o arquitecturas",
  CONTENT_GENERATION: "Generar código, assets o diálogos",
  EVALUATION: "Revisión de código o pruebas",
  CORRECTION: "Depuración asistida",
};

export const CATEGORIES: CategorySeed[] = [
  { key: "novel", labelEs: "Novela", labelEn: "Novel", group: "Literatura", phaseHints: literary },
  { key: "short_story", labelEs: "Cuento o relato", labelEn: "Short story", group: "Literatura", phaseHints: literary },
  { key: "essay", labelEs: "Ensayo", labelEn: "Essay", group: "Literatura", phaseHints: literary },
  { key: "poetry", labelEs: "Poesía", labelEn: "Poetry", group: "Literatura", phaseHints: literary },
  { key: "screenplay", labelEs: "Guion", labelEn: "Screenplay", group: "Literatura", phaseHints: literary },
  { key: "academic", labelEs: "Publicación académica", labelEn: "Academic publication", group: "Académica y divulgativa", phaseHints: academic },
  { key: "outreach", labelEs: "Publicación divulgativa", labelEn: "Popular science / outreach", group: "Académica y divulgativa", phaseHints: academic },
  { key: "painting", labelEs: "Pintura", labelEn: "Painting", group: "Artes visuales", phaseHints: visual },
  { key: "illustration", labelEs: "Ilustración", labelEn: "Illustration", group: "Artes visuales", phaseHints: visual },
  { key: "graphic_design", labelEs: "Diseño gráfico", labelEn: "Graphic design", group: "Artes visuales", phaseHints: visual },
  { key: "photography", labelEs: "Fotografía", labelEn: "Photography", group: "Artes visuales", phaseHints: visual },
  { key: "music", labelEs: "Música", labelEn: "Music", group: "Música y sonido", phaseHints: sound },
  { key: "sound", labelEs: "Producción sonora", labelEn: "Sound production", group: "Música y sonido", phaseHints: sound },
  { key: "film", labelEs: "Cine", labelEn: "Film", group: "Audiovisual", phaseHints: av },
  { key: "video", labelEs: "Vídeo", labelEn: "Video", group: "Audiovisual", phaseHints: av },
  { key: "animation", labelEs: "Animación", labelEn: "Animation", group: "Audiovisual", phaseHints: av },
  { key: "performing_arts", labelEs: "Artes escénicas", labelEn: "Performing arts", group: "Artes escénicas", phaseHints: { IDEATION: "Ideas de puesta en escena", CONTENT_GENERATION: "Música, proyecciones o textos generados" } },
  { key: "videogame", labelEs: "Videojuego", labelEn: "Video game", group: "Interactivo", phaseHints: code },
  { key: "creative_software", labelEs: "Software creativo", labelEn: "Creative software", group: "Interactivo", phaseHints: code },
  { key: "design_architecture", labelEs: "Diseño y arquitectura", labelEn: "Design and architecture", group: "Diseño", phaseHints: { IDEATION: "Explorar volúmenes o conceptos", CONTENT_GENERATION: "Renders o variantes generadas", EVALUATION: "Análisis de alternativas" } },
  { key: "multimedia", labelEs: "Obra multimedia", labelEn: "Multimedia work", group: "Multimedia", phaseHints: { ...visual, ...sound } },
  { key: "other", labelEs: "Otra modalidad", labelEn: "Other", group: "Otras", phaseHints: {} },
];
