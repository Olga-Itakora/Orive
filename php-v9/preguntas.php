<?php
/** Las preguntas frecuentes están ahora en Documentación. */
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

header('Location: ' . u('documentacion.php#preguntas'), true, 301);
exit;
