<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/builder.php';

$in = oride_input_from_get();
$r = oride_compute($in);
page_start(t('nav_inst'), tr('Cómo pueden usar el Código ORIDE centros educativos, de investigación, editoriales, concursos y estudios para fijar un uso máximo de la IA.', 'How schools, research centres, publishers, competitions and studios can use the ORIDE Code to set a maximum use of AI.'));
?>
<div class="container create">
  <?= inst_header('instituciones.php') ?>
  <div class="create-grid">
    <?= builder_form('inst', $in, 'instituciones.php') ?>
    <div class="create-aside"><?= code_box($r, ['live' => true, 'title' => t('req_title'), 'intro' => inst_intro(), 'verify' => true]) ?></div>
  </div>
</div>
<a class="code-chip" href="#declaracion" data-chip hidden><?= h(t('ref_code')) ?>: <span class="mono" data-chip-code>—</span></a>
<?php page_end();
