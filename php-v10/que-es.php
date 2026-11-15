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
    <p>When you fill in the declaration, ORIDE creates two things: the ORIDE Global Index and the ORIDE Code.</p>
  <?php else: ?>
    <p class="lead">ORIDE es una declaración del uso de herramientas de IA en la autoría de obras artísticas, científicas y técnicas, basada en el honor, la honestidad y la ética personal de la persona autora.</p>
    <div class="card honor"><p>No hay un sistema de verificación ni una certificación detrás.</p></div>
    <h2>Dos elementos</h2>
    <p>Al rellenar la declaración, ORIDE crea dos cosas: el Índice Global ORIDE y el Código ORIDE.</p>
  <?php endif; ?>
  <section class="spaced" aria-labelledby="ig-intro">
    <h3 id="ig-intro"><?= h(t('ig_title')) ?></h3>
    <p><?= h(tr('Estima de 0 a 100 cuánto aportó la IA a la obra y lo sitúa en seis tramos, de «muy bajo» a «extremo». Generar contenido pesa mucho más que documentarse o corregir. Es una metodología experimental, abierta a revisión.', 'It estimates from 0 to 100 how much AI contributed to the work and places it in six bands, from “very low” to “extreme”. Generating content weighs much more than research or proofreading. It is an experimental methodology, open to review.')) ?></p>
    <div class="ig-example"><?= code_box($example, ['reader' => true, 'actions' => false]) ?></div>
    <p class="muted small"><?= h(tr('Ejemplo: una novela en la que se usó la IA para generar ideas y para la revisión de formato.', 'Example: a novel in which AI was used to generate ideas and for form review.')) ?></p>
    <a href="<?= u('indice.php') ?>"><?= h(t('ig_how')) ?></a>
  </section>
  <section class="spaced" aria-labelledby="code-intro">
    <h3 id="code-intro"><?= h(t('code_label')) ?></h3>
    <p><?= h(tr('Es una forma estructurada de declarar el uso de la IA, pensada para que la lean sistemas automatizados: indica el tipo de obra, el uso de IA (con el tramo del índice), si hay evidencias y, si quieres, en qué actividades y cuánto.', 'It is a structured way of declaring the use of AI, intended to be read by automated systems: it states the type of work, the use of AI (with the index band), whether there is evidence and, optionally, in which activities and how much.')) ?></p>
    <p class="formula code-example"><?= implode('<br>', array_map('h', $example['lines'])) ?></p>
    <a href="<?= u('codigo.php') ?>"><?= h(tr('Cómo se lee el código', 'How to read the code')) ?></a>
  </section>
  <p class="actions spaced"><a class="btn btn-primary btn-lg" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a><a class="btn btn-secondary btn-lg" href="<?= u('como-funciona.php') ?>"><?= h(t('nav_how')) ?></a></p>
</div>
<?php page_end();
