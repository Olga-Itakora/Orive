<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$faq = [
    '¿Qué es el Código ORIDE?' => 'Un código breve que describe cómo se usó la inteligencia artificial en una obra: en qué áreas, cómo se usaron sus resultados, qué parte de la obra afectan y si hay evidencias.',
    '¿Cómo se lee IA1-I2-C43-V1?' => 'Se usó IA (IA1). Generación de ideas con forma de uso 2 (I2). Generación de contenidos con forma de uso 4 y alcance 3 (C43). Se conservan evidencias (V1).',
    '¿Qué significan los números de cada área?' => 'La primera cifra (0 a 5) dice cómo usaste los resultados: de 1, solo consultarlos, a 5, incorporarlos sin cambios. La segunda (0 a 4), solo con 3, 4 y 5, dice qué parte de la obra afectan.',
    '¿Tengo que registrarme?' => 'No. Respondes, copias tu código y listo.',
    '¿Se guardan mis respuestas?' => 'No. El código se calcula en tu navegador y no usamos cookies.',
    '¿Qué pasa si marco que conservo evidencias?' => 'Tu código lleva V1. Añade junto a él un medio para contactar contigo o un enlace a esas evidencias.',
    '¿El código certifica algo?' => 'No. Es una declaración de quien lo usa. ORIDE no verifica lo declarado ni mide la originalidad o la autoría.',
    '¿Sirve para cumplir el Reglamento europeo de IA?' => 'No sustituye ninguna obligación legal. Consulta el marco normativo si publicas contenido generado o manipulado con IA.',
    '¿Dónde pongo el código?' => 'En los créditos, la página de derechos, tu web o la ficha de la obra. Puedes copiar solo el código o la declaración completa.',
];
page_start('Preguntas frecuentes', 'Respuestas breves sobre el Código ORIDE.');
?>
<div class="container narrow-text page">
  <h1>Preguntas frecuentes</h1>
  <?php foreach ($faq as $q => $a): ?>
    <details class="faq"><summary><?= h($q) ?></summary><p><?= h($a) ?></p></details>
  <?php endforeach; ?>
  <p class="muted small spaced">¿Otra pregunta? Escríbenos a <a href="mailto:info@itakora.com">info@itakora.com</a>.</p>
</div>
<?php page_end();
