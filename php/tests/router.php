<?php
// Enrutador para el servidor de desarrollo de PHP (php -S): imita las reglas de .htaccess.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(includes|lib|sql|storage|tests)(/|$)|^/config(\.sample)?\.php$|/\.#', $path)) {
    http_response_code(404);
    echo 'No encontrado';
    return true;
}
return false;
