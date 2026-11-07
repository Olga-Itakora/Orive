<?php
/**
 * Cálculo del código y del Índice Global ORIDE.
 *  - Para el formulario (JSON que usa oride.js): mismas respuestas que crear.php, más mode=inst en instituciones.
 *  - Para descargar el registro completo del índice: code=… y format=json (opcional: q, qp, ig).
 * Determinista: no guarda nada y no usa servicios externos.
 */
declare(strict_types=1);
define('ORIDE_BUILDER', true);
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/builder.php';

$extra = ['q' => get('q'), 'qp' => get('qp')];
if (get('ig') !== '') $extra['version'] = 'ORIDE-IG-' . get('ig');
if (get('code') !== '') {
    $e = oride_explain(mb_substr(get('code'), 0, 200));
    $r = $e['valid'] ? $e['result'] : oride_compute([]);
} else {
    $r = oride_compute(oride_input_from_get());
}
$ig = ig_from_result($r, $extra);
$complete = $r['status'] === 'complete';

security_headers();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (get('format') === 'json') {
    if (!$complete) {
        http_response_code(400);
        echo json_encode(['error' => 'incomplete']);
        exit;
    }
    header('Content-Disposition: attachment; filename="indice-oride-' . preg_replace('/[^A-Z0-9-]/', '', $r['code']) . '.json"');
    echo json_encode(ig_record($r, $ig, $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

$inst = get('mode') === 'inst';
$intro = $inst ? inst_intro() : '';
$status = match ($r['status']) {
    'complete' => $r['ia'] === 0 ? t('status_no_ai') : t('status_complete'),
    'pending' => $r['missing'] ?? '',
    default => t('status_empty'),
};
echo json_encode([
    'status' => $r['status'],
    'statusText' => $status,
    'codeHtml' => code_segments($r),
    'explainHtml' => explain_list($r, $inst),
    'indexHtml' => index_panel($ig, $r, $extra, $inst),
    'allZero' => $r['all_zero'],
    'needsQ' => $ig['needs_q'],
    'chip' => $complete ? implode(' ', $r['lines']) : t('pending'),
    'copy' => [
        'code' => $complete ? 'ORIDE ' . implode("\n", $r['lines']) : '',
        'text' => oride_text($r, $intro, $ig, $extra),
        'link' => $complete ? code_url($r['code'], $extra) : '',
    ],
    'verifyUrl' => $complete ? u('comprobar.php?r=' . rawurlencode($r['code'])) : '',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
