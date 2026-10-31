<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

// Sin JavaScript el formulario se envía a esta misma página y el código se calcula aquí. No se guarda nada.
$in = oride_input_from_get();
$r = oride_compute($in);
$aSel = $in['a'];
$bSel = $in['b'];
$opt = fn(string $name, string $value, string $type, bool $checked, string $label, string $desc = '') =>
    '<label class="choice"><input type="' . $type . '" name="' . $name . '" value="' . h($value) . '"' . ($checked ? ' checked' : '') . '><span><span class="choice-label">' . $label . '</span>' . ($desc !== '' ? '<span class="choice-desc">' . h($desc) . '</span>' : '') . '</span></label>';

page_start('Crea tu declaración', 'Responde unas preguntas y obtén al momento el Código ORIDE de tu obra.');
?>
<div class="container create">
  <div class="create-head">
    <h1>Crea tu declaración</h1>
    <p class="muted">Responde y tu Código ORIDE aparece al momento. No se guarda nada.</p>
  </div>
  <div class="create-grid">
    <form class="card create-form" method="get" action="<?= u('crear.php') ?>#declaracion" data-builder data-taxonomy="<?= h(json_encode(ORIDE_DIMS, JSON_UNESCAPED_UNICODE)) ?>">
      <fieldset class="choices q" data-q="ia">
        <legend>¿Se ha utilizado inteligencia artificial durante la elaboración de esta obra?</legend>
        <div class="choice-list inline">
          <?= $opt('ia', 'si', 'radio', $in['ia'] === 'si', 'Sí') . $opt('ia', 'no', 'radio', $in['ia'] === 'no', 'No') ?>
        </div>
      </fieldset>

      <div data-when="ia=si">
        <fieldset class="choices q">
          <legend><span class="dim-tag">A</span>¿Para qué utilizaste la IA?</legend>
          <p class="hint">Marca todas las que correspondan.</p>
          <div class="choice-list">
            <?php foreach (['A1', 'A2', 'A3', 'A4', 'A5'] as $v) echo $opt('a[]', $v, 'checkbox', in_array($v, $aSel, true), '<b class="mono">' . $v . '</b> ' . h(label($v)), ORIDE_DIMS['A']['values'][$v][1]); ?>
          </div>
        </fieldset>

        <fieldset class="choices q">
          <legend><span class="dim-tag">B</span>¿Se incorporó a la obra final algo procedente de la IA?</legend>
          <div class="choice-list inline">
            <?= $opt('inc', 'si', 'radio', $in['inc'] === 'si', 'Sí') . $opt('inc', 'no', 'radio', $in['inc'] === 'no', 'No, nada') ?>
          </div>
        </fieldset>

        <div data-when="inc=si">
          <fieldset class="choices q">
            <legend>¿Qué se incorporó?</legend>
            <p class="hint">Marca todas las que correspondan.</p>
            <div class="choice-list">
              <?php foreach (['B1', 'B2', 'B3'] as $v) echo $opt('b[]', $v, 'checkbox', in_array($v, $bSel, true), '<b class="mono">' . $v . '</b> ' . h(label($v)), ORIDE_DIMS['B']['values'][$v][1]); ?>
            </div>
          </fieldset>

          <?php foreach (['B1', 'B2', 'B3'] as $b): ?>
            <fieldset class="choices q" data-when="b=<?= $b ?>">
              <legend><span class="dim-tag">C</span>¿Qué hiciste con <?= h(b_short($b)) ?>?</legend>
              <div class="choice-list">
                <?php foreach (['C1', 'C2', 'C3', 'C4', 'C5'] as $v) echo $opt('c' . $b, $v, 'radio', ($in['c'][$b] ?? '') === $v, '<b class="mono">' . $v . '</b> ' . h(label($v)), ORIDE_DIMS['C']['values'][$v][1]); ?>
              </div>
            </fieldset>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="no-js-only"><button class="btn btn-primary">Ver mi código</button></div>
      <p class="hint" data-when="ia=no">Listo. Tu código está a la derecha.</p>
    </form>

    <div class="create-aside">
      <?= code_box($r, true) ?>
    </div>
  </div>
</div>
<a class="code-chip" href="#declaracion" data-chip hidden>Tu código: <span class="mono" data-chip-code>—</span></a>
<?php page_end();
