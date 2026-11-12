<?php
/**
 * Formulario del Código ORIDE, compartido por crear.php (autores) e instituciones.php.
 * Funciona sin JavaScript (se envía por GET a la misma página); con JavaScript, oride.js
 * actualiza el código en tiempo real y muestra u oculta las preguntas.
 */
declare(strict_types=1);

/** Respuestas con las que se decide qué preguntas se ven (las del formulario, no solo las de la URL). null: las de la URL. */
$GLOBALS['ORIDE_WHEN'] = null;

/** Atributo data-when y, si la condición no se cumple con las respuestas actuales, «hidden». */
function when(string $cond): string
{
    [$key, $vals] = explode('=', $cond, 2);
    $state = $GLOBALS['ORIDE_WHEN'];
    if ($state !== null) {
        $current = (array) ($state[$key] ?? []);
    } else {
        $gk = str_replace('[]', '', $key);
        $current = isset($_GET[$gk]) && is_array($_GET[$gk]) ? get_list($gk) : [get($gk)];
    }
    $show = (bool) array_intersect(explode(',', $vals), array_map('strval', $current));
    return ' data-when="' . h($cond) . '"' . ($show ? '' : ' hidden');
}

/** Opción con aspecto de botón: el radio está oculto y la etiqueta es el botón (v10). */
function opt(string $name, string $value, bool $checked, string $label, string $class = 'opt'): string
{
    $id = preg_replace('/[^A-Za-z0-9_-]/', '-', $name . '-' . $value);
    return '<span class="' . $class . '"><input type="radio" name="' . h($name) . '" id="' . h($id) . '" value="' . h($value) . '"' . ($checked ? ' checked' : '') . '><label for="' . h($id) . '">' . h($label) . '</label></span>';
}

function choice(string $name, string $value, string $type, bool $checked, string $label, string $desc = ''): string
{
    return '<label class="choice"><input type="' . $type . '" name="' . h($name) . '" value="' . h($value) . '"' . ($checked ? ' checked' : '') . '><span><span class="choice-label">' . $label . '</span>'
        . ($desc !== '' ? '<span class="choice-desc">' . h($desc) . '</span>' : '') . '</span></label>';
}

/** Alcance en forma corta para las pastillas: «Del 5% al 25%» → «5% – 25%». */
function scope_short(string $label): string
{
    return preg_replace(['/^(Del|From) /', '/ (al|to) /'], ['', ' – '], $label);
}

/**
 * Formulario (v10): tipo de obra, uso de IA y evidencias como botones; cada actividad con un desplegable
 * («No utilicé IA» por defecto) y, si se incorporaron resultados, el alcance en pastillas.
 * @param string $mode 'author' | 'inst'
 */
function builder_form(string $mode, array $in, string $action): string
{
    $inst = $mode === 'inst';
    $yes = tr('Sí', 'Yes');
    $state = ['type' => $in['type'], 'ia' => $in['ia'], 'ev' => $in['ev']];
    foreach ($in['u'] as $a => $x) $state['u_' . $in['type'] . '_' . $a] = $x;
    $GLOBALS['ORIDE_WHEN'] = $state;
    // Las preguntas y las opciones no muestran códigos: el código aparece solo en la caja.
    $html = '<form class="card create-form" method="get" action="' . u($action) . '#declaracion" data-builder data-mode="' . $mode . '">' . lang_field();

    $html .= '<fieldset class="q" id="q-type"><legend>' . h(tr('Tipo de obra', 'Type of work')) . '</legend><div class="opts">';
    foreach (WORK_TYPES as $k => $_) $html .= opt('type', $k, $in['type'] === $k, type_name($k));
    $html .= '</div></fieldset>';

    $html .= '<fieldset class="q" id="q-ia"' . when('type=' . implode(',', array_keys(WORK_TYPES))) . '><legend>' . h($inst ? tr('Uso de inteligencia artificial', 'Use of artificial intelligence') : tr('¿Se ha utilizado inteligencia artificial durante la elaboración de esta obra?', 'Was artificial intelligence used in creating this work?')) . '</legend><div class="opts opts-two">'
        . opt('ia', 'si', $in['ia'] === 'si', $inst ? tr('Se permite usar IA', 'AI use is allowed') : $yes)
        . opt('ia', 'no', $in['ia'] === 'no', $inst ? tr('No se permite usar IA', 'AI use is not allowed') : 'No')
        . '</div></fieldset>';

    $html .= '<fieldset class="q" id="q-ev"' . when('ia=si,no') . '><legend>';
    if ($inst) {
        $html .= h(tr('¿Exiges que el autor conserve evidencias de su proceso?', 'Do you require the author to keep evidence of their process?'));
    } else {
        $html .= '<span' . when('ia=si') . '>' . h(tr('¿Conservas evidencias que permitan documentar los usos de IA que has declarado?', 'Do you keep evidence that documents the AI uses you have declared?')) . '</span>'
            . '<span' . when('ia=no') . '>' . h(tr('¿Conservas evidencias que permitan documentar que no has usado IA?', 'Do you keep evidence that documents that you did not use AI?')) . '</span>';
    }
    $html .= '</legend>';
    if (!$inst) {
        $html .= '<p class="hint"' . when('ia=si') . '>' . h(tr('Por ejemplo: qué herramientas usaste, historiales de interacción, prompts, respuestas de la IA o versiones de trabajo.', 'For example: which tools you used, interaction histories, prompts, AI responses or working versions.')) . '</p>'
            . '<p class="hint"' . when('ia=no') . '>' . h(tr('Por ejemplo: borradores, versiones de trabajo, notas o archivos fechados que muestren tu proceso de creación.', 'For example: drafts, working versions, notes or dated files that show your creative process.')) . '</p>';
    }
    $html .= '<div class="opts opts-two">' . opt('ev', 'si', $in['ev'] === 'si', $yes) . opt('ev', 'no', $in['ev'] === 'no', 'No') . '</div>'
        . ($inst ? '' : '<p class="hint ev-note"' . when('ev=si') . '>' . h(t('evidence_note')) . '</p>')
        . '</fieldset>';

    $html .= '<section class="q" id="q-acts" aria-labelledby="acts-title"' . when('ia=si') . '><h2 id="acts-title">' . h(tr('Actividades (opcional)', 'Activities (optional)')) . '</h2>'
        . '<p class="hint">' . h($inst
            ? tr('Fija, si quieres, el uso máximo de la IA en actividades concretas. Las que dejes en «No se utilizó IA» no se permiten.', 'If you want, set the maximum AI use for specific activities.')
            : tr('Indica cómo usaste la IA en cada actividad. Las que dejes en «No utilicé IA» no aparecen en el código.', 'Say how you used AI in each activity. Those left at “I did not use AI” are not included in the code.')) . '</p>';
    $levels = $inst ? LEVELS_READER[lang()] : levels();
    foreach (WORK_TYPES as $type => $_) {
        $sel = $in['type'] === $type;
        $html .= '<div class="acts"' . when("type=$type") . '>' . (type_desc($type) !== '' ? '<p class="hint">' . h(type_desc($type)) . '</p>' : '');
        foreach (acts($type) as $act => [$name, $desc]) {
            $lv = $sel ? (string) ($in['u'][$act] ?? '0') : '0';
            $id = "{$type}-{$act}";
            $html .= '<div class="act' . ($lv !== '0' && $lv !== '' ? ' used' : '') . '" id="act-' . $id . '"><p class="act-name">' . h($name) . '</p><p class="act-desc">' . h($desc) . '</p>'
                . '<div class="sel"><label for="lv-' . $id . '">' . h($inst ? tr('Uso máximo de la IA', 'Maximum AI use') : tr('¿Cómo la utilizaste?', 'How did you use it?')) . ' <span class="sr-only">(' . h($name) . ')</span></label>'
                . '<select id="lv-' . $id . '" name="u_' . $type . '_' . $act . '">';
            foreach ($levels as $n => $text) $html .= '<option value="' . $n . '"' . ((string) $n === $lv ? ' selected' : '') . '>' . h($text) . '</option>';
            $html .= '</select></div>'
                . '<fieldset class="scope"' . when("u_{$type}_{$act}=2,3,4,5") . '><legend>' . h(tr('¿Qué parte de la obra está afectada?', 'What share of the work is affected?')) . ' <span class="sr-only">(' . h($name) . ')</span></legend><div class="pills">';
            foreach (scope_labels() as $n => $text) $html .= opt("s_{$type}_{$act}", (string) $n, $sel && ($in['s'][$act] ?? '') === (string) $n, scope_short($text), 'pill');
            $html .= '</div></fieldset></div>';
        }
        $html .= '</div>';
    }
    $html .= '</section>';

    $html .= '<div class="no-js-only"><button class="btn btn-primary">' . h(tr('Continuar y ver el código', 'Continue and see the code')) . '</button></div>';
    $GLOBALS['ORIDE_WHEN'] = null;
    return $html . '</form>';
}

/** Frase previa del código en la página de instituciones. */
function inst_intro(): string
{
    return t('inst_max');
}

/** Pestañas de Instituciones: crear un requisito / verificar una entrega. */
function inst_tabs(string $current): string
{
    // La pestaña «Verificar una entrega» lleva el código del requisito si ya se ha creado (oride.js la actualiza).
    $r = $current === 'instituciones.php' ? oride_compute(oride_input_from_get()) : null;
    $verify = 'comprobar.php' . ($r && $r['status'] === 'complete' ? '?r=' . rawurlencode($r['code']) : '');
    $tabs = ['instituciones.php' => [tr('Crear un requisito', 'Create a requirement'), 'instituciones.php', ''], 'comprobar.php' => [t('cmp_title'), $verify, ' data-verify-tab']];
    $html = '<nav class="tabs" aria-label="' . h(t('nav_inst')) . '"><ul>';
    foreach ($tabs as $key => [$label, $href, $attr]) $html .= '<li><a href="' . u($href) . '"' . $attr . ($key === $current ? ' aria-current="page"' : '') . '>' . h($label) . '</a></li>';
    return $html . '</ul></nav>';
}

/** Cabecera común de Instituciones (pestañas «Crear un requisito» y «Verificar una entrega»). */
function inst_header(string $current): string
{
    $who = en()
        ? ['Schools and universities', 'Research centres, conferences and journals', 'Publishers', 'Literary and art competitions', 'Advertising agencies and design studios', 'Organisations running calls, grants and awards', 'Companies or professionals and their clients']
        : ['Centros educativos y universidades', 'Centros, congresos y revistas de investigación', 'Editoriales', 'Concursos literarios y artísticos', 'Agencias de publicidad y estudios de diseño', 'Entidades que organizan convocatorias, becas y premios', 'Empresas o profesionales y sus clientes'];
    $html = '<div class="inst-head narrow-text"><h1>' . h(t('nav_inst')) . '</h1>'
        . '<p class="subtitle">' . h(tr('Establece qué uso de IA permites en las obras que recibes', 'Set what use of AI you allow in the work you receive')) . '</p>'
        . '<p>' . h(tr('Define mediante un código ORIDE qué usos de inteligencia artificial aceptas y con qué límites. Quienes presenten sus trabajos podrán conocer las condiciones de antemano, declarar cómo han utilizado la IA y comprobar si su código ORIDE se ajusta a los requisitos establecidos.', 'Use an ORIDE code to define which uses of artificial intelligence you accept and within what limits. Those who submit their work can know the conditions in advance, declare how they used AI and check whether their ORIDE code meets the requirements.')) . '</p>'
        . '<p>' . h(tr('Una forma clara y sencilla de incorporar criterios de uso de IA en bases de concursos, normas de entrega, encargos y contratos.', 'A clear and simple way to include AI-use criteria in competition rules, submission guidelines, commissions and contracts.')) . '</p>'
        . '<h2 class="who-title">' . h(tr('¿A quién puede resultar útil?', 'Who might find it useful?')) . '</h2><ul class="who-list">';
    foreach ($who as $w) $html .= '<li>' . h($w) . '</li>';
    return $html . '</ul></div>' . inst_tabs($current);
}
