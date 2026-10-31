<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

// Sin JavaScript el formulario se envía a esta misma página y el código se calcula aquí. No se guarda nada.
$in = oride_input_from_get();
$r = oride_compute($in);
$opt = fn(string $name, string $value, string $type, bool $checked, string $label, string $desc = '') =>
    '<label class="choice"><input type="' . $type . '" name="' . $name . '" value="' . h($value) . '"' . ($checked ? ' checked' : '') . '><span><span class="choice-label">' . $label . '</span>' . ($desc !== '' ? '<span class="choice-desc">' . h($desc) . '</span>' : '') . '</span></label>';
$yes = tr('Sí', 'Yes');

page_start(t('nav_create'), tr('Responde unas preguntas y obtén al momento el Código ORIDE de tu obra.', 'Answer a few questions and get the ORIDE Code for your work instantly.'));
?>
<div class="container create">
  <div class="create-head">
    <h1><?= h(t('nav_create')) ?></h1>
    <p class="muted"><?= h(tr('Responde y tu Código ORIDE aparece al momento. No se guarda nada.', 'Answer and your ORIDE Code appears instantly. Nothing is stored.')) ?></p>
  </div>
  <div class="create-grid">
    <form class="card create-form" method="get" action="<?= u('crear.php') ?>#declaracion" data-builder>
      <?= lang_field() ?>
      <fieldset class="choices q">
        <legend><?= h(tr('¿Se ha utilizado inteligencia artificial durante la elaboración de esta obra?', 'Was artificial intelligence used in creating this work?')) ?></legend>
        <div class="choice-list inline">
          <?= $opt('ia', 'si', 'radio', $in['ia'] === 'si', $yes) . $opt('ia', 'no', 'radio', $in['ia'] === 'no', 'No') ?>
        </div>
      </fieldset>

      <div data-when="ia=si">
        <fieldset class="choices q">
          <legend><?= h(tr('Señala las áreas para las que quieres hacer la declaración', 'Select the areas you want to declare')) ?></legend>
          <p class="hint"><?= h(tr('Marca todas las que correspondan.', 'Tick all that apply.')) ?></p>
          <div class="choice-list">
            <?php foreach (areas() as $l => $a) echo $opt('areas[]', $l, 'checkbox', in_array($l, $in['areas'], true), '<b class="mono">' . $l . '</b> ' . h($a[0]), $a[1]); ?>
          </div>
        </fieldset>

        <?php foreach (areas() as $l => $a): ?>
          <div class="area-block" data-when="areas=<?= $l ?>">
            <fieldset class="choices q">
              <legend><span class="dim-tag"><?= $l ?></span><?= h(tr('¿Cómo utilizaste los resultados de la IA para ' . $a[2] . '?', 'How did you use the AI results for ' . $a[2] . '?')) ?></legend>
              <div class="choice-list">
                <?php foreach ($a[3] as $n => $text) echo $opt('u' . $l, (string) $n, 'radio', ($in['u'][$l] ?? '') === (string) $n, '<b class="mono">' . $n . '</b> ' . h($text)); ?>
              </div>
            </fieldset>
            <fieldset class="choices q" data-when="u<?= $l ?>=3,4,5">
              <legend><?= h(tr('¿Qué parte de tu obra está afectada por el uso de la IA para ' . $a[2] . '?', 'What part of your work is affected by the use of AI for ' . $a[2] . '?')) ?></legend>
              <div class="choice-list">
                <?php foreach (scope_labels() as $n => $text) echo $opt('s' . $l, (string) $n, 'radio', ($in['s'][$l] ?? '') === (string) $n, '<b class="mono">' . $n . '</b> ' . h($text)); ?>
              </div>
            </fieldset>
          </div>
        <?php endforeach; ?>

        <fieldset class="choices q">
          <legend><span class="dim-tag">V</span><?= h(tr('¿Conservas evidencias que permitan documentar los usos de IA que has declarado?', 'Do you keep evidence that documents the AI uses you have declared?')) ?></legend>
          <p class="hint"><?= h(tr('Por ejemplo: qué herramientas usaste, historiales de interacción, prompts, respuestas generadas por la IA, versiones de trabajo u otros documentos que muestren cómo tu obra incorporó o no esas propuestas.', 'For example: which AI tools you used, interaction histories, prompts, AI-generated responses, working versions or other documents showing how your work did or did not incorporate those proposals.')) ?></p>
          <div class="choice-list inline">
            <?= $opt('ev', 'si', 'radio', $in['ev'] === 'si', $yes) . $opt('ev', 'no', 'radio', $in['ev'] === 'no', 'No') ?>
          </div>
          <div class="alert alert-warn" data-when="ev=si"><?= h(t('evidence_note')) ?></div>
        </fieldset>
      </div>

      <div class="no-js-only"><button class="btn btn-primary"><?= h(tr('Ver mi código', 'Show my code')) ?></button></div>
      <p class="hint" data-when="ia=no"><?= h(tr('Listo. Tu código está a la derecha.', 'Done. Your code is on the right.')) ?></p>
    </form>

    <div class="create-aside">
      <?= code_box($r, true) ?>
    </div>
  </div>
</div>
<a class="code-chip" href="#declaracion" data-chip hidden><?= h(t('chip')) ?> <span class="mono" data-chip-code>—</span></a>
<?php page_end();
