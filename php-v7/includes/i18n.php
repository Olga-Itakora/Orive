<?php
/**
 * ORIDE v5 — textos en español e inglés.
 * Los códigos son los mismos en los dos idiomas; solo cambian los textos.
 */
declare(strict_types=1);

require_once __DIR__ . '/taxonomy.php';

const LANGS = ['es' => 'Español', 'en' => 'English'];

/** Textos comunes. %s = valor que se inserta (en PHP con sprintf; en JavaScript con fmt()). */
const STR = [
    'es' => [
        'tagline' => 'Toda creación tiene un proceso. Hazlo visible.',
        'descriptor' => 'Originalidad Declarada',
        'site_description' => 'Declara cómo has utilizado la inteligencia artificial en tu obra con un código breve y fácil de entender.',
        'skip' => 'Saltar al contenido',
        'menu' => 'Menú',
        'nav_main' => 'Principal',
        'nav_how' => 'Cómo funciona',
        'nav_code' => 'Código ORIDE',
        'nav_inst' => 'Instituciones',
        'nav_faq' => 'Preguntas frecuentes',
        'nav_create' => 'Crea tu declaración',
        'nav_about' => 'Sobre este sitio',
        'nav_law' => 'Marco normativo',
        'nav_legal' => 'Aviso legal y privacidad',
        'nav_feedback' => '¿Cómo puede mejorar el Código ORIDE?',
        'footer' => 'Pie de página',
        'contact' => 'Contacto',
        'no_cookies' => 'Sin cookies.',
        'home' => 'inicio',
        'trademark' => 'ORIDE Code™ es una marca en trámite de registro. Se permite su uso libre a autores, centros educativos públicos y centros de investigación públicos.',
        'honor' => 'El Código ORIDE es una declaración responsable de autoría basada en el honor, la honestidad y la ética personal. No hay un sistema de verificación ni una certificación detrás.',
        'evidence' => ['No conserva evidencias que documenten lo declarado.', 'Conserva evidencias que documentan lo declarado.'],
        'evidence_note' => 'Añade junto al código un medio para contactar contigo o un enlace a esas evidencias.',
        'evidence_title' => 'Evidencias',
        'scope_line' => 'Parte de la obra afectada: %s',
        'code_label' => 'Código ORIDE',
        'your_code' => 'Tu Código ORIDE',
        'ref_code' => 'Código de referencia',
        'example' => 'Ejemplo',
        'what_means' => 'Qué significa',
        'copy_code' => 'Copiar código',
        'copy_text' => 'Copiar declaración',
        'copy_link' => 'Copiar enlace',
        'copied' => 'Copiado',
        'copy_failed' => 'No se pudo copiar',
        'status_empty' => 'Elige el tipo de obra para empezar.',
        'status_need_ia' => 'Indica si se ha utilizado inteligencia artificial.',
        'status_no_ai' => 'No se utilizó inteligencia artificial.',
        'status_complete' => 'Código listo. Las actividades son opcionales.',
        'pending' => 'pendiente',
        'chip' => 'Tu código:',
        'miss_level' => 'Indica cómo usaste la IA en «%s».',
        'miss_scope' => 'Indica qué parte de la obra está afectada en «%s».',
        'type_line' => 'Tipo de obra: %s',
        'ia0_line' => 'No se utilizó inteligencia artificial.',
        'ia1_line' => 'Se utilizó inteligencia artificial.',
        'text_evidence' => 'Evidencias: %s',
        'link_line' => 'Qué significa: %s',
        'err_form' => 'Un código ORIDE empieza por el tipo de obra y el uso de IA, por ejemplo LIT-IA0-V0 o LIT-IA1-V1-GEN42.',
        'err_type' => '«%s» no es un tipo de obra. Los tipos son SCI, LIT, MUS, PHO, IL, DES, AUD, SW y TRA.',
        'err_ia' => 'Después del tipo de obra va IA0 (sin IA) o IA1 (con IA).',
        'err_ia0_extra' => 'Después de IA0 solo pueden ir las evidencias: V0 o V1.',
        'err_act' => '«%s» no es una actividad de %s.',
        'err_dup' => 'La actividad %s aparece dos veces.',
        'err_digits' => 'En %s faltan las dos cifras: grado de uso (0 a 5) y alcance (0 a 4), por ejemplo %s.',
        'err_level' => 'En %s, el grado de uso va de 0 a 5.',
        'err_pad' => 'En %s, con los grados 0 y 1 la segunda cifra es siempre 0.',
        'err_scope_range' => 'En %s, el alcance va de 0 a 4.',
        'err_v' => 'Las evidencias van justo después de IA0 o IA1: V0 o V1.',
        'miss_ev' => 'Indica si conservas evidencias.',
        'all_zero' => 'Has indicado que usaste IA, pero en todas las actividades marcadas indicas que no la usaste. Explica junto al código en qué usaste la IA, fuera de las actividades establecidas.',
        'all_zero_reader' => 'El autor indica que usó IA, pero no en las actividades detalladas: debe explicarlo junto al código.',
        'q_question' => '¿Qué proporción aproximada del contenido final de la obra procede de material generado mediante IA?',
        'q_hint' => 'Opcional. Afina el Índice Global ORIDE; no cambia el código.',
        'q_bands' => ['Ninguna', 'Menos del 10 %', 'Entre el 10 % y el 25 %', 'Entre el 25 % y el 50 %', 'Entre el 50 % y el 75 %', 'Entre el 75 % y el 99 %', 'La totalidad'],
        'qp_label' => 'Porcentaje más preciso (opcional)',
        'ig_title' => 'Índice Global ORIDE',
        'ig_estimate' => 'Estimación',
        'ig_explain' => 'El Índice Global ORIDE es una estimación calculada a partir de las actividades de IA declaradas, su alcance, el grado de transformación humana y los pesos definidos en la metodología ORIDE. No representa un porcentaje certificado de autoría de IA ni determina la originalidad o titularidad de la obra.',
        'ig_nc' => 'No calculable',
        'ig_nc_no_acts' => 'Para calcularlo, detalla en qué actividades usaste la IA.',
        'ig_nc_all_zero' => 'El uso de IA no está en las actividades marcadas.',
        'ig_nc_config' => 'La metodología actual no tiene pesos para estas actividades.',
        'ig_nc_version' => 'Versión del algoritmo desconocida.',
        'ig_breakdown' => 'Desglose del cálculo',
        'ig_G' => 'Generación efectiva (G)',
        'ig_P' => 'Influencia auxiliar (P)',
        'ig_q' => 'Proporción generada con IA (q)',
        'ig_q_scope' => 'estimada a partir del alcance declarado',
        'ig_q_question' => 'declarada',
        'ig_q_exact' => 'porcentaje indicado',
        'ig_role_gen' => 'generativa (cuenta en G)',
        'ig_role_aux' => 'auxiliar, peso %s',
        'ig_factor' => 'factor %s',
        'ig_scope' => 'alcance %s',
        'ig_contrib' => 'aporta %s a P',
        'ig_version' => 'Algoritmo %s · taxonomía %s · calculado el %s',
        'ig_line' => 'Índice Global ORIDE: %s (%s)',
        'ig_how' => 'Cómo se calcula',
        'ig_download' => 'Descargar el cálculo (JSON)',
        'nav_index' => 'Índice Global ORIDE',
        'v_required' => 'Rellena este campo.',
        'v_email' => 'Escribe un correo válido, por ejemplo nombre@dominio.com.',
        'error_server' => 'Algo ha fallado en el servidor. Vuelve a intentarlo; si persiste, escríbenos a info@itakora.com indicando la referencia %s.',
        'inst_max' => 'El autor debe garantizar que el uso de la IA que hace no supera el siguiente código:',
    ],
    'en' => [
        'tagline' => 'Every creation has a process. Make it visible.',
        'descriptor' => 'Originality Declaration',
        'site_description' => 'Declare how you used artificial intelligence in your work with a short, easy-to-read code.',
        'skip' => 'Skip to content',
        'menu' => 'Menu',
        'nav_main' => 'Main',
        'nav_how' => 'How it works',
        'nav_code' => 'ORIDE Code',
        'nav_inst' => 'Institutions',
        'nav_faq' => 'FAQ',
        'nav_create' => 'Create your declaration',
        'nav_about' => 'About this site',
        'nav_law' => 'Regulatory framework',
        'nav_legal' => 'Legal notice and privacy',
        'nav_feedback' => 'How can the ORIDE Code improve?',
        'footer' => 'Footer',
        'contact' => 'Contact',
        'no_cookies' => 'No cookies.',
        'home' => 'home',
        'trademark' => 'ORIDE Code™ is a trademark pending registration. Free use is permitted for authors, public educational institutions and public research centres.',
        'honor' => 'The ORIDE Code is a responsible declaration of authorship based on honour, honesty and personal ethics. There is no verification system or certification behind it.',
        'evidence' => ['Does not keep evidence documenting what is declared.', 'Keeps evidence documenting what is declared.'],
        'evidence_note' => 'Add a way to contact you, or a link to that evidence, next to the code.',
        'evidence_title' => 'Evidence',
        'scope_line' => 'Share of the work affected: %s',
        'code_label' => 'ORIDE Code',
        'your_code' => 'Your ORIDE Code',
        'ref_code' => 'Reference code',
        'example' => 'Example',
        'what_means' => 'What it means',
        'copy_code' => 'Copy code',
        'copy_text' => 'Copy declaration',
        'copy_link' => 'Copy link',
        'copied' => 'Copied',
        'copy_failed' => 'Could not copy',
        'status_empty' => 'Choose the type of work to begin.',
        'status_need_ia' => 'Say whether artificial intelligence was used.',
        'status_no_ai' => 'No artificial intelligence was used.',
        'status_complete' => 'Code ready. Activities are optional.',
        'pending' => 'pending',
        'chip' => 'Your code:',
        'miss_level' => 'Say how you used AI for “%s”.',
        'miss_scope' => 'Say what share of the work is affected for “%s”.',
        'type_line' => 'Type of work: %s',
        'ia0_line' => 'No artificial intelligence was used.',
        'ia1_line' => 'Artificial intelligence was used.',
        'text_evidence' => 'Evidence: %s',
        'link_line' => 'What it means: %s',
        'err_form' => 'An ORIDE code starts with the type of work and the use of AI, for example LIT-IA0-V0 or LIT-IA1-V1-GEN42.',
        'err_type' => '“%s” is not a type of work. The types are SCI, LIT, MUS, PHO, IL, DES, AUD, SW and TRA.',
        'err_ia' => 'The type of work is followed by IA0 (no AI) or IA1 (AI used).',
        'err_ia0_extra' => 'After IA0 only evidence can follow: V0 or V1.',
        'err_act' => '“%s” is not an activity of %s.',
        'err_dup' => 'Activity %s appears twice.',
        'err_digits' => '%s needs two digits: degree of use (0 to 5) and scope (0 to 4), for example %s.',
        'err_level' => 'In %s, the degree of use goes from 0 to 5.',
        'err_pad' => 'In %s, with degrees 0 and 1 the second digit is always 0.',
        'err_scope_range' => 'In %s, the scope goes from 0 to 4.',
        'err_v' => 'Evidence goes right after IA0 or IA1: V0 or V1.',
        'miss_ev' => 'Say whether you keep evidence.',
        'all_zero' => 'You said you used AI, but every activity you ticked says you did not. Explain next to the code how you used AI outside the listed activities.',
        'all_zero_reader' => 'The author says AI was used, but not in the listed activities: they should explain it next to the code.',
        'q_question' => 'Roughly what share of the final content of the work comes from AI-generated material?',
        'q_hint' => 'Optional. It refines the ORIDE Global Index; it does not change the code.',
        'q_bands' => ['None', 'Less than 10%', 'From 10% to 25%', 'From 25% to 50%', 'From 50% to 75%', 'From 75% to 99%', 'All of it'],
        'qp_label' => 'More precise percentage (optional)',
        'ig_title' => 'ORIDE Global Index',
        'ig_estimate' => 'Estimate',
        'ig_explain' => 'The ORIDE Global Index is an estimate calculated from the declared AI activities, their scope, the degree of human transformation and the weights defined in the ORIDE methodology. It is not a certified percentage of AI authorship and does not determine the originality or ownership of the work.',
        'ig_nc' => 'Not calculable',
        'ig_nc_no_acts' => 'To calculate it, say in which activities you used AI.',
        'ig_nc_all_zero' => 'The AI use is not in the ticked activities.',
        'ig_nc_config' => 'The current methodology has no weights for these activities.',
        'ig_nc_version' => 'Unknown algorithm version.',
        'ig_breakdown' => 'Calculation breakdown',
        'ig_G' => 'Effective generation (G)',
        'ig_P' => 'Auxiliary influence (P)',
        'ig_q' => 'Share generated with AI (q)',
        'ig_q_scope' => 'estimated from the declared scope',
        'ig_q_question' => 'declared',
        'ig_q_exact' => 'percentage given',
        'ig_role_gen' => 'generative (counts in G)',
        'ig_role_aux' => 'auxiliary, weight %s',
        'ig_factor' => 'factor %s',
        'ig_scope' => 'scope %s',
        'ig_contrib' => 'adds %s to P',
        'ig_version' => 'Algorithm %s · taxonomy %s · calculated on %s',
        'ig_line' => 'ORIDE Global Index: %s (%s)',
        'ig_how' => 'How it is calculated',
        'ig_download' => 'Download the calculation (JSON)',
        'nav_index' => 'ORIDE Global Index',
        'v_required' => 'Please fill in this field.',
        'v_email' => 'Enter a valid email, for example name@domain.com.',
        'error_server' => 'Something went wrong on the server. Please try again; if it persists, write to info@itakora.com quoting reference %s.',
        'inst_max' => 'The author must ensure that their use of AI does not exceed the following code:',
    ],
];

/** Idioma de la página: ?lang=es|en; si no se indica, el preferido del navegador; por defecto, español. */
function lang(): string
{
    static $l = null;
    if ($l !== null) return $l;
    $q = $_GET['lang'] ?? '';
    if (is_string($q) && isset(LANGS[$q])) return $l = $q;
    $best = ['es', -1.0];
    foreach (explode(',', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) as $i => $part) {
        if (!preg_match('/^\s*([a-z]{2})(?:-[A-Za-z]+)?\s*(?:;\s*q=([0-9.]+))?/i', $part, $m)) continue;
        $code = strtolower($m[1]);
        $qv = isset($m[2]) ? (float) $m[2] : 1.0 - $i * 0.001;
        if (isset(LANGS[$code]) && $qv > $best[1]) $best = [$code, $qv];
    }
    return $l = $best[0];
}

function en(): bool
{
    return lang() === 'en';
}

/** Texto común traducido. */
function t(string $key, string ...$args): string
{
    $s = STR[lang()][$key] ?? STR['es'][$key] ?? $key;
    return $args ? sprintf($s, ...$args) : $s;
}

/** Nombre de un tipo de obra en el idioma actual. */
function type_name(string $type): string
{
    return WORK_TYPES[$type][lang()] ?? $type;
}

/** Actividades de un tipo: código => [nombre, descripción] en el idioma actual. */
function acts(string $type): array
{
    $i = en() ? 2 : 0;
    return array_map(fn($a) => [$a[$i], $a[$i + 1]], WORK_TYPES[$type]['acts'] ?? []);
}

function levels(): array
{
    return LEVELS[lang()];
}

function scope_labels(): array
{
    return SCOPE[lang()];
}

/**
 * Datos que necesita oride.js: los mensajes del idioma actual y, en las páginas con
 * formulario de código (define('ORIDE_BUILDER', true)), la taxonomía completa.
 */
function js_i18n(): string
{
    $types = [];
    if (defined('ORIDE_BUILDER')) foreach (WORK_TYPES as $k => $_) {
        $gen = array_keys(array_filter(IG_VERSIONS[IG_CURRENT]['weights'][$k]['acts'] ?? [], fn($a) => $a[0] === 'gen'));
        $types[$k] = ['name' => type_name($k), 'acts' => acts($k), 'gen' => $gen];
    }
    return json_encode(['lang' => lang(), 'types' => $types, 'levels' => levels(), 'levelsReader' => LEVELS_READER[lang()], 'scope' => scope_labels(), 'scopeFrom' => SCOPE_FROM, 'str' => STR[lang()]], JSON_UNESCAPED_UNICODE);
}
