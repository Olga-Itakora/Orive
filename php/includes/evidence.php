<?php
/**
 * Evidencias: almacenamiento privado, lista blanca por firma de contenido
 * (no por la extensión), sin SVG/HTML, direccionado por SHA-256.
 */
declare(strict_types=1);

/** @return array|string datos del fichero o mensaje de error */
function store_evidence(array $up): array|string
{
    if (($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return in_array($up['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'El fichero supera el tamaño máximo permitido por el servidor.' : 'No se ha podido recibir el fichero. Vuelve a intentarlo.';
    }
    $max = (int) cfg('evidence_max_bytes', 10485760);
    if ($up['size'] > $max) return 'El fichero supera el tamaño máximo (' . round($max / 1048576) . ' MB).';
    $bytes = (string) file_get_contents($up['tmp_name']);
    $mime = null;
    if (str_starts_with($bytes, '%PDF-')) $mime = 'application/pdf';
    elseif (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) $mime = 'image/png';
    elseif (str_starts_with($bytes, "\xFF\xD8\xFF")) $mime = 'image/jpeg';
    elseif (substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP') $mime = 'image/webp';
    elseif (preg_match('/\.(txt|md|csv|json)$/i', (string) $up['name']) && !str_contains($bytes, "\0") && mb_check_encoding($bytes, 'UTF-8')) $mime = 'text/plain';
    if (!$mime) return 'Tipo de fichero no admitido. Usa PDF, PNG, JPEG, WebP o texto (.txt, .md, .csv, .json).';
    $sha = hash('sha256', $bytes);
    $dir = rtrim((string) cfg('evidence_dir'), '/') . '/' . substr($sha, 0, 2);
    if (!is_dir($dir) && !mkdir($dir, 0700, true)) return 'No se ha podido guardar el fichero en el servidor.';
    if (!is_file("$dir/$sha")) file_put_contents("$dir/$sha", $bytes);
    $name = mb_substr(preg_replace('/[^\p{L}\p{N}._ -]/u', '_', (string) $up['name']), 0, 120) ?: 'evidencia';
    return ['file_name' => $name, 'mime_type' => $mime, 'size_bytes' => strlen($bytes), 'sha256' => $sha];
}

function evidence_path(string $sha): ?string
{
    if (!preg_match('/^[a-f0-9]{64}$/', $sha)) return null;
    $p = rtrim((string) cfg('evidence_dir'), '/') . '/' . substr($sha, 0, 2) . '/' . $sha;
    return is_file($p) ? $p : null;
}
