<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
}
redirect('index.php');
