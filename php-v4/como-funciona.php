<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_how'), tr('Responde unas preguntas, obtén tu Código ORIDE y compártelo.', 'Answer a few questions, get your ORIDE Code and share it.'));
$steps = en() ? [
    ['Did you use AI?', 'If not, your code is <span class="mono">IA0</span>. That’s it.'],
    ['Select the areas', 'Documentation research, ideas, structure, content, content review, form review or translation.'],
    ['Say how you used the results', 'For each area, from 0 to 5. If you incorporated them into the work (3, 4 or 5), also say how much of the work they affect.'],
    ['Evidence', 'Say whether you keep evidence. If you do, add a way to contact you or a link.'],
    ['Copy and share', 'Copy the code or the full declaration and add them to the credits, your website or a submission to a publisher.'],
] : [
    ['¿Usaste IA?', 'Si no, tu código es <span class="mono">IA0</span>. Ya está.'],
    ['Señala las áreas', 'Documentación, ideas, estructura, contenidos, revisión del contenido, revisión de forma o traducción.'],
    ['Di cómo usaste los resultados', 'Para cada área, de 0 a 5. Si los incorporaste a la obra (3, 4 o 5), indica también qué parte de la obra afectan.'],
    ['Evidencias', 'Indica si conservas pruebas. Si las tienes, añade cómo contactar contigo o un enlace.'],
    ['Copia y comparte', 'Copia el código o la declaración completa y añádelos a los créditos, a tu web o a una entrega editorial.'],
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
