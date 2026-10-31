<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start('Cómo funciona', 'Responde unas preguntas, obtén tu Código ORIDE y compártelo.');
?>
<div class="container narrow-text page">
  <h1>Cómo funciona</h1>
  <ol class="how">
    <li><h2>Responde</h2><p class="muted">¿Usaste IA? Si la respuesta es sí, indica para qué (A), qué se incorporó a la obra (B) y qué hiciste tú con ello (C).</p></li>
    <li><h2>Obtén tu código</h2><p class="muted">Se genera al momento. Si marcas varias opciones, se combinan: A2 y A4 se escriben <span class="mono">A24</span>.</p></li>
    <li><h2>Compártelo</h2><p class="muted">Copia el código o la declaración completa y añádelos a los créditos, a tu web o a una entrega editorial.</p></li>
  </ol>
  <div class="card callout">
    <p><b>Sin registro y sin cookies.</b> Tus respuestas no se guardan.</p>
  </div>
  <p class="actions"><a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>">Crea tu declaración</a><a class="btn btn-secondary btn-lg" href="<?= u('codigo.php') ?>">Leer un código</a></p>
</div>
<?php page_end();
