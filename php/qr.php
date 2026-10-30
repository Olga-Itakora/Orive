<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/declarations.php';

$d = public_declaration(get('id'));
if (!$d) not_found();
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=3600');
echo oride_qr_svg(abs_url('verificar.php?id=' . $d['public_id']));
