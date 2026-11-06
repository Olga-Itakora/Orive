<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_code'), tr('Cómo se lee el Código ORIDE: tipo de obra, uso de IA, evidencias y actividades.', 'How to read the ORIDE Code: type of work, use of AI, evidence and activities.'));
$ex = 'LIT-IA1-DOC44-GEN25-V1';
?>
<div class="container page">
  <div class="narrow-text">
    <h1><?= h(t('nav_code')) ?></h1>
    <p class="lead"><?= h(tr('Dice qué tipo de obra es, si se usó IA y, si quieres, en qué actividades, cuánto y si hay evidencias.', 'It says what type of work it is, whether AI was used and, optionally, in which activities, how much, and whether there is evidence.')) ?></p>
    <div class="anatomy anatomy-5" aria-label="<?= h(t('example')) ?>: <?= $ex ?>">
      <div><span class="mono">LIT</span><p><?= h(tr('Tipo de obra', 'Type of work')) ?></p></div>
      <div><span class="mono">IA1</span><p><?= h(tr('Se usó IA', 'AI was used')) ?><br><span class="muted"><?= h(tr('IA0: no se usó', 'IA0: not used')) ?></span></p></div>
      <div><span class="mono">DOC44</span><p><?= h(tr('Actividad + grado + alcance (opcional)', 'Activity + degree + scope (optional)')) ?></p></div>
      <div><span class="mono">GEN25</span><p><?= h(tr('Otra actividad (opcional)', 'Another activity (optional)')) ?></p></div>
      <div><span class="mono">V1</span><p><?= h(t('evidence_title')) ?> <?= h(tr('(opcional)', '(optional)')) ?><br><span class="muted"><?= h(tr('V0: no hay', 'V0: none')) ?></span></p></div>
    </div>
    <ul class="rules-list muted">
      <li><?= tr('El código mínimo es el tipo de obra y el uso de IA: <span class="mono">LIT-IA0</span> o <span class="mono">LIT-IA1</span>.', 'The shortest code is the type of work and the use of AI: <span class="mono">LIT-IA0</span> or <span class="mono">LIT-IA1</span>.') ?></li>
      <li><?= tr('Cada actividad lleva siempre dos cifras: el grado de uso (0 a 5) y el alcance (0 a 4). Con los grados 0 y 1 la segunda cifra es 0: <span class="mono">DOC10</span>.', 'Each activity always has two digits: degree of use (0 to 5) and scope (0 to 4). With degrees 0 and 1 the second digit is 0: <span class="mono">DOC10</span>.') ?></li>
      <li><?= tr('Cada código tiene su propia página: <a class="mono" href="' . h(u('oride.php?' . $ex)) . '">oride.php?' . $ex . '</a>', 'Each code has its own page: <a class="mono" href="' . h(u('oride.php?' . $ex)) . '">oride.php?' . $ex . '</a>') ?></li>
    </ul>
    <?= honor_note() ?>
  </div>

  <form class="card decoder" method="get" action="<?= u('oride.php') ?>">
    <?= lang_field() ?>
    <label for="c"><?= h(tr('Escribe un código para leerlo', 'Type a code to read it')) ?></label>
    <div class="search-row"><input id="c" name="c" class="mono" maxlength="200" placeholder="<?= $ex ?>" autocomplete="off" spellcheck="false"><button class="btn btn-primary"><?= h(tr('Leer código', 'Read code')) ?></button></div>
  </form>

  <div class="grid-2 spaced">
    <section class="card">
      <h2><?= h(tr('Grado de uso de la IA', 'Degree of AI use')) ?></h2>
      <p class="muted small"><?= h(tr('Primera cifra de cada actividad.', 'First digit of each activity.')) ?></p>
      <dl class="values"><?php foreach (levels() as $n => $text): ?><div><dt class="mono"><?= $n ?></dt><dd><?= h($text) ?></dd></div><?php endforeach; ?></dl>
    </section>
    <section class="card">
      <h2><?= h(tr('Alcance', 'Scope')) ?></h2>
      <p class="muted small"><?= h(tr('Segunda cifra, a partir del grado 2: qué parte de la obra está afectada.', 'Second digit, from degree 2: what share of the work is affected.')) ?></p>
      <dl class="values"><?php foreach (scope_labels() as $n => $text): ?><div><dt class="mono"><?= $n ?></dt><dd><?= h($text) ?></dd></div><?php endforeach; ?></dl>
    </section>
  </div>

  <h2 class="spaced"><?= h(tr('Tipos de obra y actividades', 'Types of work and activities')) ?></h2>
  <div class="area-grid">
    <?php foreach (WORK_TYPES as $type => $_): ?>
      <section class="card area-card">
        <h3><span class="dim-tag"><?= $type ?></span><?= h(type_name($type)) ?></h3>
        <dl class="values">
          <?php foreach (acts($type) as $act => [$name, $desc]): ?><div><dt class="mono"><?= $act ?></dt><dd><b><?= h($name) ?></b><br><span class="muted small"><?= h($desc) ?></span></dd></div><?php endforeach; ?>
        </dl>
      </section>
    <?php endforeach; ?>
  </div>

  <section class="card spaced narrow-text">
    <h2><?= h(t('evidence_title')) ?></h2>
    <dl class="values">
      <div><dt class="mono">V0</dt><dd><?= h(STR[lang()]['evidence'][0]) ?></dd></div>
      <div><dt class="mono">V1</dt><dd><?= h(STR[lang()]['evidence'][1]) ?> <?= h(t('evidence_note')) ?></dd></div>
    </dl>
  </section>
</div>
<?php page_end();
