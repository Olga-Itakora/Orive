<?php
/**
 * PDF de la ORIDE Declaration (completo y resumen) generados a partir de la
 * instantánea emitida, de modo que reflejan exactamente el contenido registrado.
 */
declare(strict_types=1);

require_once ORIDE_ROOT . '/lib/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

function oride_qr_png(string $data): string
{
    $o = new QROptions();
    $o->outputType = QROutputInterface::GDIMAGE_PNG;
    $o->outputBase64 = false;
    $o->scale = 6;
    $o->quietzoneSize = 1;
    return (new QRCode($o))->render($data);
}

function oride_qr_svg(string $data): string
{
    $o = new QROptions();
    $o->outputType = QROutputInterface::MARKUP_SVG;
    $o->outputBase64 = false;
    $o->quietzoneSize = 1;
    $o->drawLightModules = false;
    $o->svgAddXmlHeader = false;
    return (new QRCode($o))->render($data);
}

final class OridePdf extends tFPDF
{
    public string $label = 'ORIDE Declaration';
    public string $publicId = '';

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetFont('DejaVu', '', 7.5);
        $this->SetTextColor(91, 107, 108);
        $this->Cell(0, 6, $this->label . ' · ' . $this->publicId . ' · Página ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
}

function pdf_utc(string $iso): string
{
    return gmdate('d/m/Y H:i:s', strtotime($iso)) . ' UTC';
}

function pdf_new(object $s, string $label): OridePdf
{
    $p = new OridePdf('P', 'mm', 'A4');
    $p->label = $label;
    $p->publicId = $s->sections->meta->data->publicId;
    $p->AliasNbPages();
    $p->SetMargins(20, 20, 20);
    $p->SetAutoPageBreak(true, 20);
    $p->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
    $p->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
    $p->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
    $p->AddFont('Mono', '', 'DejaVuSansMono.ttf', true);
    $w = $s->sections->work->data;
    $p->SetTitle($label . ' ' . $p->publicId . ' — ' . $w->title, true);
    $p->SetAuthor($s->sections->declarant->data->name, true);
    $p->SetCreator('ORIDE (oridecode.com)', true);
    $p->SetSubject('Declaración responsable de transparencia creativa', true);
    $p->AddPage();
    return $p;
}

function pdf_header(OridePdf $p, object $meta, string $title): void
{
    $p->SetFillColor(43, 163, 154);
    $p->Rect(0, 0, 210, 2, 'F');
    $p->SetXY(20, 14);
    $p->SetFont('DejaVu', 'B', 20);
    $p->SetTextColor(14, 59, 60);
    $p->Cell(0, 9, 'ORIDE', 0, 1);
    $p->SetFont('DejaVu', '', 7.5);
    $p->SetTextColor(91, 107, 108);
    $p->Cell(0, 4, 'Originality Declaration · Toda creación tiene un proceso. Hazlo visible.', 0, 1);
    $base = tempnam(sys_get_temp_dir(), 'qr');
    $tmp = $base . '.png';
    file_put_contents($tmp, oride_qr_png($meta->verifyUrl));
    $p->Image($tmp, 165, 10, 26, 26, 'PNG');
    @unlink($tmp);
    @unlink($base);
    $p->SetXY(20, 32);
    $p->SetFont('DejaVu', 'B', 15);
    $p->SetTextColor(14, 59, 60);
    $p->MultiCell(140, 7, $title, 0, 'L');
    $p->SetFont('Mono', '', 9.5);
    $p->SetTextColor(28, 37, 38);
    $p->Cell(0, 6, $meta->publicId . '   ·   Versión ' . $meta->versionNumber . '   ·   Emitida el ' . pdf_utc($meta->issuedAt), 0, 1);
    $p->Ln(3);
}

function pdf_h2(OridePdf $p, string $t): void
{
    if ($p->GetY() > 250) $p->AddPage();
    $p->Ln(3);
    $p->SetFont('DejaVu', 'B', 10.5);
    $p->SetTextColor(14, 59, 60);
    $p->Cell(0, 6, mb_strtoupper($t), 0, 1);
    $p->SetDrawColor(217, 226, 225);
    $p->Line(20, $p->GetY(), 190, $p->GetY());
    $p->Ln(2);
}

function pdf_p(OridePdf $p, string $t, array $o = []): void
{
    $p->SetFont('DejaVu', $o['style'] ?? '', $o['size'] ?? 9);
    $c = $o['muted'] ?? false ? [91, 107, 108] : [28, 37, 38];
    $p->SetTextColor(...$c);
    $p->MultiCell(0, 4.6, $t, 0, 'L');
    $p->Ln(1);
}

function pdf_kv(OridePdf $p, string $k, mixed $v): void
{
    if ($v === null || $v === '') return;
    if ($p->GetY() > 265) $p->AddPage();
    $p->SetFont('DejaVu', 'B', 8.5);
    $p->SetTextColor(91, 107, 108);
    $x = $p->GetX();
    $y = $p->GetY();
    $p->MultiCell(48, 4.6, $k, 0, 'L');
    $h1 = $p->GetY();
    $p->SetXY($x + 50, $y);
    $p->SetFont('DejaVu', '', 9);
    $p->SetTextColor(28, 37, 38);
    $p->MultiCell(0, 4.6, (string) $v, 0, 'L');
    $p->SetY(max($h1, $p->GetY()) + 0.8);
}

function pdf_code_box(OridePdf $p, object $cls): void
{
    if ($cls->code) {
        [$text, $cap] = ['ORIDE ' . $cls->code, "ORIDE Code v{$cls->schemeVersion} · Código compacto de la declaración"];
    } elseif ($cls->compositeNotation) {
        [$text, $cap] = [$cls->compositeNotation, "ORIDE Code v{$cls->schemeVersion} · Perfil compuesto: síntesis de los valores presentes (no son parejas B–C). Ver desglose por usos."];
    } else {
        [$text, $cap] = [L_PROFILE_STATUS[$cls->status] ?? $cls->status, "ORIDE Code v{$cls->schemeVersion} · No se emite un código sin información suficiente."];
    }
    if ($p->GetY() > 240) $p->AddPage();
    $y = $p->GetY();
    $size = mb_strlen($text) > 30 ? 11 : 18;
    $p->SetFillColor(14, 59, 60);
    $p->Rect(20, $y, 170, $size > 12 ? 24 : 22, 'F');
    $p->SetXY(26, $y + 4);
    $p->SetFont('Mono', '', $size);
    $p->SetTextColor(255, 255, 255);
    $p->Cell(158, $size > 12 ? 9 : 6, $text, 0, 1);
    $p->SetX(26);
    $p->SetFont('DejaVu', '', 7.5);
    $p->SetTextColor(191, 227, 223);
    $p->MultiCell(158, 3.8, $cap, 0, 'L');
    $p->SetY($y + ($size > 12 ? 27 : 25));
    $p->SetX(20);
}

function pdf_legend(OridePdf $p, object $cls): void
{
    if (!$cls->composite) return;
    $dims = oride_dimensions();
    foreach (['A', 'B', 'C'] as $k) {
        pdf_kv($p, "$k · {$dims[$k]['name']}", implode('; ', array_map(fn($c) => "$c — " . oride_label($c), $cls->composite->$k)) ?: '—');
    }
    pdf_kv($p, "D · {$dims['D']['name']}", $cls->composite->D . ' — ' . oride_label($cls->composite->D));
}

const PDF_NOT_MEANING = 'El código ORIDE es una clasificación descriptiva del proceso declarado. No es un porcentaje de autoría, una puntuación de originalidad ni una certificación de cumplimiento legal. Los números identifican categorías y no son magnitudes comparables.';
const PDF_VERIFY_NOTE = 'La huella criptográfica permite comprobar que el contenido registrado no ha sido alterado. No acredita la identidad del declarante, la veracidad de lo declarado, la originalidad, la titularidad de derechos ni el cumplimiento legal.';

function oride_pdf_full(object $s, string $hash): string
{
    $d = fn($n) => $s->sections->$n->data;
    [$meta, $w, $dec, $scope, $cls, $proc, $a50, $st, $ev] = [$d('meta'), $d('work'), $d('declarant'), $d('scope'), $d('classification'), $d('process'), $d('article50'), $d('statement'), $d('evidence')];
    $p = pdf_new($s, 'ORIDE Declaration');
    pdf_header($p, $meta, 'ORIDE Declaration — Declaración responsable de transparencia creativa');

    pdf_h2($p, 'Obra');
    pdf_kv($p, 'Título', $w->title . ($w->subtitle ? ". {$w->subtitle}" : ''));
    pdf_kv($p, 'Tipo de obra', $w->category->label);
    pdf_kv($p, 'Estado', L_WORK_STATUS[$w->status] ?? $w->status);
    pdf_kv($p, 'Periodo de creación', $w->creationPeriod);
    pdf_kv($p, 'Fecha de publicación', $w->publicationDate);
    pdf_kv($p, 'Idioma', $w->language);
    pdf_kv($p, 'Versión de la obra', $w->workVersion);
    pdf_kv($p, 'Editorial o institución', $w->publisher);
    pdf_kv($p, $w->identifierType ?: 'Identificador', $w->identifier);
    pdf_kv($p, 'URL', $w->url);
    pdf_kv($p, 'Descripción', $w->description);
    if ($w->components) pdf_kv($p, 'Componentes', implode('; ', array_map(fn($c) => "{$c->ref} {$c->name} ({$c->kindLabel})", $w->components)));
    if ($w->isCollective) pdf_kv($p, 'Obra colectiva', 'Sí. Roles de otros participantes: ' . (implode(', ', array_map(fn($r) => $r->role, $w->contributorRoles)) ?: 'no indicados'));

    pdf_h2($p, 'Declarante y alcance');
    pdf_kv($p, 'Declarante', $dec->name . ($dec->artisticName ? " («{$dec->artisticName}»)" : ''));
    pdf_kv($p, 'Organización', $dec->organization);
    pdf_kv($p, 'Rol en la obra', $dec->role);
    pdf_kv($p, 'Alcance', $scope->scopeLabel);
    pdf_kv($p, 'Ámbito de responsabilidad', $scope->responsibilityNote);
    pdf_p($p, $scope->coverageStatement, ['style' => 'I', 'muted' => true]);
    pdf_kv($p, 'Utilización de IA', $scope->effectiveAiUsageLabel);
    pdf_kv($p, 'Incertidumbre declarada', $scope->uncertaintyNote);

    pdf_h2($p, 'Código o perfil ORIDE');
    pdf_code_box($p, $cls);
    pdf_kv($p, 'Estado de la clasificación', L_PROFILE_STATUS[$cls->status] ?? $cls->status);
    pdf_legend($p, $cls);
    pdf_p($p, PDF_NOT_MEANING, ['muted' => true, 'size' => 8]);

    pdf_h2($p, 'Resumen');
    pdf_p($p, $d('summary')->text);

    if ($proc->tools) {
        pdf_h2($p, 'Herramientas declaradas');
        foreach ($proc->tools as $t) {
            pdf_kv($p, $t->ref, implode(' · ', array_filter([$t->identified ? $t->name : 'Herramienta no identificada' . ($t->name !== 'Herramienta no identificada' ? " ({$t->name})" : ''), $t->provider, $t->modelVersion, $t->systemType, $t->usagePeriod])));
        }
    }
    if ($proc->uses) {
        pdf_h2($p, 'Usos de IA declarados');
        foreach ($proc->uses as $u) {
            $c = null;
            foreach ($cls->uses as $x) if ($x->ref === $u->ref) $c = $x;
            if ($p->GetY() > 235) $p->AddPage();
            $p->SetFont('DejaVu', 'B', 10);
            $p->SetTextColor(28, 37, 38);
            $p->MultiCell(0, 5, "{$u->ref} · {$u->title}", 0, 'L');
            $p->SetFont('Mono', '', 9.5);
            $p->SetTextColor(14, 59, 60);
            $p->MultiCell(0, 5, $c && $c->code ? "ORIDE {$c->code}" : ($c->notation ?? '—'), 0, 'L');
            $comp = null;
            foreach ($w->components as $k) if ($k->ref === $u->componentRef) $comp = $k->name;
            $tool = null;
            foreach ($proc->tools as $t) if ($t->ref === $u->toolRef) $tool = $t->name;
            pdf_kv($p, 'Componente', $comp ?? 'Obra en general');
            pdf_kv($p, 'Herramienta', $tool ?? 'No indicada');
            pdf_kv($p, 'Funciones (A)', implode('; ', array_map(fn($f) => "$f " . oride_label($f), $u->functions)) . ($u->primaryFunction && $u->primaryFunction !== 'NONE' && count($u->functions) > 1 ? " · Principal: {$u->primaryFunction}" : ''));
            pdf_kv($p, 'Fases', implode(', ', $u->phaseLabels));
            pdf_kv($p, 'Objetivo', $u->objective);
            pdf_kv($p, 'Actividad', $u->activityDescription);
            pdf_kv($p, 'Resultado', $u->resultDescription);
            pdf_kv($p, 'Incorporación', $u->incorporation ? L_INCORPORATION[$u->incorporation] : null);
            foreach ($u->contributions as $k) {
                pdf_kv($p, "Aportación {$k->type} (" . oride_label($k->type) . ')', ($k->interventionCode ? "{$k->interventionCode} " . oride_label($k->interventionCode) : 'Intervención no precisada') . ' — ' . (L_INTERVENTION[$k->interventionAnswer] ?? 'sin respuesta') . ($k->retainsRecognizableElements ? ' (elementos reconocibles: ' . L_TRI[$k->retainsRecognizableElements] . ')' : ''));
                if ($k->interventionDescription) pdf_p($p, 'Intervención humana: ' . $k->interventionDescription, ['muted' => true, 'size' => 8.5]);
            }
            if ($c) pdf_kv($p, 'Documentación (D)', "{$c->documentation} " . oride_label($c->documentation));
            $p->Ln(2);
        }
    }
    if ($proc->processNarrative || $proc->observations) {
        pdf_h2($p, 'Descripción del proceso y observaciones');
        if ($proc->processNarrative) pdf_p($p, $proc->processNarrative);
        pdf_kv($p, 'Observaciones', $proc->observations);
    }
    pdf_h2($p, 'Documentación complementaria declarada');
    if (!$ev->items) {
        pdf_p($p, 'No se han registrado evidencias complementarias.');
    } else {
        pdf_p($p, 'Se han registrado ' . count($ev->items) . ' evidencia(s). Su contenido es privado y no se reproduce en este documento. ' . $ev->note, ['muted' => true, 'size' => 8.5]);
        foreach ($ev->items as $e) pdf_kv($p, $e->ref, (L_EVIDENCE_KIND[$e->kind] ?? $e->kind) . ($e->useRef ? " · {$e->useRef}" : ' · declaración completa') . ($e->sha256 ? ' · SHA-256 ' . substr($e->sha256, 0, 16) . '…' : ''));
    }
    if ($a50->answered) {
        pdf_h2($p, 'Orientación sobre el artículo 50 del Reglamento (UE) 2024/1689');
        foreach ($a50->findings as $f) pdf_kv($p, ORIDE_A50_LEVELS[$f->level] ?? $f->level, "{$f->title}. {$f->text}");
        pdf_p($p, $a50->disclaimer, ['muted' => true, 'size' => 8]);
        pdf_p($p, 'Esta orientación es independiente del código ORIDE, que describe prácticas creativas y no incorpora ninguna valoración de cumplimiento.', ['muted' => true, 'size' => 8]);
    }
    pdf_h2($p, 'Declaración responsable');
    pdf_p($p, $st->text, ['style' => 'I']);
    pdf_kv($p, 'Texto aceptado', "{$st->legalTextKey} v{$st->legalTextVersion} · SHA-256 " . substr($st->textSha256, 0, 16) . '…');
    pdf_kv($p, 'Registro de aceptación', pdf_utc($st->acceptedAt) . ' · ' . $st->acceptanceMethod);

    pdf_h2($p, 'Integridad y verificación');
    pdf_kv($p, 'Identificador', $meta->publicId);
    pdf_kv($p, 'Esquema', "ORIDE Code v{$meta->schemeVersion} · Cuestionario v{$meta->questionnaireVersion}");
    pdf_kv($p, 'Sustituye a', $meta->supersedesPublicId);
    $p->SetFont('DejaVu', 'B', 8.5);
    $p->SetTextColor(91, 107, 108);
    $p->Cell(0, 5, 'Huella SHA-256 del contenido registrado:', 0, 1);
    $p->SetFont('Mono', '', 8.5);
    $p->SetTextColor(28, 37, 38);
    $p->MultiCell(0, 4.6, $hash, 0, 'L');
    pdf_kv($p, 'Verificación', $meta->verifyUrl);
    pdf_p($p, PDF_VERIFY_NOTE, ['muted' => true, 'size' => 8.5]);
    pdf_p($p, 'La consulta pública solo muestra los datos que el declarante haya decidido publicar. Este documento no es una declaración jurada, no tiene fe pública y no constituye una firma electrónica cualificada.', ['muted' => true, 'size' => 8]);
    return $p->Output('S');
}

function oride_pdf_summary(object $s, string $hash): string
{
    $d = fn($n) => $s->sections->$n->data;
    [$meta, $w, $dec, $scope, $cls] = [$d('meta'), $d('work'), $d('declarant'), $d('scope'), $d('classification')];
    $p = pdf_new($s, 'Resumen ORIDE');
    pdf_header($p, $meta, 'Resumen ORIDE');
    pdf_kv($p, 'Obra', $w->title . ($w->subtitle ? ". {$w->subtitle}" : '') . " ({$w->category->label})");
    pdf_kv($p, 'Declarante', $dec->name . ($dec->artisticName ? " («{$dec->artisticName}»)" : '') . ($dec->role ? " — {$dec->role}" : ''));
    pdf_kv($p, 'Alcance', $scope->scopeLabel);
    $p->Ln(2);
    pdf_code_box($p, $cls);
    pdf_legend($p, $cls);
    if (!$cls->code && $cls->uses) pdf_kv($p, 'Códigos por uso', implode('  ·  ', array_map(fn($u) => "{$u->ref}: " . ($u->code ?? $u->notation), $cls->uses)));
    pdf_p($p, $d('summary')->text, ['size' => 8.5]);
    pdf_p($p, PDF_NOT_MEANING, ['muted' => true, 'size' => 7.5]);
    pdf_kv($p, 'Fecha de emisión', pdf_utc($meta->issuedAt));
    pdf_kv($p, 'Identificador', $meta->publicId);
    pdf_kv($p, 'Huella SHA-256', $hash);
    pdf_kv($p, 'Verificación', $meta->verifyUrl);
    pdf_p($p, PDF_VERIFY_NOTE, ['muted' => true, 'size' => 7.5]);
    return $p->Output('S');
}
