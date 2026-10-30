<?php
/** Utilidades de la zona de autor. */
declare(strict_types=1);

require_once __DIR__ . '/declarations.php';
require_once __DIR__ . '/views.php';

function page_header(string $title, ?string $desc = null, ?array $back = null, string $actions = ''): void
{
    echo '<div class="container page-head">';
    if ($back) echo '<a class="link-44 back-link" href="' . h(u($back[0])) . '">← ' . h($back[1]) . '</a>';
    echo '<div class="page-head-row"><div><h1>' . h($title) . '</h1>' . ($desc !== null ? '<p class="muted prose">' . $desc . '</p>' : '') . '</div>' . ($actions ? '<div class="actions">' . $actions . '</div>' : '') . '</div></div>';
}

/** Comprueba que la obra pertenece al usuario; si no, 404. */
function work_or_404(array $user, int $wid): array
{
    $w = owned_work((int) $user['id'], $wid);
    if (!$w) not_found();
    return $w;
}

function version_or_404(array $user, int $vid): array
{
    $v = owned_version((int) $user['id'], $vid);
    if (!$v) not_found();
    return $v;
}

function work_payload(): array
{
    $date = post('publication_date');
    return [
        'title' => mb_substr(post('title'), 0, 300), 'subtitle' => nullable(post('subtitle'), 300), 'category_key' => post('category_key'),
        'description' => nullable(post('description'), 4000), 'creation_period' => nullable(post('creation_period'), 120),
        'status' => in_array(post('status'), ['IN_PROGRESS', 'FINISHED', 'PUBLISHED'], true) ? post('status') : 'IN_PROGRESS',
        'publication_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null, 'language' => nullable(post('language'), 60),
        'work_version' => nullable(post('work_version'), 60), 'publisher' => nullable(post('publisher'), 200),
        'identifier_type' => nullable(post('identifier_type'), 30), 'identifier' => nullable(post('identifier'), 120),
        'url' => nullable(post('url'), 500), 'is_collective' => post('is_collective') === 'on' ? 1 : 0,
    ];
}

function work_errors(array $d): array
{
    $e = [];
    if ($d['title'] === '') $e['title'] = 'La obra necesita un título.';
    if (!q1('SELECT ckey FROM work_categories WHERE ckey = ? AND active = 1', [$d['category_key']])) $e['category_key'] = 'Selecciona el tipo de obra.';
    if ($d['url'] !== null && !preg_match('#^https?://\S+$#i', $d['url'])) $e['url'] = 'Escribe una dirección completa que empiece por https://.';
    return $e;
}

function work_form(array $w, array $errors, string $submit, string $draftKey, string $extra = ''): string
{
    $cats = [];
    foreach (q('SELECT * FROM work_categories WHERE active = 1 OR ckey = ? ORDER BY sort_order', [$w['category_key'] ?? ''])->fetchAll() as $c) $cats[$c['grp']][$c['ckey']] = $c['label_es'];
    $opts = ['' => 'Selecciona…'] + $cats;
    $v = fn($k) => (string) ($w[$k] ?? '');
    $html = '<div class="form-grid">'
        . field('title', 'Título', ['required' => true, 'max' => 300, 'value' => $v('title'), 'error' => $errors['title'] ?? null, 'class' => 'span-2'])
        . field('subtitle', 'Subtítulo', ['optional' => true, 'max' => 300, 'value' => $v('subtitle'), 'class' => 'span-2'])
        . field('category_key', 'Tipo de obra', ['type' => 'select', 'required' => true, 'options' => $opts, 'value' => $v('category_key'), 'error' => $errors['category_key'] ?? null, 'hint' => 'Si es una obra híbrida, elige la modalidad principal y describe sus partes como componentes.'])
        . field('status', 'Estado de la obra', ['type' => 'select', 'options' => L_WORK_STATUS, 'value' => $v('status') ?: 'IN_PROGRESS'])
        . field('description', 'Descripción', ['type' => 'textarea', 'optional' => true, 'max' => 4000, 'value' => $v('description'), 'class' => 'span-2', 'hint' => 'Evita incluir datos personales de terceros.'])
        . field('creation_period', 'Fecha o periodo de creación', ['optional' => true, 'max' => 120, 'value' => $v('creation_period'), 'hint' => 'Por ejemplo: «2024–2026» o «primavera de 2025».'])
        . field('publication_date', 'Fecha de publicación', ['type' => 'date', 'optional' => true, 'value' => $v('publication_date')])
        . field('language', 'Idioma', ['optional' => true, 'max' => 60, 'value' => $v('language') ?: ($w ? '' : 'Español')])
        . field('work_version', 'Versión de la obra', ['optional' => true, 'max' => 60, 'value' => $v('work_version'), 'hint' => 'Por ejemplo: «2.ª edición» o «v1.3».'])
        . field('publisher', 'Editorial o institución', ['optional' => true, 'max' => 200, 'value' => $v('publisher')])
        . field('identifier_type', 'Tipo de identificador', ['type' => 'select', 'optional' => true, 'options' => ['' => '—', 'ISBN' => 'ISBN', 'DOI' => 'DOI', 'ISSN' => 'ISSN', 'ISRC' => 'ISRC', 'ISAN' => 'ISAN', 'Otro' => 'Otro'], 'value' => $v('identifier_type')])
        . field('identifier', 'Identificador', ['optional' => true, 'max' => 120, 'value' => $v('identifier')])
        . field('url', 'URL de publicación', ['type' => 'url', 'optional' => true, 'max' => 500, 'value' => $v('url'), 'placeholder' => 'https://editorial.example/obra'])
        . '</div><label class="check"><input type="checkbox" name="is_collective"' . (!empty($w['is_collective']) ? ' checked' : '') . '><span><strong>Es una obra colectiva o con varios participantes</strong><br><span class="muted">Tu declaración solo cubrirá lo que tú conoces y declaras; no se atribuirá a los demás participantes.</span></span></label>';
    return $html . '<div class="sticky-actions"><button class="btn btn-primary">' . h($submit) . '</button>' . $extra . '</div>';
}
