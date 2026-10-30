<?php
/**
 * Cargador de librerías incluidas (sin Composer).
 * - chillerlan/php-qrcode 5.0.5 (MIT / Apache-2.0) → lib/qrcode
 * - chillerlan/php-settings-container 3.3.0 (MIT)   → lib/settings-container
 * - setasign/tfpdf 1.34 (LGPL-2.1, basado en FPDF)   → lib/tfpdf
 */
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $map = [
        'chillerlan\\QRCode\\' => __DIR__ . '/qrcode/src/',
        'chillerlan\\Settings\\' => __DIR__ . '/settings-container/src/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

if (!class_exists('tFPDF', false)) {
    define('FPDF_FONTPATH', __DIR__ . '/tfpdf/font/');
    require __DIR__ . '/tfpdf/font/unifont/ttfonts.php';
    require __DIR__ . '/tfpdf/tfpdf.php';
}
