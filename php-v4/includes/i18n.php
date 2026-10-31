<?php
/**
 * ORIDE v4 — textos en español e inglés.
 * Las letras y cifras del código son las mismas en los dos idiomas; solo cambian los textos.
 */
declare(strict_types=1);

const LANGS = ['es' => 'Español', 'en' => 'English'];

/** Áreas: [nombre, descripción, nombre en frase, formas de uso 0–5] */
const AREAS = [
    'es' => [
        'D' => ['Consulta de documentación', 'Buscar datos, contexto o referencias.', 'la consulta de documentación', [
            'No utilicé IA', 'Consulté información, pero no la utilicé', 'Utilicé información como referencia y la contrasté independientemente',
            'Utilicé información contrastándola parcialmente', 'Utilicé información con comprobaciones puntuales', 'Utilicé directamente la información sin comprobarla']],
        'I' => ['Generación de ideas', 'Estructuras, enfoques o alternativas.', 'la generación de ideas', [
            'No utilicé IA', 'Consulté propuestas, pero no adopté ninguna', 'Me inspiré en propuestas para crear ideas propias',
            'Desarrollé sustancialmente una idea propuesta por IA', 'Adapté parcialmente una idea propuesta por IA', 'Adopté una idea propuesta por IA prácticamente sin cambios']],
        'E' => ['Estructuración de la obra', 'Composición global, esquemas, organización por capítulos…', 'la estructuración de la obra', [
            'No utilicé IA', 'Consulté estructuras sin adoptar ninguna', 'Elaboré una estructura propia inspirándome en las propuestas',
            'Reorganicé sustancialmente una estructura propuesta', 'Modifiqué algunos elementos de una estructura propuesta', 'Adopté prácticamente toda la estructura propuesta']],
        'C' => ['Generación de contenidos', 'Texto, imagen, música, vídeo u otros elementos.', 'la generación de contenidos', [
            'No utilicé IA', 'Utilicé resultados solo como referencia', 'Reelaboré creativamente el contenido',
            'Transformé sustancialmente el contenido', 'Realicé cambios menores', 'Incorporé el contenido generado prácticamente sin cambios']],
        'R' => ['Revisión del contenido', 'Coherencia, continuidad o argumentación.', 'la revisión del contenido', [
            'No utilicé IA', 'Consulté observaciones sin adoptarlas', 'Usé la crítica para realizar una revisión propia',
            'Incorporé cambios puntuales propuestos', 'Incorporé gran parte de los cambios propuestos', 'Acepté y apliqué directamente las propuestas de revisión']],
        'F' => ['Revisión de forma', 'Ortografía, gramática o estilo.', 'la revisión de forma', [
            'No utilicé IA', 'Consulté sugerencias sin aplicarlas', 'Reescribí personalmente a partir de las propuestas',
            'Reformulé sustancialmente las propuestas', 'Ajusté las correcciones propuestas', 'Acepté las correcciones propuestas sin cambios']],
        'T' => ['Traducción', 'Traducción del texto a otro idioma.', 'la traducción', [
            'No utilicé IA', 'Consulté la traducción sin incorporarla', 'Realicé una traducción propia utilizando la propuesta como referencia',
            'Reelaboré sustancialmente la traducción', 'Corregí parcialmente la traducción', 'Incorporé la traducción sin cambios significativos']],
    ],
    'en' => [
        'D' => ['Documentation research', 'Looking up data, context or references.', 'documentation research', [
            'I did not use AI', 'I consulted information but did not use it', 'I used information as a reference and checked it independently',
            'I used information, checking it partially', 'I used information with occasional checks', 'I used the information directly without checking it']],
        'I' => ['Idea generation', 'Structures, approaches or alternatives.', 'idea generation', [
            'I did not use AI', 'I consulted proposals but adopted none', 'I drew on proposals to create my own ideas',
            'I substantially developed an AI-proposed idea', 'I partially adapted an AI-proposed idea', 'I adopted an AI-proposed idea practically unchanged']],
        'E' => ['Structuring the work', 'Overall composition, outlines, chapter organisation…', 'structuring the work', [
            'I did not use AI', 'I consulted structures without adopting any', 'I built my own structure inspired by the proposals',
            'I substantially reorganised a proposed structure', 'I modified some elements of a proposed structure', 'I adopted practically the whole proposed structure']],
        'C' => ['Content generation', 'Text, image, music, video or other elements.', 'content generation', [
            'I did not use AI', 'I used the results only as a reference', 'I creatively reworked the content',
            'I substantially transformed the content', 'I made minor changes', 'I incorporated the generated content practically unchanged']],
        'R' => ['Content review', 'Coherence, continuity or argument.', 'content review', [
            'I did not use AI', 'I consulted observations without adopting them', 'I used the critique to carry out my own revision',
            'I incorporated specific proposed changes', 'I incorporated most of the proposed changes', 'I accepted and directly applied the proposed revisions']],
        'F' => ['Form review', 'Spelling, grammar or style.', 'form review', [
            'I did not use AI', 'I consulted suggestions without applying them', 'I rewrote it myself based on the proposals',
            'I substantially reworded the proposals', 'I adjusted the proposed corrections', 'I accepted the proposed corrections unchanged']],
        'T' => ['Translation', 'Translating the text into another language.', 'translation', [
            'I did not use AI', 'I consulted the translation without incorporating it', 'I made my own translation using the proposal as a reference',
            'I substantially reworked the translation', 'I partially corrected the translation', 'I incorporated the translation without significant changes']],
    ],
];

const SCOPE = [
    'es' => ['Menos del 5%', 'Del 5% al 25%', 'Del 25% al 50%', 'Del 50% al 75%', 'Del 75% al 100%'],
    'en' => ['Less than 5%', 'From 5% to 25%', 'From 25% to 50%', 'From 50% to 75%', 'From 75% to 100%'],
];

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
        'switch_label' => 'Idioma',
        'honor' => 'El Código ORIDE es una declaración responsable de autoría basada en el honor, la honestidad y la ética personal. No hay un sistema de verificación ni una certificación detrás.',
        'evidence' => ['No conserva evidencias de los usos declarados.', 'Conserva evidencias de los usos declarados.'],
        'evidence_note' => 'Añade junto al código un medio para contactar contigo o un enlace a esas evidencias.',
        'evidence_title' => 'Evidencias',
        'scope_line' => 'Parte de la obra afectada: %s',
        'code_label' => 'Código ORIDE',
        'your_code' => 'Tu Código ORIDE',
        'example' => 'Ejemplo',
        'what_means' => 'Qué significa',
        'copy_code' => 'Copiar código',
        'copy_text' => 'Copiar declaración',
        'copied' => 'Copiado',
        'copy_failed' => 'No se pudo copiar',
        'status_empty' => 'Responde a la primera pregunta para empezar.',
        'status_no_ai' => 'No se utilizó inteligencia artificial.',
        'status_complete' => 'Declaración completa.',
        'pending' => 'pendiente',
        'chip' => 'Tu código:',
        'miss_area' => 'Señala al menos un área.',
        'miss_usage' => 'Indica cómo utilizaste los resultados de la IA para %s.',
        'miss_scope' => 'Indica qué parte de tu obra está afectada por %s.',
        'miss_ev' => 'Indica si conservas evidencias.',
        'text_no_ai' => 'No se utilizó inteligencia artificial en la elaboración de esta obra.',
        'text_evidence' => 'Evidencias: %s',
        'err_form' => 'Un código ORIDE es IA0 o tiene la forma IA1-…-V0 / V1, por ejemplo IA1-I2-C43-V1.',
        'err_area' => '«%s» no es un área válida. Las áreas son D, I, E, C, R, F y T.',
        'err_order' => 'Las áreas van una sola vez y en este orden: D, I, E, C, R, F, T.',
        'err_usage' => 'En %s, la forma de uso va de 0 a 5.',
        'err_scope_missing' => 'En %s falta el alcance: con los usos 3, 4 y 5 se añade una cifra de 0 a 4 (por ejemplo %s).',
        'err_scope_extra' => 'En %s sobra el alcance: solo se indica con los usos 3, 4 y 5.',
        'err_scope_range' => 'En %s, el alcance va de 0 a 4.',
        'v_required' => 'Rellena este campo.',
        'v_email' => 'Escribe un correo válido, por ejemplo nombre@dominio.com.',
        'error_server' => 'Algo ha fallado en el servidor. Vuelve a intentarlo; si persiste, escríbenos a info@itakora.com indicando la referencia %s.',
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
        'switch_label' => 'Language',
        'honor' => 'The ORIDE Code is a responsible declaration of authorship based on honour, honesty and personal ethics. There is no verification system or certification behind it.',
        'evidence' => ['Does not keep evidence of the declared uses.', 'Keeps evidence of the declared uses.'],
        'evidence_note' => 'Add a way to contact you, or a link to that evidence, next to the code.',
        'evidence_title' => 'Evidence',
        'scope_line' => 'Share of the work affected: %s',
        'code_label' => 'ORIDE Code',
        'your_code' => 'Your ORIDE Code',
        'example' => 'Example',
        'what_means' => 'What it means',
        'copy_code' => 'Copy code',
        'copy_text' => 'Copy declaration',
        'copied' => 'Copied',
        'copy_failed' => 'Could not copy',
        'status_empty' => 'Answer the first question to begin.',
        'status_no_ai' => 'No artificial intelligence was used.',
        'status_complete' => 'Declaration complete.',
        'pending' => 'pending',
        'chip' => 'Your code:',
        'miss_area' => 'Select at least one area.',
        'miss_usage' => 'Say how you used the AI results for %s.',
        'miss_scope' => 'Say what part of your work is affected by %s.',
        'miss_ev' => 'Say whether you keep evidence.',
        'text_no_ai' => 'No artificial intelligence was used in creating this work.',
        'text_evidence' => 'Evidence: %s',
        'err_form' => 'An ORIDE code is IA0 or has the form IA1-…-V0 / V1, for example IA1-I2-C43-V1.',
        'err_area' => '“%s” is not a valid area. The areas are D, I, E, C, R, F and T.',
        'err_order' => 'Each area appears once, in this order: D, I, E, C, R, F, T.',
        'err_usage' => 'In %s, the type of use goes from 0 to 5.',
        'err_scope_missing' => 'In %s the scope is missing: uses 3, 4 and 5 add a digit from 0 to 4 (for example %s).',
        'err_scope_extra' => 'In %s the scope should not be there: it is only given with uses 3, 4 and 5.',
        'err_scope_range' => 'In %s, the scope goes from 0 to 4.',
        'v_required' => 'Please fill in this field.',
        'v_email' => 'Enter a valid email, for example name@domain.com.',
        'error_server' => 'Something went wrong on the server. Please try again; if it persists, write to info@itakora.com quoting reference %s.',
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

function areas(): array
{
    return AREAS[lang()];
}

function scope_labels(): array
{
    return SCOPE[lang()];
}

/** Datos que necesita oride.js (taxonomía y mensajes del idioma actual). */
function js_i18n(): string
{
    return json_encode(['lang' => lang(), 'areas' => areas(), 'scope' => scope_labels(), 'str' => STR[lang()]], JSON_UNESCAPED_UNICODE);
}
