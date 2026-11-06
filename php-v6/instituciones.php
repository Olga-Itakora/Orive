<?php
declare(strict_types=1);
define('ORIDE_BUILDER', true);
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/builder.php';

$in = oride_input_from_get();
$r = oride_compute($in);
$who = en()
    ? ['Schools and universities', 'Research centres', 'Publishers', 'Literary and art competitions', 'Advertising agencies', 'Design studios', 'Freelancers and their clients', 'Calls, grants and awards']
    : ['Centros educativos', 'Centros de investigación', 'Editoriales', 'Concursos literarios y artísticos', 'Agencias de publicidad', 'Estudios de diseño', 'Freelancers y sus clientes', 'Convocatorias, becas y premios'];

page_start(t('nav_inst'), tr('Cómo pueden usar el Código ORIDE centros educativos, de investigación, editoriales, concursos y estudios para fijar un uso máximo de la IA.', 'How schools, research centres, publishers, competitions and studios can use the ORIDE Code to set a maximum use of AI.'));
?>
<div class="container create">
  <div class="create-head narrow-text">
    <h1><?= h(t('nav_inst')) ?></h1>
    <p class="lead"><?= h(tr('Fija con un código el uso máximo de IA que aceptas en las obras que recibes.', 'Use a code to set the maximum AI use you accept in the work you receive.')) ?></p>
    <p class="muted"><?= h(tr('Útil para bases de concursos, normas de entrega, encargos y contratos: el autor sabe exactamente qué se permite y lo declara con su propio código.', 'Useful for competition rules, submission guidelines, commissions and contracts: authors know exactly what is allowed and declare it with their own code.')) ?></p>
    <ul class="who-list"><?php foreach ($who as $w) echo '<li>' . h($w) . '</li>'; ?></ul>
  </div>
  <div class="create-grid">
    <?= builder_form('inst', $in, 'instituciones.php') ?>
    <div class="create-aside"><?= code_box($r, ['live' => true, 'title' => t('ref_code'), 'intro' => inst_intro()]) ?></div>
  </div>
</div>
<a class="code-chip" href="#declaracion" data-chip hidden><?= h(t('ref_code')) ?>: <span class="mono" data-chip-code>—</span></a>
<?php page_end();
