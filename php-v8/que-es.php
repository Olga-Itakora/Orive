<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_what'), tr('ORIDE es una declaración del uso de herramientas de IA en la autoría de obras artísticas, científicas y técnicas.', 'ORIDE is a declaration of the use of AI tools in the authorship of artistic, scientific and technical works.'));
$example = oride_compute(['type' => 'LI', 'ia' => 'si', 'ev' => 'si', 'acts' => ['IDE', 'FOR'], 'u' => ['IDE' => '2', 'FOR' => '5'], 's' => ['IDE' => '1', 'FOR' => '4']]);
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_what')) ?></h1>
  <?php if (en()): ?>
    <p class="lead">ORIDE is a declaration of the use of AI tools in the authorship of artistic, scientific and technical works, based on the honour, honesty and personal ethics of the author.</p>
    <div class="card honor"><p>There is no verification system or certification behind it.</p></div>
    <h2>Two elements</h2>
    <p>When you fill in the declaration, ORIDE creates two things:</p>
  <?php else: ?>
    <p class="lead">ORIDE es una declaración del uso de herramientas de IA en la autoría de obras artísticas, científicas y técnicas, basada en el honor, la honestidad y la ética personal de la persona autora.</p>
    <div class="card honor"><p>No hay un sistema de verificación ni una certificación detrás.</p></div>
    <h2>Dos elementos</h2>
    <p>Al rellenar la declaración, ORIDE crea dos cosas:</p>
  <?php endif; ?>
  <div class="two-elements">
    <section class="card">
      <h3><?= h(t('code_label')) ?></h3>
      <p class="formula code-example"><?= implode('<br>', array_map('h', $example['lines'])) ?></p>
      <p class="muted"><?= h(tr('Describe cómo se usó la IA: qué tipo de obra es, si se usó IA, si hay evidencias y, si quieres, en qué actividades y cuánto.', 'Describes how AI was used: what type of work it is, whether AI was used, whether there is evidence and, optionally, in which activities and how much.')) ?></p>
      <a href="<?= u('codigo.php') ?>"><?= h(tr('Cómo se lee el código', 'How to read the code')) ?></a>
    </section>
    <section class="card ig-card">
      <h3><?= h(t('ig_title')) ?></h3>
      <p class="ig-sample"><?= h(ig_percent(ig_from_result($example))) ?></p>
      <p class="muted"><?= h(tr('Estima de 0 a 100 cuánto contribuyó la IA a la obra. Generar contenido pesa mucho más que documentarse o corregir.', 'Estimates from 0 to 100 how much AI contributed to the work. Generating content weighs much more than research or proofreading.')) ?></p>
      <a href="<?= u('indice.php') ?>"><?= h(t('ig_how')) ?></a>
    </section>
  </div>
  <p class="actions spaced"><a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a><a class="btn btn-secondary btn-lg" href="<?= u('como-funciona.php') ?>"><?= h(t('nav_how')) ?></a></p>
</div>
<?php page_end();
