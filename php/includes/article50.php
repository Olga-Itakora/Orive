<?php
/**
 * Orientación sobre el artículo 50 del Reglamento (UE) 2024/1689.
 * Ayuda a reconocer situaciones; NO es una certificación de cumplimiento ni
 * asesoramiento jurídico. Es independiente del código ORIDE.
 */
declare(strict_types=1);

const ORIDE_A50_FIELDS = ['role', 'organizationName', 'context', 'syntheticMedia', 'realisticResemblance', 'evidentlyCreative', 'publicInterestText', 'humanReview', 'editorialResponsibility'];

const ORIDE_A50_DISCLAIMER = 'Esta orientación es meramente informativa, se basa únicamente en las respuestas facilitadas y no constituye asesoramiento jurídico ni una certificación de cumplimiento legal. El artículo 50 se aplica desde el 2 de agosto de 2026. Además de las obligaciones legales pueden existir requisitos de editoriales, concursos, instituciones o contratos, y prácticas voluntarias de transparencia. Consulta a un profesional para tu caso concreto.';

const ORIDE_A50_LEVELS = [
    'POSSIBLE_OBLIGATION' => 'Posible obligación específica',
    'LIMITED_OBLIGATION' => 'Posible obligación limitada',
    'EXCEPTION_MAY_APPLY' => 'Posible excepción',
    'UNCERTAIN' => 'Requiere aclaración',
    'INFO' => 'Información',
    'NOT_IDENTIFIED' => 'Supuesto no identificado',
];

/** Normaliza las respuestas (valores no previstos → null). */
function oride_article50_answers(array $in): array
{
    $tri = ['YES', 'NO', 'UNSURE'];
    $allowed = [
        'role' => ['OWN_AUTHORITY', 'FOR_ORGANIZATION', 'PROVIDER', 'UNSURE'],
        'context' => ['PROFESSIONAL', 'PERSONAL_NON_PROFESSIONAL', 'UNSURE'],
    ];
    $out = [];
    foreach (ORIDE_A50_FIELDS as $k) {
        if ($k === 'organizationName') {
            $out[$k] = mb_substr(trim((string) ($in[$k] ?? '')), 0, 200);
            continue;
        }
        $v = $in[$k] ?? null;
        $out[$k] = in_array($v, $allowed[$k] ?? $tri, true) ? $v : null;
    }
    return $out;
}

function oride_article50(array $raw): array
{
    $a = oride_article50_answers($raw);
    $f = [];
    $answered = (bool) array_filter($a, fn($v, $k) => $k !== 'organizationName' && $v !== null, ARRAY_FILTER_USE_BOTH);
    if (!$answered) return ['answered' => false, 'findings' => [], 'disclaimer' => ORIDE_A50_DISCLAIMER];

    $add = function (string $id, string $level, string $title, string $text, array $refs) use (&$f) {
        $f[] = ['id' => $id, 'level' => $level, 'title' => $title, 'text' => $text, 'references' => $refs];
    };
    $personal = $a['context'] === 'PERSONAL_NON_PROFESSIONAL';
    $deployer = !$personal && $a['role'] !== 'PROVIDER';

    if ($a['role'] === 'PROVIDER') {
        $add('PROVIDER', 'INFO', 'Has indicado que eres proveedor de un sistema de IA', 'Las obligaciones de los proveedores (por ejemplo, el marcado legible por máquina del art. 50.2) recaen sobre el sistema que desarrollas o comercializas, no sobre una obra concreta. ORIDE no evalúa esas obligaciones.', ['AIA-3-3', 'AIA-50-2']);
    }
    if ($a['role'] === 'FOR_ORGANIZATION') {
        $org = $a['organizationName'] !== '' ? " ({$a['organizationName']})" : '';
        $add('ORG_AUTHORITY', 'INFO', 'El sistema se utilizó bajo la autoridad de una organización', "Cuando una persona utiliza un sistema de IA por cuenta de una organización$org, el responsable del despliegue puede ser esa organización y no la persona que lo maneja.", ['AIA-3-4']);
    }
    if ($personal) {
        $add('PERSONAL_USE', 'INFO', 'Actividad personal de carácter no profesional', 'La definición de «responsable del despliegue» excluye el uso en una actividad personal no profesional, por lo que las obligaciones del art. 50.4 podrían no aplicarse. Pueden aplicarse requisitos de plataformas, editoriales o concursos.', ['AIA-3-4']);
    }
    if ($a['context'] === 'UNSURE' || $a['role'] === 'UNSURE') {
        $add('ROLE_UNCERTAIN', 'UNCERTAIN', 'No está claro tu papel o el contexto de uso', 'La aplicación del art. 50.4 depende de quién utiliza el sistema bajo su propia autoridad y de si se trata de una actividad profesional.', ['AIA-3-4']);
    }
    if ($a['syntheticMedia'] === 'YES') {
        if ($a['realisticResemblance'] === 'YES' && $deployer) {
            if ($a['evidentlyCreative'] === 'YES') {
                $add('DEEPFAKE_CREATIVE', 'LIMITED_OBLIGATION', 'Posible ultrasuplantación en una obra manifiestamente creativa', 'El uso declarado podría estar sujeto a una obligación específica de transparencia del art. 50.4. En obras manifiestamente artísticas, creativas, satíricas o de ficción, la obligación se limita a revelar la existencia del contenido generado o manipulado de forma que no dificulte la exhibición o el disfrute de la obra. Se recomienda revisar las condiciones aplicables.', ['AIA-3-60', 'AIA-50-4-1', 'AIA-50-5']);
            } else {
                $add('DEEPFAKE', $a['evidentlyCreative'] === 'UNSURE' ? 'UNCERTAIN' : 'POSSIBLE_OBLIGATION', 'Posible ultrasuplantación (deep fake)', 'El uso declarado podría estar sujeto a una obligación específica de transparencia del art. 50.4: revelar que el contenido ha sido generado o manipulado artificialmente. Se recomienda revisar las condiciones aplicables.', ['AIA-3-60', 'AIA-50-4-1', 'AIA-50-5']);
            }
        } elseif ($a['realisticResemblance'] === 'NO') {
            $add('SYNTHETIC_NOT_DEEPFAKE', 'INFO', 'Contenido sintético que no parece una ultrasuplantación', 'No toda imagen, audio o vídeo generado con IA es una ultrasuplantación: debe asemejarse a personas, objetos, lugares, entidades o sucesos reales y poder parecer falsamente auténtico. El marcado técnico (art. 50.2) corresponde a los proveedores de las herramientas.', ['AIA-3-60', 'AIA-50-2']);
        } elseif ($a['realisticResemblance'] === 'UNSURE') {
            $add('RESEMBLANCE_UNCERTAIN', 'UNCERTAIN', 'No está claro si el contenido podría parecer auténtico', 'Si se asemeja a personas, lugares o sucesos reales y puede inducir a pensar que es auténtico, podría tratarse de una ultrasuplantación sujeta al art. 50.4.', ['AIA-3-60', 'AIA-50-4-1']);
        }
    }
    if ($a['publicInterestText'] === 'YES' && $deployer) {
        if ($a['humanReview'] === 'YES' && $a['editorialResponsibility'] === 'YES') {
            $add('PUBLIC_TEXT_EXCEPTION', 'EXCEPTION_MAY_APPLY', 'Texto de interés público con revisión humana y responsabilidad editorial', 'La obligación de revelar textos que informan sobre asuntos de interés público no se aplica si el contenido ha sido sometido a revisión humana o control editorial y una persona asume la responsabilidad editorial. Según tus respuestas, esta excepción podría aplicarse.', ['AIA-50-4-2']);
        } elseif (in_array($a['humanReview'], ['UNSURE', null], true) || in_array($a['editorialResponsibility'], ['UNSURE', null], true)) {
            $add('PUBLIC_TEXT_UNCERTAIN', 'UNCERTAIN', 'Texto de interés público: falta información sobre el control editorial', 'Para valorar la excepción del art. 50.4, párrafo segundo, hay que saber si hubo revisión humana o control editorial y si alguien asume la responsabilidad editorial.', ['AIA-50-4-2']);
        } else {
            $add('PUBLIC_TEXT', 'POSSIBLE_OBLIGATION', 'Texto publicado para informar sobre asuntos de interés público', 'El uso declarado podría estar sujeto a una obligación específica de transparencia del art. 50.4: revelar que el texto ha sido generado o manipulado artificialmente. Se recomienda revisar las condiciones aplicables.', ['AIA-50-4-2', 'AIA-50-5']);
        }
    }
    $specific = array_filter($f, fn($x) => in_array($x['level'], ['POSSIBLE_OBLIGATION', 'LIMITED_OBLIGATION', 'UNCERTAIN'], true));
    if (!$specific) {
        $add('NOT_IDENTIFIED', 'NOT_IDENTIFIED', 'No se ha identificado uno de los supuestos específicos analizados', 'Con la información facilitada no se ha identificado uno de los supuestos específicos analizados. Esto no equivale a una certificación de cumplimiento legal.', []);
    }
    return ['answered' => true, 'findings' => $f, 'disclaimer' => ORIDE_A50_DISCLAIMER];
}

/** Referencias normativas (copia inicial; gestionables en la tabla legal_references). */
function oride_legal_references(): array
{
    $aia = 'https://eur-lex.europa.eu/eli/reg/2024/1689/oj';
    return [
        'AIA-3-3' => ['Definición de «proveedor»', 'Reglamento (UE) 2024/1689, art. 3, punto 3', $aia],
        'AIA-3-4' => ['Definición de «responsable del despliegue»', 'Reglamento (UE) 2024/1689, art. 3, punto 4', $aia],
        'AIA-3-60' => ['Definición de «ultrasuplantación»', 'Reglamento (UE) 2024/1689, art. 3, punto 60', $aia],
        'AIA-50-2' => ['Marcado de contenidos sintéticos (proveedores)', 'Reglamento (UE) 2024/1689, art. 50.2', $aia],
        'AIA-50-4-1' => ['Ultrasuplantaciones: obligación de revelar', 'Reglamento (UE) 2024/1689, art. 50.4, párrafo primero', $aia],
        'AIA-50-4-2' => ['Texto sobre asuntos de interés público', 'Reglamento (UE) 2024/1689, art. 50.4, párrafo segundo', $aia],
        'AIA-50-5' => ['Forma y momento de la información', 'Reglamento (UE) 2024/1689, art. 50.5', $aia],
        'AIA-113' => ['Entrada en vigor y aplicación', 'Reglamento (UE) 2024/1689, art. 113', $aia],
        'EU-2026-1744' => ['Reglamento «Digital Omnibus on AI»', 'Reglamento (UE) 2026/1744 (verificar en el DOUE)', 'https://eur-lex.europa.eu/eli/reg/2026/1744/oj'],
        'EC-COP-AIGC' => ['Código de prácticas sobre contenido generado por IA', 'Comisión Europea — Code of Practice on Transparency of AI-generated Content', 'https://digital-strategy.ec.europa.eu/en/policies/code-practice-ai-generated-content'],
        'EC-ART50-FAQ' => ['Preguntas frecuentes sobre el art. 50', 'Comisión Europea — FAQ art. 50', 'https://digital-strategy.ec.europa.eu/en/faqs/transparency-obligations-under-article-50-ai-act'],
    ];
}
