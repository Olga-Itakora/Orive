<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start('Inicio');
$example = oride_compute(['ia' => 'si', 'a' => ['A2', 'A4'], 'inc' => 'si', 'b' => ['B1', 'B2'], 'c' => ['B1' => 'C5', 'B2' => 'C1']]);
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
    <div class="code-card">
      <div class="inner">
        <p class="label">Ejemplo</p>
        <p class="big code-line"><?= code_segments($example) ?></p>
        <div class="dims"><?= explain_list($example['a'], $example['b'], $example['pairs'], true) ?></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2 class="h-section">Tres preguntas, un código</h2>
    <ol class="steps-home">
      <li class="card"><span class="num">A</span><h3>¿Para qué usaste la IA?</h3><p class="muted small">Consultar, idear, revisar, corregir o generar.</p></li>
      <li class="card"><span class="num">B</span><h3>¿Qué quedó en la obra?</h3><p class="muted small">Nada, ideas, cambios formales o contenido generado.</p></li>
      <li class="card"><span class="num">C</span><h3>¿Qué hiciste tú con ello?</h3><p class="muted small">Desde aceptarlo tal cual hasta crear a partir de una referencia.</p></li>
    </ol>
  </div>
</section>

<section class="band">
  <div class="container"><div class="narrow-text">
    <h2 class="h-section">Lo que el código no es</h2>
    <p class="muted">No es un porcentaje de autoría, ni una nota de originalidad, ni una certificación. Los números son categorías: A5 no es «más IA» que A3.</p>
    <p><a class="btn btn-primary" href="<?= u('crear.php') ?>">Crea tu declaración</a></p>
  </div></div>
</section>
<?php page_end();
