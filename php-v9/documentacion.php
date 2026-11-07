<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$licence = '<a href="https://creativecommons.org/licenses/by-nc-nd/4.0/' . (en() ? '' : 'deed.es') . '" rel="license">CC BY-NC-ND 4.0</a>';
$faq = en() ? [
    'What is the ORIDE Code?' => 'An originality declaration: a short code in which authors state, on their honour, how they used artificial intelligence in a work.',
    'What do the two digits of each activity mean?' => 'The first (0 to 5) is how you used the AI results: from 0, not at all, to 5, incorporating them unchanged. The second (0 to 4) is how much of the work they affect, from less than 5% to 75–100%. With degrees 0 and 1 the second digit is always 0.',
    'Do I have to fill in the activities?' => 'No. The type of work, whether you used AI and whether you keep evidence are enough. Activities add detail, go on a second line and are used to calculate the ORIDE Global Index.',
    'What is the ORIDE Global Index?' => 'An estimate from 0 to 100 of the contribution of AI to the work, calculated from the declared activities, their scope, the degree of human transformation and the weights of the ORIDE methodology. It is shown as a category from A (very low) to F (extreme), calculated from the midpoint of the estimated range, and the letter goes in the code (for example, LI-IGB-EV1). It is not a certified percentage of AI authorship.',
    'Do all activities carry the same weight in the ORIDE Global Index?' => 'No. Generating content with AI weighs much more than using it to edit or to look up information. Content generation counts directly in the share of the work that comes from AI; research, ideas, structure or review count as auxiliary activities, each with a small weight (between 0.05 and 0.15 in literature). Human transformation also matters a lot: substantially reworking AI output weighs much less than accepting it unchanged.',
    'Do the code or the index certify anything?' => STR['en']['honor'] . ' The ORIDE Global Index is an estimate based on what is declared: it is not a certified percentage of AI authorship and does not determine the originality or ownership of the work.',
    'Does the ORIDE Code mean I comply with Article 50 of the EU AI Act?' => 'No. The ORIDE Code is voluntary and helps you be transparent, but it is not the same as complying with Article 50 of Regulation (EU) 2024/1689 and does not replace any legal obligation. In some cases, that article requires disclosing that content has been generated or manipulated with AI: images, audio or video that look real and could make people believe they are authentic (deep fakes), and AI-generated text published to inform the public on matters of public interest, unless it has undergone human review and someone holds editorial responsibility. For clearly artistic, creative, satirical or fictional works, it is enough to disclose it in a way that does not spoil the work. These obligations apply from 2 August 2026. Text of the regulation: <a href="https://eur-lex.europa.eu/eli/reg/2024/1689/oj" rel="noopener">Regulation (EU) 2024/1689 on EUR-Lex</a>. This is general information, not legal advice.',
    'Who can use ORIDE?' => 'On the one hand, authors, to declare the originality of their work. On the other, institutions (schools, research centres, publishers, competitions, studios…), which can require a minimum of originality, that is, a maximum use of AI, with a reference code.',
    'Can institutions use it?' => 'Yes. On the Institutions page, schools, publishers, competitions or studios can set a reference code with the maximum use of AI they accept, and check whether a submission meets it.',
    'Can I share a link to my code?' => 'Yes. Use the “Copy link” button to share a page where anyone can see your code explained in plain words, together with its ORIDE Global Index, for example oride.php?LI-IGB-EV1-GEN24.',
    'Do I need to sign up? Are my answers stored?' => 'No to both. Nothing you answer is stored and we don’t use cookies.',
    'Under what licence is ORIDE released?' => 'The ORIDE Code, the ORIDE Global Index, their methodology and the contents of this site are © 2026 Olga Revilla and are released under the Creative Commons ' . $licence . ' licence (Attribution-NonCommercial-NoDerivatives). You may copy and share them in any medium or format, as long as you credit the author, link to the licence and do not use them for commercial purposes. You may not distribute modified versions or add legal or technical restrictions that stop others doing what the licence allows. Declaring your own work with its ORIDE Code is the intended use of the system. For any other use, write to info@itakora.com.',
] : [
    '¿Qué es el Código ORIDE?' => 'Una declaración de originalidad: un código breve con el que el autor declara, bajo su palabra, cómo usó la inteligencia artificial en una obra.',
    '¿Qué significan las dos cifras de cada actividad?' => 'La primera (0 a 5) es cómo usaste los resultados de la IA: de 0, nada, a 5, incorporarlos sin cambios. La segunda (0 a 4) es qué parte de la obra afectan, de menos del 5% al 75–100%. Con los grados 0 y 1 la segunda cifra es siempre 0.',
    '¿Tengo que rellenar las actividades?' => 'No. Basta con el tipo de obra, si usaste IA y si conservas evidencias. Las actividades añaden detalle, van en una segunda línea y permiten calcular el Índice Global ORIDE.',
    '¿Qué es el Índice Global ORIDE?' => 'Una estimación de 0 a 100 de la contribución de la IA a la obra, calculada a partir de las actividades declaradas, su alcance, el grado de transformación humana y los pesos de la metodología ORIDE. Se muestra como una categoría de A (muy bajo) a F (extremo), calculada a partir del punto medio del intervalo estimado, y la letra va en el código (por ejemplo, LI-IGB-EV1). No es un porcentaje certificado de autoría de IA.',
    '¿Todas las actividades tienen el mismo peso a la hora de calcular el Índice Global ORIDE?' => 'No. Generar contenido con IA pesa mucho más que usarla para editar o para buscar documentación. La generación de contenido cuenta directamente en la parte de la obra que procede de la IA; la documentación, las ideas, la estructura o la revisión cuentan como actividades auxiliares, cada una con un peso pequeño (entre 0,05 y 0,15 en literatura). También importa mucho la transformación humana: reelaborar a fondo lo que propone la IA pesa mucho menos que aceptarlo sin cambios.',
    '¿El código o el índice certifican algo?' => STR['es']['honor'] . ' El Índice Global ORIDE es una estimación a partir de lo declarado: no es un porcentaje certificado de autoría de IA ni determina la originalidad o la titularidad de la obra.',
    '¿Usar el Código ORIDE equivale a cumplir el artículo 50 del Reglamento de IA?' => 'No. El Código ORIDE es voluntario y ayuda a ser transparente, pero no equivale a cumplir el artículo 50 del Reglamento (UE) 2024/1689 ni sustituye ninguna obligación legal. Ese artículo exige avisar, en algunos casos, de que un contenido se ha generado o manipulado con IA: imágenes, audio o vídeo que parecen reales y pueden hacer creer que son auténticos (ultrasuplantaciones), y texto generado con IA que se publica para informar sobre asuntos de interés público, salvo que tenga revisión humana y alguien asuma la responsabilidad editorial. En obras claramente artísticas, creativas, satíricas o de ficción basta con indicarlo de forma que no estropee la obra. Estas obligaciones se aplican desde el 2 de agosto de 2026. Texto del reglamento: <a href="https://eur-lex.europa.eu/eli/reg/2024/1689/oj" rel="noopener">Reglamento (UE) 2024/1689 en EUR-Lex</a>. Información orientativa, no asesoramiento jurídico.',
    '¿Quién puede usar ORIDE?' => 'Por un lado, los autores, para declarar la originalidad de su obra. Por otro, las instituciones (centros educativos, de investigación, editoriales, concursos, estudios…), que pueden exigir un mínimo de originalidad, es decir, un uso máximo de IA, con un código de referencia.',
    '¿Pueden usarlo las instituciones?' => 'Sí. En la página Instituciones, centros educativos, editoriales, concursos o estudios pueden fijar un código de referencia con el uso máximo de IA que aceptan y verificar si una entrega lo cumple.',
    '¿Puedo compartir un enlace a mi código?' => 'Sí. Usa el botón «Copiar enlace» para compartir una página en la que cualquiera puede ver tu código explicado con palabras sencillas, junto con su Índice Global ORIDE; por ejemplo, oride.php?LI-IGB-EV1-GEN24.',
    '¿Tengo que registrarme? ¿Se guardan mis respuestas?' => 'No, en ambos casos. No se guarda nada de lo que respondes y no usamos cookies.',
    '¿Con qué licencia se publica ORIDE?' => 'El Código ORIDE, el Índice Global ORIDE, su metodología y los contenidos de este sitio son © 2026 Olga Revilla y se publican con la licencia Creative Commons ' . $licence . ' (Reconocimiento-NoComercial-SinObraDerivada). Puedes copiarlos y compartirlos en cualquier medio o formato, siempre que cites la autoría, enlaces a la licencia y no los uses con fines comerciales. No puedes distribuir versiones modificadas ni añadir restricciones legales o técnicas que impidan a otros hacer lo que la licencia permite. Declarar tu propia obra con su Código ORIDE es el uso previsto del sistema. Para cualquier otro uso, escribe a info@itakora.com.',
];
$withHtml = [tr('¿Con qué licencia se publica ORIDE?', 'Under what licence is ORIDE released?'), tr('¿Usar el Código ORIDE equivale a cumplir el artículo 50 del Reglamento de IA?', 'Does the ORIDE Code mean I comply with Article 50 of the EU AI Act?')];

page_start(t('nav_docs'), tr('Cómo funcionan el Código ORIDE y el Índice Global ORIDE, y preguntas frecuentes.', 'How the ORIDE Code and the ORIDE Global Index work, and frequently asked questions.'));
?>
<div class="container narrow-text page docs">
  <h1><?= h(t('nav_docs')) ?></h1>
  <nav class="toc" aria-label="<?= h(tr('En esta página', 'On this page')) ?>">
    <a href="#codigo"><?= h(tr('Cómo funciona el código', 'How the code works')) ?></a>
    <a href="#indice"><?= h(tr('Cómo funciona el índice', 'How the index works')) ?></a>
    <a href="#preguntas"><?= h(t('nav_faq')) ?></a>
  </nav>

  <section id="codigo">
    <h2><?= h(tr('Cómo funciona el Código ORIDE', 'How the ORIDE Code works')) ?></h2>
    <p><?= h(tr('El código tiene dos líneas:', 'The code has two lines:')) ?></p>
    <p class="formula code-example">LI-IGB-EV1<br>DOC44-GEN24</p>
    <h3><?= h(tr('Primera línea: tipo de obra, uso de IA y evidencias', 'First line: type of work, use of AI and evidence')) ?></h3>
    <ul>
      <li><?= tr('<b>Tipo de obra</b>: ', '<b>Type of work</b>: ') . implode(', ', array_map(fn($k) => '<span class="mono">' . $k . '</span> ' . h(mb_strtolower(type_name($k))), array_keys(WORK_TYPES))) ?>.</li>
      <li><?= tr('<b>Uso de IA</b>: <span class="mono">IA0</span>, no se usó. Si se usó, la categoría del Índice Global, de <span class="mono">IGA</span> a <span class="mono">IGF</span>, calculada a partir de las actividades; <span class="mono">IA1</span> si se usó pero no hay categoría (sin actividades). Los códigos anteriores con IA1 y actividades siguen siendo válidos.', '<b>Use of AI</b>: <span class="mono">IA0</span>, not used. If used, the Global Index category, from <span class="mono">IGA</span> to <span class="mono">IGF</span>, calculated from the activities; <span class="mono">IA1</span> if used but there is no category (no activities). Older codes with IA1 and activities are still valid.') ?></li>
      <li><?= tr('<b>Evidencias</b>: <span class="mono">EV1</span>, el autor conserva pruebas del proceso; <span class="mono">EV0</span>, no. Con EV1 conviene añadir cómo contactar con el autor o un enlace a esas pruebas.', '<b>Evidence</b>: <span class="mono">EV1</span>, the author keeps evidence of the process; <span class="mono">EV0</span>, they do not. With EV1, add a way to contact the author or a link to that evidence.') ?></li>
    </ul>
    <h3><?= h(tr('Segunda línea: actividades (opcional)', 'Second line: activities (optional)')) ?></h3>
    <p><?= h(tr('Solo si se usó IA. Cada actividad tiene tres letras propias de su tipo de obra (DOC, GEN, COD…) y dos cifras:', 'Only if AI was used. Each activity has three letters specific to its type of work (DOC, GEN, COD…) and two digits:')) ?></p>
    <div class="grid-2">
      <table class="factor-table"><caption><?= h(tr('Primera cifra: grado de uso', 'First digit: degree of use')) ?></caption><tbody>
        <?php foreach (levels() as $n => $text): ?><tr><td class="n"><?= $n ?></td><td><?= h($text) ?></td></tr><?php endforeach; ?>
      </tbody></table>
      <table class="factor-table"><caption><?= h(tr('Segunda cifra: alcance (desde el grado 2)', 'Second digit: scope (from degree 2)')) ?></caption><tbody>
        <?php foreach (scope_labels() as $n => $text): ?><tr><td class="n"><?= $n ?></td><td><?= h($text) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <p class="muted"><?= tr('Con los grados 0 y 1 la segunda cifra es 0 (<span class="mono">DOC10</span>). Si se usó IA pero todas las actividades están a 0, hay que explicar el uso de la IA fuera de las actividades establecidas.', 'With degrees 0 and 1 the second digit is 0 (<span class="mono">DOC10</span>). If AI was used but every activity is at 0, the use of AI must be explained outside the listed activities.') ?></p>
    <p><?= tr('Las actividades de cada tipo de obra están en <a href="' . h(u('codigo.php')) . '">Código ORIDE</a>.<br>Cada código tiene su propia página, por ejemplo <a class="mono" href="' . h(u('oride.php?LI-IGB-EV1-DOC44-GEN24')) . '">oride.php?LI-IGB-EV1-DOC44-GEN24</a>.', 'The activities for each type of work are listed in <a href="' . h(u('codigo.php')) . '">ORIDE Code</a>.<br>Each code has its own page, for example <a class="mono" href="' . h(u('oride.php?LI-IGB-EV1-DOC44-GEN24')) . '">oride.php?LI-IGB-EV1-DOC44-GEN24</a>.') ?></p>
  </section>

  <section id="indice">
    <h2><?= h(tr('Cómo funciona el Índice Global ORIDE', 'How the ORIDE Global Index works')) ?></h2>
    <p><?= h(tr('Junto al código se calcula una estimación de 0 a 100 de la contribución de la IA a la obra:', 'Alongside the code, an estimate from 0 to 100 of the contribution of AI to the work is calculated:')) ?></p>
    <p class="formula">IG = G + (100 − G) · P</p>
    <ul>
      <li><?= tr('<b>G</b>, generación efectiva: cuánto contenido generado con IA queda en la obra final, según la parte de la obra afectada y lo que el autor transformó.', '<b>G</b>, effective generation: how much AI-generated content remains in the final work, based on the share of the work affected and what the author transformed.') ?></li>
      <li><?= tr('<b>P</b>, influencia auxiliar: el uso de IA en documentación, ideas, estructura o revisión, con pesos pequeños.', '<b>P</b>, auxiliary influence: the use of AI for research, ideas, structure or review, with small weights.') ?></li>
      <li><?= h(tr('Si los datos son tramos (por ejemplo, «del 50% al 75%»), el índice se calcula como intervalo y se clasifica en una categoría de A a F por su punto medio: A, hasta el 10 %; B, hasta el 25 %; C, hasta el 50 %; D, hasta el 75 %; E, hasta el 90 %; F, más del 90 %. La categoría va en el código (IGB).', 'If the data are bands (for example, “from 50% to 75%”), the index is calculated as a range and classified into a category from A to F by its midpoint: A, up to 10%; B, up to 25%; C, up to 50%; D, up to 75%; E, up to 90%; F, over 90%. The category goes in the code (IGB).')) ?></li>
      <li><?= h(tr('Sin IA no hay índice ni categoría (IA0, que no equivale a IGA). Con IA pero sin actividades detalladas, no se calcula (IA1).', 'Without AI there is no index or category (IA0, which is not the same as IGA). With AI but no activities given, it is not calculated (IA1).')) ?></li>
    </ul>
    <p><?= tr('Los factores, los pesos y las limitaciones están en <a href="' . h(u('indice.php')) . '">Cómo se calcula el Índice Global ORIDE</a>.', 'The factors, weights and limitations are in <a href="' . h(u('indice.php')) . '">How the ORIDE Global Index is calculated</a>.') ?></p>
  </section>

  <section id="preguntas">
    <h2><?= h(t('nav_faq')) ?></h2>
    <?php foreach ($faq as $q => $a): ?>
      <details class="faq"><summary><?= h($q) ?></summary><p><?= in_array($q, $withHtml, true) ? $a : h($a) ?></p></details>
    <?php endforeach; ?>
    <p class="muted small spaced"><?= h(tr('¿Otra pregunta? Escríbenos a', 'Another question? Write to')) ?> <a href="mailto:info@itakora.com">info@itakora.com</a>.</p>
  </section>
</div>
<?php page_end();
