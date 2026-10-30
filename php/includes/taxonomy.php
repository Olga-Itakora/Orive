<?php
/**
 * ORIDE Code v1.0 — taxonomía.
 *
 * Los números son identificadores de categoría. No son puntuaciones,
 * porcentajes ni magnitudes comparables.
 */
declare(strict_types=1);

const ORIDE_SCHEME_VERSION = '1.0';

function oride_dimensions(): array
{
    static $d = null;
    if ($d !== null) {
        return $d;
    }
    $d = [
        'A' => [
            'name' => 'Función de la IA',
            'question' => '¿Para qué se utilizó la IA?',
            'not' => 'No mide intensidad de uso: A5 no es «más uso» que A3; son funciones distintas.',
            'values' => [
                'A0' => ['No utilización de IA', 'El declarante afirma que no se utilizaron sistemas de IA en el ámbito declarado.', null],
                'A1' => ['Consulta, documentación o exploración', 'La IA se usó para consultar, documentarse o explorar información.', 'Preguntar por el contexto histórico de una época.'],
                'A2' => ['Generación de ideas, sugerencias o alternativas', 'La IA propuso ideas, sugerencias o alternativas.', 'Pedir posibles giros argumentales.'],
                'A3' => ['Revisión, crítica o evaluación', 'La IA revisó, criticó o evaluó material existente.', 'Solicitar una lectura crítica de un ensayo.'],
                'A4' => ['Corrección, edición o transformación', 'La IA corrigió, editó, tradujo o transformó material existente.', 'Corrección ortográfica; traducción; limpieza de audio.'],
                'A5' => ['Generación de contenido creativo', 'La IA generó contenido: texto, imagen, música, sonido, vídeo, código u otros elementos.', 'Generar una ilustración a partir de una descripción.'],
            ],
        ],
        'B' => [
            'name' => 'Tipo de aportación incorporada',
            'question' => '¿Qué tipo de aportación procedente de IA está presente en la obra final?',
            'not' => 'No describe cuánto modificó la persona esa aportación (eso es la dimensión C).',
            'values' => [
                'B0' => ['Ninguna aportación incorporada', 'La IA pudo usarse, pero ninguna de sus propuestas o resultados se incorporó a la obra.', 'Se descartaron todas las observaciones de una revisión.'],
                'B1' => ['Aportación conceptual', 'Se incorporaron ideas, sugerencias, estructuras o conceptos de IA, desarrollados después por la persona.', 'La IA propone una estructura narrativa y el autor redacta la novela.'],
                'B2' => ['Aportación formal', 'Se incorporaron correcciones, reformulaciones, traducciones o cambios de estilo hechos con IA.', 'Se aceptan correcciones gramaticales sugeridas.'],
                'B3' => ['Aportación de contenido expresivo', 'Se incorporaron textos, imágenes, música, sonidos, vídeos, código creativo u otros elementos expresivos generados con IA.', 'Una ilustración generada con IA aparece en el libro.'],
            ],
        ],
        'C' => [
            'name' => 'Modalidad de intervención humana',
            'question' => '¿Qué hizo la persona con la aportación de IA?',
            'not' => 'No mide mérito artístico, originalidad, esfuerzo ni porcentaje de autoría. C5 no es «más autoría» que C4.',
            'values' => [
                'C0' => ['No aplicable', 'No se incorporaron resultados de IA que requieran describir una intervención posterior.', null],
                'C1' => ['Selección o aceptación', 'La persona seleccionó o aceptó un resultado sin modificarlo significativamente. Seleccionar puede ser una decisión creativa relevante.', 'Se incorpora una ilustración generada sin cambios.'],
                'C2' => ['Edición puntual', 'La persona hizo ajustes menores sobre el resultado.', 'Retocar colores o cambiar algunas palabras.'],
                'C3' => ['Transformación sustancial', 'La persona modificó considerablemente el resultado, aunque se mantienen elementos reconocibles del original.', null],
                'C4' => ['Reelaboración creativa', 'La persona usó el resultado como punto de partida y desarrolló una expresión considerablemente diferente.', null],
                'C5' => ['Creación propia a partir de referencias', 'La persona tomó ideas o referencias de la IA y realizó por sí misma la expresión final.', 'Escribir una novela a partir de una idea surgida en una conversación con IA.'],
            ],
        ],
        'D' => [
            'name' => 'Documentación y trazabilidad',
            'question' => '¿Qué información complementaria se aportó sobre el proceso?',
            'not' => 'No clasifica la veracidad. D3 no implica evidencias auditadas; D0 no implica una declaración falsa ni de menor validez.',
            'values' => [
                'D0' => ['Declaración básica', 'Existe una declaración responsable sin documentación adicional significativa.', null],
                'D1' => ['Herramientas y usos identificados', 'Se identifican las herramientas, las funciones y los usos.', null],
                'D2' => ['Proceso creativo descrito en detalle', 'Se documentan además las fases, los resultados, las decisiones y las intervenciones humanas.', null],
                'D3' => ['Evidencias complementarias registradas', 'Se han registrado evidencias complementarias. No han sido auditadas por ORIDE.', null],
            ],
        ],
    ];
    return $d;
}

function oride_label(string $code): string
{
    $dims = oride_dimensions();
    return $dims[$code[0] ?? '']['values'][$code][0] ?? $code;
}

function oride_description(string $code): string
{
    $dims = oride_dimensions();
    return $dims[$code[0] ?? '']['values'][$code][1] ?? '';
}

const ORIDE_PHASES = [
    'RESEARCH' => 'Documentación',
    'IDEATION' => 'Generación de ideas',
    'PLANNING' => 'Planificación',
    'STRUCTURING' => 'Estructuración',
    'DRAFTING' => 'Creación de borradores',
    'CONTENT_GENERATION' => 'Generación de contenido',
    'EVALUATION' => 'Evaluación y crítica',
    'CORRECTION' => 'Corrección',
    'EDITING' => 'Edición',
    'TRANSFORMATION' => 'Transformación',
    'TRANSLATION' => 'Traducción',
    'POSTPRODUCTION' => 'Posproducción',
    'PUBLICATION' => 'Publicación',
    'OTHER' => 'Otras',
];

/** Catálogo de reglas (referenciadas en rules_applied). */
const ORIDE_RULES = [
    'R-A-01' => 'Los valores A de un uso son las funciones declaradas (A1–A5). Se conservan todas.',
    'R-A-02' => 'Con varias funciones y una principal identificada, el código compacto usa la función principal.',
    'R-A-03' => 'A0 solo es válido en declaraciones sin utilización de IA.',
    'R-A-04' => 'Varias funciones sin función principal: perfil multifunción, sin código compacto. Nunca se elige el número mayor.',
    'R-B-01' => 'Si no se incorporó nada del uso, B = B0.',
    'R-B-02' => 'Si se incorporó algo, B toma los tipos declarados (B1, B2, B3), al menos uno.',
    'R-B-03' => 'B0 es incompatible con cualquier otro valor B en el mismo uso.',
    'R-B-04' => 'Un tipo de aportación no puede repetirse en un uso; los resultados heterogéneos se dividen en subusos.',
    'R-B-05' => 'Si no se puede precisar si se incorporó algo: información insuficiente, sin código.',
    'R-B-06' => 'La pregunta sobre incorporación es esencial: sin respuesta, la clasificación queda pendiente.',
    'R-BC-01' => 'B0 implica C0 en el mismo uso.',
    'R-C-01' => '«Lo seleccioné o acepté sin cambios significativos» → C1.',
    'R-C-02' => '«Hice ajustes menores» → C2.',
    'R-C-03' => '«Lo transformé» y se reconocen elementos del original → C3.',
    'R-C-04' => '«Lo transformé» y la expresión es considerablemente diferente → C4.',
    'R-C-05' => '«Tomé la idea y realicé yo la expresión final» → C5.',
    'R-C-06' => '«No puedo precisarlo» → intervención no determinada (información insuficiente).',
    'R-C-07' => 'Una transformación exige precisar si se reconocen elementos del original (C3 o C4).',
    'R-C-08' => 'Cada aportación incorporada exige indicar la intervención humana.',
    'W-BC-01' => 'B3 con C5: si solo se tomó la idea, la aportación puede ser conceptual (B1).',
    'W-BC-02' => 'B2 con C4/C5: combinación poco habitual; revisar.',
    'W-AB-01' => 'B3 sin A5 ni A4: revisar las funciones declaradas.',
    'R-D-00' => 'D0: declaración básica.',
    'R-D-01' => 'D1: herramienta identificada, al menos una función y actividad descrita (≥10 caracteres).',
    'R-D-02' => 'D2: D1 + fase, resultado (≥10), incorporación respondida e intervención descrita en cada aportación (≥10).',
    'R-D-03' => 'D3: D2 + al menos una evidencia del uso o de la declaración.',
    'R-D-MIN' => 'La D de la declaración es la D mínima de sus usos.',
    'R-D-NOAI' => 'Sin IA o uso indeterminado: D0; D2 con relato del proceso (≥100 caracteres); D3 si además hay evidencias.',
    'R-CODE-01' => 'Código compacto de un uso: solo con una función compacta y una única pareja B–C completa.',
    'R-G-00' => 'La pregunta general sobre el uso de IA es esencial.',
    'R-G-01' => 'Sin IA: A0B0C0 + D según R-D-NOAI.',
    'R-G-02' => 'Una declaración sin IA no puede contener usos.',
    'R-G-03' => 'Uso de IA indeterminado: se registra la incertidumbre, sin código.',
    'R-G-04' => 'Una declaración con IA exige al menos un uso.',
    'R-G-05' => 'Perfil compuesto: unión de valores presentes; código global solo si todos los usos coinciden.',
];
