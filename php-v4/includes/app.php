<?php
/**
 * ORIDE v4 — núcleo: configuración, utilidades, taxonomía y cálculo del código.
 * Sin base de datos, sin sesiones y sin cookies.
 */
declare(strict_types=1);

define('ORIDE_ROOT', dirname(__DIR__));
require_once __DIR__ . '/i18n.php';
const ORIDE_VERSION = '4.0.0';

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
// Código ORIDE: áreas de uso, forma de uso, alcance y evidencias
// ---------------------------------------------------------------------------

const AREA_ORDER = ['D', 'I', 'E', 'C', 'R', 'F', 'T'];

/** Los niveles 3, 4 y 5 incorporan resultados de la IA a la obra: se pregunta el alcance. */
function needs_scope(int $usage): bool
{
    return $usage >= 3;
}

function area_part(string $l, ?int $usage, ?int $scope): string
{
    return $l . ($usage ?? '') . ($usage !== null && needs_scope($usage) ? ($scope ?? '') : '');
}

/**
 * @param array{ia?:string, areas?:array, u?:array, s?:array, ev?:string} $in
 * @return array{status:string, code:?string, areas:array<int,array{0:string,1:?int,2:?int}>, ev:?int, missing:?string}
 *   status: empty | pending | complete
 */
function oride_compute(array $in): array
{
    $r = ['status' => 'empty', 'code' => null, 'areas' => [], 'ev' => null, 'missing' => null];
    $ia = $in['ia'] ?? '';
    if ($ia === 'no') return ['status' => 'complete', 'code' => 'IA0'] + $r;
    if ($ia !== 'si') return $r;

    $r['status'] = 'pending';
    $chosen = (array) ($in['areas'] ?? []);
    $u = (array) ($in['u'] ?? []);
    $s = (array) ($in['s'] ?? []);
    foreach (AREA_ORDER as $l) {
        if (!in_array($l, $chosen, true)) continue;
        $usage = isset($u[$l]) && preg_match('/^[0-5]$/', (string) $u[$l]) ? (int) $u[$l] : null;
        $scope = $usage !== null && needs_scope($usage) && isset($s[$l]) && preg_match('/^[0-4]$/', (string) $s[$l]) ? (int) $s[$l] : null;
        $r['areas'][] = [$l, $usage, $scope];
    }
    $ev = in_array($in['ev'] ?? '', ['si', 'no'], true) ? (int) ($in['ev'] === 'si') : null;
    $r['ev'] = $ev;

    if (!$r['areas']) return ['missing' => t('miss_area')] + $r;
    foreach ($r['areas'] as [$l, $usage, $scope]) {
        if ($usage === null) return ['missing' => t('miss_usage', areas()[$l][2])] + $r;
        if (needs_scope($usage) && $scope === null) return ['missing' => t('miss_scope', areas()[$l][2])] + $r;
    }
    if ($ev === null) return ['missing' => t('miss_ev')] + $r;

    $r['status'] = 'complete';
    $r['code'] = 'IA1-' . implode('-', array_map(fn($a) => area_part(...$a), $r['areas'])) . '-V' . $ev;
    return $r;
}

/** Lee las respuestas de la URL (formulario sin JavaScript o enlace compartido). */
function oride_input_from_get(): array
{
    $u = $s = [];
    foreach (AREA_ORDER as $l) {
        if (get('u' . $l) !== '') $u[$l] = get('u' . $l);
        if (get('s' . $l) !== '') $s[$l] = get('s' . $l);
    }
    return ['ia' => get('ia'), 'areas' => get_list('areas'), 'u' => $u, 's' => $s, 'ev' => get('ev')];
}

/** Normaliza un código escrito a mano: admite «ORIDE», minúsculas y espacios o puntos en lugar de guiones. */
function oride_normalize(string $s): string
{
    $s = mb_strtoupper(trim($s));
    $s = preg_replace('/^((C[OÓ]DIGO|CODE)\s+)?ORIDE(\s+CODE)?[\s:]*/u', '', $s);
    $s = preg_replace('/[\s·_—–.,;]+/u', '-', $s);
    return trim(preg_replace('/-+/', '-', $s), '-');
}

/** Lee un código. @return array{valid:bool, code:string, error:?string, result:?array} */
function oride_explain(string $raw): array
{
    $code = oride_normalize($raw);
    $out = ['valid' => false, 'code' => $code, 'error' => null, 'result' => null];
    if ($code === 'IA0') return ['valid' => true, 'result' => oride_compute(['ia' => 'no'])] + $out;
    if (!preg_match('/^IA1-(.+)-V([01])$/', $code, $m)) return ['error' => t('err_form')] + $out;
    $last = -1;
    $areas = $u = $s = [];
    foreach (explode('-', $m[1]) as $tok) {
        if (!preg_match('/^([A-Z])(\d)(\d?)$/', $tok, $x) || !in_array($x[1], AREA_ORDER, true)) return ['error' => t('err_area', $tok)] + $out;
        [$l, $usage, $scope] = [$x[1], (int) $x[2], $x[3]];
        $pos = array_search($l, AREA_ORDER, true);
        if ($pos <= $last) return ['error' => t('err_order')] + $out;
        $last = $pos;
        if ($usage > 5) return ['error' => t('err_usage', $tok)] + $out;
        if (needs_scope($usage) && $scope === '') return ['error' => t('err_scope_missing', $tok, $l . $usage . '2')] + $out;
        if (!needs_scope($usage) && $scope !== '') return ['error' => t('err_scope_extra', $tok)] + $out;
        if ($scope !== '' && (int) $scope > 4) return ['error' => t('err_scope_range', $tok)] + $out;
        $areas[] = $l;
        $u[$l] = (string) $usage;
        if ($scope !== '') $s[$l] = $scope;
    }
    $r = oride_compute(['ia' => 'si', 'areas' => $areas, 'u' => $u, 's' => $s, 'ev' => $m[2] === '1' ? 'si' : 'no']);
    return ['valid' => true, 'result' => $r] + $out;
}

function area_lines(string $l, int $usage, ?int $scope): array
{
    $lines = [areas()[$l][3][$usage]];
    if ($scope !== null) $lines[] = t('scope_line', mb_strtolower(scope_labels()[$scope]));
    return $lines;
}

/** Texto plano de la declaración (para copiar). */
function oride_text(array $r): string
{
    if ($r['status'] !== 'complete') return '';
    if ($r['code'] === 'IA0') return t('code_label') . " IA0\n" . t('text_no_ai');
    $lines = [t('code_label') . ' ' . $r['code']];
    foreach ($r['areas'] as [$l, $usage, $scope]) $lines[] = areas()[$l][0] . ': ' . implode('. ', area_lines($l, $usage, $scope)) . '.';
    $lines[] = t('text_evidence', mb_strtolower(STR[lang()]['evidence'][$r['ev']]));
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
    $nav = ['como-funciona.php' => t('nav_how'), 'codigo.php' => t('nav_code'), 'preguntas.php' => t('nav_faq')];
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
    echo '</div><p class="container footer-note">© ' . gmdate('Y') . ' ORIDE · ' . h(t('no_cookies')) . ' ' . h(t('honor')) . '</p></footer></body></html>';
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
    if ($r['status'] === 'empty') return $seg('IA–', true);
    if ($r['code'] === 'IA0') return $seg('IA0');
    $parts = [$seg('IA1')];
    foreach ($r['areas'] as [$l, $usage, $scope]) {
        $done = $usage !== null && (!needs_scope($usage) || $scope !== null);
        $parts[] = $seg(area_part($l, $usage, $scope) . ($done ? '' : '–'), !$done);
    }
    if (!$r['areas']) $parts[] = $seg('…', true);
    $parts[] = $seg($r['ev'] === null ? 'V–' : 'V' . $r['ev'], $r['ev'] === null);
    return implode('<span class="sep">-</span>', $parts);
}

function explain_list(array $r): string
{
    if ($r['status'] === 'empty' || $r['code'] === 'IA0') return '';
    $html = '';
    foreach ($r['areas'] as [$l, $usage, $scope]) {
        if ($usage === null) continue;
        $html .= '<div class="dim"><span>' . h(area_part($l, $usage, $scope) . ' · ' . areas()[$l][0]) . '</span>'
            . implode('', array_map(fn($x) => '<p class="val">' . h($x) . '</p>', area_lines($l, $usage, $scope))) . '</div>';
    }
    if ($r['ev'] !== null) {
        $html .= '<div class="dim"><span>V' . $r['ev'] . ' · ' . h(t('evidence_title')) . '</span><p class="val">' . h(STR[lang()]['evidence'][$r['ev']]) . '</p>'
            . ($r['ev'] ? '<p class="val note">' . h(t('evidence_note')) . '</p>' : '') . '</div>';
    }
    return $html;
}

function code_box(array $r, bool $live = false, ?string $title = null): string
{
    $status = match ($r['status']) {
        'complete' => $r['code'] === 'IA0' ? t('status_no_ai') : t('status_complete'),
        'pending' => $r['missing'] ?? '',
        default => t('status_empty'),
    };
    $complete = $r['status'] === 'complete';
    return '<section class="code-card decl-box" id="declaracion" aria-labelledby="decl-title"' . ($live ? ' data-box' : '') . '><div class="inner">'
        . '<p class="label" id="decl-title">' . h($title ?? t('your_code')) . '</p>'
        . '<p class="big code-line" data-code aria-live="polite">' . code_segments($r) . '</p>'
        . ($live || $r['code'] === 'IA0' ? '<p class="status" data-status aria-live="polite">' . h($status) . '</p>' : '')
        . '<div class="dims" data-explain>' . explain_list($r) . '</div>'
        . ($live ? '<div class="box-actions"><button type="button" class="btn btn-copy" data-copy-code="' . h($complete ? 'ORIDE ' . $r['code'] : '') . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_code')) . '</button>'
            . '<button type="button" class="btn btn-copy" data-copy-text="' . h(oride_text($r)) . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_text')) . '</button></div>' : '')
        . '</div>' . ($live ? '<p class="foot">' . h(t('honor')) . ' <a href="' . u('codigo.php') . '">' . h(t('what_means')) . '</a></p>' : '') . '</section>';
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
