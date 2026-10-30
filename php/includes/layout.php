<?php
/** Plantilla común y componentes de formulario accesibles. */
declare(strict_types=1);

function logo_svg(string $class = 'logo-mark'): string
{
    return '<svg class="' . $class . '" viewBox="0 0 32 32" aria-hidden="true"><path d="M26.5 10.5A12 12 0 1 0 28 16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="26.9" cy="10.9" r="3.1" fill="#2ba39a"/><circle cx="16" cy="16" r="3.2" fill="currentColor"/></svg>';
}

function logo(bool $descriptor = false): string
{
    return '<span class="logo">' . logo_svg() . '<span><span class="logo-word">ORIDE</span>' . ($descriptor ? '<span class="logo-desc">Originality Declaration</span>' : '') . '</span></span>';
}

/**
 * @param array{area?:string,description?:string,wide?:bool} $o area: site | author | admin | auth
 */
function page_start(string $title, array $o = []): void
{
    $area = $o['area'] ?? 'site';
    $user = function_exists('db') && isset($GLOBALS['ORIDE_CONFIG']) && is_file(ORIDE_ROOT . '/config.php') ? current_user() : null;
    $desc = $o['description'] ?? 'ORIDE permite documentar cómo se ha utilizado la inteligencia artificial en un proceso creativo y emitir una declaración responsable verificable.';
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . ' · ORIDE</title><meta name="description" content="' . h($desc) . '">';
    if ($area !== 'site') echo '<meta name="robots" content="noindex">';
    echo '<link rel="icon" href="' . u('assets/img/favicon.svg') . '" type="image/svg+xml"><link rel="stylesheet" href="' . u('assets/css/oride.css') . '?v=1">';
    echo '<script src="' . u('assets/js/oride.js') . '?v=1" defer></script>';
    foreach ($o['scripts'] ?? [] as $s) echo '<script src="' . u($s) . '?v=1" defer></script>';
    echo '</head><body class="area-' . h($area) . '"><a class="skip" href="#main">Saltar al contenido</a>';

    if ($area === 'auth') {
        echo '<main id="main" class="auth"><a class="auth-logo" href="' . u('index.php') . '" aria-label="ORIDE — inicio">' . logo(true) . '</a>';
        flashes();
        return;
    }

    if ($area === 'site') {
        $links = ['como-funciona.php' => 'Cómo funciona', 'codigo.php' => 'ORIDE Code', 'declaraciones.php' => 'Declaraciones', 'verificar.php' => 'Verificar', 'marco-normativo.php' => 'Marco normativo'];
        echo '<header class="site-header"><div class="container header-row"><a class="brand" href="' . u('index.php') . '" aria-label="ORIDE — inicio">' . logo() . '</a>';
        echo '<button type="button" class="btn btn-ghost menu-toggle" aria-expanded="false" aria-controls="site-nav">Menú</button><nav id="site-nav" class="site-nav" aria-label="Principal"><ul>';
        foreach ($links as $href => $label) echo '<li><a href="' . u($href) . '">' . h($label) . '</a></li>';
        echo '</ul><div class="menu-actions">';
        if ($user) {
            echo '<a class="btn btn-primary" href="' . u('autor/index.php') . '">Mi espacio</a>';
        } else {
            echo '<a class="btn btn-ghost" href="' . u('entrar.php') . '">Iniciar sesión</a><a class="btn btn-primary" href="' . u('registro.php') . '">Crear cuenta</a>';
        }
        echo '</div></nav></div></header>';
    } else {
        $nav = $area === 'admin'
            ? ['admin/index.php' => 'Resumen', 'admin/usuarios.php' => 'Usuarios', 'admin/taxonomia.php' => 'Taxonomía', 'admin/textos.php' => 'Textos legales', 'admin/incidencias.php' => 'Incidencias', 'admin/auditoria.php' => 'Auditoría', 'autor/index.php' => 'Salir del panel']
            : ['autor/index.php' => 'Inicio', 'autor/obras.php' => 'Mis obras', 'autor/declaraciones.php' => 'Mis declaraciones', 'autor/perfil.php' => 'Perfil y datos'];
        if ($area === 'author' && $user && $user['role'] === 'ADMIN') $nav['admin/index.php'] = 'Administración';
        echo '<header class="app-header' . ($area === 'admin' ? ' admin' : '') . '"><div class="container header-row"><a class="brand" href="' . u('index.php') . '" aria-label="ORIDE — inicio">' . logo() . '</a>';
        if ($user) {
            echo '<form method="post" action="' . u('salir.php') . '" class="inline">' . csrf_field() . '<button class="btn btn-ghost">Cerrar sesión</button></form>';
        }
        echo '</div><nav class="container app-nav" aria-label="' . ($area === 'admin' ? 'Administración' : 'Área de autor') . '"><ul>';
        $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
        foreach ($nav as $href => $label) {
            $active = basename($href) === $current && str_contains($_SERVER['SCRIPT_NAME'] ?? '', dirname($href));
            echo '<li><a href="' . u($href) . '"' . ($active ? ' aria-current="page"' : '') . '>' . h($label) . '</a></li>';
        }
        echo '</ul></nav></header>';
    }
    echo '<main id="main" class="' . (!empty($o['wide']) ? 'wide' : '') . '">';
    flashes();
}

function flashes(): void
{
    $f = take_flash();
    if (!$f) return;
    echo '<div class="container flashes">';
    foreach ($f as [$type, $msg]) echo alert($msg, $type);
    echo '</div>';
}

function alert(string $msg, string $tone = 'info', ?string $title = null, bool $raw = false): string
{
    $role = $tone === 'danger' ? ' role="alert"' : ($tone === 'ok' ? ' role="status"' : '');
    return '<div class="alert alert-' . h($tone) . '"' . $role . '>' . ($title ? '<p class="alert-title">' . h($title) . '</p>' : '') . '<div>' . ($raw ? $msg : h($msg)) . '</div></div>';
}

function page_end(): void
{
    $area = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) ;
    echo '</main>';
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/autor/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') || in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['entrar.php', 'registro.php', 'recuperar.php', 'restablecer.php'], true)) {
        echo '</body></html>';
        return;
    }
    echo '<footer class="site-footer"><div class="container footer-grid"><div>' . logo(true) . '<p class="muted">Toda creación tiene un proceso. Hazlo visible.</p></div>';
    $cols = [
        'ORIDE' => ['acerca.php' => 'Qué es ORIDE', 'como-funciona.php' => 'Cómo funciona', 'codigo.php' => 'ORIDE Code', 'preguntas.php' => 'Preguntas'],
        'Consulta' => ['declaraciones.php' => 'Declaraciones', 'verificar.php' => 'Verificar', 'marco-normativo.php' => 'Marco normativo'],
        'Legal' => ['legal.php?doc=privacidad' => 'Privacidad', 'legal.php?doc=condiciones' => 'Condiciones de uso', 'legal.php?doc=aviso' => 'Aviso legal', 'contacto.php' => 'Contacto'],
    ];
    foreach ($cols as $t => $links) {
        echo '<nav aria-label="' . h($t) . '"><h2 class="footer-title">' . h($t) . '</h2><ul>';
        foreach ($links as $href => $label) echo '<li><a href="' . u($href) . '">' . h($label) . '</a></li>';
        echo '</ul></nav>';
    }
    echo '</div><p class="container footer-note">© ' . gmdate('Y') . ' ORIDE · ORIDE no es un servicio de la Unión Europea ni una certificación oficial. Las declaraciones son responsabilidad de quien las emite.</p></footer></body></html>';
    unset($area);
}

// ---------------------------------------------------------------------------
// Campos de formulario
// ---------------------------------------------------------------------------

/**
 * Campo con etiqueta visible, ayuda y hueco de error (la validación al salir del
 * campo la hace oride.js; los errores del servidor llegan en $err).
 * Opciones: type, required, optional, hint, value, max, min, attrs, rows, options (para select), placeholder, class
 */
function field(string $name, string $label, array $o = []): string
{
    $id = $o['id'] ?? 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
    $type = $o['type'] ?? 'text';
    $err = $o['error'] ?? null;
    $req = !empty($o['required']);
    $desc = trim(($o['hint'] ?? '') !== '' ? "$id-hint " : '') . " $id-error";
    $attrs = ' id="' . h($id) . '" name="' . h($name) . '" aria-describedby="' . h(trim($desc)) . '"' . ($err ? ' aria-invalid="true"' : '') . ($req ? ' required' : '');
    if (isset($o['max'])) $attrs .= ' maxlength="' . (int) $o['max'] . '"';
    if (isset($o['min'])) $attrs .= ' minlength="' . (int) $o['min'] . '"';
    if (isset($o['placeholder'])) $attrs .= ' placeholder="' . h($o['placeholder']) . '"';
    $attrs .= $o['attrs'] ?? '';
    $value = (string) ($o['value'] ?? '');
    $html = '<div class="field ' . h($o['class'] ?? '') . '"><label for="' . h($id) . '">' . h($label);
    if (!empty($o['optional'])) $html .= ' <span class="optional">(opcional)</span>';
    if ($req) $html .= ' <span class="req" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span>';
    $html .= '</label>';
    if ($type === 'textarea') {
        $html .= '<textarea' . $attrs . ' rows="' . (int) ($o['rows'] ?? 3) . '">' . h($value) . '</textarea>';
    } elseif ($type === 'select') {
        $html .= '<select' . $attrs . '>';
        foreach ($o['options'] as $k => $v) {
            if (is_array($v)) {
                $html .= '<optgroup label="' . h((string) $k) . '">';
                foreach ($v as $kk => $vv) $html .= '<option value="' . h((string) $kk) . '"' . ((string) $kk === $value ? ' selected' : '') . '>' . h($vv) . '</option>';
                $html .= '</optgroup>';
            } else {
                $html .= '<option value="' . h((string) $k) . '"' . ((string) $k === $value ? ' selected' : '') . '>' . h($v) . '</option>';
            }
        }
        $html .= '</select>';
    } else {
        $html .= '<input type="' . h($type) . '"' . $attrs . ' value="' . h($value) . '">';
    }
    if (($o['hint'] ?? '') !== '') $html .= '<p class="hint" id="' . h($id) . '-hint">' . h($o['hint']) . '</p>';
    $html .= '<p class="field-error" id="' . h($id) . '-error" role="alert"' . ($err ? '' : ' hidden') . '>' . h((string) $err) . '</p></div>';
    return $html;
}

/** Grupo de opciones (radio/checkbox) con leyenda accesible. $choices: value => [label, description?] */
function choices(string $name, string $legend, array $choices, array $o = []): string
{
    $type = $o['type'] ?? 'radio';
    $selected = (array) ($o['value'] ?? []);
    $html = '<fieldset class="choices ' . h($o['class'] ?? '') . '"' . (isset($o['attrs']) ? $o['attrs'] : '') . '><legend>' . h($legend) . (!empty($o['required']) ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</legend>';
    if (!empty($o['hint'])) $html .= '<p class="hint">' . h($o['hint']) . '</p>';
    $html .= '<div class="choice-list' . (!empty($o['inline']) ? ' inline' : '') . '">';
    foreach ($choices as $value => $c) {
        [$label, $desc] = is_array($c) ? [$c[0], $c[1] ?? null] : [$c, null];
        $id = 'c-' . preg_replace('/[^a-z0-9_-]/i', '-', $name . '-' . $value);
        $checked = in_array((string) $value, array_map('strval', $selected), true) ? ' checked' : '';
        $html .= '<label class="choice" for="' . h($id) . '"><input type="' . $type . '" id="' . h($id) . '" name="' . h($name) . ($type === 'checkbox' ? '[]' : '') . '" value="' . h((string) $value) . '"' . $checked . '><span><span class="choice-label">' . h($label) . '</span>' . ($desc ? '<span class="choice-desc">' . h($desc) . '</span>' : '') . '</span></label>';
    }
    $html .= '</div><p class="field-error" role="alert"' . (!empty($o['error']) ? '' : ' hidden') . '>' . h((string) ($o['error'] ?? '')) . '</p></fieldset>';
    return $html;
}

/**
 * Botón de acción destructiva con diálogo de confirmación (oride.js).
 * El título nombra la entidad; Cancelar recibe el foco; Escape cancela.
 */
function confirm_button(string $action, array $hidden, string $triggerLabel, string $title, string $body, string $confirmLabel, bool $icon = false, string $extra = ''): string
{
    $fields = csrf_field();
    foreach ($hidden as $k => $v) $fields .= '<input type="hidden" name="' . h($k) . '" value="' . h((string) $v) . '">';
    $trigger = $icon
        ? '<span class="tip-wrap"><button type="button" class="icon-btn danger" data-confirm aria-label="' . h($triggerLabel) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button><span class="tip" role="tooltip">' . h($triggerLabel) . '</span></span>'
        : '<button type="button" class="btn btn-link danger" data-confirm>' . h($triggerLabel) . '</button>';
    return '<div class="confirm">' . $trigger . '<dialog class="dialog" aria-labelledby="dlg-' . md5($title) . '"><form method="post" action="' . h(u($action)) . '">' . $fields
        . '<h2 id="dlg-' . md5($title) . '">' . h($title) . '</h2><p class="muted">' . h($body) . '</p>' . $extra
        . '<div class="dialog-actions"><button type="button" class="btn btn-secondary" data-cancel>Cancelar</button><button class="btn btn-danger">' . h($confirmLabel) . '</button></div></form></dialog></div>';
}

/** Paginación: siempre visible; los controles desactivados conservan su etiqueta. */
function pagination(int $page, int $pages, callable $href): string
{
    $pages = max(1, $pages);
    $prev = $page > 1 ? '<a class="btn btn-secondary" href="' . h($href($page - 1)) . '">Página anterior</a>' : '<span class="btn btn-disabled" aria-disabled="true">Página anterior</span>';
    $next = $page < $pages ? '<a class="btn btn-secondary" href="' . h($href($page + 1)) . '">Página siguiente</a>' : '<span class="btn btn-disabled" aria-disabled="true">Página siguiente</span>';
    return '<nav class="pagination" aria-label="Paginación">' . $prev . '<span class="muted">Página ' . $page . ' de ' . $pages . '</span>' . $next . '</nav>';
}

/** Buscador reconocible a simple vista (icono + etiqueta visible); «/» lo enfoca. */
function search_form(string $action, string $q, string $label, string $hint = ''): string
{
    return '<form class="search" role="search" method="get" action="' . h(u($action)) . '"><label for="q"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="m20 20-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' . h($label) . ' <kbd aria-hidden="true">/</kbd></label><div class="search-row"><input id="q" name="q" value="' . h($q) . '" maxlength="100" data-slash-focus' . ($hint ? ' aria-describedby="q-hint"' : '') . '><button class="btn btn-primary">Buscar</button></div>' . ($hint ? '<p class="hint" id="q-hint">' . h($hint) . '</p>' : '') . '</form>';
}
