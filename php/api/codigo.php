<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
$e = oride_explain_code(mb_substr(get('c'), 0, 40));
http_response_code($e['valid'] ? 200 : 400);
echo json_encode($e, JSON_UNESCAPED_UNICODE);
