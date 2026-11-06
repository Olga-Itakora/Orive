<?php
/**
 * ORIDE v5 — núcleo: configuración, utilidades, taxonomía y cálculo del código.
 * Sin base de datos, sin sesiones y sin cookies.
 */
declare(strict_types=1);

define('ORIDE_ROOT', dirname(__DIR__));
require_once __DIR__ . '/i18n.php';
const ORIDE_VERSION = '5.0.0';

// ---------------------------------------------------------------------------
// Configuración
// ---------------------------------------------------------------------------

function cfg(string $key): mixed
{
    static $c = null;
    if ($c === null) {
        $c = require ORIDE_ROOT . '/config.php';
        if (is_file(ORIDE_ROOT . '/config.local.php')) $c = array_merge($c, require ORIDE_ROOT . '/config.local.php');
    }
    return $c[$key] ?? null;
}

/** Secreto propio de la instalación: se crea solo la primera vez en storage/. */
function app_secret(): string
{
    static $s = null;
    if ($s !== null) return $s;
    $file = ORIDE_ROOT . '/storage/secret.php';
    if (is_file($file)) return $s = (string) require $file;
    $new = bin2hex(random_bytes(32));
    if (@file_put_contents($file, "<?php return '$new';\n", LOCK_EX) !== false) return $s = $new;
    error_log('[ORIDE] No se puede escribir storage/secret.php; se usa un secreto derivado.');
    return $s = hash('sha256', __FILE__ . php_uname() . (string) @filemtime(__FILE__));
}

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Ruta base del sitio (admite instalarlo en una subcarpeta). */
function base_path(): string
{
    static $b = null;
    if ($b !== null) return $b;
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $root = str_replace('\\', '/', ORIDE_ROOT);
    $rel = str_starts_with($script, $root) ? substr($script, strlen($root)) : '/' . basename($script);
    $name = $_SERVER['SCRIPT_NAME'] ?? '/';
    $b = str_ends_with($name, $rel) ? substr($name, 0, -strlen($rel)) : rtrim(dirname($name), '/');
    return $b = rtrim($b, '/') . '/';
}

/**
 * Enlace interno. Las páginas llevan siempre el idioma (?lang=es|en), así no hacen falta cookies.
 * Los ficheros de assets/ se enlazan sin idioma.
 */
function u(string $path = '', ?string $lang = null): string
{
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'assets/')) return base_path() . $path;
    $frag = '';
    if (($p = strpos($path, '#')) !== false) { $frag = substr($path, $p); $path = substr($path, 0, $p); }
    $path = preg_replace('/([?&])lang=[a-z]{2}(&|$)/', '$1', $path);
    $path = rtrim($path, '?&');
    return base_path() . $path . (str_contains($path, '?') ? '&' : '?') . 'lang=' . ($lang ?? lang()) . $frag;
}

/** La página actual en otro idioma, con los mismos parámetros. */
function current_in(string $lang): string
{
    $q = $_GET;
    unset($q['lang']);
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    return u($script . ($q ? '?' . http_build_query($q) : ''), $lang);
}

function is_https(): bool
{
    return ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function host(): string
{
    $h = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return preg_match('/^[a-z0-9.-]+(:\d+)?$/', $h) ? $h : 'localhost';
}

function abs_url(string $path = ''): string
{
    return (is_https() ? 'https' : 'http') . '://' . host() . u($path);
}

function get(string $k): string
{
    $v = $_GET[$k] ?? '';
    return is_string($v) ? trim($v) : '';
}

function get_list(string $k): array
{
    $v = $_GET[$k] ?? [];
    return is_array($v) ? array_values(array_filter($v, 'is_string')) : [];
}

function post(string $k): string
{
    $v = $_POST[$k] ?? '';
    return is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : '';
}

function security_headers(): void
{
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
}

set_exception_handler(function (Throwable $e): void {
    $ref = bin2hex(random_bytes(4));
    error_log("[ORIDE] $ref " . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) http_response_code(500);
    echo '<!doctype html><html lang="' . lang() . '"><meta charset="utf-8"><title>Error · ORIDE</title><body><p>' . h(t('error_server', $ref)) . '</p></body></html>';
});
ini_set('log_errors', '1');
ini_set('error_log', ORIDE_ROOT . '/storage/php-errors.log');
ini_set('display_errors', cfg('debug') ? '1' : '0');

// ---------------------------------------------------------------------------
// Código ORIDE (v5): TIPO-IA1-ACT##-…-V#
//   TIPO  tipo de obra (SCI, LIT, MUS, PHO, IL, DES, AUD, SW, TRA)
//   IA0 / IA1  sin IA / con IA. Con IA0 el código termina ahí.
//   ACT##  actividad opcional + grado de uso (0–5) + alcance (0–4; 0 con los grados 0 y 1)
//   V0 / V1  evidencias (opcional, al final)
// ---------------------------------------------------------------------------

function needs_scope(int $level): bool
{
    return $level >= SCOPE_FROM;
}

function act_part(string $act, ?int $level, ?int $scope): string
{
    if ($level === null) return $act;
    return $act . $level . (needs_scope($level) ? ($scope ?? '') : '0');
}

/**
 * @param array{type?:string, ia?:string, acts?:array, u?:array, s?:array, ev?:string} $in
 * @return array{status:string, type:?string, ia:?int, acts:array<int,array{0:string,1:?int,2:?int}>, ev:?int, code:?string, missing:?string}
 *   status: empty (sin tipo) | pending (falta algo) | complete
 */
function oride_compute(array $in): array
{
    $r = ['status' => 'empty', 'type' => null, 'ia' => null, 'acts' => [], 'ev' => null, 'code' => null, 'missing' => null];
    $type = $in['type'] ?? '';
    if (!isset(WORK_TYPES[$type])) return $r;
    $r['type'] = $type;
    $r['status'] = 'pending';
    $ia = $in['ia'] ?? '';
    if ($ia !== 'si' && $ia !== 'no') return ['missing' => t('status_need_ia')] + $r;
    if ($ia === 'no') return ['status' => 'complete', 'ia' => 0, 'code' => $type . '-IA0'] + $r;
    $r['ia'] = 1;

    $chosen = (array) ($in['acts'] ?? []);
    $u = (array) ($in['u'] ?? []);
    $s = (array) ($in['s'] ?? []);
    foreach (array_keys(WORK_TYPES[$type]['acts']) as $act) {
        if (!in_array($act, $chosen, true)) continue;
        $level = isset($u[$act]) && preg_match('/^[0-5]$/', (string) $u[$act]) ? (int) $u[$act] : null;
        $scope = $level !== null && needs_scope($level) && isset($s[$act]) && preg_match('/^[0-4]$/', (string) $s[$act]) ? (int) $s[$act] : null;
        $r['acts'][] = [$act, $level, $scope];
    }
    $r['ev'] = ($in['ev'] ?? '') === 'si' ? 1 : (($in['ev'] ?? '') === 'no' ? 0 : null);

    foreach ($r['acts'] as [$act, $level, $scope]) {
        $name = acts($type)[$act][0];
        if ($level === null) return ['missing' => t('miss_level', $name)] + $r;
        if (needs_scope($level) && $scope === null) return ['missing' => t('miss_scope', $name)] + $r;
    }
    $r['status'] = 'complete';
    $r['code'] = implode('-', array_merge([$type, 'IA1'], array_map(fn($x) => act_part(...$x), $r['acts']), $r['ev'] === null ? [] : ['V' . $r['ev']]));
    return $r;
}

/** Lee las respuestas de la URL (formulario sin JavaScript o enlace compartido). */
function oride_input_from_get(): array
{
    $type = get('type');
    $u = $s = [];
    $acts = [];
    if (isset(WORK_TYPES[$type])) {
        $acts = get_list('acts_' . $type);
        foreach (array_keys(WORK_TYPES[$type]['acts']) as $act) {
            if (get("u_{$type}_{$act}") !== '') $u[$act] = get("u_{$type}_{$act}");
            if (get("s_{$type}_{$act}") !== '') $s[$act] = get("s_{$type}_{$act}");
        }
    }
    return ['type' => $type, 'ia' => get('ia'), 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => get('ev')];
}

/** Normaliza un código escrito a mano: admite «ORIDE», minúsculas y espacios, «:» o puntos en lugar de guiones. */
function oride_normalize(string $s): string
{
    $s = mb_strtoupper(trim(rawurldecode($s)));
    $s = preg_replace('/^((C[OÓ]DIGO|CODE)\s+)?ORIDE(\s+CODE)?[\s:]*/u', '', $s);
    $s = preg_replace('/[\s·_—–.,;:\/+]+/u', '-', $s);
    return trim(preg_replace('/-+/', '-', $s), '-');
}

/**
 * Lee cualquier código. Las actividades pueden venir en cualquier orden; el resultado
 * se devuelve en el orden canónico. @return array{valid:bool, code:string, error:?string, result:?array}
 */
function oride_explain(string $raw): array
{
    $code = oride_normalize($raw);
    $out = ['valid' => false, 'code' => $code, 'error' => null, 'result' => null];
    $tok = $code === '' ? [] : explode('-', $code);
    if (count($tok) < 2) return ['error' => t('err_form')] + $out;
    if (count($tok) === 2 && $tok[1] === '') return ['error' => t('err_form')] + $out;
    $type = array_shift($tok);
    if (!isset(WORK_TYPES[$type])) return ['error' => t('err_type', $type)] + $out;
    // Si tras el tipo vienen directamente actividades o evidencias, se entiende IA1 (LIT:DOC44-V).
    $ia = preg_match('/^IA[01]$/', $tok[0] ?? '') ? array_shift($tok) : ($tok && preg_match('/^([A-Z]{3}\d*|V[01]?)$/', $tok[0]) ? 'IA1' : array_shift($tok));
    if ($ia === 'IA0') {
        if ($tok) return ['error' => t('err_ia0_extra')] + $out;
        return ['valid' => true, 'result' => oride_compute(['type' => $type, 'ia' => 'no'])] + $out;
    }
    if ($ia !== 'IA1') return ['error' => t('err_ia')] + $out;
    $ev = '';
    if ($tok && preg_match('/^V([01]?)$/', end($tok), $m)) {
        array_pop($tok);
        $ev = $m[1] === '0' ? 'no' : 'si'; // «V» solo equivale a V1
    }
    $acts = $u = $s = [];
    foreach ($tok as $t) {
        if (preg_match('/^V[01]?$/', $t)) return ['error' => t('err_v')] + $out;
        if (!preg_match('/^([A-Z]{3})(\d*)$/', $t, $m) || !isset(WORK_TYPES[$type]['acts'][$m[1]])) return ['error' => t('err_act', preg_replace('/\d+$/', '', $t), type_name($type))] + $out;
        [$act, $digits] = [$m[1], $m[2]];
        if (in_array($act, $acts, true)) return ['error' => t('err_dup', $act)] + $out;
        if (strlen($digits) !== 2) return ['error' => t('err_digits', $t, $act . '42')] + $out;
        [$level, $scope] = [(int) $digits[0], (int) $digits[1]];
        if ($level > 5) return ['error' => t('err_level', $t)] + $out;
        if (!needs_scope($level) && $scope !== 0) return ['error' => t('err_pad', $t)] + $out;
        if ($scope > 4) return ['error' => t('err_scope_range', $t)] + $out;
        $acts[] = $act;
        $u[$act] = (string) $level;
        if (needs_scope($level)) $s[$act] = (string) $scope;
    }
    $r = oride_compute(['type' => $type, 'ia' => 'si', 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => $ev]);
    return ['valid' => true, 'result' => $r] + $out;
}

/** $reader: en impersonal, para quien lee el código (oride.php e instituciones). */
function act_lines(int $level, ?int $scope, bool $reader = false): array
{
    $lines = [$reader ? LEVELS_READER[lang()][$level] : levels()[$level]];
    if ($scope !== null) $lines[] = t('scope_line', mb_strtolower(scope_labels()[$scope]));
    return $lines;
}

/** Enlace público que explica un código. */
function code_url(string $code): string
{
    return abs_url('oride.php?' . $code);
}

/** Texto plano de la declaración (para copiar). $intro: frase previa (página de instituciones). */
function oride_text(array $r, string $intro = ''): string
{
    if ($r['status'] !== 'complete') return '';
    $lines = $intro !== '' ? [$intro] : [];
    $lines[] = t('code_label') . ' ' . $r['code'];
    $lines[] = t('type_line', type_name($r['type']));
    $lines[] = $r['ia'] ? t('ia1_line') : t('ia0_line');
    foreach ($r['acts'] as [$act, $level, $scope]) $lines[] = acts($r['type'])[$act][0] . ': ' . implode('. ', act_lines($level, $scope, $intro !== '')) . '.';
    if ($r['ev'] !== null) $lines[] = t('text_evidence', mb_strtolower(STR[lang()]['evidence'][$r['ev']]));
    $lines[] = t('link_line', code_url($r['code']));
    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// Plantilla
// ---------------------------------------------------------------------------

function logo(bool $descriptor = false): string
{
    return '<span class="logo"><svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M26.5 10.5A12 12 0 1 0 28 16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="26.9" cy="10.9" r="3.1" fill="#2ba39a"/><circle cx="16" cy="16" r="3.2" fill="currentColor"/></svg>'
        . '<span><span class="logo-word">ORIDE</span>' . ($descriptor ? '<span class="logo-desc">' . h(t('descriptor')) . '</span>' : '') . '</span></span>';
}

function lang_switch(): string
{
    $other = en() ? 'es' : 'en';
    return '<a class="lang-switch" href="' . h(current_in($other)) . '" hreflang="' . $other . '" lang="' . $other . '" data-lang-switch="' . $other . '">' . (en() ? 'Español' : 'English') . '</a>';
}

function page_start(string $title, ?string $description = null): void
{
    security_headers();
    header('Content-Language: ' . lang());
    header('Vary: Accept-Language');
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $nav = ['como-funciona.php' => t('nav_how'), 'codigo.php' => t('nav_code'), 'instituciones.php' => t('nav_inst'), 'preguntas.php' => t('nav_faq')];
    echo '<!doctype html><html lang="' . lang() . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title === '' ? 'ORIDE · ' . t('descriptor') : $title . ' · ORIDE') . '</title><meta name="description" content="' . h($description ?? t('site_description')) . '">';
    foreach (LANGS as $code => $_) echo '<link rel="alternate" hreflang="' . $code . '" href="' . h(current_in($code)) . '">';
    echo '<link rel="icon" href="' . u('assets/img/favicon.svg') . '" type="image/svg+xml"><link rel="stylesheet" href="' . u('assets/css/oride.css') . '?v=' . ORIDE_VERSION . '">';
    echo '<script src="' . u('assets/js/oride.js') . '?v=' . ORIDE_VERSION . '" defer></script>';
    echo '</head><body data-i18n="' . h(js_i18n()) . '"><a class="skip" href="#main">' . h(t('skip')) . '</a>';
    echo '<header class="site-header"><div class="container header-row"><a class="brand" href="' . u('index.php') . '" aria-label="ORIDE, ' . h(t('home')) . '">' . logo() . '</a>';
    echo '<div class="header-tools">' . lang_switch() . '<button type="button" class="btn btn-ghost menu-toggle" aria-expanded="false" aria-controls="site-nav">' . h(t('menu')) . '</button></div>';
    echo '<nav id="site-nav" class="site-nav" aria-label="' . h(t('nav_main')) . '"><ul>';
    foreach ($nav as $href => $label) echo '<li><a href="' . u($href) . '"' . ($current === $href ? ' aria-current="page"' : '') . '>' . h($label) . '</a></li>';
    echo '</ul><div class="menu-actions"><a class="btn btn-primary" href="' . u('crear.php') . '"' . ($current === 'crear.php' ? ' aria-current="page"' : '') . '>' . h(t('nav_create')) . '</a></div></nav></div></header>';
    echo '<main id="main">';
}

function page_end(): void
{
    echo '</main><footer class="site-footer"><div class="container footer-row">';
    echo '<div>' . logo(true) . '<p class="muted small">' . h(t('tagline')) . '</p></div>';
    echo '<nav aria-label="' . h(t('footer')) . '"><ul class="footer-links">';
    foreach (['sobre.php' => t('nav_about'), 'marco-normativo.php' => t('nav_law'), 'legal.php' => t('nav_legal')] as $href => $label) {
        echo '<li><a href="' . u($href) . '">' . h($label) . '</a></li>';
    }
    echo '</ul></nav>';
    echo '<div class="footer-cta"><a class="btn btn-secondary" href="' . u('mejora.php') . '">' . h(t('nav_feedback')) . '</a><p class="small muted">' . h(t('contact')) . ': <a href="mailto:info@itakora.com">info@itakora.com</a></p></div>';
    echo '</div><div class="container footer-note"><p>© ' . gmdate('Y') . ' Olga Revilla Muñoz. ' . h(t('trademark')) . '</p><p>' . h(t('no_cookies')) . ' ' . h(t('honor')) . '</p></div></footer></body></html>';
}

function alert(string $msg, string $tone = 'info'): string
{
    return '<div class="alert alert-' . h($tone) . '"' . ($tone === 'danger' ? ' role="alert"' : ' role="status"') . '>' . h($msg) . '</div>';
}

/** Aviso de declaración responsable (honor, honestidad y ética). */
function honor_note(string $class = ''): string
{
    return '<div class="card honor ' . h($class) . '"><p>' . h(t('honor')) . '</p></div>';
}

// ---------------------------------------------------------------------------
// Caja del código (la misma estructura la rellena oride.js en tiempo real)
// ---------------------------------------------------------------------------

function code_segments(array $r): string
{
    $seg = fn(string $v, bool $empty = false) => '<span class="seg' . ($empty ? ' seg-empty' : '') . '">' . h($v) . '</span>';
    if ($r['status'] === 'empty') return $seg('TIPO–', true) . '<span class="sep">-</span>' . $seg('IA–', true);
    $parts = [$seg($r['type'])];
    if ($r['ia'] === null) return implode('<span class="sep">-</span>', array_merge($parts, [$seg('IA–', true)]));
    $parts[] = $seg('IA' . $r['ia']);
    foreach ($r['acts'] as [$act, $level, $scope]) {
        $done = $level !== null && (!needs_scope($level) || $scope !== null);
        $parts[] = $seg(act_part($act, $level, $scope) . ($done ? '' : '–'), !$done);
    }
    if ($r['ev'] !== null) $parts[] = $seg('V' . $r['ev']);
    return implode('<span class="sep">-</span>', $parts);
}

function explain_list(array $r, bool $reader = false): string
{
    if ($r['status'] === 'empty') return '';
    $html = '<div class="dim"><span>' . h($r['type'] . ' · ' . tr('Tipo de obra', 'Type of work')) . '</span><p class="val">' . h(type_name($r['type'])) . '</p></div>';
    if ($r['ia'] === null) return $html;
    $html .= '<div class="dim"><span>IA' . $r['ia'] . ' · ' . h(tr('Inteligencia artificial', 'Artificial intelligence')) . '</span><p class="val">' . h($r['ia'] ? t('ia1_line') : t('ia0_line')) . '</p></div>';
    foreach ($r['acts'] as [$act, $level, $scope]) {
        if ($level === null) continue;
        $html .= '<div class="dim"><span>' . h(act_part($act, $level, $scope) . ' · ' . acts($r['type'])[$act][0]) . '</span>'
            . implode('', array_map(fn($x) => '<p class="val">' . h($x) . '</p>', act_lines($level, $scope, $reader))) . '</div>';
    }
    if ($r['ev'] !== null) {
        $html .= '<div class="dim"><span>V' . $r['ev'] . ' · ' . h(t('evidence_title')) . '</span><p class="val">' . h(STR[lang()]['evidence'][$r['ev']]) . '</p>'
            . ($r['ev'] ? '<p class="val note">' . h(t('evidence_note')) . '</p>' : '') . '</div>';
    }
    return $html;
}

/**
 * @param array{live?:bool, title?:string, intro?:string, actions?:bool, reader?:bool} $o
 *   live: la rellena oride.js; intro: frase previa (instituciones); actions: botones de copiar
 */
function code_box(array $r, array $o = []): string
{
    $live = !empty($o['live']);
    $actions = $o['actions'] ?? $live;
    $intro = $o['intro'] ?? '';
    $status = match ($r['status']) {
        'complete' => $r['ia'] === 0 ? t('status_no_ai') : t('status_complete'),
        'pending' => $r['missing'] ?? '',
        default => t('status_empty'),
    };
    $complete = $r['status'] === 'complete';
    $html = '<section class="code-card decl-box" id="declaracion" aria-labelledby="decl-title"' . ($live ? ' data-box' : '') . '><div class="inner">'
        . '<p class="label" id="decl-title">' . h($o['title'] ?? t('your_code')) . '</p>'
        . ($intro !== '' ? '<p class="intro" data-intro>' . h($intro) . '</p>' : '')
        . '<p class="big code-line" data-code aria-live="polite">' . code_segments($r) . '</p>'
        . ($live ? '<p class="status" data-status aria-live="polite">' . h($status) . '</p>' : '')
        . '<div class="dims" data-explain>' . explain_list($r, $intro !== '' || !empty($o['reader'])) . '</div>';
    if ($actions) {
        $html .= '<div class="box-actions">'
            . '<button type="button" class="btn btn-copy" data-copy-code="' . h($complete ? 'ORIDE ' . $r['code'] : '') . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_code')) . '</button>'
            . '<button type="button" class="btn btn-copy" data-copy-text="' . h(oride_text($r, $intro)) . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_text')) . '</button>'
            . '<button type="button" class="btn btn-copy" data-copy-link="' . h($complete ? code_url($r['code']) : '') . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_link')) . '</button>'
            . '</div>';
    }
    $html .= '</div>' . ($live ? '<p class="foot">' . h(t('honor')) . ' <a href="' . u('codigo.php') . '">' . h(t('what_means')) . '</a></p>' : '') . '</section>';
    return $html;
}

/** Texto de una página en el idioma actual: tr('español', 'English'). */
function tr(string $es, string $en): string
{
    return en() ? $en : $es;
}

/** Campo oculto con el idioma, para formularios GET (el navegador descarta la consulta de action). */
function lang_field(): string
{
    return '<input type="hidden" name="lang" value="' . lang() . '">';
}
