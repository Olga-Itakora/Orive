<?php
/**
 * ORIDE v2 — núcleo: configuración, utilidades, taxonomía y cálculo del código.
 * Sin base de datos, sin sesiones y sin cookies.
 */
declare(strict_types=1);

define('ORIDE_ROOT', dirname(__DIR__));
const ORIDE_VERSION = '2.0.0';

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

function u(string $path = ''): string
{
    return base_path() . ltrim($path, '/');
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
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error · ORIDE</title><body><p>Algo ha fallado en el servidor. Vuelve a intentarlo; si persiste, escríbenos a info@itakora.com indicando la referencia ' . $ref . '.</p></body></html>';
});
ini_set('log_errors', '1');
ini_set('error_log', ORIDE_ROOT . '/storage/php-errors.log');
ini_set('display_errors', cfg('debug') ? '1' : '0');

// ---------------------------------------------------------------------------
// Taxonomía del Código ORIDE (v2: dimensiones A, B y C)
// ---------------------------------------------------------------------------

const ORIDE_DIMS = [
    'A' => ['name' => 'Función de la IA', 'question' => '¿Para qué utilizaste la IA?', 'values' => [
        'A0' => ['No se utilizó IA', ''],
        'A1' => ['Consulta o documentación', 'Buscar datos, contexto o referencias.'],
        'A2' => ['Ideas o sugerencias', 'Tramas, títulos, enfoques o alternativas.'],
        'A3' => ['Revisión o crítica', 'Comentarios o evaluación de una versión.'],
        'A4' => ['Corrección, edición o traducción', 'Ortografía, estilo, traducción o retoque.'],
        'A5' => ['Generación de contenido', 'Texto, imagen, música, vídeo u otros elementos.'],
    ]],
    'B' => ['name' => 'Qué se incorporó a la obra', 'question' => '¿Qué se incorporó?', 'values' => [
        'B0' => ['Nada incorporado', ''],
        'B1' => ['Aportación conceptual', 'Ideas o estructuras que después desarrollaste tú.', 'las ideas o estructuras'],
        'B2' => ['Aportación formal', 'Correcciones, reformulaciones, traducciones o cambios de estilo.', 'los cambios formales'],
        'B3' => ['Incorporación de contenido generado por IA', 'Texto, imagen, música, vídeo o código generado con IA.', 'el contenido generado por IA'],
    ]],
    'C' => ['name' => 'Intervención humana', 'question' => '¿Qué hiciste con ello?', 'values' => [
        'C0' => ['No aplicable', ''],
        'C1' => ['Selección o aceptación', 'Lo aceptaste o elegiste sin cambios significativos.'],
        'C2' => ['Edición puntual', 'Hiciste ajustes menores.'],
        'C3' => ['Transformación sustancial', 'Lo cambiaste mucho, pero se reconocen elementos del original.'],
        'C4' => ['Reelaboración creativa', 'El resultado es una expresión claramente diferente.'],
        'C5' => ['Creación propia a partir de referencias', 'Tomaste la idea o la referencia; la expresión final es tuya.'],
    ]],
];

function label(string $v): string
{
    return ORIDE_DIMS[$v[0]]['values'][$v][0] ?? $v;
}

/** Nombre corto de un tipo de aportación B, para frases («los cambios formales»). */
function b_short(string $b): string
{
    return ORIDE_DIMS['B']['values'][$b][2] ?? mb_strtolower(label($b));
}

/** Une valores de una dimensión: [A1, A3, A5] → «A135». */
function join_values(string $dim, array $values): string
{
    $digits = array_unique(array_map(fn($v) => (int) substr($v, 1), $values));
    sort($digits);
    return $dim . implode('', $digits);
}

/**
 * Calcula el código a partir de las respuestas del formulario.
 *
 * @param array{ia?:string,a?:array,inc?:string,b?:array,c?:array} $in
 * @return array{status:string, code:?string, a:array, b:array, pairs:array, missing:?string}
 *   status: empty (sin responder) | pending (faltan respuestas) | complete
 */
function oride_compute(array $in): array
{
    $r = ['status' => 'empty', 'code' => null, 'a' => [], 'b' => [], 'pairs' => [], 'missing' => null];
    $ia = $in['ia'] ?? '';
    if ($ia === 'no') return ['status' => 'complete', 'code' => 'A0B0C0', 'a' => ['A0'], 'b' => ['B0'], 'pairs' => [['B0', 'C0']], 'missing' => null];
    if ($ia !== 'si') return $r;

    $r['status'] = 'pending';
    $r['a'] = array_values(array_intersect(['A1', 'A2', 'A3', 'A4', 'A5'], (array) ($in['a'] ?? [])));
    if (!$r['a']) return ['missing' => 'Indica para qué utilizaste la IA.'] + $r;

    $inc = $in['inc'] ?? '';
    if ($inc === 'no') {
        $r['b'] = ['B0'];
        $r['pairs'] = [['B0', 'C0']];
        $r['status'] = 'complete';
        $r['code'] = join_values('A', $r['a']) . 'B0C0';
        return $r;
    }
    if ($inc !== 'si') return ['missing' => 'Indica si se incorporó a la obra algo procedente de la IA.'] + $r;

    $r['b'] = array_values(array_intersect(['B1', 'B2', 'B3'], (array) ($in['b'] ?? [])));
    if (!$r['b']) return ['missing' => 'Indica qué se incorporó a la obra.'] + $r;
    $c = (array) ($in['c'] ?? []);
    foreach ($r['b'] as $b) {
        $cv = $c[$b] ?? '';
        if (!in_array($cv, ['C1', 'C2', 'C3', 'C4', 'C5'], true)) return ['missing' => 'Indica qué hiciste con ' . b_short($b) . '.'] + $r;
        $r['pairs'][] = [$b, $cv];
    }
    $r['status'] = 'complete';
    $r['code'] = join_values('A', $r['a']) . join_values('B', $r['b']) . join_values('C', array_column($r['pairs'], 1));
    return $r;
}

/** Lee las respuestas de la URL (formulario sin JavaScript o enlace compartido). */
function oride_input_from_get(): array
{
    $c = [];
    foreach (['B1', 'B2', 'B3'] as $b) if (get('c' . $b) !== '') $c[$b] = get('c' . $b);
    return ['ia' => get('ia'), 'a' => get_list('a'), 'inc' => get('inc'), 'b' => get_list('b'), 'c' => $c];
}

/**
 * Normaliza un código escrito por alguien: admite «ORIDE », espacios, minúsculas,
 * la notación antigua con «+» y «|» (A2+A4 → A24) y descarta la D de la versión anterior.
 */
function oride_normalize(string $s): string
{
    $s = mb_strtoupper(trim($s));
    $s = preg_replace('/^(C[OÓ]DIGO\s+)?ORIDE\s*/u', '', $s);
    $s = preg_replace('/\+([A-D])/', '', $s);
    $s = preg_replace('/[\s|·]+/u', '', $s);
    return preg_replace('/D[0-3]$/', '', $s);
}

/**
 * Explica un código. @return array{valid:bool, code:string, error:?string, parts:array<string,array<string>>}
 */
function oride_explain(string $raw): array
{
    $code = oride_normalize($raw);
    $out = ['valid' => false, 'code' => $code, 'error' => null, 'parts' => []];
    if (!preg_match('/^A([0-5]+)B([0-3]+)C([0-5]+)$/', $code, $m)) {
        $out['error'] = 'Un código ORIDE tiene la forma A…B…C…, por ejemplo A24B13C15.';
        return $out;
    }
    foreach (['A' => $m[1], 'B' => $m[2], 'C' => $m[3]] as $dim => $digits) {
        $d = str_split($digits);
        $sorted = $d;
        sort($sorted);
        if ($d !== $sorted || count(array_unique($d)) !== count($d)) {
            $out['error'] = "En la dimensión $dim las cifras van en orden creciente y sin repetir (por ejemplo {$dim}" . implode('', array_unique($sorted)) . ').';
            return $out;
        }
        if (in_array('0', $d, true) && count($d) > 1) {
            $out['error'] = "{$dim}0 no puede combinarse con otros valores de {$dim}.";
            return $out;
        }
        $out['parts'][$dim] = array_map(fn($x) => $dim . $x, $d);
    }
    [$a, $b, $c] = [$out['parts']['A'], $out['parts']['B'], $out['parts']['C']];
    if ($a === ['A0'] && ($b !== ['B0'] || $c !== ['C0'])) $out['error'] = 'A0 (no se utilizó IA) solo puede ir con B0 y C0.';
    elseif (($b === ['B0']) !== ($c === ['C0'])) $out['error'] = 'B0 y C0 van siempre juntos: si no se incorporó nada, no hay intervención que describir.';
    elseif ($b !== ['B0'] && count($c) > count($b)) $out['error'] = 'Cada tipo de aportación (B) tiene una sola intervención (C): no puede haber más valores C que B.';
    if ($out['error']) return $out;
    $out['valid'] = true;
    return $out;
}

/** Texto plano de la declaración (para copiar). */
function oride_text(array $r): string
{
    if ($r['status'] !== 'complete') return '';
    if ($r['code'] === 'A0B0C0') return "Código ORIDE A0B0C0\nNo se utilizó inteligencia artificial en la elaboración de esta obra.";
    $lines = ['Código ORIDE ' . $r['code'], 'Función de la IA: ' . implode('; ', array_map('label', $r['a']))];
    if ($r['b'] === ['B0']) $lines[] = 'No se incorporó a la obra nada procedente de la IA.';
    else foreach ($r['pairs'] as [$b, $c]) $lines[] = 'Incorporado a la obra: ' . label($b) . ' · Intervención humana: ' . label($c);
    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// Plantilla
// ---------------------------------------------------------------------------

function logo(bool $descriptor = false): string
{
    return '<span class="logo"><svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M26.5 10.5A12 12 0 1 0 28 16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="26.9" cy="10.9" r="3.1" fill="#2ba39a"/><circle cx="16" cy="16" r="3.2" fill="currentColor"/></svg>'
        . '<span><span class="logo-word">ORIDE</span>' . ($descriptor ? '<span class="logo-desc">Originalidad Declarada</span>' : '') . '</span></span>';
}

const NAV = ['como-funciona.php' => 'Cómo funciona', 'codigo.php' => 'Código ORIDE', 'preguntas.php' => 'Preguntas frecuentes'];

function page_start(string $title, string $description = 'Declara cómo has utilizado la inteligencia artificial en tu obra con un código breve y fácil de entender.', array $scripts = []): void
{
    security_headers();
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title === 'Inicio' ? 'ORIDE · Originalidad Declarada' : $title . ' · ORIDE') . '</title><meta name="description" content="' . h($description) . '">';
    echo '<link rel="icon" href="' . u('assets/img/favicon.svg') . '" type="image/svg+xml"><link rel="stylesheet" href="' . u('assets/css/oride.css') . '?v=' . ORIDE_VERSION . '">';
    echo '<script src="' . u('assets/js/oride.js') . '?v=' . ORIDE_VERSION . '" defer></script>';
    foreach ($scripts as $s) echo '<script src="' . u($s) . '?v=' . ORIDE_VERSION . '" defer></script>';
    echo '</head><body><a class="skip" href="#main">Saltar al contenido</a>';
    echo '<header class="site-header"><div class="container header-row"><a class="brand" href="' . u('index.php') . '" aria-label="ORIDE, inicio">' . logo() . '</a>';
    echo '<button type="button" class="btn btn-ghost menu-toggle" aria-expanded="false" aria-controls="site-nav">Menú</button><nav id="site-nav" class="site-nav" aria-label="Principal"><ul>';
    foreach (NAV as $href => $label) echo '<li><a href="' . u($href) . '"' . ($current === $href ? ' aria-current="page"' : '') . '>' . h($label) . '</a></li>';
    echo '</ul><div class="menu-actions"><a class="btn btn-primary" href="' . u('crear.php') . '"' . ($current === 'crear.php' ? ' aria-current="page"' : '') . '>Crea tu declaración</a></div></nav></div></header>';
    echo '<main id="main">';
}

function page_end(): void
{
    echo '</main><footer class="site-footer"><div class="container footer-row">';
    echo '<div>' . logo(true) . '<p class="muted small">Toda creación tiene un proceso. Hazlo visible.</p></div>';
    echo '<nav aria-label="Pie de página"><ul class="footer-links">';
    foreach (['crear.php' => 'Crea tu declaración'] + NAV + ['marco-normativo.php' => 'Marco normativo', 'legal.php' => 'Aviso legal y privacidad'] as $href => $label) {
        echo '<li><a href="' . u($href) . '">' . h($label) . '</a></li>';
    }
    echo '</ul></nav>';
    echo '<div class="footer-cta"><a class="btn btn-secondary" href="' . u('mejora.php') . '">¿Cómo puede mejorar el Código ORIDE?</a><p class="small muted">Contacto: <a href="mailto:info@itakora.com">info@itakora.com</a></p></div>';
    echo '</div><p class="container footer-note">© ' . gmdate('Y') . ' ORIDE · Sin cookies. El Código ORIDE es una declaración de quien lo usa; no es una certificación.</p></footer></body></html>';
}

function alert(string $msg, string $tone = 'info'): string
{
    return '<div class="alert alert-' . h($tone) . '"' . ($tone === 'danger' ? ' role="alert"' : ' role="status"') . '>' . h($msg) . '</div>';
}

// ---------------------------------------------------------------------------
// Caja del código (la misma estructura la rellena oride.js en tiempo real)
// ---------------------------------------------------------------------------

function code_segments(array $r): string
{
    $seg = function (string $dim, ?string $value) {
        return '<span class="seg' . ($value === null ? ' seg-empty' : '') . '" data-seg="' . $dim . '">' . h($value ?? $dim . '–') . '</span>';
    };
    if ($r['status'] === 'complete') {
        preg_match('/^(A\d+)(B\d+)(C\d+)$/', $r['code'], $m);
        return $seg('A', $m[1]) . $seg('B', $m[2]) . $seg('C', $m[3]);
    }
    $a = $r['a'] ? join_values('A', $r['a']) : null;
    $b = $r['b'] ? join_values('B', $r['b']) : null;
    return $seg('A', $a) . $seg('B', $b) . $seg('C', null);
}

/** Lista de explicación de un resultado (formulario) o de un código (explicador). */
function explain_list(array $a, array $b, array $pairsOrC, bool $pairs): string
{
    $item = fn(string $dim, string $body) => '<div class="dim"><span>' . h($dim . ' · ' . ORIDE_DIMS[$dim]['name']) . '</span>' . $body . '</div>';
    $vals = fn(array $vs) => implode('', array_map(fn($v) => '<p class="val"><b>' . h($v) . '</b> ' . h(label($v)) . '</p>', $vs));
    $html = '';
    if ($a) $html .= $item('A', $vals($a));
    if ($b) $html .= $item('B', $vals($b));
    if ($pairs && $pairsOrC) {
        $html .= $item('C', implode('', array_map(fn($p) => $p[0] === 'B0' ? '<p class="val"><b>C0</b> ' . h(label('C0')) . '</p>' : '<p class="val"><b>' . h($p[1]) . '</b> ' . h(label($p[1])) . ' <span class="pair">con ' . h(b_short($p[0])) . '</span></p>', $pairsOrC)));
    } elseif (!$pairs && $pairsOrC) {
        $html .= $item('C', $vals($pairsOrC));
    }
    return $html;
}

function code_box(array $r, bool $live = false): string
{
    $status = match ($r['status']) {
        'complete' => $r['code'] === 'A0B0C0' ? 'No se utilizó inteligencia artificial.' : 'Declaración completa.',
        'pending' => $r['missing'] ?? 'Faltan respuestas.',
        default => 'Responde a la primera pregunta para empezar.',
    };
    $complete = $r['status'] === 'complete';
    return '<section class="code-card decl-box" id="declaracion" aria-labelledby="decl-title"' . ($live ? ' data-box' : '') . '><div class="inner">'
        . '<p class="label" id="decl-title">Tu Código ORIDE</p>'
        . '<p class="big code-line" data-code aria-live="polite">' . code_segments($r) . '</p>'
        . '<p class="status" data-status aria-live="polite">' . h($status) . '</p>'
        . '<div class="dims" data-explain>' . explain_list($r['a'] === ['A0'] ? [] : $r['a'], $r['b'] === ['B0'] && $r['a'] === ['A0'] ? [] : $r['b'], $r['a'] === ['A0'] ? [] : $r['pairs'], true) . '</div>'
        . '<div class="box-actions"><button type="button" class="btn btn-copy" data-copy-code="' . h($complete ? 'ORIDE ' . $r['code'] : '') . '"' . ($complete ? '' : ' disabled') . '>Copiar código</button>'
        . '<button type="button" class="btn btn-copy" data-copy-text="' . h(oride_text($r)) . '"' . ($complete ? '' : ' disabled') . '>Copiar declaración</button></div>'
        . '</div><p class="foot">Describe el proceso; no mide la autoría ni certifica nada. <a href="' . u('codigo.php') . '">Qué significa</a></p></section>';
}
