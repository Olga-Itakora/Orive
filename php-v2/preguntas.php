<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$faq = [
    '¿Qué es el Código ORIDE?' => 'Un código breve que describe cómo se usó la inteligencia artificial en una obra: para qué (A), qué se incorporó (B) y qué hizo la persona con ello (C).',
    '¿Tengo que registrarme?' => 'No. Respondes, copias tu código y listo.',
    '¿Se guardan mis respuestas?' => 'No. El código se calcula en tu navegador y no usamos cookies.',
    '¿Un número más alto significa más IA?' => 'No. Los números son categorías. A5 no es «más» que A3, ni C5 «más autoría» que C4.',
    'Usé la IA para varias cosas. ¿Qué marco?' => 'Todas las que correspondan. El código las combina: A2 y A4 se escriben A24.',
    '¿El código certifica algo?' => 'No. Es una declaración de quien lo usa. ORIDE no verifica lo declarado ni mide la originalidad.',
    '¿Sirve para cumplir el Reglamento europeo de IA?' => 'No sustituye ninguna obligación legal. Consulta el marco normativo si publicas contenido generado o manipulado con IA.',
    '¿Dónde pongo el código?' => 'En los créditos, la página de derechos, tu web o la ficha de la obra. Puedes copiar solo el código o la declaración completa.',
    '¿Por qué no hay dimensión D?' => 'La dimensión D (documentación y trazabilidad) llegará en una próxima versión.',
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
