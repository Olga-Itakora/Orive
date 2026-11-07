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
    // Las preguntas y las opciones no muestran códigos: el código aparece solo en la caja.
    $html = '<form class="card create-form" method="get" action="' . u($action) . '#declaracion" data-builder data-mode="' . $mode . '">' . lang_field();

    $html .= '<fieldset class="choices q"><legend>' . h(tr('Tipo de obra', 'Type of work')) . '</legend><div class="choice-list types">';
    foreach (WORK_TYPES as $k => $_) $html .= choice('type', $k, 'radio', $in['type'] === $k, h(type_name($k)));
    $html .= '</div></fieldset>';

    $html .= '<fieldset class="choices q"' . when("type=$all") . '><legend>' . h($inst ? tr('Uso de inteligencia artificial', 'Use of artificial intelligence') : tr('¿Se ha utilizado inteligencia artificial durante la elaboración de esta obra?', 'Was artificial intelligence used in creating this work?')) . '</legend><div class="choice-list inline">'
        . choice('ia', 'si', 'radio', $in['ia'] === 'si', h($inst ? tr('Se permite usar IA', 'AI use is allowed') : $yes))
        . choice('ia', 'no', 'radio', $in['ia'] === 'no', h($inst ? tr('No se permite usar IA', 'AI use is not allowed') : 'No'))
        . '</div></fieldset>';

    // Evidencias: con IA y también sin IA.
    $html .= '<fieldset class="choices q"' . when('ia=si,no') . '><legend>';
    if ($inst) {
        $html .= h(tr('¿Exiges que el autor conserve evidencias de su proceso?', 'Do you require the author to keep evidence of their process?'));
    } else {
        $html .= '<span' . when('ia=si') . '>' . h(tr('¿Conservas evidencias que permitan documentar los usos de IA que has declarado?', 'Do you keep evidence that documents the AI uses you have declared?')) . '</span>'
            . '<span' . when('ia=no') . '>' . h(tr('¿Conservas evidencias que permitan documentar que no has usado IA?', 'Do you keep evidence that documents that you did not use AI?')) . '</span>';
    }
    $html .= '</legend>';
    if (!$inst) {
        $html .= '<p class="hint"' . when('ia=si') . '>' . h(tr('Por ejemplo: qué herramientas usaste, historiales de interacción, prompts, respuestas generadas por la IA, versiones de trabajo u otros documentos que muestren cómo tu obra incorporó o no esas propuestas.', 'For example: which AI tools you used, interaction histories, prompts, AI-generated responses, working versions or other documents showing how your work did or did not incorporate those proposals.')) . '</p>'
            . '<p class="hint"' . when('ia=no') . '>' . h(tr('Por ejemplo: borradores, versiones de trabajo, notas o archivos fechados que muestren tu proceso de creación.', 'For example: drafts, working versions, notes or dated files that show your creative process.')) . '</p>';
    }
    $html .= '<div class="choice-list inline">' . choice('ev', 'si', 'radio', $in['ev'] === 'si', $yes) . choice('ev', 'no', 'radio', $in['ev'] === 'no', 'No') . '</div>'
        . ($inst ? '' : '<div class="alert alert-warn"' . when('ev=si') . '>' . h(t('evidence_note')) . '</div>')
        . '</fieldset>';

    $html .= '<div class="stack-q"' . when('ia=si') . '>';
    $html .= '<div class="optional-head"><h2>' . h(tr('Actividades (opcional)', 'Activities (optional)')) . '</h2><p class="hint">' . h($inst
            ? tr('Fija, si quieres, el uso máximo de la IA en actividades concretas.', 'If you want, set the maximum AI use for specific activities.')
            : tr('Si quieres, detalla en qué actividades usaste la IA y cómo.', 'If you want, say in which activities you used AI and how.')) . '</p></div>';

    foreach (WORK_TYPES as $type => $_) {
        $html .= '<div class="stack-q"' . when("type=$type") . '><fieldset class="choices q"><legend>' . h(tr('Actividades de ', 'Activities in ') . mb_strtolower(type_name($type))) . '</legend><div class="choice-list">';
        foreach (acts($type) as $act => [$name, $desc]) $html .= choice("acts_{$type}[]", $act, 'checkbox', $in['type'] === $type && in_array($act, $in['acts'], true), h($name), $desc);
        $html .= '</div></fieldset>';
        foreach (acts($type) as $act => [$name]) {
            $sel = $in['type'] === $type;
            $html .= '<div class="area-block"' . when("acts_{$type}[]=$act") . '>'
                . '<fieldset class="choices q"><legend>' . h($inst ? tr("Uso máximo de la IA en «{$name}»", "Maximum AI use for “{$name}”") : tr("¿Cómo utilizaste los resultados de la IA en «{$name}»?", "How did you use the AI results for “{$name}”?")) . '</legend><div class="choice-list">';
            foreach (($inst ? LEVELS_READER[lang()] : levels()) as $n => $text) $html .= choice("u_{$type}_{$act}", (string) $n, 'radio', $sel && ($in['u'][$act] ?? '') === (string) $n, h($text));
            $html .= '</div></fieldset><fieldset class="choices q"' . when("u_{$type}_{$act}=2,3,4,5") . '><legend>' . h(tr('¿Qué parte de la obra está afectada?', 'What share of the work is affected?')) . '</legend><div class="choice-list">';
            foreach (scope_labels() as $n => $text) $html .= choice("s_{$type}_{$act}", (string) $n, 'radio', $sel && ($in['s'][$act] ?? '') === (string) $n, h($text));
            $html .= '</div></fieldset></div>';
        }
        $html .= '</div>';
    }

    // Aviso: IA declarada, pero todas las actividades marcadas con «No utilicé IA».
    $r = oride_compute($in);
    $html .= '<div class="alert alert-warn" data-allzero-form' . ($r['all_zero'] ? '' : ' hidden') . '>' . h(t('all_zero')) . '</div>';

    // La pregunta adicional de proporción (q) ya no se hace en el formulario: el índice usa el alcance
    // declarado. Los enlaces antiguos con q o qp se siguen respetando (ig_from_result).
    $html .= '</div>';

    $html .= '<div class="no-js-only"><button class="btn btn-primary">' . h(tr('Continuar y ver el código', 'Continue and see the code')) . '</button></div>';
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
