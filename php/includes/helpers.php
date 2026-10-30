<?php
declare(strict_types=1);

function cfg(string $key, mixed $default = null): mixed
{
    return $GLOBALS['ORIDE_CONFIG'][$key] ?? $default;
}

/** Ruta base del sitio (permite instalarlo en una subcarpeta). */
function oride_base_path(): string
{
    static $base = null;
    if ($base === null) {
        $root = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
        $doc = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
        $base = ($doc !== '' && str_starts_with($root, $doc)) ? rtrim(substr($root, strlen($doc)), '/') : '';
    }
    return $base;
}

/** URL relativa al sitio. */
function u(string $path = ''): string
{
    return oride_base_path() . '/' . ltrim($path, '/');
}

/** URL absoluta pública (PDF, QR, enlaces compartibles). */
function abs_url(string $path = ''): string
{
    return rtrim((string) cfg('app_url'), '/') . '/' . ltrim($path, '/');
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function fecha(?string $dt, bool $time = false): string
{
    if (!$dt) return '—';
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $t = strtotime($dt . ' UTC');
    $s = (int) gmdate('j', $t) . ' de ' . $months[(int) gmdate('n', $t) - 1] . ' de ' . gmdate('Y', $t);
    return $time ? $s . ', ' . gmdate('H:i', $t) . ' UTC' : $s;
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : u($path)), true, 303);
    exit;
}

function not_found(): never
{
    http_response_code(404);
    page_start('Página no encontrada');
    echo '<div class="container narrow"><h1>Página no encontrada</h1><p class="muted">La página que buscas no existe o no tienes acceso a ella.</p><p><a href="' . u('index.php') . '">Volver al inicio</a></p></div>';
    page_end();
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_list(string $key): array
{
    $v = $_POST[$key] ?? [];
    return is_array($v) ? array_values(array_filter(array_map(fn($x) => is_string($x) ? trim($x) : '', $v), fn($x) => $x !== '')) : [];
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function nullable(string $s, int $max): ?string
{
    $s = trim($s);
    return $s === '' ? null : mb_substr($s, 0, $max);
}

// ---------------------------------------------------------------------------
// Base de datos
// ---------------------------------------------------------------------------

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), (int) cfg('db_port', 3306), cfg('db_name'));
        $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q1(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function qv(string $sql, array $params = []): mixed
{
    $r = q($sql, $params)->fetchColumn();
    return $r === false ? null : $r;
}

function tx(callable $fn): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $r = $fn($pdo);
        $pdo->commit();
        return $r;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

// ---------------------------------------------------------------------------
// Seguridad
// ---------------------------------------------------------------------------

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function oride_security_headers(): void
{
    if (headers_sent()) return;
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    if (is_https()) header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
    header_remove('X-Powered-By');
}

function oride_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('ORIDESESS');
    session_set_cookie_params(['lifetime' => 0, 'path' => oride_base_path() . '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

/** Toda petición POST debe llevar el token de la sesión. */
function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        page_start('Sesión caducada');
        echo '<div class="container narrow"><h1>No se ha podido enviar el formulario</h1><p>La página llevaba demasiado tiempo abierta o la sesión ha cambiado. Vuelve atrás, recarga la página y envíalo de nuevo; tus datos guardados están a salvo.</p></div>';
        page_end();
        exit;
    }
}

function fingerprint(string $value, string $purpose): string
{
    return substr(hash_hmac('sha256', "$purpose:$value", (string) cfg('app_secret')), 0, 32);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/** Límite de frecuencia de ventana fija en MySQL. Devuelve true si se permite. */
function rate_limit(string $bucket, string $subject, int $limit, int $window): bool
{
    if (cfg('disable_rate_limit')) return true;
    $key = hash('sha256', $bucket . ':' . fingerprint($subject, 'rl'));
    $start = intdiv(time(), $window) * $window;
    q('INSERT INTO rate_limits (rl_key, window_start, hits) VALUES (?, ?, 1)
       ON DUPLICATE KEY UPDATE hits = IF(window_start = VALUES(window_start), hits + 1, 1), window_start = VALUES(window_start)', [$key, $start]);
    return (int) qv('SELECT hits FROM rate_limits WHERE rl_key = ?', [$key]) <= $limit;
}

function audit(string $action, string $entity, ?string $entityId, ?int $actorId = null, array $meta = []): void
{
    q('INSERT INTO audit_log (actor_id, action, entity, entity_id, metadata, ip_fingerprint, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [
        $actorId, $action, $entity, $entityId, $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null, fingerprint(client_ip(), 'ip'), now(),
    ]);
}

// ---------------------------------------------------------------------------
// Usuarios y sesión
// ---------------------------------------------------------------------------

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    $id = $_SESSION['uid'] ?? null;
    if (!$id) return null;
    $u = q1('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$u || $u['disabled_at'] || (int) $u['session_version'] !== (int) ($_SESSION['sv'] ?? 0)) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    return $cache = $u;
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    $_SESSION['sv'] = (int) $u['session_version'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function require_login(): array
{
    $u = current_user();
    if (!$u) redirect('entrar.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ''));
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'ADMIN') redirect('autor/index.php');
    return $u;
}

// ---------------------------------------------------------------------------
// Mensajes y errores de formulario
// ---------------------------------------------------------------------------

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function take_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Guarda errores y valores enviados para re-pintar el formulario tras redirigir (no se pierde nada). */
function form_fail(array $errors, string $to, ?string $message = null): never
{
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = array_diff_key($_POST, ['_csrf' => 1, 'password' => 1, 'confirm' => 1, 'current' => 1]);
    if ($message) flash('danger', $message);
    redirect($to);
}

function form_state(): array
{
    $e = $_SESSION['form_errors'] ?? [];
    $o = $_SESSION['form_old'] ?? [];
    unset($_SESSION['form_errors'], $_SESSION['form_old']);
    return [$e, $o];
}

function send_mail(string $to, string $subject, string $body): bool
{
    $from = (string) cfg('mail_from', 'ORIDE <no-reply@localhost>');
    $headers = "From: $from\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    if (!$ok || cfg('debug')) {
        error_log("[ORIDE] Correo para $to: $subject\n$body");
    }
    return $ok;
}
