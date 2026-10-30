<?php
/**
 * Configuración de ORIDE. Copia este fichero como config.php y rellena los datos.
 * config.php nunca debe subirse a un repositorio público.
 */
return [
    // Base de datos MySQL (en IONOS: «Bases de datos» → datos de acceso).
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'oride',
    'db_user' => 'usuario',
    'db_pass' => 'contraseña',

    // URL pública del sitio, sin barra final. Se imprime en los PDF y en los QR.
    'app_url' => 'https://oridecode.com',

    // Secreto aleatorio de al menos 32 caracteres (lo genera instalar.php).
    'app_secret' => 'cambia-esto-por-un-secreto-largo-y-aleatorio',

    // Remitente de los correos (recuperación de contraseña).
    'mail_from' => 'ORIDE <no-reply@oridecode.com>',

    // Carpeta privada para las evidencias (fuera del acceso web o protegida por .htaccess).
    'evidence_dir' => __DIR__ . '/storage/evidencias',
    'evidence_max_bytes' => 10 * 1024 * 1024,

    // Mostrar errores detallados (solo en desarrollo).
    'debug' => false,
];
