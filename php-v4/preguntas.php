<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$faq = en() ? [
    'What is the ORIDE Code?' => 'A short code that describes how artificial intelligence was used in a work: in which areas, how its results were used, how much of the work they affect and whether there is evidence.',
    'How do I read IA1-I2-C43-V1?' => 'AI was used (IA1). Idea generation with type of use 2 (I2). Content generation with type of use 4 and scope 3 (C43). Evidence is kept (V1).',
    'What do the numbers in each area mean?' => 'The first digit (0 to 5) says how you used the results: from 1, only consulting them, to 5, incorporating them unchanged. The second (0 to 4), only with 3, 4 and 5, says how much of the work they affect.',
    'Does the code certify anything?' => STR['en']['honor'],
    'Do I need to sign up?' => 'No. You answer, copy your code and you’re done.',
    'Are my answers stored?' => 'No. The code is calculated in your browser and we don’t use cookies.',
    'What if I say I keep evidence?' => 'Your code ends in V1. Add a way to contact you, or a link to that evidence, next to it.',
    'Does it help me comply with the EU AI Act?' => 'It does not replace any legal obligation. See the regulatory framework if you publish AI-generated or manipulated content.',
    'Where do I put the code?' => 'In the credits, the copyright page, your website or the work’s details. You can copy just the code or the full declaration.',
] : [
    '¿Qué es el Código ORIDE?' => 'Un código breve que describe cómo se usó la inteligencia artificial en una obra: en qué áreas, cómo se usaron sus resultados, qué parte de la obra afectan y si hay evidencias.',
    '¿Cómo se lee IA1-I2-C43-V1?' => 'Se usó IA (IA1). Generación de ideas con forma de uso 2 (I2). Generación de contenidos con forma de uso 4 y alcance 3 (C43). Se conservan evidencias (V1).',
    '¿Qué significan los números de cada área?' => 'La primera cifra (0 a 5) dice cómo usaste los resultados: de 1, solo consultarlos, a 5, incorporarlos sin cambios. La segunda (0 a 4), solo con 3, 4 y 5, dice qué parte de la obra afectan.',
    '¿El código certifica algo?' => STR['es']['honor'],
    '¿Tengo que registrarme?' => 'No. Respondes, copias tu código y listo.',
    '¿Se guardan mis respuestas?' => 'No. El código se calcula en tu navegador y no usamos cookies.',
    '¿Qué pasa si marco que conservo evidencias?' => 'Tu código lleva V1. Añade junto a él un medio para contactar contigo o un enlace a esas evidencias.',
    '¿Sirve para cumplir el Reglamento europeo de IA?' => 'No sustituye ninguna obligación legal. Consulta el marco normativo si publicas contenido generado o manipulado con IA.',
    '¿Dónde pongo el código?' => 'En los créditos, la página de derechos, tu web o la ficha de la obra. Puedes copiar solo el código o la declaración completa.',
];
page_start(t('nav_faq'), tr('Respuestas breves sobre el Código ORIDE.', 'Short answers about the ORIDE Code.'));
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_faq')) ?></h1>
  <?php foreach ($faq as $q => $a): ?>
    <details class="faq"><summary><?= h($q) ?></summary><p><?= h($a) ?></p></details>
  <?php endforeach; ?>
  <p class="muted small spaced"><?= h(tr('¿Otra pregunta? Escríbenos a', 'Another question? Write to')) ?> <a href="mailto:info@itakora.com">info@itakora.com</a>.</p>
</div>
<?php page_end();
