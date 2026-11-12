<?php
declare(strict_types=1);
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
    <p class="muted"><?= h(tr('Con el tipo de obra, el uso de IA y las evidencias ya tienes tu código. Las actividades son opcionales. No se guarda nada.', 'The type of work, the use of AI and evidence are enough for your code. Activities are optional. Nothing is stored.')) ?></p>
  </div>
  <div class="create-grid">
    <?= builder_form('author', $in, 'crear.php') ?>
    <div class="create-aside"><?= code_box($r, ['live' => true, 'extra' => $in]) ?>
      <p class="note"><?= h(t('honor')) ?> <a href="<?= u('codigo.php') ?>"><?= h(t('what_means')) ?></a></p></div>
  </div>
</div>
<a class="mini-bar" href="#declaracion" data-chip<?= $r['status'] === 'empty' ? ' hidden' : '' ?>><span data-chip-text><?= h(decl_view($r, ig_from_result($r), false)['title']) ?></span><b><?= h(tr('Ver declaración', 'See declaration')) ?> ↓</b></a>
<?php page_end();
