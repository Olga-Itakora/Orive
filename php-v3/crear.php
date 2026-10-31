<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

// Sin JavaScript el formulario se envía a esta misma página y el código se calcula aquí. No se guarda nada.
$in = oride_input_from_get();
$r = oride_compute($in);
$opt = fn(string $name, string $value, string $type, bool $checked, string $label, string $desc = '') =>
    '<label class="choice"><input type="' . $type . '" name="' . $name . '" value="' . h($value) . '"' . ($checked ? ' checked' : '') . '><span><span class="choice-label">' . $label . '</span>' . ($desc !== '' ? '<span class="choice-desc">' . h($desc) . '</span>' : '') . '</span></label>';
$taxonomy = ['areas' => ORIDE_AREAS, 'scope' => ORIDE_SCOPE, 'evidence' => ORIDE_EVIDENCE, 'evidenceNote' => ORIDE_EVIDENCE_NOTE];

page_start('Crea tu declaración', 'Responde unas preguntas y obtén al momento el Código ORIDE de tu obra.');
?>
<div class="container create">
  <div class="create-head">
    <h1>Crea tu declaración</h1>
    <p class="muted">Responde y tu Código ORIDE aparece al momento. No se guarda nada.</p>
  </div>
  <div class="create-grid">
    <form class="card create-form" method="get" action="<?= u('crear.php') ?>#declaracion" data-builder data-taxonomy="<?= h(json_encode($taxonomy, JSON_UNESCAPED_UNICODE)) ?>">
      <fieldset class="choices q">
        <legend>¿Se ha utilizado inteligencia artificial durante la elaboración de esta obra?</legend>
        <div class="choice-list inline">
          <?= $opt('ia', 'si', 'radio', $in['ia'] === 'si', 'Sí') . $opt('ia', 'no', 'radio', $in['ia'] === 'no', 'No') ?>
        </div>
      </fieldset>

      <div data-when="ia=si">
        <fieldset class="choices q">
          <legend>Señala las áreas para las que quieres hacer la declaración</legend>
          <p class="hint">Marca todas las que correspondan.</p>
          <div class="choice-list">
            <?php foreach (ORIDE_AREAS as $l => $a) echo $opt('areas[]', $l, 'checkbox', in_array($l, $in['areas'], true), '<b class="mono">' . $l . '</b> ' . h($a[0]), $a[1]); ?>
          </div>
        </fieldset>

        <?php foreach (ORIDE_AREAS as $l => $a): ?>
          <div class="area-block" data-when="areas=<?= $l ?>">
            <fieldset class="choices q">
              <legend><span class="dim-tag"><?= $l ?></span>¿Cómo utilizaste los resultados de la IA para <?= h($a[2]) ?>?</legend>
              <div class="choice-list">
                <?php foreach ($a[3] as $n => $text) echo $opt('u' . $l, (string) $n, 'radio', ($in['u'][$l] ?? '') === (string) $n, '<b class="mono">' . $n . '</b> ' . h($text)); ?>
              </div>
            </fieldset>
            <fieldset class="choices q" data-when="u<?= $l ?>=3,4,5">
              <legend>¿Qué parte de tu obra está afectada por el uso de la IA para <?= h($a[2]) ?>?</legend>
              <div class="choice-list">
                <?php foreach (ORIDE_SCOPE as $n => $text) echo $opt('s' . $l, (string) $n, 'radio', ($in['s'][$l] ?? '') === (string) $n, '<b class="mono">' . $n . '</b> ' . h($text)); ?>
              </div>
            </fieldset>
          </div>
        <?php endforeach; ?>

        <fieldset class="choices q">
          <legend><span class="dim-tag">V</span>¿Conservas evidencias que permitan documentar los usos de IA que has declarado?</legend>
          <p class="hint">Por ejemplo: qué herramientas usaste, historiales de interacción, prompts, respuestas generadas por la IA, versiones de trabajo u otros documentos que muestren cómo tu obra incorporó o no esas propuestas.</p>
          <div class="choice-list inline">
            <?= $opt('ev', 'si', 'radio', $in['ev'] === 'si', 'Sí') . $opt('ev', 'no', 'radio', $in['ev'] === 'no', 'No') ?>
          </div>
          <div class="alert alert-warn" data-when="ev=si"><?= h(ORIDE_EVIDENCE_NOTE) ?></div>
        </fieldset>
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
