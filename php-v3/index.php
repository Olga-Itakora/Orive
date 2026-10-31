<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start('Inicio');
$example = oride_compute(['ia' => 'si', 'areas' => ['I', 'C', 'F'], 'u' => ['I' => '2', 'C' => '4', 'F' => '5'], 's' => ['C' => '1', 'F' => '4'], 'ev' => 'si']);
?>
<section class="hero">
  <div class="container grid-main-aside">
    <div>
      <p class="eyebrow">ORIDE · Originalidad Declarada</p>
      <h1>Toda creación tiene un proceso.<br><span class="hero-accent">Hazlo visible.</span></h1>
      <p class="lead">Declara cómo has usado la inteligencia artificial en tu obra con un código breve y fácil de entender.</p>
      <div class="actions">
        <a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>">Crea tu declaración</a>
        <a class="btn btn-secondary btn-lg" href="<?= u('codigo.php') ?>">Qué significa el código</a>
      </div>
    </div>
    <?= code_box($example, false, 'Ejemplo') ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2 class="h-section">Qué dice el código</h2>
    <ol class="steps-home">
      <li class="card"><span class="num">1</span><h3>Para qué</h3><p class="muted small">Documentación, ideas, estructura, contenidos, revisión, forma o traducción.</p></li>
      <li class="card"><span class="num">2</span><h3>Cómo y cuánto</h3><p class="muted small">Si solo consultaste, si te inspiraste o si incorporaste los resultados, y qué parte de la obra afectan.</p></li>
      <li class="card"><span class="num">3</span><h3>Con qué pruebas</h3><p class="muted small">Si conservas evidencias que lo documenten.</p></li>
    </ol>
  </div>
</section>

<section class="band">
  <div class="container"><div class="narrow-text">
    <h2 class="h-section">Lo que el código no es</h2>
    <p class="muted">No es un porcentaje de autoría, ni una nota de originalidad, ni una certificación. Es una declaración de quien lo usa.</p>
    <p><a class="btn btn-primary" href="<?= u('crear.php') ?>">Crea tu declaración</a></p>
  </div></div>
</section>
<?php page_end();
