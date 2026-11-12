<?php
/**
 * ORIDE v10 — núcleo: configuración, utilidades, taxonomía y cálculo del código.
 * Sin base de datos, sin sesiones y sin cookies.
 */
declare(strict_types=1);

define('ORIDE_ROOT', dirname(__DIR__));
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/indice.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/compare.php';
const ORIDE_VERSION = '10.0.0';

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
// Código ORIDE (v9). Dos líneas:
//   1. Tríada: TT-XXX-EV#    tipo de obra (2 letras) · uso de IA · EV0 / EV1 evidencias
//      XXX: IA0  no se usó IA (nunca se convierte en IGA)
//           IGA…IGF  se usó IA; categoría del Índice Global (ORIDE-IG-CAT-1.0), calculada
//           IA1  se usó IA pero no hay categoría (sin actividades o todas a 0) — y códigos anteriores
//   2. Actividades (opcional, solo con IA1): ACT##-ACT##…
//      ACT##  actividad + grado de uso (0–5) + alcance (0–4; 0 con los grados 0 y 1)
// En una sola línea (enlaces) se escribe todo seguido: LI-IGB-EV1-DOC44-GEN24.
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

/** Segundo término: IA0, IGA…IGF o IA1 (véase arriba). Un código anterior conserva su IA1. */
function code_term(array $r): string
{
    if ($r['ia'] === 0) return 'IA0';
    if (!empty($r['keep_ia1'])) return 'IA1';
    return $r['cat'] !== null ? 'IG' . $r['cat'] : 'IA1';
}

/** Las dos líneas del código: [tríada, actividades]. La segunda puede faltar. */
function code_lines(array $r): array
{
    $triad = $r['type'] . '-' . code_term($r) . ($r['ev'] === null ? '' : '-EV' . $r['ev']);
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
    $r = ['status' => 'empty', 'type' => null, 'ia' => null, 'acts' => [], 'ev' => null, 'code' => null, 'lines' => [], 'all_zero' => false, 'missing' => null,
        'cat' => null, 'cat_source' => null, 'keep_ia1' => !empty($in['keep_ia1'])];
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
    $r['all_zero'] = $r['ia'] === 1 && $r['acts'] && !array_filter($r['acts'], fn($x) => $x[1] !== 0);
    // Categoría: primero el Índice Global (algoritmo actual) y después su punto medio.
    if ($r['ia'] === 1) {
        $ig = ig_from_result($r);
        if ($ig['status'] === 'ok') {
            $r['cat'] = ig_classify_range($ig['IG'][0], $ig['IG'][1]);
            $r['cat_source'] = $r['keep_ia1'] ? 'derived' : 'computed';
        } elseif (!$r['acts'] && isset($in['declared_cat'])) {
            // Requisito por categoría sin actividades (p. ej. LI-IGB-EV0): la letra la fija el código.
            $r['cat'] = $in['declared_cat'];
            $r['cat_source'] = 'declared';
        }
    }
    $r['lines'] = code_lines($r);
    $r['code'] = implode('-', $r['lines']);
    return $r;
}

/** Lee las respuestas de la URL (formulario sin JavaScript o enlace compartido). */
function oride_input_from_get(): array
{
    // «Editar requisito» / «Editar declaración» (comprobar.php): ?from=CÓDIGO marca las respuestas de ese código.
    if (get('from') !== '' && ($e = oride_explain(mb_substr(get('from'), 0, 200)))['valid']) return oride_input_from_result($e['result']);
    $type = get('type');
    $u = $s = [];
    $acts = [];
    if (isset(WORK_TYPES[$type])) {
        foreach (array_keys(WORK_TYPES[$type]['acts']) as $act) {
            if (get("u_{$type}_{$act}") !== '') $u[$act] = get("u_{$type}_{$act}");
            if (get("s_{$type}_{$act}") !== '') $s[$act] = get("s_{$type}_{$act}");
        }
        // v10: una actividad se declara si su grado es mayor que 0 («No utilicé IA» no entra en el código).
        // Los enlaces anteriores, con acts_TIPO[], se siguen respetando.
        $acts = isset($_GET['acts_' . $type]) ? get_list('acts_' . $type) : array_keys(array_filter($u, fn($x) => preg_match('/^[1-5]$/', (string) $x)));
    }
    return ['type' => $type, 'ia' => get('ia'), 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => get('ev'), 'q' => get('q'), 'qp' => get('qp')];
}

/** Respuestas del formulario a partir de un código leído (lo contrario de oride_input_from_get()). */
function oride_input_from_result(array $r): array
{
    $in = ['type' => (string) $r['type'], 'ia' => $r['ia'] === null ? '' : ($r['ia'] ? 'si' : 'no'), 'acts' => [], 'u' => [], 's' => [],
        'ev' => $r['ev'] === null ? '' : ($r['ev'] ? 'si' : 'no'), 'q' => '', 'qp' => ''];
    foreach ($r['acts'] as [$act, $level, $scope]) {
        $in['acts'][] = $act;
        if ($level !== null) $in['u'][$act] = (string) $level;
        if ($scope !== null) $in['s'][$act] = (string) $scope;
    }
    return $in;
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
    $ia = preg_match('/^(IA[01]|IG[A-Z])$/', $tok[0] ?? '') ? array_shift($tok) : ($tok && preg_match('/^([A-Z]{3}\d*|E?V[01]?)$/', $tok[0]) ? 'IA1' : array_shift($tok));
    $declaredCat = null;
    if (preg_match('/^IG([A-Z])$/', (string) $ia, $mc)) {
        if (!ig_valid_cat($mc[1])) return ['error' => t('err_cat', (string) $ia)] + $out;
        $declaredCat = $mc[1];
    } elseif ($ia !== 'IA0' && $ia !== 'IA1') {
        return ['error' => t('err_ia')] + $out;
    }
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
    // IA1 con actividades es un código anterior a las categorías: se conserva IA1 y la categoría
    // calculada se muestra solo como información derivada.
    $keepIa1 = $ia === 'IA1' && $acts;
    $r = oride_compute(['type' => $type, 'ia' => 'si', 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => $ev, 'require_ev' => false, 'keep_ia1' => $keepIa1, 'declared_cat' => $declaredCat]);
    // La letra del código debe coincidir con la calculada a partir de sus actividades.
    if ($declaredCat !== null && $acts) {
        if ($r['cat'] === null) return ['error' => t('err_cat_nc', 'IG' . $declaredCat)] + $out;
        if ($r['cat'] !== $declaredCat) return ['error' => t('err_cat_mismatch', 'IG' . $declaredCat, 'IG' . $r['cat'])] + $out;
    }
    return ['valid' => true, 'legacy' => $legacy || $keepIa1, 'incomplete' => $incomplete, 'result' => $r] + $out;
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
    $q = ['ig' => substr(IG_CURRENT, strlen('ORIDE-IG-')), 'igc' => substr(IG_CAT_VERSION, strlen('ORIDE-IG-CAT-'))];
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

/** Categoría que se muestra: la del índice calculado o, sin él, la fijada en el código. */
function shown_cat(array $ig, array $r): ?string
{
    if ($ig['status'] === 'ok') return ig_classify_range($ig['IG'][0], $ig['IG'][1]);
    return $r['cat'] ?? null;
}

/** Línea del índice para copiar: «Índice Global ORIDE: B — Bajo (18–23 % · versiones)». */
function ig_line(array $ig, array $r, bool $inst = false): string
{
    $cat = shown_cat($ig, $r);
    if ($cat === null) return '';
    $title = $inst ? t('ig_title_max') : t('ig_title');
    $info = $ig['status'] === 'ok' ? [ig_percent($ig), IG_CAT_VERSION, $ig['version']] : [IG_CAT_VERSION];
    return t('ig_line', $title, ig_cat_label($cat), implode(' · ', $info));
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
    if ($ig && ($line = ig_line($ig, $r, $intro !== '')) !== '') $lines[] = $line;
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
    echo '</ul><div class="menu-actions"><a class="btn btn-primary" href="' . u('crear.php') . '"' . ($current === 'crear.php' ? ' aria-current="page"' : '') . '>' . h(t('nav_create')) . '</a>'
        . '<a class="btn btn-primary" href="' . u('leer.php') . '"' . (in_array($current, ['leer.php', 'oride.php'], true) ? ' aria-current="page"' : '') . '>' . h(t('nav_read')) . '</a></div></nav></div></header>';
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
    // Segundo término: IA0; con IA, la categoría (IGA…IGF) en cuanto se pueda calcular.
    $term = match (true) {
        $r['ia'] === null => $seg('IA–', true),
        $r['status'] === 'complete' => $seg(code_term($r)),
        $r['ia'] === 1 && $r['acts'] => $seg('IG–', true),
        default => $seg('IA' . $r['ia']),
    };
    $triad = [$seg($r['type']), $term, $r['ev'] === null ? $seg('EV–', true) : $seg('EV' . $r['ev'])];
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
    $term = $r['status'] === 'complete' ? code_term($r) : 'IA' . $r['ia'];
    $val = match (true) {
        $r['ia'] === 0 => t('ia0_line'),
        str_starts_with($term, 'IG') => t('term_ig', ig_cat_label($r['cat'])),
        $r['status'] === 'complete' && $r['cat'] === null => t('term_ia1'),
        default => t('ia1_line'),
    };
    $html .= '<div class="dim"><span>' . h($term . ' · ' . tr('Inteligencia artificial', 'Artificial intelligence')) . '</span><p class="val">' . h($val) . '</p>'
        . ($r['cat_source'] === 'derived' ? '<p class="val note">' . h(t('ig_derived_val', ig_cat_label($r['cat']))) . '</p>' : '')
        . ($r['cat_source'] === 'declared' ? '<p class="val note">' . h(t('ig_cat_declared')) . '</p>' : '') . '</div>';
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
function all_zero_note(array $r, bool $reader = false, bool $live = false): string
{
    $text = t($reader ? 'all_zero_reader' : 'all_zero');
    if (!empty($r['all_zero'])) return '<p class="box-warn" data-allzero data-text="' . h($text) . '" role="status">' . h($text) . '</p>';
    // Oculto y vacío: el texto solo existe cuando hace falta (no se indexa un aviso que no aplica).
    return $live ? '<p class="box-warn" hidden data-allzero data-text="' . h($text) . '" role="status"></p>' : '';
}

/** Índice para mostrar: porcentaje entero, o intervalo de enteros (el redondeo es solo para mostrar). */
function ig_percent(array $ig): string
{
    $lo = (int) round($ig['IG'][0]);
    $hi = (int) round($ig['IG'][1]);
    return ($lo === $hi ? (string) $lo : $lo . '–' . $hi) . tr(' %', '%');
}

/**
 * Lo que muestra la caja de la declaración (v10): el Índice Global en primer plano, explicado con palabras.
 * @return array{head:string, typeName:?string, title:string, body:string, mid:?float, interval:?array, cat:?string,
 *               ev:bool, evText:string, lines:array, complete:bool}
 *   mid: punto de la escala (0–100) al que apunta el globo; interval: intervalo estimado.
 */
function decl_view(array $r, array $ig, bool $inst = false, bool $reader = false): array
{
    $v = ['head' => $inst ? t('req_title') : t('decl_title'), 'typeName' => $r['type'] !== null ? type_name($r['type']) : null,
        'title' => '', 'body' => '', 'mid' => null, 'interval' => null, 'cat' => null, 'ev' => $r['ev'] === 1,
        'evText' => $inst ? tr('Se exige poder mostrar el proceso de creación', 'Being able to show the creative process is required') : tr('Se puede mostrar el proceso de creación', 'The creative process can be shown'),
        'lines' => [], 'complete' => $r['status'] === 'complete'];
    $catWord = fn(string $c) => mb_strtolower(IG_CAT_LABELS[lang()][$c]);
    $pc = fn(float $x) => (string) (int) round($x);
    $nb = "\u{00a0}";
    // Código en curso (con huecos «–» mientras faltan respuestas)
    if ($r['status'] === 'empty') {
        $v['lines'] = [tr('TIPO', 'TYPE') . '–IA–EV–'];
    } elseif ($r['status'] === 'complete') {
        $v['lines'] = $r['lines'];
    } else {
        $term = $r['ia'] === null ? 'IA–' : ($r['ia'] === 0 ? 'IA0' : ($r['acts'] ? 'IG–' : 'IA1'));
        $v['lines'] = [$r['type'] . '-' . $term . '-' . ($r['ev'] === null ? 'EV–' : 'EV' . $r['ev'])];
        $acts = array_map(fn($x) => act_part($x[0], $x[1], $x[2]) . ($x[1] === null || (needs_scope($x[1]) && $x[2] === null) ? '–' : ''), $r['acts']);
        if ($acts) $v['lines'][] = implode('-', $acts);
    }

    if ($r['status'] === 'empty') {
        return ['title' => tr('Empieza por el tipo de obra', 'Start with the type of work'),
            'body' => $inst ? tr('Responde a las preguntas y aquí verás el uso máximo de IA que permites.', 'Answer the questions and you will see here the maximum AI use you allow.')
                : tr('Responde a las preguntas y aquí verás cuánto aportó la IA a tu obra.', 'Answer the questions and you will see here how much AI contributed to your work.')] + $v;
    }
    if ($r['ia'] === null) {
        return ['title' => $inst ? tr('¿Permites usar IA?', 'Do you allow AI?') : tr('¿Usaste IA?', 'Did you use AI?'),
            'body' => tr('Indica si se utilizó inteligencia artificial en esta obra.', 'Say whether artificial intelligence was used in this work.')] + $v;
    }
    if ($r['ia'] === 0) {
        $v['mid'] = 0.0;
        return $inst
            ? ['title' => tr('No se permite usar IA', 'AI use is not allowed'), 'body' => tr('Las obras tienen que estar 100' . $nb . '% libres de IA.', 'Works must be 100' . $nb . '% AI-free.')] + $v
            : ['title' => tr('Obra 100' . $nb . '% libre de IA', '100' . $nb . '% AI-free work'), 'body' => tr('La persona autora declara que no utilizó inteligencia artificial en esta obra.', 'The author declares that no artificial intelligence was used in this work.')] + $v;
    }
    if ($r['status'] !== 'complete') {
        if ($r['ev'] === null && $r['missing'] === t('miss_ev')) {
            return ['title' => tr('Se utilizó IA', 'AI was used'), 'body' => $inst ? tr('Indica si exiges evidencias del proceso.', 'Say whether you require evidence of the process.') : tr('Indica si conservas evidencias de tu proceso.', 'Say whether you keep evidence of your process.')] + $v;
        }
        return ['title' => tr('Falta un dato', 'One answer is missing'), 'body' => (string) $r['missing']] + $v;
    }
    $cat = shown_cat($ig, $r);
    if ($ig['status'] === 'ok') {
        $v['cat'] = $cat;
        $v['interval'] = $ig['IG'];
        $v['mid'] = ig_midpoint($ig['IG'][0], $ig['IG'][1]);
        $lo = $pc($ig['IG'][0]);
        $hi = $pc($ig['IG'][1]);
        if ($inst) {
            return ['title' => tr('Uso máximo de IA: ' . $catWord($cat), 'Maximum AI use: ' . $catWord($cat)),
                'body' => tr('Cumplen las obras con un uso de IA ' . $catWord($cat) . ' o menor (hasta el ' . $pc(IG_CATS[$cat][1]) . $nb . '%), sin superar los límites de cada actividad.',
                    'Works comply if their AI use is ' . $catWord($cat) . ' or lower (up to ' . $pc(IG_CATS[$cat][1]) . '%), within the limits for each activity.')] + $v;
        }
        $amount = match (true) {
            $hi === '0' && $ig['IG'][1] > 0 => tr('menos del 1' . $nb . '%', 'less than 1%'),
            $lo === $hi => tr('alrededor del' . $nb . $lo . $nb . '%', 'about ' . $lo . '%'),
            default => tr('entre el ' . $lo . $nb . '% y el ' . $hi . $nb . '%', 'between ' . $lo . '% and ' . $hi . '%'),
        };
        return ['title' => tr('Uso de IA ' . $catWord($cat), 'AI use: ' . $catWord($cat)),
            'body' => tr('Se estima que la IA aportó ' . $amount . ' de esta obra, según lo declarado.', 'AI is estimated to have contributed ' . $amount . ' of this work, as declared.')] + $v;
    }
    if ($cat !== null) { // categoría fijada en el código, sin actividades (requisitos)
        $v['cat'] = $cat;
        return ['title' => $inst ? tr('Uso máximo de IA: ' . $catWord($cat), 'Maximum AI use: ' . $catWord($cat)) : tr('Uso de IA ' . $catWord($cat), 'AI use: ' . $catWord($cat)),
            'body' => t('ig_cat_declared')] + $v;
    }
    if ($r['all_zero']) return ['title' => tr('Se utilizó IA', 'AI was used'), 'body' => t('all_zero_reader')] + $v;
    if ($inst) return ['title' => tr('Se permite usar IA', 'AI use is allowed'), 'body' => tr('Sin límites por actividad.', 'No limits per activity.')] + $v;
    return ['title' => tr('Se utilizó IA', 'AI was used'), 'body' => $reader
        ? tr('No se detalla en qué actividades, así que no se puede estimar cuánto aportó.', 'It does not say in which activities, so its contribution cannot be estimated.')
        : tr('Indica en qué actividades la usaste para estimar cuánto aportó a la obra.', 'Say in which activities you used it to estimate how much it contributed.')] + $v;
}

/** Corta un texto en líneas de como mucho $max caracteres (los espacios duros no se parten). */
function svg_wrap(string $text, int $max): array
{
    $lines = [];
    $line = '';
    foreach (explode(' ', $text) as $w) {
        if ($line !== '' && mb_strlen($line . ' ' . $w) > $max) { $lines[] = $line; $line = $w; }
        else $line = trim($line . ' ' . $w);
    }
    if ($line !== '') $lines[] = $line;
    return $lines;
}

/**
 * Figura de la declaración en SVG: tipo de obra, globo con la explicación del índice y escala de 0 a 100 %
 * en seis tramos (A–F, proporcionales). $width: 720 (ancha) o 360 (estrecha). $footer: código y versión al pie (descargas).
 */
function decl_svg(array $v, int $width = 720, bool $footer = false): string
{
    $W = $width;
    $narrow = $W < 560;
    $X0 = $narrow ? 14 : 40;
    $X1 = $W - $X0;
    $px = fn(float $p) => round($X0 + ($X1 - $X0) * $p / 100, 2);
    $H = $footer ? ($narrow ? 356 : 336) : 300;
    $nb = "\u{00a0}";
    $font = 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif';
    $label = $v['head'] . ($v['typeName'] ? ' · ' . $v['typeName'] : '') . '. ' . $v['title'] . '. ' . $v['body'] . ($v['ev'] ? ' ' . $v['evText'] . '.' : '');
    $s = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $W . ' ' . $H . '" width="' . $W . '" height="' . $H . '" role="img" aria-label="' . h($label) . '" font-family="' . $font . '">';
    $s .= '<title>' . h($label) . '</title>';
    if ($footer) $s .= '<rect width="' . $W . '" height="' . $H . '" fill="#ffffff"/>';
    $s .= '<text x="' . $X0 . '" y="30" font-size="12" font-weight="600" letter-spacing="2" fill="#154849">' . h(mb_strtoupper($v['head'])) . '</text>';
    $s .= '<text x="' . $X0 . '" y="62" font-size="26" font-weight="700" fill="' . ($v['typeName'] ? '#0a2b2c' : '#6b7a7b') . '">' . h($v['typeName'] ?? tr('Tipo de obra', 'Type of work')) . '</text>';

    // Escala
    $barY = 206;
    $barH = 34;
    $i = 0;
    foreach (IG_CATS as $k => [$lo, $hi]) {
        $a = $px($lo);
        $b = $px($hi);
        $here = $v['cat'] === $k;
        $s .= '<rect x="' . $a . '" y="' . $barY . '" width="' . round($b - $a, 2) . '" height="' . $barH . '" fill="' . ($here ? '#0e3b3c' : ($i % 2 ? '#a9d2cd' : '#ffffff')) . '"/>';
        $name = IG_CAT_LABELS[lang()][$k];
        if (mb_strlen($name) * 6.6 + 6 <= $b - $a) {
            $s .= '<text x="' . round(($a + $b) / 2, 2) . '" y="' . ($barY + 21) . '" font-size="11.5" text-anchor="middle" font-weight="' . ($here ? 700 : 500) . '" fill="' . ($here ? '#ffffff' : '#0a2b2c') . '">' . h($name) . '</text>';
        }
        $i++;
    }
    foreach (IG_CATS as [, $hi]) $s .= '<line x1="' . $px($hi) . '" y1="' . $barY . '" x2="' . $px($hi) . '" y2="' . ($barY + $barH) . '" stroke="#0e3b3c" stroke-width="1.5"/>';
    $s .= '<rect x="' . $X0 . '" y="' . $barY . '" width="' . ($X1 - $X0) . '" height="' . $barH . '" fill="none" stroke="#0e3b3c" stroke-width="2"/>';
    foreach ($narrow ? [0, 25, 50, 75, 100] : [0, 10, 25, 50, 75, 90, 100] as $p) {
        $s .= '<line x1="' . $px($p) . '" y1="' . ($barY + $barH) . '" x2="' . $px($p) . '" y2="' . ($barY + $barH + 6) . '" stroke="#0e3b3c" stroke-width="1.5"/>';
        $s .= '<text x="' . $px($p) . '" y="' . ($barY + $barH + 22) . '" font-size="12" text-anchor="' . ($p === 100 && $narrow ? 'end' : 'middle') . '" fill="#5b6b6c">' . $p . ($p === 100 ? $nb . '%' : '') . '</text>';
    }
    if ($v['interval']) {
        $a0 = $px($v['interval'][0]);
        $b0 = $px($v['interval'][1]);
        if ($b0 - $a0 < 3) { $a0 -= 1.5; $b0 += 1.5; }
        $s .= '<rect x="' . $a0 . '" y="' . ($barY - 7) . '" width="' . round($b0 - $a0, 2) . '" height="5" rx="2" fill="#2ba39a"/>';
    }

    // Globo de diálogo (con el tick de evidencias junto al título)
    $bw = min(380, $W - 24);
    $body = svg_wrap($v['body'], (int) floor(($bw - 32) / 7.4));
    $bh = 34 + count($body) * 20 + 10;
    $by = $barY - 22 - $bh;
    $tipX = $v['mid'] === null ? null : $px($v['mid']);
    $bx = max(12, min($W - 12 - $bw, ($tipX ?? $W / 2) - $bw / 2));
    $tail = $tipX === null ? '' : ' L ' . min(max($tipX + 9, $bx + 18), $bx + $bw - 6) . ' ' . ($by + $bh) . ' L ' . $tipX . ' ' . ($barY - 9) . ' L ' . max(min($tipX - 9, $bx + $bw - 18), $bx + 6) . ' ' . ($by + $bh);
    $path = 'M ' . ($bx + 10) . ' ' . $by . ' H ' . ($bx + $bw - 10) . ' Q ' . ($bx + $bw) . ' ' . $by . ' ' . ($bx + $bw) . ' ' . ($by + 10)
        . ' V ' . ($by + $bh - 10) . ' Q ' . ($bx + $bw) . ' ' . ($by + $bh) . ' ' . ($bx + $bw - 10) . ' ' . ($by + $bh) . $tail
        . ' H ' . ($bx + 10) . ' Q ' . $bx . ' ' . ($by + $bh) . ' ' . $bx . ' ' . ($by + $bh - 10) . ' V ' . ($by + 10) . ' Q ' . $bx . ' ' . $by . ' ' . ($bx + 10) . ' ' . $by . ' Z';
    $s .= '<path d="' . $path . '" fill="#eef6f5" stroke="#0e3b3c" stroke-width="2" stroke-linejoin="round"/>';
    $s .= '<text x="' . ($bx + 16) . '" y="' . ($by + 26) . '" font-size="17" font-weight="700" fill="#0a2b2c">' . h($v['title']) . '</text>';
    if ($v['ev']) {
        $cx = min($bx + 16 + mb_strlen($v['title']) * 9.6 + 20, $bx + $bw - 16);
        $cy = $by + 20;
        $s .= '<g data-ev-tick><title>' . h($v['evText']) . '</title><circle cx="' . $cx . '" cy="' . $cy . '" r="10" fill="#1f6b45"/><path d="M ' . ($cx - 5) . ' ' . $cy . ' l 3.5 3.5 l 6.5 -7" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></g>';
    }
    foreach ($body as $n => $l) $s .= '<text x="' . ($bx + 16) . '" y="' . ($by + 50 + $n * 20) . '" font-size="14.5" fill="#1c2526">' . h($l) . '</text>';

    if ($footer) {
        $y = $H - ($narrow ? 36 : 16);
        $s .= '<line x1="' . $X0 . '" y1="' . ($H - ($narrow ? 60 : 40)) . '" x2="' . $X1 . '" y2="' . ($H - ($narrow ? 60 : 40)) . '" stroke="#e3e8e6"/>';
        $s .= '<text x="' . $X0 . '" y="' . $y . '" font-size="12" fill="#5b6b6c">' . h(t('code_label')) . ' <tspan font-family="ui-monospace, Menlo, Consolas, monospace" fill="#1c2526">' . h(implode(' ', $v['lines'])) . '</tspan></text>';
        $s .= '<text x="' . ($narrow ? $X0 : $X1) . '" y="' . ($narrow ? $y + 20 : $y) . '" font-size="11" text-anchor="' . ($narrow ? 'start' : 'end') . '" fill="#5b6b6c">' . h(($v['ev'] ? '✓ ' . $v['evText'] . ' · ' : '') . IG_CURRENT) . '</text>';
    }
    return $s . '</svg>';
}

/** La figura para la página: ancha y estrecha (se elige por el ancho de la caja) y la versión para descargar. */
function decl_figure(array $v): string
{
    return '<div class="fig fig-wide">' . decl_svg($v, 720) . '</div><div class="fig fig-narrow">' . decl_svg($v, 360) . '</div>'
        . '<template data-export>' . decl_svg($v, 720, true) . '</template>';
}

/** Texto de la figura para lectores de pantalla y para la barra fija del móvil. */
function decl_text(array $v): string
{
    return $v['head'] . ($v['typeName'] ? ', ' . $v['typeName'] : '') . '. ' . $v['title'] . '. ' . $v['body'] . ($v['ev'] ? ' ' . $v['evText'] . '.' : '');
}

/** Desglose del cálculo (oride.php): intervalo, punto medio, categoría, G, P, actividades y versiones. */
function ig_breakdown(array $ig, array $r, array $extra = []): string
{
    if ($r['ia'] === 0) return '<p>' . h(t('ig_no_ai_note')) . '</p>';
    if ($ig['status'] !== 'ok') {
        $cat = shown_cat($ig, $r);
        return '<p>' . h($cat !== null ? t('ig_cat_declared') : ($r['all_zero'] ? t('ig_nc_all_zero') : t('ig_nc_no_acts'))) . '</p>';
    }
    $cat = shown_cat($ig, $r);
    $html = '<ul class="breakdown-list">';
    $html .= '<li>' . h(t('ig_interval', num_range($ig['IG'], 2, tr(' %', '%')))) . '</li>'
        . '<li>' . h(t('ig_midpoint_line', num(ig_midpoint($ig['IG'][0], $ig['IG'][1]), 2) . tr(' %', '%'))) . '</li>'
        . '<li>' . h(t('ig_cat_line', IG_CAT_LABELS[lang()][$cat] . ' (' . ig_cat_range_text($cat) . ')')) . '</li>';
    $html .= '<li>' . h(t('ig_G')) . ': <b>' . h(num_range($ig['G'])) . '</b>';
    if ($ig['q'] !== null) {
        $src = ['scope' => 'ig_q_scope', 'question' => 'ig_q_question', 'exact' => 'ig_q_exact'][$ig['q_source']];
        $html .= ' · ' . h(t('ig_q')) . ': ' . h(num_range([$ig['q'][0] * 100, $ig['q'][1] * 100], 1, tr(' %', '%'))) . ' (' . h(t($src)) . ')';
    }
    $html .= '</li><li>' . h(t('ig_P')) . ': <b>' . h(num_range($ig['P'], 3)) . '</b></li><li>IG = G + (100 − G) · P</li>';
    foreach ($ig['rows'] as $row) {
        $parts = [$row['role'] === 'gen' ? t('ig_role_gen') : t('ig_role_aux', num($row['weight'], 2)), t('ig_factor', num($row['factor'], 2))];
        if ($row['scope'] !== null) $parts[] = t('ig_scope', num_range([$row['S'][0] * 100, $row['S'][1] * 100], 0, tr(' %', '%')));
        if ($row['contrib'] !== null) $parts[] = t('ig_contrib', num_range($row['contrib'], 4));
        $html .= '<li><span class="mono">' . h(act_part($row['act'], $row['level'], $row['scope'])) . '</span> ' . h(acts($r['type'])[$row['act']][0] . ': ' . implode(' · ', $parts)) . '</li>';
    }
    $html .= '<li>' . h(t('ig_exact', num_range($ig['IG']))) . '</li></ul>';
    $html .= '<p class="ig-ver">' . h(t('ig_versions', $ig['version'], IG_CAT_VERSION, $ig['taxonomy'] ?? ORIDE_TAXONOMY)) . '</p>';
    $html .= '<p class="ig-links"><a href="' . h(u('calc.php?' . http_build_query(['code' => $r['code']] + array_filter(['q' => $extra['q'] ?? null, 'qp' => $extra['qp'] ?? null], fn($x) => $x !== null && $x !== '') + ['format' => 'json']))) . '" download>' . h(t('ig_download')) . '</a>'
        . ' · <a href="' . u('indice.php') . '">' . h(t('ig_how')) . '</a></p>';
    return $html;
}

/**
 * Caja «Declaración ORIDE» (v10): figura con el índice, descargas en PNG y SVG y, en letra pequeña,
 * el código con «Copiar código» y «Copiar enlace».
 * @param array{live?:bool, inst?:bool, reader?:bool, actions?:bool, extra?:array, verify?:bool} $o
 *   live: la rellena oride.js (crear.php e instituciones.php); inst: requisito; reader: para quien lee el código;
 *   actions: descargas y botones de copiar; verify: botón «Verificar una entrega» (instituciones)
 */
function code_box(array $r, array $o = []): string
{
    $live = !empty($o['live']);
    $inst = !empty($o['inst']);
    $actions = $o['actions'] ?? true;
    $extra = $o['extra'] ?? [];
    $ig = ig_from_result($r, $extra);
    $v = decl_view($r, $ig, $inst, !empty($o['reader']));
    $complete = $v['complete'];
    $dis = $complete ? '' : ' disabled';
    $html = '<section class="decl-box" id="declaracion" aria-labelledby="decl-title"' . ($live ? ' data-box' : '') . ' data-file="' . h($complete ? 'oride-' . strtolower($r['code']) : 'oride') . '">'
        . '<h2 class="sr-only" id="decl-title">' . h($v['head']) . '</h2>'
        . '<div class="decl-figure" data-figure aria-hidden="true">' . decl_figure($v) . '</div>'
        . '<p class="sr-only" data-figure-text' . ($live ? ' aria-live="polite"' : '') . '>' . h(decl_text($v)) . '</p>';
    if ($actions) {
        $html .= '<div class="decl-actions">'
            . '<button type="button" class="btn btn-secondary btn-sm" data-download="png"' . $dis . '>' . h(tr('Descargar imagen PNG', 'Download PNG image')) . '</button>'
            . '<button type="button" class="btn btn-secondary btn-sm" data-download="svg"' . $dis . '>' . h(tr('Descargar imagen SVG', 'Download SVG image')) . '</button>';
        if (!empty($o['verify'])) {
            $html .= '<a class="btn btn-primary btn-sm" data-verify' . ($complete ? ' href="' . h(u('comprobar.php?r=' . rawurlencode($r['code']))) . '"' : ' aria-disabled="true"') . '>' . h(t('cmp_title')) . '</a>';
        }
        $html .= '</div>';
    }
    $html .= '<div class="decl-code"><span>' . h(t('code_label')) . ' <code data-code>' . h(implode(' ', $v['lines'])) . '</code></span>';
    if ($actions) {
        $html .= '<button type="button" class="link-btn" data-copy-code="' . h($complete ? 'ORIDE ' . implode("\n", $r['lines']) : '') . '"' . $dis . '>' . h(t('copy_code')) . '</button>'
            . '<button type="button" class="link-btn" data-copy-link="' . h($complete ? code_url($r['code'], $extra) : '') . '"' . $dis . '>' . h(t('copy_link')) . '</button>';
    }
    return $html . '</div></section>';
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
