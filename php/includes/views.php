<?php
/** Vistas compartidas (revisión, ficha del autor y página pública). */
declare(strict_types=1);

function dl_row(string $label, ?string $valueHtml): string
{
    if ($valueHtml === null || $valueHtml === '') return '';
    return '<div><dt>' . h($label) . '</dt><dd>' . $valueHtml . '</dd></div>';
}

function code_card(object $cls, array $o = []): string
{
    $html = '<section class="code-card" aria-label="Código ORIDE"><div class="inner"><div class="top"><span class="label">ORIDE Code v' . h($cls->schemeVersion) . '</span>';
    $copy = $cls->code ? 'ORIDE ' . $cls->code : ($cls->compositeNotation ? 'ORIDE ' . $cls->compositeNotation : null);
    if ($copy) $html .= '<button type="button" class="btn btn-copy" data-copy="' . h($copy) . '">Copiar código</button>';
    $html .= '</div>';
    if ($cls->code) {
        $html .= '<p class="big">ORIDE ' . h($cls->code) . '</p>';
        $html .= '<div class="dims">';
        foreach (str_split($cls->code, 2) as $seg) $html .= '<div class="dim"><span>' . h(oride_dimensions()[$seg[0]]['name']) . '</span><span class="code-seg">' . h($seg) . '</span> ' . h(oride_label($seg)) . '</div>';
        $html .= '</div>';
    } elseif ($cls->compositeNotation) {
        $html .= '<p class="big">' . h($cls->compositeNotation) . '</p><p>Perfil ORIDE compuesto: síntesis de los valores presentes en la obra. No es una secuencia de parejas B–C; las relaciones originales se conservan en el desglose por usos.</p>';
        if ($cls->composite) {
            $html .= '<div class="dims">';
            foreach (['A', 'B', 'C'] as $k) {
                $html .= '<div class="dim"><span>' . h(oride_dimensions()[$k]['name']) . '</span>' . (implode('<br>', array_map(fn($c) => '<span class="mono">' . h($c) . '</span> ' . h(oride_label($c)), $cls->composite->$k)) ?: '—') . '</div>';
            }
            $html .= '<div class="dim"><span>' . h(oride_dimensions()['D']['name']) . '</span><span class="mono">' . h($cls->composite->D) . '</span> ' . h(oride_label($cls->composite->D)) . '</div></div>';
        }
    } else {
        $html .= '<p class="big status-big">' . h(L_PROFILE_STATUS[$cls->status] ?? $cls->status) . '</p>';
    }
    if ($cls->status !== 'COMPLETE' && ($cls->code || $cls->compositeNotation)) $html .= '<p>' . h(L_PROFILE_STATUS[$cls->status] ?? '') . '</p>';
    if (!empty($o['scope'])) $html .= '<p class="scope-note">' . h($o['scope']) . '</p>';
    $html .= '</div>';
    if ($cls->uses) {
        $html .= '<div class="uses"><span class="label">Desglose por usos</span><ul>';
        foreach ($cls->uses as $u) $html .= '<li><span class="ref">' . h($u->ref) . '</span> <span class="mono">' . h($u->code ?? $u->notation) . '</span> ' . h($o['titles'][$u->ref] ?? '') . '</li>';
        $html .= '</ul></div>';
    }
    $html .= '<div class="foot"><span>Clasificación descriptiva. No es un porcentaje de autoría ni una certificación.</span><a class="link-44" href="' . u('codigo.php') . '">Qué significa</a></div></section>';
    return $html;
}

function a50_findings(object $a50): string
{
    $tone = ['POSSIBLE_OBLIGATION' => 'warn', 'LIMITED_OBLIGATION' => 'warn', 'UNCERTAIN' => 'warn', 'EXCEPTION_MAY_APPLY' => 'petrol', 'INFO' => 'neutral', 'NOT_IDENTIFIED' => 'ok'];
    $refs = oride_legal_references();
    $html = '';
    foreach ($a50->findings as $f) {
        $html .= '<div class="use-card"><p>' . badge(ORIDE_A50_LEVELS[$f->level], $tone[$f->level]) . ' <strong>' . h($f->title) . '</strong></p><p class="small">' . h($f->text) . '</p>';
        if ($f->references) {
            $html .= '<p class="meta">Referencias: ' . implode('; ', array_map(fn($k) => isset($refs[$k]) ? '<a href="' . h($refs[$k][2]) . '" rel="noopener noreferrer" target="_blank">' . h($refs[$k][1]) . '</a>' : h($k), $f->references)) . '</p>';
        }
        $html .= '</div>';
    }
    return $html . '<p class="meta">' . h($a50->disclaimer) . '</p><p class="meta">Esta orientación es independiente del código ORIDE, que describe prácticas creativas y no incorpora ninguna valoración de cumplimiento.</p>';
}

/**
 * Presenta una instantánea (completa o divulgada).
 * @param array<string,object> $s datos por sección (solo las visibles)
 * @param string[]|null $render secciones a pintar (null = todas las presentes)
 */
function render_snapshot(array $s, ?array $render = null, bool $codeCard = true): string
{
    $show = fn($n) => isset($s[$n]) && ($render === null || in_array($n, $render, true));
    $html = '';
    $w = $s['work'] ?? null;
    $cls = $s['classification'] ?? null;
    $proc = $s['process'] ?? null;
    $comp = function (?string $ref) use ($w) {
        if (!$ref || !$w) return null;
        foreach ($w->components as $c) if ($c->ref === $ref) return $c->name;
        return $ref;
    };
    $section = fn(string $title, string $body) => '<section class="section-card"><h2>' . h($title) . '</h2><div class="body">' . $body . '</div></section>';

    if ($cls && $codeCard && $show('classification')) {
        $titles = [];
        foreach ($proc->uses ?? [] as $u) $titles[$u->ref] = $u->title;
        $html .= code_card($cls, ['scope' => $s['scope']->coverageStatement ?? null, 'titles' => $titles]);
    }
    if ($show('summary')) $html .= $section('Resumen', '<p class="small">' . h($s['summary']->text) . '</p>');
    if ($show('work')) {
        $rows = dl_row('Título', h($w->title . ($w->subtitle ? ". {$w->subtitle}" : '')))
            . dl_row('Tipo de obra', h($w->category->label)) . dl_row('Estado', h(L_WORK_STATUS[$w->status] ?? $w->status))
            . dl_row('Descripción', $w->description ? h($w->description) : null) . dl_row('Periodo de creación', $w->creationPeriod ? h($w->creationPeriod) : null)
            . dl_row('Fecha de publicación', $w->publicationDate ? h($w->publicationDate) : null) . dl_row('Idioma', $w->language ? h($w->language) : null)
            . dl_row('Versión', $w->workVersion ? h($w->workVersion) : null) . dl_row('Editorial o institución', $w->publisher ? h($w->publisher) : null)
            . dl_row($w->identifierType ?: 'Identificador', $w->identifier ? h($w->identifier) : null)
            . dl_row('URL', $w->url ? '<a href="' . h($w->url) . '" rel="noopener noreferrer nofollow" target="_blank">' . h($w->url) . '</a>' : null)
            . dl_row('Componentes', $w->components ? h(implode(' — ', array_map(fn($c) => "{$c->ref} · {$c->name} ({$c->kindLabel})", $w->components))) : null)
            . dl_row('Obra colectiva', $w->isCollective ? h('Sí' . ($w->contributorRoles ? ' · otros roles: ' . implode(', ', array_map(fn($r) => $r->role, $w->contributorRoles)) : '')) : null);
        $html .= $section('Obra', '<dl class="dl">' . $rows . '</dl>');
    }
    if (($render === null || in_array('declarant', $render, true)) && (isset($s['declarant']) || isset($s['scope']))) {
        $d = $s['declarant'] ?? null;
        $sc = $s['scope'] ?? null;
        $rows = $d ? dl_row('Declarante', h($d->name . ($d->artisticName ? " («{$d->artisticName}»)" : ''))) . dl_row('Organización', $d->organization ? h($d->organization) : null) . dl_row('Rol en la obra', $d->role ? h($d->role) : null)
            : dl_row('Declarante', '<em class="muted">No publicado por decisión del declarante</em>');
        if ($sc) {
            $rows .= dl_row('Alcance', $sc->scopeLabel ? h($sc->scopeLabel) : null) . dl_row('Cobertura', h($sc->coverageStatement))
                . dl_row('Ámbito de responsabilidad', $sc->responsibilityNote ? h($sc->responsibilityNote) : null)
                . dl_row('Utilización de IA', $sc->effectiveAiUsageLabel ? h($sc->effectiveAiUsageLabel) : null) . dl_row('Incertidumbre declarada', $sc->uncertaintyNote ? h($sc->uncertaintyNote) : null);
        }
        $html .= $section('Declarante y alcance', '<dl class="dl">' . $rows . '</dl>');
    }
    if ($cls && $show('classification')) {
        $rows = dl_row('Estado', h(L_PROFILE_STATUS[$cls->status] ?? $cls->status))
            . dl_row('Código compacto', $cls->code ? '<span class="mono">' . h($cls->code) . '</span>' : 'No aplicable: ver perfil compuesto y desglose')
            . dl_row('Perfil compuesto', $cls->compositeNotation ? '<span class="mono">' . h($cls->compositeNotation) . '</span>' : null);
        if ($cls->composite) foreach (['A', 'B', 'C'] as $k) $rows .= dl_row($k . ' · ' . oride_dimensions()[$k]['name'], h(implode('; ', array_map(fn($c) => "$c " . oride_label($c), $cls->composite->$k)) ?: '—'));
        $rows .= dl_row('D · ' . oride_dimensions()['D']['name'], h($cls->documentation . ' ' . oride_label($cls->documentation)))
            . dl_row('Esquema', h('ORIDE Code v' . $cls->schemeVersion)) . dl_row('Reglas aplicadas', '<span class="mono meta">' . h(implode(' · ', $cls->rulesApplied)) . '</span>');
        $html .= $section('Clasificación ORIDE', '<dl class="dl">' . $rows . '</dl>');
    }
    if ($proc && $show('process')) {
        $b = '';
        if ($proc->tools) {
            $b .= '<h3>Herramientas</h3><ul class="small">';
            foreach ($proc->tools as $t) {
                $extra = implode(' · ', array_filter([$t->provider, $t->modelVersion, $t->systemType, $t->usagePeriod]));
                $b .= '<li><span class="mono meta">' . h($t->ref) . '</span> ' . ($t->identified ? '<strong>' . h($t->name) . '</strong>' : '<em>Herramienta no identificada</em>') . ($extra ? ' <span class="muted">· ' . h($extra) . '</span>' : '') . ($t->purpose ? '<br><span class="muted">Finalidad: ' . h($t->purpose) . '</span>' : '') . '</li>';
            }
            $b .= '</ul>';
        }
        foreach ($proc->uses as $u) {
            $c = null;
            foreach ($cls->uses ?? [] as $x) if ($x->ref === $u->ref) $c = $x;
            $tool = null;
            foreach ($proc->tools as $t) if ($t->ref === $u->toolRef) $tool = $t->name;
            $rows = dl_row('Componente', h($comp($u->componentRef) ?? 'Obra en general')) . dl_row('Herramienta', h($tool ?? 'No indicada'))
                . dl_row('Fases', h(implode(', ', $u->phaseLabels)) ?: null)
                . dl_row('Funciones (A)', h(implode('; ', array_map(fn($f) => "$f " . oride_label($f), $u->functions)) . ($u->primaryFunction && $u->primaryFunction !== 'NONE' && count($u->functions) > 1 ? " — principal: {$u->primaryFunction}" : '')))
                . dl_row('Objetivo', $u->objective ? h($u->objective) : null) . dl_row('Actividad', $u->activityDescription ? h($u->activityDescription) : null)
                . dl_row('Resultado obtenido', $u->resultDescription ? h($u->resultDescription) : null) . dl_row('Incorporación', $u->incorporation ? h(L_INCORPORATION[$u->incorporation]) : null);
            foreach ($u->contributions as $k) {
                $rows .= dl_row("Aportación {$k->type}", '<strong>' . h(oride_label($k->type)) . '</strong> → ' . h($k->interventionCode ? "{$k->interventionCode} " . oride_label($k->interventionCode) : 'intervención no precisada')
                    . '<br><span class="meta">Respuesta: ' . h(L_INTERVENTION[$k->interventionAnswer] ?? '—') . ($k->retainsRecognizableElements ? ' · ¿elementos reconocibles del original? ' . h(L_TRI[$k->retainsRecognizableElements]) : '') . '</span>'
                    . ($k->interventionDescription ? '<br>' . h($k->interventionDescription) : ''));
            }
            if ($c) $rows .= dl_row('Documentación (D)', h("{$c->documentation} " . oride_label($c->documentation)));
            if ($u->evidenceRefs) $rows .= dl_row('Evidencias', h(implode(', ', $u->evidenceRefs)));
            $b .= '<article class="use-card"><h3><span><span class="mono meta">' . h($u->ref) . '</span> ' . h($u->title) . '</span><span class="mono">' . h($c ? ($c->code ?? $c->notation) : '') . '</span></h3><dl class="dl">' . $rows . '</dl></article>';
        }
        if ($proc->processNarrative || $proc->observations) {
            $b .= '<dl class="dl">' . dl_row('Descripción del proceso', $proc->processNarrative ? h($proc->processNarrative) : null) . dl_row('Observaciones', $proc->observations ? h($proc->observations) : null) . '</dl>';
        }
        $html .= $section('Proceso declarado', $b ?: '<p class="muted small">Sin información adicional sobre el proceso.</p>');
    }
    if ($show('evidence')) {
        $e = $s['evidence'];
        $b = $e->items ? '<ul class="small">' . implode('', array_map(fn($x) => '<li><span class="mono meta">' . h($x->ref) . '</span> ' . h(L_EVIDENCE_KIND[$x->kind] ?? $x->kind) . ' — ' . h($x->description) . ($x->sha256 ? '<br><span class="mono meta">SHA-256 ' . h($x->sha256) . '</span>' : '') . '</li>', $e->items)) . '</ul>' : '<p class="small muted">No se han registrado evidencias.</p>';
        $html .= $section('Evidencias complementarias', $b . '<p class="meta">' . h($e->note) . '</p>');
    }
    if ($show('article50') && $s['article50']->answered) $html .= $section('Orientación sobre el artículo 50 (Reglamento UE 2024/1689)', a50_findings($s['article50']));
    if ($show('statement')) {
        $st = $s['statement'];
        $html .= $section('Declaración responsable', '<blockquote class="statement">' . h($st->text) . '</blockquote><dl class="dl">' . dl_row('Texto aceptado', h("{$st->legalTextKey} v{$st->legalTextVersion}")) . dl_row('Aceptación', h($st->acceptanceMethod)) . '</dl>');
    }
    return $html;
}

/** Datos de las secciones de una instantánea (objeto) como array nombre => datos. */
function snapshot_sections(object $snap): array
{
    $out = [];
    foreach ($snap->sections as $n => $sec) $out[$n] = $sec->data;
    return $out;
}
