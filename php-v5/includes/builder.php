<?php
/**
 * Formulario del Código ORIDE, compartido por crear.php (autores) e instituciones.php.
 * Funciona sin JavaScript (se envía por GET a la misma página); con JavaScript, oride.js
 * actualiza el código en tiempo real y muestra u oculta las preguntas.
 */
declare(strict_types=1);

/** Atributo data-when y, si la condición no se cumple con las respuestas de la URL, «hidden». */
function when(string $cond): string
{
    [$key, $vals] = explode('=', $cond, 2);
    $gk = str_replace('[]', '', $key); // PHP guarda «acts_LIT[]» como $_GET['acts_LIT']
    $current = isset($_GET[$gk]) && is_array($_GET[$gk]) ? get_list($gk) : [get($gk)];
    $show = (bool) array_intersect(explode(',', $vals), $current);
    return ' data-when="' . h($cond) . '"' . ($show ? '' : ' hidden');
}

function choice(string $name, string $value, string $type, bool $checked, string $label, string $desc = ''): string
{
    return '<label class="choice"><input type="' . $type . '" name="' . h($name) . '" value="' . h($value) . '"' . ($checked ? ' checked' : '') . '><span><span class="choice-label">' . $label . '</span>'
        . ($desc !== '' ? '<span class="choice-desc">' . h($desc) . '</span>' : '') . '</span></label>';
}

/** @param string $mode 'author' | 'inst' */
function builder_form(string $mode, array $in, string $action): string
{
    $inst = $mode === 'inst';
    $yes = tr('Sí', 'Yes');
    $all = implode(',', array_keys(WORK_TYPES));
    $html = '<form class="card create-form" method="get" action="' . u($action) . '#declaracion" data-builder data-mode="' . $mode . '">' . lang_field();

    if ($inst) {
        $limit = get('limit') === 'min' ? 'min' : 'max';
        $html .= '<fieldset class="choices q"><legend>' . h(tr('¿Qué quieres fijar?', 'What do you want to set?')) . '</legend><div class="choice-list inline">'
            . choice('limit', 'max', 'radio', $limit === 'max', h(tr('Un uso máximo', 'A maximum use')), tr('El uso de IA no puede superar el código.', 'AI use must not exceed the code.'))
            . choice('limit', 'min', 'radio', $limit === 'min', h(tr('Un uso mínimo', 'A minimum use')), tr('El uso de IA debe alcanzar al menos el código.', 'AI use must reach at least the code.'))
            . '</div></fieldset>';
    }

    $html .= '<fieldset class="choices q"><legend>' . h(tr('Tipo de obra', 'Type of work')) . '</legend><div class="choice-list types">';
    foreach (WORK_TYPES as $k => $_) $html .= choice('type', $k, 'radio', $in['type'] === $k, '<b class="mono">' . $k . '</b> ' . h(type_name($k)));
    $html .= '</div></fieldset>';

    $html .= '<fieldset class="choices q"' . when("type=$all") . '><legend>' . h($inst ? tr('Uso de inteligencia artificial', 'Use of artificial intelligence') : tr('¿Se ha utilizado inteligencia artificial durante la elaboración de esta obra?', 'Was artificial intelligence used in creating this work?')) . '</legend><div class="choice-list inline">'
        . choice('ia', 'si', 'radio', $in['ia'] === 'si', $inst ? h(tr('Con IA', 'With AI')) . ' <b class="mono">IA1</b>' : $yes . ' <b class="mono">IA1</b>')
        . choice('ia', 'no', 'radio', $in['ia'] === 'no', $inst ? h(tr('Sin IA', 'Without AI')) . ' <b class="mono">IA0</b>' : 'No <b class="mono">IA0</b>')
        . '</div></fieldset>';

    $html .= '<div class="stack-q"' . when('ia=si') . '>';
    $html .= '<fieldset class="choices q"><legend><span class="dim-tag">V</span>' . h($inst
            ? tr('¿Exiges que el autor conserve evidencias del uso de IA? (opcional)', 'Do you require the author to keep evidence of AI use? (optional)')
            : tr('¿Conservas evidencias que permitan documentar los usos de IA que has declarado? (opcional)', 'Do you keep evidence that documents the AI uses you have declared? (optional)')) . '</legend>'
        . ($inst ? '' : '<p class="hint">' . h(tr('Por ejemplo: qué herramientas usaste, historiales de interacción, prompts, respuestas generadas por la IA, versiones de trabajo u otros documentos que muestren cómo tu obra incorporó o no esas propuestas.', 'For example: which AI tools you used, interaction histories, prompts, AI-generated responses, working versions or other documents showing how your work did or did not incorporate those proposals.')) . '</p>')
        . '<div class="choice-list inline">' . choice('ev', 'si', 'radio', $in['ev'] === 'si', $yes . ' <b class="mono">V1</b>') . choice('ev', 'no', 'radio', $in['ev'] === 'no', 'No <b class="mono">V0</b>') . '</div>'
        . ($inst ? '' : '<div class="alert alert-warn"' . when('ev=si') . '>' . h(t('evidence_note')) . '</div>')
        . '</fieldset>';

    $html .= '<div class="optional-head"><h2>' . h(tr('Actividades (opcional)', 'Activities (optional)')) . '</h2><p class="hint">' . h($inst
            ? tr('Fija, si quieres, el grado de uso de la IA en actividades concretas.', 'If you want, set the degree of AI use for specific activities.')
            : tr('Si quieres, detalla en qué actividades usaste la IA y cómo.', 'If you want, say in which activities you used AI and how.')) . '</p></div>';

    foreach (WORK_TYPES as $type => $_) {
        $html .= '<div class="stack-q"' . when("type=$type") . '><fieldset class="choices q"><legend>' . h(tr('Actividades de ', 'Activities in ') . mb_strtolower(type_name($type))) . '</legend><div class="choice-list">';
        foreach (acts($type) as $act => [$name, $desc]) $html .= choice("acts_{$type}[]", $act, 'checkbox', $in['type'] === $type && in_array($act, $in['acts'], true), '<b class="mono">' . $act . '</b> ' . h($name), $desc);
        $html .= '</div></fieldset>';
        foreach (acts($type) as $act => [$name]) {
            $sel = $in['type'] === $type;
            $html .= '<div class="area-block"' . when("acts_{$type}[]=$act") . '>'
                . '<fieldset class="choices q"><legend><span class="dim-tag">' . $act . '</span>' . h($inst ? tr("Grado máximo o mínimo de uso de la IA en «{$name}»", "Maximum or minimum degree of AI use for “{$name}”") : tr("¿Cómo utilizaste los resultados de la IA en «{$name}»?", "How did you use the AI results for “{$name}”?")) . '</legend><div class="choice-list">';
            foreach (($inst ? LEVELS_READER[lang()] : levels()) as $n => $text) $html .= choice("u_{$type}_{$act}", (string) $n, 'radio', $sel && ($in['u'][$act] ?? '') === (string) $n, '<b class="mono">' . $n . '</b> ' . h($text));
            $html .= '</div></fieldset><fieldset class="choices q"' . when("u_{$type}_{$act}=2,3,4,5") . '><legend>' . h(tr('¿Qué parte de la obra está afectada?', 'What share of the work is affected?')) . '</legend><div class="choice-list">';
            foreach (scope_labels() as $n => $text) $html .= choice("s_{$type}_{$act}", (string) $n, 'radio', $sel && ($in['s'][$act] ?? '') === (string) $n, '<b class="mono">' . $n . '</b> ' . h($text));
            $html .= '</div></fieldset></div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';

    $html .= '<div class="no-js-only"><button class="btn btn-primary">' . h(tr('Continuar y ver el código', 'Continue and see the code')) . '</button></div>';
    $html .= '<p class="hint"' . when('ia=no') . '>' . h(tr('Listo. Con IA0 el código termina aquí.', 'Done. With IA0 the code ends here.')) . '</p>';
    return $html . '</form>';
}

/** Frase previa del código en la página de instituciones. */
function inst_intro(): string
{
    return get('limit') === 'min' ? t('inst_min') : t('inst_max');
}
