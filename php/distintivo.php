<?php
/** Distintivo SVG incrustable. Describe; no certifica. */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/declarations.php';

$d = public_declaration(get('id'));
$cls = $d['view']->sections->classification->data ?? null;
$value = 'no disponible';
if ($d) {
    $value = $d['status'] === 'REVOKED' ? 'revocada' : ($cls?->code ?: ($cls ? 'perfil compuesto' : $d['public_id']));
    if ($d['status'] === 'SUPERSEDED') $value .= ' · sustituida';
}
$value = htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
$lw = 62;
$vw = (int) max(70, mb_strlen($value) * 7.4 + 20);
$w = $lw + $vw;
http_response_code($d ? 200 : 404);
header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Cross-Origin-Resource-Policy: cross-origin');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
header_remove('X-Frame-Options');
echo "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"$w\" height=\"28\" viewBox=\"0 0 $w 28\" role=\"img\" aria-label=\"ORIDE: $value\"><title>ORIDE: $value</title><rect width=\"$w\" height=\"28\" rx=\"6\" fill=\"#0e3b3c\"/><rect x=\"$lw\" width=\"$vw\" height=\"28\" rx=\"6\" fill=\"#faf8f4\"/><rect x=\"$lw\" width=\"8\" height=\"28\" fill=\"#faf8f4\"/><circle cx=\"14\" cy=\"14\" r=\"4\" fill=\"#2ba39a\"/><text x=\"23\" y=\"18.5\" fill=\"#fff\" font-family=\"Segoe UI,Helvetica,Arial,sans-serif\" font-size=\"11\" font-weight=\"600\" letter-spacing=\"1.5\">ORIDE</text><text x=\"" . ($lw + $vw / 2) . "\" y=\"18.5\" text-anchor=\"middle\" fill=\"#0e3b3c\" font-family=\"Menlo,Consolas,monospace\" font-size=\"12\">$value</text><rect x=\"0.5\" y=\"0.5\" width=\"" . ($w - 1) . "\" height=\"27\" rx=\"5.5\" fill=\"none\" stroke=\"#0e3b3c\"/></svg>";
