<?php
/** PDF públicos: solo si el autor publicó su nombre y el contenido que incluye cada documento. */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/declarations.php';

$d = public_declaration(get('id'));
if (!$d) not_found();
$p = $d['publication'];
$full = get('tipo') === 'pdf';
$allowed = $full ? $p['show_name'] && $p['show_full'] : $p['show_name'] && ($p['show_full'] || ($p['show_work'] && $p['show_code'] && $p['show_summary']));
if (!$allowed) not_found();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $d['public_id'] . '-' . ($full ? 'declaracion' : 'resumen') . '.pdf"');
echo qv('SELECT ' . ($full ? 'pdf_full' : 'pdf_summary') . ' FROM declaration_versions WHERE public_id = ?', [$d['public_id']]);
