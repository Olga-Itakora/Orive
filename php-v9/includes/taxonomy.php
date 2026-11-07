<?php
/**
 * ORIDE v5 — tipos de obra y actividades (fuente: codigos_oride_2.0.xlsx).
 * Los códigos son los mismos en los dos idiomas.
 * Cada actividad: [nombre ES, descripción ES, nombre EN, descripción EN].
 */
declare(strict_types=1);

const WORK_TYPES = [
    'SC' => ['es' => 'Investigación científica', 'en' => 'Scientific research', 'acts' => [
        'DOC' => ['Búsqueda y revisión bibliográfica', 'Localización, análisis y síntesis de publicaciones y antecedentes científicos.', 'Literature search and review', 'Finding, analysing and synthesising publications and prior scientific work.'],
        'HIP' => ['Preguntas e hipótesis', 'Formulación y exploración de preguntas de investigación, hipótesis y variables.', 'Questions and hypotheses', 'Formulating and exploring research questions, hypotheses and variables.'],
        'MET' => ['Diseño metodológico', 'Diseño de estudios, experimentos, protocolos, instrumentos y procedimientos.', 'Methodological design', 'Designing studies, experiments, protocols, instruments and procedures.'],
        'DAT' => ['Recogida y preparación de datos', 'Extracción, etiquetado, limpieza, transformación y generación de datos sintéticos.', 'Data collection and preparation', 'Extracting, labelling, cleaning and transforming data, and generating synthetic data.'],
        'ANA' => ['Análisis de datos', 'Aplicación de técnicas estadísticas, computacionales y de modelado para analizar datos.', 'Data analysis', 'Applying statistical, computational and modelling techniques to analyse data.'],
        'INT' => ['Interpretación de resultados', 'Exploración de hallazgos, identificación de patrones, discusión e interpretación científica.', 'Interpretation of results', 'Exploring findings, identifying patterns, discussion and scientific interpretation.'],
        'RED' => ['Redacción científica', 'Elaboración de secciones, resúmenes, tablas, figuras y otros contenidos científicos.', 'Scientific writing', 'Writing sections, abstracts, tables, figures and other scientific content.'],
        'REV' => ['Revisión científica', 'Evaluación crítica de la metodología, argumentación, resultados, conclusiones y limitaciones.', 'Scientific review', 'Critical evaluation of methodology, argument, results, conclusions and limitations.'],
        'FOR' => ['Revisión formal y presentación', 'Corrección lingüística, referencias, formato y adaptación a normas editoriales.', 'Formal review and presentation', 'Language editing, references, formatting and adapting to publisher guidelines.'],
    ]],
    'LI' => ['es' => 'Literatura', 'en' => 'Literature', 'acts' => [
        'DOC' => ['Documentación', 'Búsqueda de información, datos, fuentes, antecedentes y referencias para elaborar el texto.', 'Research', 'Looking up information, data, sources, background and references for the text.'],
        'IDE' => ['Generación de ideas', 'Exploración de conceptos, argumentos, tramas, personajes, enfoques o alternativas creativas.', 'Idea generation', 'Exploring concepts, arguments, plots, characters, approaches or creative alternatives.'],
        'EST' => ['Estructuración', 'Planificación de capítulos, secciones, escenas y organización global del texto.', 'Structuring', 'Planning chapters, sections, scenes and the overall organisation of the text.'],
        'GEN' => ['Generación de contenido', 'Producción de frases, párrafos, capítulos, diálogos u otros contenidos textuales.', 'Content generation', 'Producing sentences, paragraphs, chapters, dialogue or other text.'],
        'REV' => ['Revisión de contenido', 'Análisis de coherencia, argumentación, continuidad, consistencia y desarrollo de ideas.', 'Content review', 'Analysing coherence, argument, continuity, consistency and development of ideas.'],
        'FOR' => ['Revisión de formato', 'Corrección de ortografía, gramática, puntuación, claridad, estilo y expresión.', 'Form review', 'Correcting spelling, grammar, punctuation, clarity, style and expression.'],
    ]],
    'MU' => ['es' => 'Música y sonido', 'en' => 'Music and sound', 'acts' => [
        'IDE' => ['Ideación musical', 'Exploración de conceptos sonoros, estilos, referencias, motivos y propuestas musicales.', 'Musical ideation', 'Exploring sound concepts, styles, references, motifs and musical proposals.'],
        'COM' => ['Composición', 'Generación o desarrollo de melodías, armonías, ritmos y estructuras musicales.', 'Composition', 'Generating or developing melodies, harmonies, rhythms and musical structures.'],
        'ARR' => ['Arreglos musicales', 'Orquestación, instrumentación, armonización y adaptación de composiciones existentes.', 'Musical arrangement', 'Orchestration, instrumentation, harmonisation and adaptation of existing compositions.'],
        'GEN' => ['Generación sonora', 'Creación de voces, instrumentos, fragmentos musicales, efectos o sonidos sintéticos.', 'Sound generation', 'Creating voices, instruments, musical fragments, effects or synthetic sounds.'],
        'INT' => ['Interpretación', 'Asistencia o generación de interpretaciones vocales e instrumentales y sus características expresivas.', 'Performance', 'Assisting with or generating vocal and instrumental performances and their expressive qualities.'],
        'MIX' => ['Mezcla y producción', 'Procesamiento, ecualización, balance, espacialización y combinación de pistas.', 'Mixing and production', 'Processing, equalisation, balance, spatialisation and combining tracks.'],
        'MAS' => ['Masterización', 'Optimización del sonido final, dinámica, niveles, equilibrio espectral y preparación de distribución.', 'Mastering', 'Optimising the final sound, dynamics, levels, spectral balance and preparing for distribution.'],
        'RES' => ['Restauración de audio', 'Reducción de ruido, reparación, separación de fuentes y recuperación de grabaciones.', 'Audio restoration', 'Noise reduction, repair, source separation and recovery of recordings.'],
    ]],
    'PH' => ['es' => 'Fotografía', 'en' => 'Photography', 'acts' => [
        'PLA' => ['Planificación de la toma', 'Propuestas de encuadre, iluminación, localización, composición y configuración previa.', 'Shot planning', 'Suggestions for framing, lighting, location, composition and camera set-up.'],
        'CAP' => ['Captura asistida', 'Enfoque, exposición, seguimiento, selección de tomas y otras operaciones de captura mediante IA.', 'Assisted capture', 'Focus, exposure, tracking, shot selection and other AI-assisted capture operations.'],
        'REV' => ['Revelado y ajustes técnicos', 'Ajustes de exposición, color, balance de blancos, contraste y procesamiento de archivos fotográficos.', 'Development and technical adjustments', 'Adjusting exposure, colour, white balance, contrast and processing photo files.'],
        'RET' => ['Retoque fotográfico', 'Corrección de imperfecciones, ajustes locales, tratamiento de retratos y modificaciones de apariencia.', 'Photo retouching', 'Correcting imperfections, local adjustments, portrait retouching and changes of appearance.'],
        'MOD' => ['Modificación de elementos', 'Eliminación, sustitución, incorporación o alteración de objetos, personas y partes de una escena.', 'Element modification', 'Removing, replacing, adding or altering objects, people and parts of a scene.'],
        'GEN' => ['Generación visual', 'Creación de imágenes completas o componentes visuales sintéticos mediante IA.', 'Visual generation', 'Creating complete images or synthetic visual components with AI.'],
        'REC' => ['Reconstrucción y mejora', 'Ampliación, completado de regiones, recuperación de detalle, reducción de ruido y mejora de resolución.', 'Reconstruction and enhancement', 'Upscaling, filling in regions, recovering detail, noise reduction and resolution enhancement.'],
    ]],
    'IL' => ['es' => 'Ilustración', 'en' => 'Illustration', 'acts' => [
        'REF' => ['Documentación y referencias', 'Exploración de referentes visuales, estilos, materiales, anatomía y otros recursos.', 'Research and references', 'Exploring visual references, styles, materials, anatomy and other resources.'],
        'IDE' => ['Conceptualización', 'Generación de conceptos visuales, personajes, escenarios y alternativas creativas.', 'Concept development', 'Generating visual concepts, characters, settings and creative alternatives.'],
        'COM' => ['Composición y bocetado', 'Elaboración de bocetos, encuadres, distribución de elementos y organización visual.', 'Composition and sketching', 'Making sketches, framing, arranging elements and visual organisation.'],
        'GEN' => ['Generación de imágenes', 'Creación de ilustraciones completas, personajes, fondos, objetos y otros elementos gráficos.', 'Image generation', 'Creating complete illustrations, characters, backgrounds, objects and other graphic elements.'],
        'TRA' => ['Transformación de imágenes', 'Transformación de bocetos o imágenes existentes, variaciones de estilo y modificaciones visuales.', 'Image transformation', 'Transforming sketches or existing images, style variations and visual changes.'],
        'RET' => ['Retoque y edición', 'Ajuste de colores, iluminación, proporciones, detalles y corrección de imperfecciones.', 'Retouching and editing', 'Adjusting colours, lighting, proportions and details, and correcting imperfections.'],
        'AMP' => ['Ampliación y reconstrucción', 'Extensión del lienzo, completado de regiones, reconstrucción y mejora de resolución.', 'Extension and reconstruction', 'Extending the canvas, filling in regions, reconstruction and resolution enhancement.'],
        'VAR' => ['Variantes y adaptación', 'Generación de versiones para estilos, composiciones, tamaños o soportes diferentes.', 'Variants and adaptation', 'Generating versions for different styles, compositions, sizes or media.'],
        'REV' => ['Revisión visual', 'Evaluación de composición, legibilidad, coherencia estética y adecuación al encargo.', 'Visual review', 'Evaluating composition, readability, aesthetic coherence and fit with the brief.'],
        'FIN' => ['Preparación final', 'Optimización, separación de elementos, preparación de archivos y exportación.', 'Final preparation', 'Optimising, separating elements, preparing files and exporting.'],
    ]],
    'DS' => ['es' => 'Diseño', 'en' => 'Design', 'acts' => [
        'IDE' => ['Conceptualización', 'Definición de propuestas, conceptos, estilos, soluciones y direcciones creativas.', 'Concept development', 'Defining proposals, concepts, styles, solutions and creative directions.'],
        'VAR' => ['Exploración de alternativas', 'Generación y comparación de variantes de diseño, estilos, colores y configuraciones.', 'Exploring alternatives', 'Generating and comparing design variants, styles, colours and configurations.'],
        'COM' => ['Composición visual', 'Distribución de elementos, jerarquía visual, organización espacial y composición.', 'Visual composition', 'Arranging elements, visual hierarchy, spatial organisation and composition.'],
        'GEN' => ['Generación de elementos', 'Producción de gráficos, iconos, logotipos, texturas, modelos y otros recursos visuales.', 'Element generation', 'Producing graphics, icons, logos, textures, models and other visual assets.'],
        'MOD' => ['Edición y transformación', 'Modificación de formas, materiales, colores, proporciones o elementos visuales existentes.', 'Editing and transformation', 'Modifying shapes, materials, colours, proportions or existing visual elements.'],
        'ADA' => ['Adaptación de formatos', 'Adecuación de diseños a distintos tamaños, soportes, medios y contextos de uso.', 'Format adaptation', 'Adapting designs to different sizes, media, channels and contexts of use.'],
        'FIN' => ['Producción final', 'Preparación técnica de archivos, optimización, exportación y generación de entregables.', 'Final production', 'Technical file preparation, optimisation, export and producing deliverables.'],
    ]],
    'AU' => ['es' => 'Audiovisual', 'en' => 'Audiovisual', 'acts' => [
        'GUI' => ['Guion y narrativa', 'Creación o revisión de guiones, diálogos, secuencias y planteamientos narrativos.', 'Script and narrative', 'Writing or revising scripts, dialogue, sequences and narrative approaches.'],
        'STO' => ['Storyboard y previsualización', 'Generación de bocetos de planos, animáticas y propuestas de planificación visual.', 'Storyboard and previsualisation', 'Generating shot sketches, animatics and visual planning proposals.'],
        'GEN' => ['Generación audiovisual', 'Creación de imágenes en movimiento, secuencias, animaciones o escenas sintéticas.', 'Audiovisual generation', 'Creating moving images, sequences, animations or synthetic scenes.'],
        'VOZ' => ['Voces y sonido', 'Generación o transformación de voces, narraciones, sonidos y elementos de audio.', 'Voices and sound', 'Generating or transforming voices, narration, sounds and audio elements.'],
        'MON' => ['Montaje y edición', 'Selección, organización, sincronización y edición de planos y secuencias.', 'Editing and assembly', 'Selecting, organising, synchronising and editing shots and sequences.'],
        'EFX' => ['Efectos visuales', 'Creación, modificación o integración de efectos, elementos y composiciones visuales.', 'Visual effects', 'Creating, modifying or integrating effects, elements and visual composites.'],
        'DOB' => ['Doblaje y subtitulado', 'Traducción, síntesis o adaptación de voces, sincronización labial y creación de subtítulos.', 'Dubbing and subtitling', 'Translating, synthesising or adapting voices, lip-sync and creating subtitles.'],
        'POS' => ['Posproducción', 'Corrección de color, mejora de imagen y sonido, restauración y acabado técnico.', 'Post-production', 'Colour grading, image and sound enhancement, restoration and technical finishing.'],
    ]],
    'SW' => ['es' => 'Software y programación', 'en' => 'Software and programming', 'acts' => [
        'REQ' => ['Análisis de requisitos', 'Identificación, especificación y organización de funcionalidades, restricciones y necesidades.', 'Requirements analysis', 'Identifying, specifying and organising features, constraints and needs.'],
        'ARQ' => ['Diseño y arquitectura', 'Diseño de componentes, interfaces, estructuras de datos y organización del sistema.', 'Design and architecture', 'Designing components, interfaces, data structures and system organisation.'],
        'COD' => ['Generación de código', 'Producción de funciones, clases, módulos, scripts y otros componentes de software.', 'Code generation', 'Producing functions, classes, modules, scripts and other software components.'],
        'REF' => ['Refactorización', 'Reestructuración y mejora del código existente, manteniendo su comportamiento previsto.', 'Refactoring', 'Restructuring and improving existing code while keeping its intended behaviour.'],
        'DEP' => ['Depuración', 'Detección, diagnóstico y propuesta de soluciones para errores de software.', 'Debugging', 'Detecting, diagnosing and proposing fixes for software bugs.'],
        'PRU' => ['Pruebas y validación', 'Generación de pruebas, casos de prueba, datos de ensayo y análisis de resultados.', 'Testing and validation', 'Generating tests, test cases, test data and analysing results.'],
        'DOC' => ['Documentación técnica', 'Elaboración de comentarios, manuales, explicaciones, especificaciones y documentación de API.', 'Technical documentation', 'Writing comments, manuals, explanations, specifications and API documentation.'],
    ]],
    'TR' => ['es' => 'Traducción', 'en' => 'Translation', 'acts' => [
        'DOC' => ['Documentación y terminología', 'Búsqueda de equivalencias terminológicas, referencias culturales, contexto lingüístico y recursos de apoyo a la traducción.', 'Research and terminology', 'Looking up terminology, cultural references, linguistic context and other translation resources.'],
        'INT' => ['Interpretación del original', 'Análisis del significado, ambigüedades, expresiones idiomáticas, referencias culturales y matices del contenido original.', 'Understanding the source', 'Analysing meaning, ambiguities, idioms, cultural references and nuances of the source text.'],
        'GEN' => ['Generación de traducciones', 'Producción mediante IA de traducciones completas o parciales de textos, diálogos u otros contenidos entre idiomas.', 'Translation generation', 'Producing full or partial AI translations of texts, dialogue or other content between languages.'],
        'ADA' => ['Adaptación lingüística y cultural', 'Adaptación de expresiones, referencias culturales, registros, tono y convenciones al público y contexto de destino.', 'Linguistic and cultural adaptation', 'Adapting expressions, cultural references, register, tone and conventions to the target audience and context.'],
        'REV' => ['Revisión de traducciones', 'Evaluación y corrección de fidelidad, precisión terminológica, omisiones, errores de sentido y coherencia respecto al original.', 'Translation review', 'Checking and correcting accuracy, terminology, omissions, mistranslations and consistency with the source.'],
        'STY' => ['Revisión de estilo', 'Mejora de naturalidad, fluidez, claridad, uniformidad del registro y calidad expresiva en el idioma de destino.', 'Style review', 'Improving naturalness, fluency, clarity, consistency of register and expression in the target language.'],
        'LOC' => ['Localización', 'Adaptación de interfaces, videojuegos, aplicaciones, formatos, unidades, convenciones y otros elementos a un mercado o entorno lingüístico.', 'Localisation', 'Adapting interfaces, games, apps, formats, units, conventions and other elements to a market or locale.'],
        'VAL' => ['Validación y control de calidad', 'Comprobación de consistencia global, cumplimiento de glosarios, requisitos lingüísticos y criterios de calidad de la traducción.', 'Validation and quality control', 'Checking overall consistency, glossary compliance, linguistic requirements and quality criteria.'],
    ]],
];

/** Grado de uso de los resultados de la IA en una actividad (0–5). El alcance se pide a partir del grado 2. */
/**
 * Códigos de tipo de obra de versiones anteriores (se siguen leyendo en los enlaces).
 * v5–v7 usaban tres letras; v5 usó también SOF e ILU.
 */
const LEGACY_TYPES = ['SCI' => 'SC', 'LIT' => 'LI', 'MUS' => 'MU', 'PHO' => 'PH', 'ILU' => 'IL', 'DES' => 'DS', 'AUD' => 'AU', 'SOF' => 'SW', 'TRA' => 'TR'];

const LEVELS = [
    'es' => ['No utilicé IA', 'Consulté los resultados, pero no los incorporé', 'Creé algo propio usando los resultados como referencia',
        'Incorporé los resultados transformados sustancialmente', 'Incorporé los resultados transformados parcialmente', 'Incorporé los resultados prácticamente sin cambios'],
    'en' => ['I did not use AI', 'I consulted the results but did not incorporate them', 'I created my own work using the results as a reference',
        'I incorporated the results, substantially transformed', 'I incorporated the results, partially transformed', 'I incorporated the results practically unchanged'],
];

/** Los mismos grados en impersonal, para explicar un código a quien lo lee (oride.php). */
const LEVELS_READER = [
    'es' => ['No se utilizó IA', 'Se consultaron los resultados, pero no se incorporaron', 'Se creó algo propio usando los resultados como referencia',
        'Se incorporaron los resultados transformados sustancialmente', 'Se incorporaron los resultados transformados parcialmente', 'Se incorporaron los resultados prácticamente sin cambios'],
    'en' => ['AI was not used', 'The results were consulted but not incorporated', 'Original work was created using the results as a reference',
        'The results were incorporated, substantially transformed', 'The results were incorporated, partially transformed', 'The results were incorporated practically unchanged'],
];

const SCOPE = [
    'es' => ['Menos del 5%', 'Del 5% al 25%', 'Del 25% al 50%', 'Del 50% al 75%', 'Del 75% al 100%'],
    'en' => ['Less than 5%', 'From 5% to 25%', 'From 25% to 50%', 'From 50% to 75%', 'From 75% to 100%'],
];

const SCOPE_FROM = 2;
