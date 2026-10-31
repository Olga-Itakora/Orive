<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$c = mb_substr(get('c'), 0, 80);
$e = $c !== '' ? oride_explain($c) : null;
$taxonomy = ['areas' => ORIDE_AREAS, 'scope' => ORIDE_SCOPE, 'evidence' => ORIDE_EVIDENCE, 'evidenceNote' => ORIDE_EVIDENCE_NOTE];
page_start('Código ORIDE', 'Cómo se lee el Código ORIDE: áreas, forma de uso, alcance y evidencias.');
?>
<div class="container page">
  <div class="narrow-text">
    <h1>Código ORIDE</h1>
    <p class="lead">Dice en qué áreas se usó la IA, cómo se usaron sus resultados, qué parte de la obra afectan y si hay evidencias.</p>
    <div class="anatomy" aria-label="Ejemplo: IA1-I2-C43-V1">
      <div><span class="mono">IA1</span><p>Se usó IA<br><span class="muted">IA0: no se usó</span></p></div>
      <div><span class="mono">I2</span><p>Área + forma de uso</p></div>
      <div><span class="mono">C43</span><p>Área + forma de uso + alcance</p></div>
      <div><span class="mono">V1</span><p>Evidencias<br><span class="muted">V0: no hay</span></p></div>
    </div>
  </div>

  <form class="card decoder" method="get" action="<?= u('codigo.php') ?>" data-decoder data-taxonomy="<?= h(json_encode($taxonomy, JSON_UNESCAPED_UNICODE)) ?>">
    <label for="c">Escribe un código para leerlo</label>
    <div class="search-row"><input id="c" name="c" class="mono" value="<?= h($c) ?>" maxlength="80" placeholder="IA1-I2-C43-V1" autocomplete="off" spellcheck="false"><button class="btn btn-primary">Leer código</button></div>
  </form>
  <div data-decoder-out>
    <?php if ($e && !$e['valid']) echo '<div class="alert alert-warn" role="status">' . h($e['error']) . '</div>'; ?>
    <?php if ($e && $e['valid']) echo code_box($e['result'], false, 'Código ORIDE'); ?>
  </div>

  <h2 class="spaced">Áreas</h2>
  <div class="area-grid">
    <?php foreach (ORIDE_AREAS as $l => $a): ?>
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
      <h2>Alcance</h2>
      <p class="muted small">Solo con las formas de uso 3, 4 y 5, que incorporan resultados de la IA a la obra. Es la segunda cifra: <span class="mono">C43</span> = forma de uso 4, alcance 3.</p>
      <dl class="values"><?php foreach (ORIDE_SCOPE as $n => $text): ?><div><dt class="mono"><?= $n ?></dt><dd><?= h($text) ?></dd></div><?php endforeach; ?></dl>
    </section>
    <section class="card">
      <h2>Evidencias</h2>
      <dl class="values">
        <div><dt class="mono">V0</dt><dd>No conserva evidencias.</dd></div>
        <div><dt class="mono">V1</dt><dd>Conserva evidencias (herramientas, historiales, prompts, versiones…). <?= h(ORIDE_EVIDENCE_NOTE) ?></dd></div>
      </dl>
    </section>
  </div>
</div>
<?php page_end();
