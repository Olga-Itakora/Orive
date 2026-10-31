<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start('Cómo funciona', 'Responde unas preguntas, obtén tu Código ORIDE y compártelo.');
?>
<div class="container narrow-text page">
  <h1>Cómo funciona</h1>
  <ol class="how">
    <li><h2>¿Usaste IA?</h2><p class="muted">Si no, tu código es <span class="mono">IA0</span>. Ya está.</p></li>
    <li><h2>Señala las áreas</h2><p class="muted">Documentación, ideas, estructura, contenidos, revisión del contenido, revisión de forma o traducción.</p></li>
    <li><h2>Di cómo usaste los resultados</h2><p class="muted">Para cada área, de 0 a 5. Si los incorporaste a la obra (3, 4 o 5), indica también qué parte de la obra afectan.</p></li>
    <li><h2>Evidencias</h2><p class="muted">Indica si conservas pruebas. Si las tienes, añade cómo contactar contigo o un enlace.</p></li>
    <li><h2>Copia y comparte</h2><p class="muted">Copia el código o la declaración completa y añádelos a los créditos, a tu web o a una entrega editorial.</p></li>
  </ol>
  <div class="card callout"><p><b>Sin registro y sin cookies.</b> Tus respuestas no se guardan.</p></div>
  <p class="actions"><a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>">Crea tu declaración</a><a class="btn btn-secondary btn-lg" href="<?= u('codigo.php') ?>">Leer un código</a></p>
</div>
<?php page_end();
