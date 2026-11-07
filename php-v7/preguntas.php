<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$faq = en() ? [
    'What is the ORIDE Code?' => 'A short code that describes how artificial intelligence was used in a work: what type of work it is, whether AI was used and, optionally, in which activities, how much, and whether there is evidence.',
    'What do the two digits of each activity mean?' => 'The first (0 to 5) is how you used the AI results: from 0, not at all, to 5, incorporating them unchanged. The second (0 to 4) is how much of the work they affect, from less than 5% to 75–100%. With degrees 0 and 1 the second digit is always 0.',
    'Do I have to fill in the activities?' => 'No. The type of work, whether you used AI and whether you keep evidence are enough. Activities add detail, go on a second line and are used to calculate the ORIDE Global Index.',
    'What is the ORIDE Global Index?' => 'An estimate from 0 to 100 of the contribution of AI to the work, calculated from the declared activities, their scope, the degree of human transformation and the weights of the ORIDE methodology. It is not a certified percentage of AI authorship. See “How the ORIDE Global Index is calculated”.',
    'Does the code certify anything?' => STR['en']['honor'],
    'Does the ORIDE Code mean I comply with Article 50 of the EU AI Act?' => 'No. The ORIDE Code helps you be transparent, but it is not the same as complying with Article 50 of Regulation (EU) 2024/1689. That article has its own obligations, such as labelling deep fakes or AI-generated text on matters of public interest. Check whether they apply to you; see the regulatory framework.',
    'Can institutions use it?' => 'Yes. On the Institutions page, schools, publishers, competitions or studios can set a reference code with the maximum use of AI they accept.',
    'Can I share a link to my code?' => 'Yes. Each code has its own page that explains it, for example oride.php?LIT-IA1-V1-DOC44. Use the “Copy link” button.',
    'Do I need to sign up? Are my answers stored?' => 'No to both. The code is calculated in your browser and we don’t use cookies.',
    'Who can use the ORIDE Code?' => STR['en']['trademark'],
    'What is this site’s CC BY-NC-ND 4.0 licence?' => 'The contents of this site (texts, code tables and methodology) are published under the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 licence. You may copy and share them in any medium or format, as long as you credit the author (Olga Revilla), link to the licence and do not use them for commercial purposes. You may not distribute modified versions, use them commercially without permission, or add legal or technical restrictions that stop others doing what the licence allows. Using an ORIDE Code to declare your work is governed by the trademark notice above.',
] : [
    '¿Qué es el Código ORIDE?' => 'Un código breve que describe cómo se usó la inteligencia artificial en una obra: qué tipo de obra es, si se usó IA y, si quieres, en qué actividades, cuánto y si hay evidencias.',
    '¿Qué significan las dos cifras de cada actividad?' => 'La primera (0 a 5) es cómo usaste los resultados de la IA: de 0, nada, a 5, incorporarlos sin cambios. La segunda (0 a 4) es qué parte de la obra afectan, de menos del 5% al 75–100%. Con los grados 0 y 1 la segunda cifra es siempre 0.',
    '¿Tengo que rellenar las actividades?' => 'No. Basta con el tipo de obra, si usaste IA y si conservas evidencias. Las actividades añaden detalle, van en una segunda línea y permiten calcular el Índice Global ORIDE.',
    '¿Qué es el Índice Global ORIDE?' => 'Una estimación de 0 a 100 de la contribución de la IA a la obra, calculada a partir de las actividades declaradas, su alcance, el grado de transformación humana y los pesos de la metodología ORIDE. No es un porcentaje certificado de autoría de IA. Consulta «Cómo se calcula el Índice Global ORIDE».',
    '¿El código certifica algo?' => STR['es']['honor'],
    '¿Usar el Código ORIDE equivale a cumplir el artículo 50 del Reglamento de IA?' => 'No. El Código ORIDE ayuda a ser transparente, pero no equivale a cumplir el artículo 50 del Reglamento (UE) 2024/1689. Ese artículo tiene sus propias obligaciones, como señalar las ultrasuplantaciones o el texto generado con IA sobre asuntos de interés público. Comprueba si te afectan; consulta el marco normativo.',
    '¿Pueden usarlo las instituciones?' => 'Sí. En la página Instituciones, centros educativos, editoriales, concursos o estudios pueden fijar un código de referencia con el uso máximo de IA que aceptan.',
    '¿Puedo compartir un enlace a mi código?' => 'Sí. Cada código tiene su propia página que lo explica, por ejemplo oride.php?LIT-IA1-V1-DOC44. Usa el botón «Copiar enlace».',
    '¿Tengo que registrarme? ¿Se guardan mis respuestas?' => 'No, en ambos casos. El código se calcula en tu navegador y no usamos cookies.',
    '¿Quién puede usar el Código ORIDE?' => STR['es']['trademark'],
    '¿Qué es la licencia CC BY-NC-ND 4.0 de este sitio?' => 'Los contenidos de este sitio (textos, tablas de códigos y metodología) se publican con la licencia Creative Commons Reconocimiento-NoComercial-SinObraDerivada 4.0. Permite copiarlos y compartirlos en cualquier medio o formato, siempre que cites la autoría (Olga Revilla), enlaces a la licencia y no los uses con fines comerciales. No permite distribuir versiones modificadas, usarlos con fines comerciales sin permiso ni añadir restricciones legales o técnicas que impidan a otros hacer lo que la licencia permite. El uso de un Código ORIDE para declarar tu obra se rige por la nota de la marca de la pregunta anterior.',
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
