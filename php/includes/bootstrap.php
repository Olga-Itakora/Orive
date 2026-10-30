<?php
/**
 * Arranque común de todas las páginas: configuración, sesión segura,
 * cabeceras de seguridad, base de datos y utilidades.
 */
declare(strict_types=1);

define('ORIDE_ROOT', dirname(__DIR__));

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/taxonomy.php';
require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/integrity.php';
require_once __DIR__ . '/article50.php';
require_once __DIR__ . '/labels.php';
require_once __DIR__ . '/layout.php';

if (is_file(ORIDE_ROOT . '/config.php')) {
    $GLOBALS['ORIDE_CONFIG'] = require ORIDE_ROOT . '/config.php';
} else {
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'instalar.php') {
        header('Location: ' . oride_base_path() . '/instalar.php');
        exit;
    }
    $GLOBALS['ORIDE_CONFIG'] = require ORIDE_ROOT . '/config.sample.php';
}

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
ini_set('display_errors', cfg('debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ORIDE_ROOT . '/storage/logs/php-errors.log');
error_reporting(E_ALL);

oride_security_headers();
oride_start_session();

// Error inesperado: página genérica sin detalles internos (se registran en storage/logs).
set_exception_handler(function (Throwable $e) {
    error_log('[ORIDE] ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) http_response_code(500);
    $ref = substr(hash('sha256', $e->getMessage() . microtime()), 0, 10);
    error_log("[ORIDE] referencia $ref");
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Error · ORIDE</title><link rel="stylesheet" href="' . h(u('assets/css/oride.css')) . '"></head><body><main class="container narrow"><h1>No se ha podido completar la operación</h1><p>Ha fallado algo en el servidor. Lo que ya habías guardado está a salvo; vuelve a intentarlo y, si persiste, escríbenos indicando la referencia <code>' . h($ref) . '</code>.</p><p><a href="' . h(u('contacto.php')) . '">Contactar con soporte</a> · <a href="' . h(u('index.php')) . '">Ir al inicio</a></p>' . (cfg('debug') ? '<pre>' . h((string) $e) . '</pre>' : '') . '</main></body></html>';
    exit;
});
