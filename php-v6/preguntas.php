<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$faq = en() ? [
    'What is the ORIDE Code?' => 'A short code that describes how artificial intelligence was used in a work: what type of work it is, whether AI was used and, optionally, in which activities, how much, and whether there is evidence.',
    'What do the two digits of each activity mean?' => 'The first (0 to 5) is how you used the AI results: from 0, not at all, to 5, incorporating them unchanged. The second (0 to 4) is how much of the work they affect, from less than 5% to 75–100%. With degrees 0 and 1 the second digit is always 0.',
    'Do I have to fill in the activities?' => 'No. The type of work and whether you used AI are enough. Evidence and activities add detail.',
    'Does the code certify anything?' => STR['en']['honor'],
    'Does the ORIDE Code mean I comply with Article 50 of the EU AI Act?' => 'No. The ORIDE Code helps you be transparent, but it is not the same as complying with Article 50 of Regulation (EU) 2024/1689. That article has its own obligations, such as labelling deep fakes or AI-generated text on matters of public interest. Check whether they apply to you; see the regulatory framework.',
    'Can institutions use it?' => 'Yes. On the Institutions page, schools, publishers, competitions or studios can set a reference code with the maximum use of AI they accept.',
    'Can I share a link to my code?' => 'Yes. Each code has its own page that explains it, for example oride.php?LIT-IA1-DOC44-V1. Use the “Copy link” button.',
    'Do I need to sign up? Are my answers stored?' => 'No to both. The code is calculated in your browser and we don’t use cookies.',
    'Who can use the ORIDE Code?' => STR['en']['trademark'],
] : [
    '¿Qué es el Código ORIDE?' => 'Un código breve que describe cómo se usó la inteligencia artificial en una obra: qué tipo de obra es, si se usó IA y, si quieres, en qué actividades, cuánto y si hay evidencias.',
    '¿Qué significan las dos cifras de cada actividad?' => 'La primera (0 a 5) es cómo usaste los resultados de la IA: de 0, nada, a 5, incorporarlos sin cambios. La segunda (0 a 4) es qué parte de la obra afectan, de menos del 5% al 75–100%. Con los grados 0 y 1 la segunda cifra es siempre 0.',
    '¿Tengo que rellenar las actividades?' => 'No. Basta con el tipo de obra y si usaste IA. Las evidencias y las actividades añaden detalle.',
    '¿El código certifica algo?' => STR['es']['honor'],
    '¿Usar el Código ORIDE equivale a cumplir el artículo 50 del Reglamento de IA?' => 'No. El Código ORIDE ayuda a ser transparente, pero no equivale a cumplir el artículo 50 del Reglamento (UE) 2024/1689. Ese artículo tiene sus propias obligaciones, como señalar las ultrasuplantaciones o el texto generado con IA sobre asuntos de interés público. Comprueba si te afectan; consulta el marco normativo.',
    '¿Pueden usarlo las instituciones?' => 'Sí. En la página Instituciones, centros educativos, editoriales, concursos o estudios pueden fijar un código de referencia con el uso máximo de IA que aceptan.',
    '¿Puedo compartir un enlace a mi código?' => 'Sí. Cada código tiene su propia página que lo explica, por ejemplo oride.php?LIT-IA1-DOC44-V1. Usa el botón «Copiar enlace».',
    '¿Tengo que registrarme? ¿Se guardan mis respuestas?' => 'No, en ambos casos. El código se calcula en tu navegador y no usamos cookies.',
    '¿Quién puede usar el Código ORIDE?' => STR['es']['trademark'],
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
