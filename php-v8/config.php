<?php
/**
 * Configuración de ORIDE v2. No hace falta base de datos.
 * Para cambiar algo sin tocar este fichero, crea config.local.php con las claves que quieras sustituir.
 */
return [
    // Dirección que recibe los mensajes del formulario «¿Cómo puede mejorar el Código ORIDE?».
    'mail_to' => 'info@itakora.com',

    // Remitente de esos correos. Vacío = no-reply@<tu dominio>. En IONOS conviene que sea una dirección del propio dominio.
    'mail_from' => '',

    // Máximo de mensajes por hora en todo el sitio (protección contra spam).
    'mail_per_hour' => 30,

    // Solo para desarrollo: muestra errores y guarda los correos en storage/correo-pruebas.log en lugar de enviarlos.
    'debug' => false,
];
