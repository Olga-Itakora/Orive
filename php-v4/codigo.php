<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$c = mb_substr(get('c'), 0, 80);
$e = $c !== '' ? oride_explain($c) : null;
page_start(t('nav_code'), tr('Cómo se lee el Código ORIDE: áreas, forma de uso, alcance y evidencias.', 'How to read the ORIDE Code: areas, type of use, scope and evidence.'));
?>
<div class="container page">
  <div class="narrow-text">
    <h1><?= h(t('nav_code')) ?></h1>
    <p class="lead"><?= h(tr('Dice en qué áreas se usó la IA, cómo se usaron sus resultados, qué parte de la obra afectan y si hay evidencias.', 'It says in which areas AI was used, how its results were used, how much of the work they affect and whether there is evidence.')) ?></p>
    <div class="anatomy" aria-label="<?= h(t('example')) ?>: IA1-I2-C43-V1">
      <div><span class="mono">IA1</span><p><?= h(tr('Se usó IA', 'AI was used')) ?><br><span class="muted"><?= h(tr('IA0: no se usó', 'IA0: not used')) ?></span></p></div>
      <div><span class="mono">I2</span><p><?= h(tr('Área + forma de uso', 'Area + type of use')) ?></p></div>
      <div><span class="mono">C43</span><p><?= h(tr('Área + forma de uso + alcance', 'Area + type of use + scope')) ?></p></div>
      <div><span class="mono">V1</span><p><?= h(t('evidence_title')) ?><br><span class="muted"><?= h(tr('V0: no hay', 'V0: none')) ?></span></p></div>
    </div>
    <?= honor_note() ?>
  </div>

  <form class="card decoder" method="get" action="<?= u('codigo.php') ?>" data-decoder>
    <?= lang_field() ?>
    <label for="c"><?= h(tr('Escribe un código para leerlo', 'Type a code to read it')) ?></label>
    <div class="search-row"><input id="c" name="c" class="mono" value="<?= h($c) ?>" maxlength="80" placeholder="IA1-I2-C43-V1" autocomplete="off" spellcheck="false"><button class="btn btn-primary"><?= h(tr('Leer código', 'Read code')) ?></button></div>
  </form>
  <div data-decoder-out>
    <?php if ($e && !$e['valid']) echo '<div class="alert alert-warn" role="status">' . h($e['error']) . '</div>'; ?>
    <?php if ($e && $e['valid']) echo code_box($e['result'], false, t('code_label')); ?>
  </div>

  <h2 class="spaced"><?= h(tr('Áreas', 'Areas')) ?></h2>
  <div class="area-grid">
    <?php foreach (areas() as $l => $a): ?>
      <section class="card area-card">
        <h3><span class="dim-tag"><?= $l ?></span><?= h($a[0]) ?></h3>
        <p class="muted small"><?= h($a[1]) ?></p>
        <dl class="values">
          <?php foreach ($a[3] as $n => $text): ?><div><dt class="mono"><?= $l . $n ?></dt><dd><?= h($text) ?></dd></div><?php endforeach; ?>
        </dl>
      </section>
    <?php endforeach; ?>
  </div>

  <div class="grid-2 spaced">
    <section class="card">
      <h2><?= h(tr('Alcance', 'Scope')) ?></h2>
      <p class="muted small"><?= tr('Solo con las formas de uso 3, 4 y 5, que incorporan resultados de la IA a la obra. Es la segunda cifra: <span class="mono">C43</span> = forma de uso 4, alcance 3.', 'Only with types of use 3, 4 and 5, which bring AI results into the work. It is the second digit: <span class="mono">C43</span> = type of use 4, scope 3.') ?></p>
      <dl class="values"><?php foreach (scope_labels() as $n => $text): ?><div><dt class="mono"><?= $n ?></dt><dd><?= h($text) ?></dd></div><?php endforeach; ?></dl>
    </section>
    <section class="card">
      <h2><?= h(t('evidence_title')) ?></h2>
      <dl class="values">
        <div><dt class="mono">V0</dt><dd><?= h(STR[lang()]['evidence'][0]) ?></dd></div>
        <div><dt class="mono">V1</dt><dd><?= h(STR[lang()]['evidence'][1]) ?> <?= h(t('evidence_note')) ?></dd></div>
      </dl>
    </section>
  </div>
</div>
<?php page_end();
