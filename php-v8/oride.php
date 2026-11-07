<?php
/**
 * Página enlazable para cualquier código: oride.php?LI-IA1-EV1-DOC44-GEN24
 * Admite también oride.php?c=…, «:» en lugar de guiones y las actividades en cualquier orden.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

/** El código es la primera parte de la consulta que no es «lang=…» (también vale «c=…»). */
function code_from_query(): string
{
    if (get('c') !== '') return get('c');
    foreach (explode('&', (string) ($_SERVER['QUERY_STRING'] ?? '')) as $part) {
        if ($part === '') continue;
        [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
        if ($v === '' && !in_array(strtolower($k), ['lang', 'c'], true)) return rawurldecode($k);
    }
    return '';
}

$raw = mb_substr(code_from_query(), 0, 200);
$e = $raw !== '' ? oride_explain($raw) : null;
$r = $e && $e['valid'] ? $e['result'] : null;
$title = $r ? 'ORIDE ' . $r['code'] : t('code_label');
// Datos para el índice que no van en el código: pregunta de proporción y versión del algoritmo.
$extra = ['q' => get('q'), 'qp' => get('qp')];
if (get('ig') !== '') $extra['version'] = 'ORIDE-IG-' . get('ig');

/** Explicación en frases sencillas. */
function plain_explanation(array $r): array
{
    $type = type_name($r['type']);
    $out = [];
    if ($r['ia'] === 0) {
        $out[] = tr("Obra de tipo «{$type}» en la que no se utilizó inteligencia artificial.", "A work of type “{$type}” in which no artificial intelligence was used.");
        if ($r['ev'] === 1) $out[] = tr('El autor conserva evidencias de su proceso de creación y puede mostrarlas si se le piden.', 'The author keeps evidence of their creative process and can show it on request.');
        if ($r['ev'] === 0) $out[] = tr('El autor no conserva evidencias de su proceso de creación.', 'The author does not keep evidence of their creative process.');
        return $out;
    }
    $out[] = tr("Obra de tipo «{$type}» en la que se utilizó inteligencia artificial.", "A work of type “{$type}” in which artificial intelligence was used.");
    if ($r['ev'] === 1) $out[] = tr('El autor conserva evidencias de estos usos y puede mostrarlas si se le piden.', 'The author keeps evidence of these uses and can show it on request.');
    if ($r['ev'] === 0) $out[] = tr('El autor no conserva evidencias de estos usos.', 'The author does not keep evidence of these uses.');
    if ($r['all_zero']) $out[] = t('all_zero_reader');
    if (!$r['acts']) $out[] = tr('El autor no detalla en qué actividades.', 'The author does not detail in which activities.');
    foreach ($r['acts'] as [$act, $level, $scope]) {
        $name = acts($r['type'])[$act][0];
        $line = "«{$name}»: " . mb_strtolower(LEVELS_READER[lang()][$level]);
        if ($scope !== null) $line .= ' (' . mb_strtolower(t('scope_line', mb_strtolower(scope_labels()[$scope]))) . ')';
        $out[] = (en() ? str_replace(['«', '»'], ['“', '”'], $line) : $line) . '.';
    }
    return $out;
}

page_start($title, $r ? implode(' ', plain_explanation($r)) : tr('Qué significa un Código ORIDE.', 'What an ORIDE Code means.'));
?>
<div class="container narrow-text page">
  <?php if ($r): ?>
    <p class="eyebrow"><?= h(t('code_label')) ?></p>
    <h1 class="mono code-title"><?= implode('<br>', array_map('h', $r['lines'])) ?></h1>
    <?php if ($e['code'] !== $r['code']): ?><p class="muted small"><?= h(tr('Escrito como', 'Written as')) ?> <span class="mono"><?= h($raw) ?></span>.</p><?php endif; ?>
    <ul class="plain-list">
      <?php foreach (plain_explanation($r) as $line) echo '<li>' . h($line) . '</li>'; ?>
    </ul>
    <?= code_box($r, ['actions' => true, 'reader' => true, 'extra' => $extra]) ?>
    <?= honor_note() ?>
  <?php else: ?>
    <h1><?= h(t('code_label')) ?></h1>
    <?php if ($e) echo alert($e['error'], 'warn'); ?>
    <p class="muted"><?= h(tr('Escribe un código para ver qué significa, o abre un enlace como', 'Type a code to see what it means, or open a link such as')) ?> <a class="mono" href="<?= u('oride.php?LI-IA1-EV1-DOC44-GEN24') ?>">oride.php?LI-IA1-EV1-DOC44-GEN24</a>.</p>
  <?php endif; ?>
  <form class="card decoder" method="get" action="<?= u('oride.php') ?>">
    <?= lang_field() ?>
    <label for="c"><?= h(tr('Leer otro código', 'Read another code')) ?></label>
    <div class="search-row"><input id="c" name="c" class="mono" maxlength="200" placeholder="LI-IA1-EV1-GEN42" autocomplete="off" spellcheck="false"><button class="btn btn-primary"><?= h(tr('Leer código', 'Read code')) ?></button></div>
  </form>
  <p class="actions"><a class="btn btn-primary" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a><a class="btn btn-secondary" href="<?= u('codigo.php') ?>"><?= h(tr('Cómo se lee el código', 'How to read the code')) ?></a></p>
</div>
<?php page_end();
