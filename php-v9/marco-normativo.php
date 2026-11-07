<?php
/** El marco normativo está ahora en Documentación (pregunta sobre el artículo 50). */
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

header('Location: ' . u('documentacion.php#preguntas'), true, 301);
exit;
