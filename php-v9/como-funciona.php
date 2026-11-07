<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_how'), tr('Responde unas preguntas, obtén tu Código ORIDE y compártelo.', 'Answer a few questions, get your ORIDE Code and share it.'));
$steps = en() ? [
    ['Choose the type of work', 'Scientific research, literature, music, photography, illustration, design, audiovisual, software, translation or academic work.'],
    ['Did you use AI?', 'Say whether artificial intelligence was used.'],
    ['Evidence', 'Say whether you keep evidence. With these three answers you have your code: <span class="mono">LI-IA1-EV1</span>. If you keep evidence, add a way to contact you or a link.'],
    ['Activities (optional)', 'They go on a second line. Choose the activities where you used AI, say how you used the results and, from degree 2, how much of the work they affect. They are used to calculate the ORIDE Global Index, whose category (A to F) replaces IA1 in the code: <span class="mono">LI-IGB-EV1</span>.'],
    ['Copy and share', 'Copy the code, the full declaration or a link that explains it, and add them to the credits, your website or a submission.'],
] : [
    ['Elige el tipo de obra', 'Investigación científica, literatura, música, fotografía, ilustración, diseño, audiovisual, software, traducción o trabajos académicos.'],
    ['¿Usaste IA?', 'Indica si se utilizó inteligencia artificial.'],
    ['Evidencias', 'Indica si conservas pruebas. Con estas tres respuestas ya tienes código: <span class="mono">LI-IA1-EV1</span>. Si conservas pruebas, añade cómo contactar contigo o un enlace.'],
    ['Actividades (opcional)', 'Van en una segunda línea. Marca las actividades en las que usaste IA, di cómo usaste los resultados y, desde el grado 2, qué parte de la obra afectan. Sirven para calcular el Índice Global ORIDE, cuya categoría (de A a F) sustituye a IA1 en el código: <span class="mono">LI-IGB-EV1</span>.'],
    ['Copia y comparte', 'Copia el código, la declaración completa o un enlace que lo explica, y añádelos a los créditos, a tu web o a una entrega.'],
];
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_how')) ?></h1>
  <p class="lead"><?= tr('Para generar el Código ORIDE y calcular el Índice Global ORIDE, rellena el <a href="' . h(u('crear.php')) . '">formulario guiado</a>.', 'To generate the ORIDE Code and calculate the ORIDE Global Index, fill in the <a href="' . h(u('crear.php')) . '">guided form</a>.') ?></p>
  <ol class="how">
    <?php foreach ($steps as [$title, $text]): ?><li><h2><?= h($title) ?></h2><p class="muted"><?= $text ?></p></li><?php endforeach; ?>
  </ol>
  <div class="card callout"><p><b><?= h(tr('Sin registro y sin cookies.', 'No sign-up and no cookies.')) ?></b> <?= h(tr('Tus respuestas no se guardan.', 'Your answers are not stored.')) ?></p></div>
  <?= honor_note() ?>
  <p class="actions"><a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a><a class="btn btn-secondary btn-lg" href="<?= u('codigo.php') ?>"><?= h(tr('Leer un código', 'Read a code')) ?></a></p>
</div>
<?php page_end();
