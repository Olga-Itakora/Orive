<?php
/**
 * ORIDE v3 — núcleo: configuración, utilidades, taxonomía y cálculo del código.
 * Sin base de datos, sin sesiones y sin cookies.
 */
declare(strict_types=1);

define('ORIDE_ROOT', dirname(__DIR__));
const ORIDE_VERSION = '3.0.0';

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
// Código ORIDE (v3): áreas de uso, forma de uso, alcance y evidencias
// ---------------------------------------------------------------------------

const ORIDE_SCOPE = ['Menos del 5%', 'Del 5% al 25%', 'Del 25% al 50%', 'Del 50% al 75%', 'Del 75% al 100%'];

/** Áreas en el orden en que aparecen en el código. [nombre, descripción, nombre en frase, niveles 0–5] */
const ORIDE_AREAS = [
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
        'Introduje cambios importantes a partir de las observaciones', 'Introduje cambios puntuales sugeridos', 'Acepté y apliqué directamente las propuestas de revisión']],
    'F' => ['Revisión de forma', 'Ortografía, gramática o estilo.', 'la revisión de forma', [
        'No utilicé IA', 'Consulté sugerencias sin aplicarlas', 'Reescribí personalmente a partir de las propuestas',
        'Reformulé sustancialmente las propuestas', 'Ajusté las correcciones propuestas', 'Acepté las correcciones propuestas sin cambios']],
    'T' => ['Traducción', 'Traducción del texto a otro idioma.', 'la traducción', [
        'No utilicé IA', 'Consulté la traducción sin incorporarla', 'Realicé una traducción propia utilizando la propuesta como referencia',
        'Reelaboré sustancialmente la traducción', 'Corregí parcialmente la traducción', 'Incorporé la traducción sin cambios significativos']],
];

const ORIDE_EVIDENCE = ['No conserva evidencias de los usos declarados.', 'Conserva evidencias de los usos declarados.'];
const ORIDE_EVIDENCE_NOTE = 'Añade junto al código un medio para contactar contigo o un enlace a esas evidencias.';

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
    foreach (array_keys(ORIDE_AREAS) as $l) {
        if (!in_array($l, $chosen, true)) continue;
        $usage = isset($u[$l]) && preg_match('/^[0-5]$/', (string) $u[$l]) ? (int) $u[$l] : null;
        $scope = $usage !== null && needs_scope($usage) && isset($s[$l]) && preg_match('/^[0-4]$/', (string) $s[$l]) ? (int) $s[$l] : null;
        $r['areas'][] = [$l, $usage, $scope];
    }
    $ev = in_array($in['ev'] ?? '', ['si', 'no'], true) ? (int) ($in['ev'] === 'si') : null;
    $r['ev'] = $ev;

    if (!$r['areas']) return ['missing' => 'Señala al menos un área.'] + $r;
    foreach ($r['areas'] as [$l, $usage, $scope]) {
        if ($usage === null) return ['missing' => 'Indica cómo utilizaste los resultados de la IA para ' . ORIDE_AREAS[$l][2] . '.'] + $r;
        if (needs_scope($usage) && $scope === null) return ['missing' => 'Indica qué parte de tu obra está afectada por ' . ORIDE_AREAS[$l][2] . '.'] + $r;
    }
    if ($ev === null) return ['missing' => 'Indica si conservas evidencias.'] + $r;

    $r['status'] = 'complete';
    $r['code'] = 'IA1-' . implode('-', array_map(fn($a) => area_part(...$a), $r['areas'])) . '-V' . $ev;
    return $r;
}

/** Lee las respuestas de la URL (formulario sin JavaScript o enlace compartido). */
function oride_input_from_get(): array
{
    $u = $s = [];
    foreach (array_keys(ORIDE_AREAS) as $l) {
        if (get('u' . $l) !== '') $u[$l] = get('u' . $l);
        if (get('s' . $l) !== '') $s[$l] = get('s' . $l);
    }
    return ['ia' => get('ia'), 'areas' => get_list('areas'), 'u' => $u, 's' => $s, 'ev' => get('ev')];
}

/** Normaliza un código escrito a mano: admite «ORIDE», minúsculas y espacios o puntos en lugar de guiones. */
function oride_normalize(string $s): string
{
    $s = mb_strtoupper(trim($s));
    $s = preg_replace('/^(C[OÓ]DIGO\s+)?ORIDE[\s:]*/u', '', $s);
    $s = preg_replace('/[\s·_—–.,;]+/u', '-', $s);
    return trim(preg_replace('/-+/', '-', $s), '-');
}

/** Lee un código. @return array{valid:bool, code:string, error:?string, result:?array} */
function oride_explain(string $raw): array
{
    $code = oride_normalize($raw);
    $out = ['valid' => false, 'code' => $code, 'error' => null, 'result' => null];
    if ($code === 'IA0') return ['valid' => true, 'result' => oride_compute(['ia' => 'no'])] + $out;
    $form = 'Un código ORIDE es IA0 o tiene la forma IA1-…-V0 / V1, por ejemplo IA1-I2-C43-V1.';
    if (!preg_match('/^IA1-(.+)-V([01])$/', $code, $m)) return ['error' => $form] + $out;
    $order = array_keys(ORIDE_AREAS);
    $last = -1;
    $areas = [];
    foreach (explode('-', $m[1]) as $tok) {
        if (!preg_match('/^([A-Z])(\d)(\d?)$/', $tok, $t) || !isset(ORIDE_AREAS[$t[1]])) return ['error' => "«{$tok}» no es un área válida. Las áreas son D, I, E, C, R, F y T."] + $out;
        [$l, $usage, $scope] = [$t[1], (int) $t[2], $t[3]];
        $pos = array_search($l, $order, true);
        if ($pos <= $last) return ['error' => 'Las áreas van una sola vez y en este orden: D, I, E, C, R, F, T.'] + $out;
        $last = $pos;
        if ($usage > 5) return ['error' => "En {$tok}, la forma de uso va de 0 a 5."] + $out;
        if (needs_scope($usage) && $scope === '') return ['error' => "En {$tok} falta el alcance: con los usos 3, 4 y 5 se añade una cifra de 0 a 4 (por ejemplo {$l}{$usage}2)."] + $out;
        if (!needs_scope($usage) && $scope !== '') return ['error' => "En {$tok} sobra el alcance: solo se indica con los usos 3, 4 y 5."] + $out;
        if ($scope !== '' && (int) $scope > 4) return ['error' => "En {$tok}, el alcance va de 0 a 4."] + $out;
        $areas[] = $l;
        $u[$l] = $usage;
        if ($scope !== '') $s[$l] = $scope;
    }
    $r = oride_compute(['ia' => 'si', 'areas' => $areas, 'u' => $u ?? [], 's' => $s ?? [], 'ev' => $m[2] === '1' ? 'si' : 'no']);
    return ['valid' => true, 'result' => $r] + $out;
}

function area_lines(string $l, int $usage, ?int $scope): array
{
    $lines = [ORIDE_AREAS[$l][3][$usage]];
    if ($scope !== null) $lines[] = 'Parte de la obra afectada: ' . mb_strtolower(ORIDE_SCOPE[$scope]);
    return $lines;
}

/** Texto plano de la declaración (para copiar). */
function oride_text(array $r): string
{
    if ($r['status'] !== 'complete') return '';
    if ($r['code'] === 'IA0') return "Código ORIDE IA0\nNo se utilizó inteligencia artificial en la elaboración de esta obra.";
    $lines = ['Código ORIDE ' . $r['code']];
    foreach ($r['areas'] as [$l, $usage, $scope]) $lines[] = ORIDE_AREAS[$l][0] . ': ' . implode('. ', area_lines($l, $usage, $scope)) . '.';
    $lines[] = 'Evidencias: ' . mb_strtolower(ORIDE_EVIDENCE[$r['ev']]);
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
    $seg = fn(string $v, bool $empty = false) => '<span class="seg' . ($empty ? ' seg-empty' : '') . '">' . h($v) . '</span>';
    if ($r['status'] === 'empty') return $seg('IA–', true);
    if ($r['code'] === 'IA0') return $seg('IA0');
    $parts = [$seg('IA1')];
    foreach ($r['areas'] as [$l, $usage, $scope]) {
        $done = $usage !== null && (!needs_scope($usage) || $scope !== null);
        $parts[] = $seg($done ? area_part($l, $usage, $scope) : area_part($l, $usage, $scope) . '–', !$done);
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
        $html .= '<div class="dim"><span>' . h(area_part($l, $usage, $scope) . ' · ' . ORIDE_AREAS[$l][0]) . '</span>'
            . implode('', array_map(fn($t) => '<p class="val">' . h($t) . '</p>', area_lines($l, $usage, $scope))) . '</div>';
    }
    if ($r['ev'] !== null) {
        $html .= '<div class="dim"><span>V' . $r['ev'] . ' · Evidencias</span><p class="val">' . h(ORIDE_EVIDENCE[$r['ev']]) . '</p>'
            . ($r['ev'] ? '<p class="val note">' . h(ORIDE_EVIDENCE_NOTE) . '</p>' : '') . '</div>';
    }
    return $html;
}

function code_box(array $r, bool $live = false, string $title = 'Tu Código ORIDE'): string
{
    $status = match ($r['status']) {
        'complete' => $r['code'] === 'IA0' ? 'No se utilizó inteligencia artificial.' : 'Declaración completa.',
        'pending' => $r['missing'] ?? 'Faltan respuestas.',
        default => 'Responde a la primera pregunta para empezar.',
    };
    $complete = $r['status'] === 'complete';
    return '<section class="code-card decl-box" id="declaracion" aria-labelledby="decl-title"' . ($live ? ' data-box' : '') . '><div class="inner">'
        . '<p class="label" id="decl-title">' . h($title) . '</p>'
        . '<p class="big code-line" data-code aria-live="polite">' . code_segments($r) . '</p>'
        . ($live ? '<p class="status" data-status aria-live="polite">' . h($status) . '</p>' : '')
        . '<div class="dims" data-explain>' . explain_list($r) . '</div>'
        . ($live ? '<div class="box-actions"><button type="button" class="btn btn-copy" data-copy-code="' . h($complete ? 'ORIDE ' . $r['code'] : '') . '"' . ($complete ? '' : ' disabled') . '>Copiar código</button>'
            . '<button type="button" class="btn btn-copy" data-copy-text="' . h(oride_text($r)) . '"' . ($complete ? '' : ' disabled') . '>Copiar declaración</button></div>' : '')
        . '</div>' . ($live ? '<p class="foot">Describe el proceso; no mide la autoría ni certifica nada. <a href="' . u('codigo.php') . '">Qué significa</a></p>' : '') . '</section>';
}
