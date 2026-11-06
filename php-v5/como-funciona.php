<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_how'), tr('Responde unas preguntas, obtén tu Código ORIDE y compártelo.', 'Answer a few questions, get your ORIDE Code and share it.'));
$steps = en() ? [
    ['Choose the type of work', 'Scientific research, literature, music, photography, illustration, design, audiovisual, software or translation.'],
    ['Did you use AI?', 'With these two answers you already have a code: <span class="mono">LIT-IA0</span> or <span class="mono">LIT-IA1</span>.'],
    ['Evidence (optional)', 'Say whether you keep evidence. If you do, add a way to contact you or a link.'],
    ['Activities (optional)', 'Choose the activities where you used AI, say how you used the results (0 to 5) and, from degree 2, how much of the work they affect.'],
    ['Copy and share', 'Copy the code, the full declaration or a link that explains it, and add them to the credits, your website or a submission.'],
] : [
    ['Elige el tipo de obra', 'Investigación científica, literatura, música, fotografía, ilustración, diseño, audiovisual, software o traducción.'],
    ['¿Usaste IA?', 'Con estas dos respuestas ya tienes código: <span class="mono">LIT-IA0</span> o <span class="mono">LIT-IA1</span>.'],
    ['Evidencias (opcional)', 'Indica si conservas pruebas. Si las tienes, añade cómo contactar contigo o un enlace.'],
    ['Actividades (opcional)', 'Marca las actividades en las que usaste IA, di cómo usaste los resultados (0 a 5) y, desde el grado 2, qué parte de la obra afectan.'],
    ['Copia y comparte', 'Copia el código, la declaración completa o un enlace que lo explica, y añádelos a los créditos, a tu web o a una entrega.'],
];
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_how')) ?></h1>
  <ol class="how">
    <?php foreach ($steps as [$title, $text]): ?><li><h2><?= h($title) ?></h2><p class="muted"><?= $text ?></p></li><?php endforeach; ?>
  </ol>
  <div class="card callout"><p><b><?= h(tr('Sin registro y sin cookies.', 'No sign-up and no cookies.')) ?></b> <?= h(tr('Tus respuestas no se guardan.', 'Your answers are not stored.')) ?></p></div>
  <?= honor_note() ?>
  <p class="actions"><a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a><a class="btn btn-secondary btn-lg" href="<?= u('codigo.php') ?>"><?= h(tr('Leer un código', 'Read a code')) ?></a></p>
</div>
<?php page_end();
