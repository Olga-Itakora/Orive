<?php
declare(strict_types=1);
define('ORIDE_BUILDER', true);
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/builder.php';

// Sin JavaScript el formulario se envía a esta misma página y el código se calcula aquí. No se guarda nada.
$in = oride_input_from_get();
$r = oride_compute($in);

page_start(t('nav_create'), tr('Elige el tipo de obra, indica si usaste IA y obtén al momento tu Código ORIDE.', 'Choose the type of work, say whether you used AI and get your ORIDE Code instantly.'));
?>
<div class="container create">
  <div class="create-head">
    <h1><?= h(t('nav_create')) ?></h1>
    <p class="muted"><?= h(tr('Con el tipo de obra y el uso de IA ya tienes tu código. Lo demás es opcional. No se guarda nada.', 'The type of work and the use of AI are enough for your code. The rest is optional. Nothing is stored.')) ?></p>
  </div>
  <div class="create-grid">
    <?= builder_form('author', $in, 'crear.php') ?>
    <div class="create-aside"><?= code_box($r, ['live' => true]) ?></div>
  </div>
</div>
<a class="code-chip" href="#declaracion" data-chip hidden><?= h(t('chip')) ?> <span class="mono" data-chip-code>—</span></a>
<?php page_end();
