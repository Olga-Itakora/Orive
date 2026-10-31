<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start('');
$example = oride_compute(['ia' => 'si', 'areas' => ['I', 'C', 'F'], 'u' => ['I' => '2', 'C' => '4', 'F' => '5'], 's' => ['C' => '1', 'F' => '4'], 'ev' => 'si']);
?>
<section class="hero">
  <div class="container grid-main-aside">
    <div>
      <p class="eyebrow">ORIDE · <?= h(t('descriptor')) ?></p>
      <?php if (en()): ?>
        <h1>Every creation has a process.<br><span class="hero-accent">Make it visible.</span></h1>
      <?php else: ?>
        <h1>Toda creación tiene un proceso.<br><span class="hero-accent">Hazlo visible.</span></h1>
      <?php endif; ?>
      <p class="lead"><?= h(t('site_description')) ?></p>
      <div class="actions">
        <a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a>
        <a class="btn btn-secondary btn-lg" href="<?= u('codigo.php') ?>"><?= h(tr('Qué significa el código', 'What the code means')) ?></a>
      </div>
    </div>
    <?= code_box($example, false, t('example')) ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2 class="h-section"><?= h(tr('Qué dice el código', 'What the code says')) ?></h2>
    <ol class="steps-home">
      <li class="card"><span class="num">1</span><h3><?= h(tr('Para qué', 'What for')) ?></h3><p class="muted small"><?= h(tr('Documentación, ideas, estructura, contenidos, revisión, forma o traducción.', 'Research, ideas, structure, content, review, form or translation.')) ?></p></li>
      <li class="card"><span class="num">2</span><h3><?= h(tr('Cómo y cuánto', 'How and how much')) ?></h3><p class="muted small"><?= h(tr('Si solo consultaste, si te inspiraste o si incorporaste los resultados, y qué parte de la obra afectan.', 'Whether you only consulted, drew inspiration or incorporated the results, and how much of the work they affect.')) ?></p></li>
      <li class="card"><span class="num">3</span><h3><?= h(tr('Con qué pruebas', 'With what evidence')) ?></h3><p class="muted small"><?= h(tr('Si conservas evidencias que lo documenten.', 'Whether you keep evidence that documents it.')) ?></p></li>
    </ol>
  </div>
</section>

<section class="band">
  <div class="container"><div class="narrow-text">
    <h2 class="h-section"><?= h(tr('Una declaración de honor', 'A declaration on your honour')) ?></h2>
    <p class="muted"><?= h(t('honor')) ?></p>
    <p><a class="btn btn-primary" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a></p>
  </div></div>
</section>
<?php page_end();
