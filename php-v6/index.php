<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start('');
$example = oride_compute(['type' => 'LIT', 'ia' => 'si', 'acts' => ['IDE', 'FOR'], 'u' => ['IDE' => '2', 'FOR' => '5'], 's' => ['IDE' => '1', 'FOR' => '4'], 'ev' => 'si']);
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
    <?= code_box($example, ['title' => t('example')]) ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2 class="h-section"><?= h(tr('Qué dice el código', 'What the code says')) ?></h2>
    <ol class="steps-home">
      <li class="card"><span class="num">1</span><h3><?= h(tr('Qué obra y si hubo IA', 'What work, and whether AI was used')) ?></h3><p class="muted small"><?= h(tr('Literatura, ciencia, música, fotografía, ilustración, diseño, audiovisual, software o traducción. Con esto ya tienes tu código.', 'Literature, science, music, photography, illustration, design, audiovisual, software or translation. That is enough for your code.')) ?></p></li>
      <li class="card"><span class="num">2</span><h3><?= h(tr('Con qué pruebas', 'With what evidence')) ?></h3><p class="muted small"><?= h(tr('Si conservas evidencias que lo documenten (opcional).', 'Whether you keep evidence that documents it (optional).')) ?></p></li>
      <li class="card"><span class="num">3</span><h3><?= h(tr('En qué, cómo y cuánto', 'Where, how and how much')) ?></h3><p class="muted small"><?= h(tr('Las actividades concretas, el grado de uso y qué parte de la obra afectan (opcional).', 'Specific activities, the degree of use and how much of the work they affect (optional).')) ?></p></li>
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
