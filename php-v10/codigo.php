<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_code'), tr('Cómo se lee el Código ORIDE: tipo de obra, uso de IA, evidencias y actividades.', 'How to read the ORIDE Code: type of work, use of AI, evidence and activities.'));
$ex = 'LI-IGB-EV1-DOC44-GEN24';
?>
<div class="container page">
  <div class="narrow-text">
    <?= breadcrumb([['documentacion.php', t('nav_docs')], [null, t('nav_code')]]) ?>
    <h1><?= h(t('nav_code')) ?></h1>
    <p class="lead"><?= h(tr('Dos líneas: primero qué obra es, si se usó IA y si hay evidencias; después, si quieres, en qué actividades y cuánto.', 'Two lines: first what the work is, whether AI was used and whether there is evidence; then, optionally, in which activities and how much.')) ?></p>
    <p class="formula code-example">LI-IGB-EV1<br>DOC44-GEN24</p>
    <div class="anatomy" aria-label="<?= h(t('example')) ?>">
      <div><span class="mono">LI</span><p><?= h(tr('Tipo de obra', 'Type of work')) ?></p></div>
      <div><span class="mono">IGB</span><p><?= h(tr('Se usó IA; categoría del Índice Global: B — Bajo', 'AI was used; Global Index category: B — Low')) ?><br><span class="muted"><?= h(tr('IA0: no se usó', 'IA0: not used')) ?></span></p></div>
      <div><span class="mono">EV1</span><p><?= h(t('evidence_title')) ?><br><span class="muted"><?= h(tr('EV0: no hay', 'EV0: none')) ?></span></p></div>
      <div><span class="mono">DOC44</span><p><?= h(tr('Segunda línea: actividad + grado + alcance (opcional)', 'Second line: activity + degree + scope (optional)')) ?></p></div>
    </div>
    <ul class="rules-list muted">
      <li><?= tr('La primera línea siempre tiene tres partes: <span class="mono">LI-IA0-EV0</span>, <span class="mono">LI-IGB-EV1</span>…', 'The first line always has three parts: <span class="mono">LI-IA0-EV0</span>, <span class="mono">LI-IGB-EV1</span>…') ?></li>
      <li><?= tr('El segundo término es <span class="mono">IA0</span> si no se usó IA. Si se usó, es la categoría del Índice Global, de <span class="mono">IGA</span> (muy bajo) a <span class="mono">IGF</span> (extremo), que se calcula sola a partir de las actividades: no se elige. Sin actividades (o con todas a 0) queda <span class="mono">IA1</span>: se usó IA, sin categoría. <span class="mono">IA0</span> nunca equivale a <span class="mono">IGA</span>.', 'The second term is <span class="mono">IA0</span> if no AI was used. If it was, it is the Global Index category, from <span class="mono">IGA</span> (very low) to <span class="mono">IGF</span> (extreme), calculated automatically from the activities: it is not chosen. Without activities (or with all at 0) it stays <span class="mono">IA1</span>: AI used, no category. <span class="mono">IA0</span> never equals <span class="mono">IGA</span>.') ?></li>
      <li><?= tr('Los códigos anteriores a las categorías, con <span class="mono">IA1</span> y actividades (<span class="mono">LI-IA1-EV1-GEN24</span>), siguen siendo válidos: conservan IA1 y la categoría se muestra como información derivada.', 'Codes from before categories, with <span class="mono">IA1</span> and activities (<span class="mono">LI-IA1-EV1-GEN24</span>), are still valid: they keep IA1 and the category is shown as derived information.') ?></li>
      <li><?= tr('La segunda línea solo existe si se usó IA. Cada actividad lleva dos cifras: el grado de uso (0 a 5) y el alcance (0 a 4). Con los grados 0 y 1 la segunda cifra es 0: <span class="mono">DOC10</span>.', 'The second line only exists if AI was used. Each activity has two digits: degree of use (0 to 5) and scope (0 to 4). With degrees 0 and 1 the second digit is 0: <span class="mono">DOC10</span>.') ?></li>
      <li><?= tr('En un enlace se escribe todo seguido: <a class="mono" href="' . h(u('oride.php?' . $ex)) . '">oride.php?' . $ex . '</a>. Se siguen entendiendo los códigos con el formato anterior.', 'In a link everything goes on one line: <a class="mono" href="' . h(u('oride.php?' . $ex)) . '">oride.php?' . $ex . '</a>. Codes in the previous format are still understood.') ?></li>
      <li><?= tr('La categoría procede del <a href="' . h(u('indice.php')) . '">Índice Global ORIDE</a>, una estimación de 0 a 100 clasificada en seis tramos (A–F).', 'The category comes from the <a href="' . h(u('indice.php')) . '">ORIDE Global Index</a>, an estimate from 0 to 100 classified into six bands (A–F).') ?></li>
    </ul>
    <?= honor_note() ?>
  </div>

  <form class="card decoder" method="get" action="<?= u('oride.php') ?>">
    <?= lang_field() ?>
    <label for="c"><?= h(tr('Escribe un código para leerlo', 'Type a code to read it')) ?></label>
    <div class="search-row"><input id="c" name="c" class="mono" maxlength="200" placeholder="LI-IGB-EV1-GEN24" autocomplete="off" spellcheck="false"><button class="btn btn-primary"><?= h(tr('Leer código', 'Read code')) ?></button></div>
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
        <?php if (type_desc($type) !== ''): ?><p class="muted small"><?= h(type_desc($type)) ?></p><?php endif; ?>
        <dl class="values">
          <?php foreach (acts($type) as $act => [$name, $desc]): ?><div><dt class="mono"><?= $act ?></dt><dd><b><?= h($name) ?></b><br><span class="muted small"><?= h($desc) ?></span></dd></div><?php endforeach; ?>
        </dl>
      </section>
    <?php endforeach; ?>
  </div>

  <section class="card spaced narrow-text">
    <h2><?= h(t('evidence_title')) ?></h2>
    <dl class="values">
      <div><dt class="mono">EV0</dt><dd><?= h(STR[lang()]['evidence'][0]) ?></dd></div>
      <div><dt class="mono">EV1</dt><dd><?= h(STR[lang()]['evidence'][1]) ?> <?= h(t('evidence_note')) ?></dd></div>
    </dl>
  </section>
</div>
<?php page_end();
