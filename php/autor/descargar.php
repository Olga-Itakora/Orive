<?php
/** Descargas privadas del autor: PDF, resumen, JSON exportado y evidencias. */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';
require_once __DIR__ . '/../includes/evidence.php';

$user = require_login();
$vid = (int) get('id');
$v = owned_version((int) $user['id'], $vid);
if (!$v) not_found();
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

if ($eid = (int) get('evidencia')) {
    $e = q1('SELECT * FROM evidence WHERE id = ? AND version_id = ?', [$eid, $vid]);
    $path = $e && $e['sha256'] ? evidence_path($e['sha256']) : null;
    if (!$path) not_found();
    header('Content-Type: ' . ($e['mime_type'] === 'text/plain' ? 'text/plain; charset=utf-8' : $e['mime_type']));
    header("Content-Security-Policy: default-src 'none'; sandbox");
    header('Content-Disposition: attachment; filename="' . rawurlencode((string) $e['file_name']) . '"');
    readfile($path);
    exit;
}
if ($v['status'] === 'DRAFT') not_found();
$tipo = get('tipo');
if ($tipo === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $v['public_id'] . '.json"');
    echo json_encode([
        'exportFormat' => 'oride-declaration-export/1',
        'verification' => [
            'algorithm' => ORIDE_HASH_ALGORITHM, 'canonicalization' => ORIDE_CANONICALIZATION, 'contentHash' => $v['content_hash'],
            'sectionDigests' => json_decode($v['section_digests']), 'pdfFullSha256' => $v['pdf_full_hash'], 'pdfSummarySha256' => $v['pdf_summary_hash'], 'statusAtExport' => $v['status'],
            'procedure' => 'Para cada sección s: digest = SHA-256(canonical({section: s, salt, data})). contentHash = SHA-256(canonical({format, sections: {s: digest}})). canonical = JSON con claves ordenadas, sin espacios, solo enteros.',
            'note' => 'La huella acredita la integridad del contenido registrado; no acredita identidad, veracidad, originalidad, titularidad de derechos ni cumplimiento legal.',
        ],
        'snapshot' => json_decode($v['snapshot']),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$col = $tipo === 'resumen' ? 'pdf_summary' : 'pdf_full';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $v['public_id'] . '-' . ($tipo === 'resumen' ? 'resumen' : 'declaracion') . '.pdf"');
echo qv("SELECT $col FROM declaration_versions WHERE id = ?", [$vid]);
