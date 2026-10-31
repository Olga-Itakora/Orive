<?php
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(includes|storage|tests|docs)(/|$)|^/config|\.md$#', $p)) { http_response_code(404); exit; }
return false;
