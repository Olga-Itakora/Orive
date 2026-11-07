<?php
/**
 * ORIDE v8 — núcleo: configuración, utilidades, taxonomía y cálculo del código.
 * Sin base de datos, sin sesiones y sin cookies.
 */
declare(strict_types=1);

define('ORIDE_ROOT', dirname(__DIR__));
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/indice.php';
require_once __DIR__ . '/compare.php';
const ORIDE_VERSION = '8.0.0';

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
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: https://mirrors.creativecommons.org; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'");
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
// Código ORIDE (v8). Dos líneas:
//   1. Tríada: TT-IA#-EV#    tipo de obra (2 letras) · IA0 sin IA / IA1 con IA · EV0 / EV1 evidencias
//   2. Actividades (opcional, solo con IA1): ACT##-ACT##…
//      ACT##  actividad + grado de uso (0–5) + alcance (0–4; 0 con los grados 0 y 1)
// En una sola línea (enlaces) se escribe todo seguido: LI-IA1-EV1-DOC44-GEN24.
// Se siguen leyendo los formatos anteriores (tipos de tres letras, V0/V1, V al final o sin V).
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

/** Las dos líneas del código: [tríada, actividades]. La segunda puede faltar. */
function code_lines(array $r): array
{
    $triad = $r['type'] . '-IA' . $r['ia'] . ($r['ev'] === null ? '' : '-EV' . $r['ev']);
    $acts = implode('-', array_map(fn($x) => act_part(...$x), $r['acts']));
    return $acts === '' ? [$triad] : [$triad, $acts];
}

/**
 * @param array{type?:string, ia?:string, acts?:array, u?:array, s?:array, ev?:string, require_ev?:bool} $in
 *   require_ev: el formulario exige las evidencias (tríada); al leer códigos antiguos, no.
 * @return array{status:string, type:?string, ia:?int, acts:array<int,array{0:string,1:?int,2:?int}>, ev:?int, code:?string, lines:array, all_zero:bool, missing:?string}
 *   status: empty (sin tipo) | pending (falta algo) | complete
 *   all_zero: con IA, pero todas las actividades marcadas con grado 0
 */
function oride_compute(array $in): array
{
    $r = ['status' => 'empty', 'type' => null, 'ia' => null, 'acts' => [], 'ev' => null, 'code' => null, 'lines' => [], 'all_zero' => false, 'missing' => null];
    $type = $in['type'] ?? '';
    if (!isset(WORK_TYPES[$type])) return $r;
    $r['type'] = $type;
    $r['status'] = 'pending';
    $ia = $in['ia'] ?? '';
    if ($ia !== 'si' && $ia !== 'no') return ['missing' => t('status_need_ia')] + $r;
    $r['ia'] = $ia === 'si' ? 1 : 0;
    $r['ev'] = ($in['ev'] ?? '') === 'si' ? 1 : (($in['ev'] ?? '') === 'no' ? 0 : null);

    if ($r['ia'] === 1) {
        $chosen = (array) ($in['acts'] ?? []);
        $u = (array) ($in['u'] ?? []);
        $s = (array) ($in['s'] ?? []);
        foreach (array_keys(WORK_TYPES[$type]['acts']) as $act) {
            if (!in_array($act, $chosen, true)) continue;
            $level = isset($u[$act]) && preg_match('/^[0-5]$/', (string) $u[$act]) ? (int) $u[$act] : null;
            $scope = $level !== null && needs_scope($level) && isset($s[$act]) && preg_match('/^[0-4]$/', (string) $s[$act]) ? (int) $s[$act] : null;
            $r['acts'][] = [$act, $level, $scope];
        }
    }
    if ($r['ev'] === null && ($in['require_ev'] ?? true)) return ['missing' => t('miss_ev')] + $r;
    foreach ($r['acts'] as [$act, $level, $scope]) {
        $name = acts($type)[$act][0];
        if ($level === null) return ['missing' => t('miss_level', $name)] + $r;
        if (needs_scope($level) && $scope === null) return ['missing' => t('miss_scope', $name)] + $r;
    }
    $r['status'] = 'complete';
    $r['lines'] = code_lines($r);
    $r['code'] = implode('-', $r['lines']);
    $r['all_zero'] = $r['ia'] === 1 && $r['acts'] && !array_filter($r['acts'], fn($x) => $x[1] !== 0);
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
    return ['type' => $type, 'ia' => get('ia'), 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => get('ev'), 'q' => get('q'), 'qp' => get('qp')];
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
 * Lee cualquier código, en el formato actual (TIPO-IA#-V# y actividades) o en los
 * anteriores (V al final, o sin V). Las actividades pueden venir en cualquier orden.
 * @return array{valid:bool, code:string, error:?string, result:?array}
 */
function oride_explain(string $raw): array
{
    $code = oride_normalize($raw);
    $out = ['valid' => false, 'code' => $code, 'error' => null, 'result' => null, 'legacy' => false, 'incomplete' => false];
    $tok = $code === '' ? [] : explode('-', $code);
    if (count($tok) < 2) return ['error' => t('err_form')] + $out;
    $type = array_shift($tok);
    $legacy = isset(LEGACY_TYPES[$type]) && LEGACY_TYPES[$type] !== $type; // LIT → LI, SCI → SC…
    $type = LEGACY_TYPES[$type] ?? $type;
    if (!isset(WORK_TYPES[$type])) return ['error' => t('err_type', $type)] + $out;
    // Si tras el tipo vienen directamente actividades o evidencias, se entiende IA1 (LI:DOC44-EV).
    $ia = preg_match('/^IA[01]$/', $tok[0] ?? '') ? array_shift($tok) : ($tok && preg_match('/^([A-Z]{3}\d*|E?V[01]?)$/', $tok[0]) ? 'IA1' : array_shift($tok));
    if ($ia !== 'IA0' && $ia !== 'IA1') return ['error' => t('err_ia')] + $out;
    $ev = '';
    $isV = fn($x) => (bool) preg_match('/^E?V[01]?$/', $x); // EV0/EV1; V0/V1 de versiones anteriores
    if ($tok && $isV($tok[0])) $ev = array_shift($tok);          // formato actual: tríada
    elseif ($tok && $isV(end($tok))) { $ev = array_pop($tok); $legacy = true; } // formato anterior: V al final
    if ($ev !== '' && $ev[0] === 'V') $legacy = true;               // V0/V1 de versiones anteriores
    $incomplete = $ev === '';                                       // sin evidencias (códigos anteriores a la tríada)
    $ev = $ev === '' ? '' : (substr($ev, -1) === '0' ? 'no' : 'si'); // «EV» o «V» solos equivalen a EV1
    if ($ia === 'IA0') {
        if ($tok) return ['error' => t('err_ia0_extra')] + $out;
        return ['valid' => true, 'legacy' => $legacy, 'incomplete' => $incomplete, 'result' => oride_compute(['type' => $type, 'ia' => 'no', 'ev' => $ev, 'require_ev' => false])] + $out;
    }
    $acts = $u = $s = [];
    foreach ($tok as $t) {
        if ($isV($t)) return ['error' => t('err_v')] + $out;
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
    $r = oride_compute(['type' => $type, 'ia' => 'si', 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => $ev, 'require_ev' => false]);
    return ['valid' => true, 'legacy' => $legacy, 'incomplete' => $incomplete, 'result' => $r] + $out;
}

/** $reader: en impersonal, para quien lee el código (oride.php e instituciones). */
function act_lines(int $level, ?int $scope, bool $reader = false): array
{
    $lines = [$reader ? LEVELS_READER[lang()][$level] : levels()[$level]];
    if ($scope !== null) $lines[] = t('scope_line', mb_strtolower(scope_labels()[$scope]));
    return $lines;
}

/**
 * Enlace público que explica un código. Lleva, si los hay, la respuesta a la pregunta de
 * proporción (q, qp) y la versión del algoritmo, para que el índice se pueda reproducir.
 */
function code_url(string $code, array $extra = []): string
{
    $q = ['ig' => substr(IG_CURRENT, strlen('ORIDE-IG-'))];
    if (($extra['qp'] ?? '') !== '' && $extra['qp'] !== null) $q['qp'] = $extra['qp'];
    elseif (($extra['q'] ?? '') !== '' && $extra['q'] !== null) $q['q'] = $extra['q'];
    return abs_url('oride.php?' . $code . '&' . http_build_query($q));
}

/** Número para mostrar: hasta un decimal, con la coma o el punto del idioma. */
function num(float $x, int $dec = 1): string
{
    $sep = en() ? '.' : ',';
    $s = number_format($x, $dec, $sep, '');
    if ($dec > 0) $s = rtrim(rtrim($s, '0'), $sep); // quita solo los ceros decimales
    return $s === '-0' ? '0' : $s;
}

/** Intervalo para mostrar: «52,5» si es un valor, «48–61» si es un intervalo. */
function num_range(array $r, int $dec = 1, string $unit = ''): string
{
    $a = num($r[0], $dec);
    $b = num($r[1], $dec);
    return ($a === $b ? $a : $a . '–' . $b) . $unit;
}

/** Línea del índice para copiar: «Índice Global ORIDE: 23 % (versión)». */
function ig_line(array $ig, bool $inst = false): string
{
    $title = $inst ? t('ig_title_max') : t('ig_title');
    if ($ig['status'] === 'ok') return t('ig_line', $title, ig_percent($ig), $ig['version']);
    return '';
}

/** Texto plano de la declaración (para copiar). $intro: frase previa (página de instituciones). */
function oride_text(array $r, string $intro = '', ?array $ig = null, array $extra = []): string
{
    if ($r['status'] !== 'complete') return '';
    $lines = $intro !== '' ? [$intro] : [];
    $lines[] = t('code_label') . ' ' . $r['lines'][0];
    if (isset($r['lines'][1])) $lines[] = $r['lines'][1];
    $lines[] = t('type_line', type_name($r['type']));
    $lines[] = $r['ia'] ? t('ia1_line') : t('ia0_line');
    if ($r['ev'] !== null) $lines[] = t('text_evidence', mb_strtolower(STR[lang()]['evidence'][$r['ev']]));
    foreach ($r['acts'] as [$act, $level, $scope]) $lines[] = acts($r['type'])[$act][0] . ': ' . implode('. ', act_lines($level, $scope, $intro !== '')) . '.';
    if ($ig && ($line = ig_line($ig, $intro !== '')) !== '') $lines[] = $line;
    $lines[] = t('link_line', code_url($r['code'], $extra));
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
    $nav = ['que-es.php' => t('nav_what'), 'como-funciona.php' => t('nav_how'), 'instituciones.php' => t('nav_inst'), 'documentacion.php' => t('nav_docs')];
    // Subpáginas: Instituciones › Verificar una entrega; Documentación › Código ORIDE e Índice Global ORIDE.
    $section = ['comprobar.php' => 'instituciones.php', 'codigo.php' => 'documentacion.php', 'indice.php' => 'documentacion.php'][$current] ?? $current;
    echo '<!doctype html><html lang="' . lang() . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title === '' ? 'ORIDE · ' . t('descriptor') : $title . ' · ORIDE') . '</title><meta name="description" content="' . h($description ?? t('site_description')) . '">';
    foreach (LANGS as $code => $_) echo '<link rel="alternate" hreflang="' . $code . '" href="' . h(current_in($code)) . '">';
    echo '<link rel="icon" href="' . u('assets/img/favicon.svg') . '" type="image/svg+xml"><link rel="stylesheet" href="' . u('assets/css/oride.css') . '?v=' . ORIDE_VERSION . '">';
    echo '<script src="' . u('assets/js/oride.js') . '?v=' . ORIDE_VERSION . '" defer></script>';
    echo '</head><body data-i18n="' . h(js_i18n()) . '"><a class="skip" href="#main">' . h(t('skip')) . '</a>';
    echo '<header class="site-header"><div class="container header-row"><a class="brand" href="' . u('index.php') . '" aria-label="ORIDE, ' . h(t('home')) . '">' . logo() . '</a>';
    echo '<div class="header-tools">' . lang_switch() . '<button type="button" class="btn btn-ghost menu-toggle" aria-expanded="false" aria-controls="site-nav">' . h(t('menu')) . '</button></div>';
    echo '<nav id="site-nav" class="site-nav" aria-label="' . h(t('nav_main')) . '"><ul>';
    foreach ($nav as $href => $label) echo '<li><a href="' . u($href) . '"' . ($section === $href ? ' aria-current="page"' : '') . '>' . h($label) . '</a></li>';
    echo '</ul><div class="menu-actions"><a class="btn btn-primary" href="' . u('crear.php') . '"' . ($current === 'crear.php' ? ' aria-current="page"' : '') . '>' . h(t('nav_create')) . '</a></div></nav></div></header>';
    echo '<main id="main">';
}

function page_end(): void
{
    echo '</main><footer class="site-footer"><div class="container footer-row">';
    echo '<div>' . logo(true) . '<p class="muted small">' . h(t('tagline')) . '</p></div>';
    echo '<nav aria-label="' . h(t('footer')) . '"><ul class="footer-links">';
    foreach (['sobre.php' => t('nav_about'), 'legal.php' => t('nav_legal'), 'mejora.php' => t('nav_feedback')] as $href => $label) {
        echo '<li><a href="' . u($href) . '">' . h($label) . '</a></li>';
    }
    echo '</ul></nav>';
    $icons = '';
    foreach (['cc', 'by', 'nc', 'nd'] as $i) $icons .= '<img class="cc-icon" src="https://mirrors.creativecommons.org/presskit/icons/' . $i . '.svg" alt="">';
    $license = '<a href="http://oridecode.com">Oride</a> © 2026 ' . tr('de', 'by') . ' <a href="http://itakora.com">Olga Revilla</a> '
        . tr('está bajo la licencia', 'is licensed under') . ' <a href="https://creativecommons.org/licenses/by-nc-nd/4.0/' . (en() ? '' : 'deed.es') . '" rel="license">CC BY-NC-ND 4.0</a>' . $icons;
    echo '</div><div class="container footer-note"><p class="license">' . $license . '</p><p>' . h(t('no_cookies')) . ' ' . h(t('honor')) . '</p></div></footer></body></html>';
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

/** Código en dos líneas: tríada y actividades. */
function code_segments(array $r): string
{
    $seg = fn(string $v, bool $empty = false) => '<span class="seg' . ($empty ? ' seg-empty' : '') . '">' . h($v) . '</span>';
    $sep = '<span class="sep">-</span>';
    if ($r['status'] === 'empty') return '<span class="code-row">' . $seg(tr('TIPO', 'TYPE') . '–', true) . $sep . $seg('IA–', true) . $sep . $seg('EV–', true) . '</span>';
    $triad = [$seg($r['type']), $r['ia'] === null ? $seg('IA–', true) : $seg('IA' . $r['ia']), $r['ev'] === null ? $seg('EV–', true) : $seg('EV' . $r['ev'])];
    $html = '<span class="code-row">' . implode($sep, $triad) . '</span>';
    $acts = [];
    foreach ($r['acts'] as [$act, $level, $scope]) {
        $done = $level !== null && (!needs_scope($level) || $scope !== null);
        $acts[] = $seg(act_part($act, $level, $scope) . ($done ? '' : '–'), !$done);
    }
    return $html . ($acts ? '<span class="code-row">' . implode($sep, $acts) . '</span>' : '');
}

function explain_list(array $r, bool $reader = false): string
{
    if ($r['status'] === 'empty') return '';
    $html = '<div class="dim"><span>' . h($r['type'] . ' · ' . tr('Tipo de obra', 'Type of work')) . '</span><p class="val">' . h(type_name($r['type'])) . '</p></div>';
    if ($r['ia'] === null) return $html;
    $html .= '<div class="dim"><span>IA' . $r['ia'] . ' · ' . h(tr('Inteligencia artificial', 'Artificial intelligence')) . '</span><p class="val">' . h($r['ia'] ? t('ia1_line') : t('ia0_line')) . '</p></div>';
    if ($r['ev'] !== null) {
        $html .= '<div class="dim"><span>EV' . $r['ev'] . ' · ' . h(t('evidence_title')) . '</span><p class="val">' . h(STR[lang()]['evidence'][$r['ev']]) . '</p>'
            . ($r['ev'] ? '<p class="val note">' . h(t('evidence_note')) . '</p>' : '') . '</div>';
    }
    foreach ($r['acts'] as [$act, $level, $scope]) {
        if ($level === null) continue;
        $html .= '<div class="dim"><span>' . h(act_part($act, $level, $scope) . ' · ' . acts($r['type'])[$act][0]) . '</span>'
            . implode('', array_map(fn($x) => '<p class="val">' . h($x) . '</p>', act_lines($level, $scope, $reader))) . '</div>';
    }
    return $html;
}

/** Aviso cuando se declara IA pero todas las actividades marcadas tienen grado 0. */
function all_zero_note(array $r, bool $reader = false): string
{
    return '<p class="box-warn"' . (!empty($r['all_zero']) ? '' : ' hidden') . ' data-allzero role="status">' . h(t($reader ? 'all_zero_reader' : 'all_zero')) . '</p>';
}

/** Índice para mostrar: porcentaje entero, o intervalo de enteros (el redondeo es solo para mostrar). */
function ig_percent(array $ig): string
{
    $lo = (int) round($ig['IG'][0]);
    $hi = (int) round($ig['IG'][1]);
    return ($lo === $hi ? (string) $lo : $lo . '–' . $hi) . tr(' %', '%');
}

/**
 * Caja del Índice Global ORIDE: destacada, siempre visible. Mientras no se pueda calcular,
 * dice «Pendiente de los valores de las actividades».
 */
function index_panel(array $ig, array $r, array $extra = [], bool $inst = false, bool $copy = true): string
{
    $title = $inst ? t('ig_box_title_max') : t('ig_box_title');
    $html = '<div class="hl-box ig-box" data-index-box><h3 class="ig-head hl-head">' . h($title) . '</h3>';
    if ($ig['status'] !== 'ok') {
        $nc = in_array($ig['reason'], ['config', 'version'], true);
        $html .= '<p class="ig-value ig-pending">' . h($nc ? t('ig_nc') : t('ig_pending')) . '</p>';
        if ($nc) $html .= '<p class="ig-note">' . h(t($ig['reason'] === 'version' ? 'ig_nc_version' : 'ig_nc_config')) . '</p>';
        if ($ig['reason'] === 'all_zero') $html .= '<p class="ig-note">' . h(t('ig_nc_all_zero')) . '</p>';
        return $html . '</div>';
    }
    $single = (int) round($ig['IG'][0]) === (int) round($ig['IG'][1]);
    $html .= '<p class="ig-value-row"><b class="ig-value">' . h(ig_percent($ig)) . '</b>' . ($single ? '' : '<span class="ig-est">' . h(t('ig_estimate')) . '</span>')
        . ($copy ? '<button type="button" class="btn btn-hl" data-copy-index="' . h(ig_line($ig, $inst)) . '">' . h(t('copy_index')) . '</button>' : '') . '</p>';
    $html .= '<details class="hl-details ig-details"><summary>' . h(t('ig_breakdown')) . '</summary><ul>';
    $html .= '<li>' . h(t('ig_G')) . ': <b>' . h(num_range($ig['G'])) . '</b>';
    if ($ig['q'] !== null) {
        $src = ['scope' => 'ig_q_scope', 'question' => 'ig_q_question', 'exact' => 'ig_q_exact'][$ig['q_source']];
        $html .= ' · ' . h(t('ig_q')) . ': ' . h(num_range([$ig['q'][0] * 100, $ig['q'][1] * 100], 1, tr(' %', '%'))) . ' (' . h(t($src)) . ')';
    }
    $html .= '</li><li>' . h(t('ig_P')) . ': <b>' . h(num_range($ig['P'], 3)) . '</b></li>';
    foreach ($ig['rows'] as $row) {
        $parts = [$row['role'] === 'gen' ? t('ig_role_gen') : t('ig_role_aux', num($row['weight'], 2)), t('ig_factor', num($row['factor'], 2))];
        if ($row['scope'] !== null) $parts[] = t('ig_scope', num_range([$row['S'][0] * 100, $row['S'][1] * 100], 0, tr(' %', '%')));
        if ($row['contrib'] !== null) $parts[] = t('ig_contrib', num_range($row['contrib'], 4));
        $html .= '<li class="ig-row"><span class="mono">' . h(act_part($row['act'], $row['level'], $row['scope'])) . '</span> ' . h(implode(' · ', $parts)) . '</li>';
    }
    $html .= '<li>' . h(t('ig_exact', num_range($ig['IG']))) . '</li>';
    $html .= '</ul><p class="ig-ver">' . h(t('ig_version', $ig['version'], $ig['taxonomy'] ?? ORIDE_TAXONOMY, $ig['date'])) . '</p>';
    $html .= '<p class="ig-links"><a href="' . h(u('calc.php?' . http_build_query(['code' => $r['code']] + array_filter(['q' => $extra['q'] ?? null, 'qp' => $extra['qp'] ?? null], fn($v) => $v !== null && $v !== '') + ['format' => 'json']))) . '" download>' . h(t('ig_download')) . '</a>'
        . ' · <a href="' . u('indice.php') . '">' . h(t('ig_how')) . '</a></p></details>';
    return $html . '</div>';
}

/**
 * Caja «Declaración ORIDE»: dentro, la caja «Código» (con su desglose a petición) y la caja «Índice global».
 * @param array{live?:bool, title?:string, intro?:string, actions?:bool, copy?:bool, reader?:bool, extra?:array, verify?:bool} $o
 *   live: la rellena oride.js; intro: frase previa (instituciones); actions: copiar declaración y enlace;
 *   copy: botones de copiar código e índice; extra: respuesta a la pregunta de proporción (q, qp);
 *   verify: botón «Verificar una entrega» (instituciones)
 */
function code_box(array $r, array $o = []): string
{
    $live = !empty($o['live']);
    $actions = $o['actions'] ?? $live;
    $copy = $o['copy'] ?? true;
    $intro = $o['intro'] ?? '';
    $reader = $intro !== '' || !empty($o['reader']);
    $extra = $o['extra'] ?? [];
    $ig = ig_from_result($r, $extra);
    $status = match ($r['status']) {
        'complete' => $r['ia'] === 0 ? t('status_no_ai') : t('status_complete'),
        'pending' => $r['missing'] ?? '',
        default => t('status_empty'),
    };
    $complete = $r['status'] === 'complete';
    $copyCode = $complete ? 'ORIDE ' . implode("\n", $r['lines']) : '';
    $html = '<section class="code-card decl-box" id="declaracion" aria-labelledby="decl-title"' . ($live ? ' data-box' : '') . '><div class="inner">'
        . '<h2 class="label" id="decl-title">' . h($o['title'] ?? t('decl_title')) . '</h2>'
        . ($intro !== '' ? '<p class="intro" data-intro>' . h($intro) . '</p>' : '');
    // Caja «Código»
    $html .= '<div class="hl-box code-box"><div class="hl-top"><h3 class="hl-head">' . h(t('code_box_title')) . '</h3>'
        . ($copy ? '<button type="button" class="btn btn-hl" data-copy-code="' . h($copyCode) . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_code')) . '</button>' : '')
        . '</div><p class="big code-line" data-code aria-live="polite">' . code_segments($r) . '</p>'
        . '<details class="hl-details code-details"><summary>' . h(t('code_breakdown')) . '</summary><div class="dims" data-explain>' . explain_list($r, $reader) . '</div></details></div>';
    // Caja «Índice global»
    $html .= '<div data-index aria-live="polite">' . index_panel($ig, $r, $extra, $intro !== '', $copy) . '</div>'
        . ($live ? '<p class="status" data-status aria-live="polite">' . h($status) . '</p>' : '')
        . all_zero_note($r, $reader);
    if ($actions) {
        $html .= '<div class="box-actions">'
            . '<button type="button" class="btn btn-copy" data-copy-text="' . h(oride_text($r, $intro, $ig, $extra)) . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_text')) . '</button>'
            . '<button type="button" class="btn btn-copy" data-copy-link="' . h($complete ? code_url($r['code'], $extra) : '') . '"' . ($complete ? '' : ' disabled') . '>' . h(t('copy_link')) . '</button>';
        if (!empty($o['verify'])) {
            $html .= '<a class="btn btn-copy" data-verify' . ($complete ? ' href="' . h(u('comprobar.php?r=' . rawurlencode($r['code']))) . '"' : ' aria-disabled="true"') . '>' . h(t('cmp_title')) . '</a>';
        }
        $html .= '</div>';
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

/** Miga de pan: [[enlace, texto], …, [null, página actual]]. */
function breadcrumb(array $items): string
{
    $html = '<nav class="breadcrumb" aria-label="' . h(tr('Ruta', 'Breadcrumb')) . '"><ol>';
    foreach ($items as [$href, $label]) {
        $html .= '<li>' . ($href === null ? '<span aria-current="page">' . h($label) . '</span>' : '<a href="' . u($href) . '">' . h($label) . '</a>') . '</li>';
    }
    return $html . '</ol></nav>';
}
