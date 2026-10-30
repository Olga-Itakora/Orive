<?php
/** API pública v1: solo contenido publicado. 404 igual si no existe, es privada o se retiró. */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/declarations.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if (!rate_limit('api', client_ip(), 120, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'rate_limited']);
    exit;
}
$d = public_declaration(get('id'));
if (!$d) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found', 'message' => 'No hay ninguna declaración pública con este identificador.'], JSON_UNESCAPED_UNICODE);
    exit;
}
header('Cache-Control: public, max-age=60');
echo json_encode([
    'apiVersion' => '1',
    'publicId' => $d['public_id'],
    'status' => $d['status'],
    'issuedAt' => gmdate('c', strtotime($d['issued_at'] . ' UTC')),
    'revokedAt' => $d['revoked_at'] ? gmdate('c', strtotime($d['revoked_at'] . ' UTC')) : null,
    'supersededBy' => $d['superseded_by'] ? ($d['superseded_by']['is_public'] ? $d['superseded_by']['public_id'] : '(no pública)') : null,
    'supersedes' => $d['supersedes'] ? ($d['supersedes']['is_public'] ? $d['supersedes']['public_id'] : '(no pública)') : null,
    'verification' => ['algorithm' => ORIDE_HASH_ALGORITHM, 'canonicalization' => ORIDE_CANONICALIZATION, 'contentHash' => $d['content_hash'], 'pdfFullSha256' => $d['pdf_full_hash'], 'pdfSummarySha256' => $d['pdf_summary_hash']],
    'disclosedSections' => $d['visible'],
    'view' => $d['view'],
    'notice' => 'La verificación de integridad no acredita identidad, veracidad, originalidad, titularidad de derechos ni cumplimiento legal.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
