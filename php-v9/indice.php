<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$cfg = ig_config();
$levels = levels();
page_start(t('nav_index'), tr('Cómo se calcula el Índice Global ORIDE: fórmula, factores, pesos, limitaciones y versión.', 'How the ORIDE Global Index is calculated: formula, factors, weights, limitations and version.'));
?>
<div class="container narrow-text page">
  <?= breadcrumb([['documentacion.php', t('nav_docs')], [null, t('nav_index')]]) ?>
  <p class="eyebrow"><?= h(IG_CURRENT . ' · ' . IG_CAT_VERSION) ?></p>
  <h1><?= h(tr('Cómo se calcula el Índice Global ORIDE', 'How the ORIDE Global Index is calculated')) ?></h1>
  <p class="lead"><?= h(tr('Un valor de 0 a 100 que estima la contribución de la IA a una obra, a partir de su Código ORIDE, y que se muestra como una categoría de A a F.', 'A value from 0 to 100 that estimates the contribution of AI to a work, based on its ORIDE Code, and is shown as a category from A to F.')) ?></p>
  <div class="card honor"><p><?= h(t('ig_explain')) ?></p><p><b><?= h(tr('Estado: metodología experimental, abierta a revisión.', 'Status: experimental methodology, open to review.')) ?></b> <?= h(tr('Los factores, los pesos y los umbrales de las categorías son convenciones iniciales: no se han validado científica ni empíricamente, y pueden cambiar en versiones posteriores. Cada cambio crea una versión nueva, así que un índice publicado siempre se puede reproducir con las reglas con que se calculó.', 'The factors, weights and category thresholds are initial conventions: they have not been scientifically or empirically validated and may change in later versions. Each change creates a new version, so a published index can always be reproduced with the rules used to calculate it.')) ?></p></div>

  <h2><?= h(tr('La fórmula', 'The formula')) ?></h2>
  <p class="formula">IG = G + (100 − G) · P</p>
  <ul>
    <li><?= tr('<b>G</b>, generación efectiva (0–100): cuánto contenido generado con IA queda en la obra final, ajustado por la transformación humana.', '<b>G</b>, effective generation (0–100): how much AI-generated content remains in the final work, adjusted for human transformation.') ?></li>
    <li><?= tr('<b>P</b>, influencia auxiliar (0–1): el uso de IA en el resto del proceso (documentación, ideas, estructura, revisión…), ponderado por su importancia.', '<b>P</b>, auxiliary influence (0–1): the use of AI in the rest of the process (research, ideas, structure, review…), weighted by importance.') ?></li>
  </ul>
  <p class="muted"><?= h(tr('Si G es 100, el índice es 100. Si G es 0, el índice es 100 · P. El índice nunca baja al aumentar G o P, y nunca pasa de 100.', 'If G is 100, the index is 100. If G is 0, the index is 100 · P. The index never goes down when G or P go up, and never exceeds 100.')) ?></p>

  <h2><?= h(tr('Generación efectiva (G)', 'Effective generation (G)')) ?></h2>
  <p class="formula">G = 100 · q · f(H)</p>
  <p class="muted"><?= h(tr('q es la proporción del contenido final que procede de material generado con IA, y f(H) el factor según el grado de uso de la actividad que genera el contenido (por ejemplo, «Generación de contenido» en literatura o «Generación de código» en software).', 'q is the share of the final content that comes from AI-generated material, and f(H) the factor for the degree of use of the activity that generates the content (for example, “Content generation” in literature or “Code generation” in software).')) ?></p>
  <ul class="muted">
    <li><?= h(tr('q sale de la pregunta «¿Qué proporción aproximada del contenido final procede de material generado mediante IA?». Si no se responde, se usa el alcance declarado en la actividad.', 'q comes from the question “Roughly what share of the final content comes from AI-generated material?”. If it is not answered, the scope declared for the activity is used.')) ?></li>
    <li><?= h(tr('Si hay varias actividades generativas, no se inventan pesos entre ellas: el índice se calcula como intervalo, entre la que menos y la que más incorpora.', 'If there are several generative activities, no weights are invented between them: the index is calculated as a range, between the one that incorporates least and the one that incorporates most.')) ?></li>
    <li><?= h(tr('El motor admite obras con varios componentes (G = 100 · Σ wⱼ qⱼ f(Hⱼ), con pesos que suman 1), pero el formulario actual declara la obra como un único componente.', 'The engine supports works with several components (G = 100 · Σ wⱼ qⱼ f(Hⱼ), with weights adding up to 1), but the current form declares the work as a single component.')) ?></li>
  </ul>

  <h3><?= h(tr('Factor de IA según el grado de uso', 'AI factor by degree of use')) ?></h3>
  <table class="factor-table"><thead><tr><th><?= h(tr('Grado', 'Degree')) ?></th><th><?= h(tr('Significado', 'Meaning')) ?></th><th><?= h(tr('Factor', 'Factor')) ?></th></tr></thead><tbody>
    <?php foreach ($cfg['factors'] as $n => $f): ?><tr><td class="n"><?= $n ?></td><td><?= h($levels[$n]) ?></td><td class="n"><?= num($f, 2) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <p class="small muted"><?= h(tr('El grado 1 (se consultó la IA pero no se incorporó nada) se registra en el código aunque su factor sea 0: es distinto de no haber usado IA.', 'Degree 1 (AI was consulted but nothing was incorporated) is recorded in the code even though its factor is 0: it is different from not using AI.')) ?></p>

  <h3><?= h(tr('Proporción generada con IA (q)', 'Share generated with AI (q)')) ?></h3>
  <table class="factor-table"><thead><tr><th><?= h(tr('Respuesta', 'Answer')) ?></th><th>q</th></tr></thead><tbody>
    <?php foreach ($cfg['q_bands'] as $n => $b): ?><tr><td><?= h(STR[lang()]['q_bands'][$n]) ?></td><td class="n"><?= h(num_range([$b[0] * 100, $b[1] * 100], 0, ' %')) ?></td></tr><?php endforeach; ?>
  </tbody></table>

  <h2><?= h(tr('Influencia auxiliar (P)', 'Auxiliary influence (P)')) ?></h2>
  <p class="formula">P = min(1, Σ wᵢ · f(Hᵢ) · Sᵢ)</p>
  <p class="muted"><?= h(tr('Para cada actividad auxiliar: su peso w, el factor de su grado de uso f(H) y su alcance S. Cada actividad cuenta una sola vez.', 'For each auxiliary activity: its weight w, the factor for its degree of use f(H) and its scope S. Each activity counts only once.')) ?></p>
  <h3><?= h(tr('Alcance (S)', 'Scope (S)')) ?></h3>
  <p class="small muted"><?= h(tr('En el Código ORIDE el alcance son tramos de porcentaje (0–4). Para no convertir un tramo en un número exacto, se usa el intervalo del tramo y el índice se calcula como intervalo, que después se clasifica en una categoría (véase más abajo).', 'In the ORIDE Code the scope is a percentage band (0–4). To avoid turning a band into an exact number, the band’s range is used and the index is calculated as a range, which is then classified into a category (see below).')) ?></p>
  <table class="factor-table"><thead><tr><th><?= h(tr('Dígito', 'Digit')) ?></th><th><?= h(tr('Alcance', 'Scope')) ?></th><th>S</th></tr></thead><tbody>
    <?php foreach ($cfg['scope'] as $n => $b): ?><tr><td class="n"><?= $n ?></td><td><?= h(scope_labels()[$n]) ?></td><td class="n"><?= num($b[0], 2) ?>–<?= num($b[1], 2) ?></td></tr><?php endforeach; ?>
  </tbody></table>

  <h3><?= h(tr('Pesos por disciplina', 'Weights by discipline')) ?></h3>
  <p class="small muted"><?= h(tr('Las actividades generativas cuentan en G; las auxiliares, en P con su peso. Los pesos de literatura son los de la especificación inicial. Los del resto de disciplinas son provisionales, asignados por analogía con literatura y con un máximo de 0,50 en total.', 'Generative activities count in G; auxiliary ones count in P with their weight. The literature weights are those of the initial specification. The weights for the other disciplines are provisional, assigned by analogy with literature, with a total maximum of 0.50.')) ?></p>
  <?php foreach ($cfg['weights'] as $type => $wt): $acts = acts($type); ?>
    <details class="faq"><summary><?= h(type_name($type)) ?> <span class="muted small">· <?= h(['spec' => tr('pesos de la especificación inicial', 'initial specification weights'), 'proposal' => tr('pesos propios, experimentales', 'own experimental weights')][$wt['basis']] ?? tr('provisional, por analogía', 'provisional, by analogy')) ?></span></summary>
      <table class="factor-table"><tbody>
        <?php foreach ($wt['acts'] as $act => [$role, $w, $why]): ?>
          <tr><td><?= h($acts[$act][0]) ?></td><td class="n"><?= $role === 'gen' ? 'G' : num($w, 2) ?></td><td class="muted"><?= h(ig_why($why)) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    </details>
  <?php endforeach; ?>


  <section id="categorias">
  <h2><?= h(tr('Categorías del Índice Global ORIDE', 'ORIDE Global Index categories')) ?></h2>
  <p><?= h(tr('El índice se calcula como un intervalo [mínimo, máximo] y se muestra como una única categoría, de A a F. La categoría se obtiene del punto medio del intervalo, sin redondear:', 'The index is calculated as a range [minimum, maximum] and shown as a single category, from A to F. The category is obtained from the midpoint of the range, without rounding:')) ?></p>
  <p class="formula"><?= h(tr('punto medio = (mínimo + máximo) / 2', 'midpoint = (minimum + maximum) / 2')) ?></p>
  <table class="factor-table"><caption><?= h(IG_CAT_VERSION) ?></caption><thead><tr><th><?= h(tr('Categoría', 'Category')) ?></th><th><?= h(tr('Nombre', 'Name')) ?></th><th><?= h(tr('Punto medio', 'Midpoint')) ?></th></tr></thead><tbody>
    <?php foreach (IG_CATS as $c => [$lo, $hi]): ?><tr><td class="n"><b><?= $c ?></b><br><span class="mono small">IG<?= $c ?></span></td><td><?= h(IG_CAT_LABELS[lang()][$c]) ?></td><td><?= $c === 'A' ? '0 ≤ m ≤ 10' : num($lo, 0) . ' &lt; m ≤ ' . num($hi, 0) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <p class="small muted"><?= h(tr('Límites exactos: cada valor límite pertenece a la categoría inferior. 10 es A y 10,01 es B; 25 es B; 50 es C; 75 es D; 90 es E; más de 90 es F.', 'Exact limits: each boundary value belongs to the lower category. 10 is A and 10.01 is B; 25 is B; 50 is C; 75 is D; 90 is E; over 90 is F.')) ?></p>
  <h3><?= h(tr('Ejemplos', 'Examples')) ?></h3>
  <table class="factor-table"><thead><tr><th><?= h(tr('Intervalo', 'Range')) ?></th><th><?= h(tr('Punto medio', 'Midpoint')) ?></th><th><?= h(tr('Categoría', 'Category')) ?></th></tr></thead><tbody>
    <?php foreach ([[0, 3], [12, 20], [18, 32], [30, 42], [45, 65], [68, 82], [80, 95], [92, 98]] as [$lo, $hi]): $c = ig_classify_range($lo, $hi); ?>
      <tr><td class="n"><?= $lo ?>–<?= $hi ?> <?= h(tr('%', '%')) ?></td><td class="n"><?= h(num(ig_midpoint($lo, $hi), 2)) ?></td><td><?= h(ig_cat_label($c)) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <h3><?= h(tr('En el código', 'In the code')) ?></h3>
  <ul class="muted">
    <li><?= tr('La categoría va en el segundo término: <span class="mono">LI-IGB-EV1</span> + <span class="mono">GEN24</span>. Se calcula sola a partir de las actividades; no se elige.', 'The category goes in the second term: <span class="mono">LI-IGB-EV1</span> + <span class="mono">GEN24</span>. It is calculated automatically from the activities; it is not chosen.') ?></li>
    <li><?= tr('<span class="mono">IA0</span>: no se usó IA. Nunca se convierte en <span class="mono">IGA</span>: «sin IA» y «uso muy bajo» son cosas distintas.', '<span class="mono">IA0</span>: no AI was used. It never becomes <span class="mono">IGA</span>: “no AI” and “very low use” are different things.') ?></li>
    <li><?= tr('<span class="mono">IA1</span>: se usó IA, pero no hay categoría (no se detallan actividades o todas están a 0).', '<span class="mono">IA1</span>: AI was used, but there is no category (no activities are given or all are at 0).') ?></li>
    <li><?= tr('Los códigos anteriores con <span class="mono">IA1</span> y actividades siguen siendo válidos: conservan IA1 y la categoría se muestra como información derivada.', 'Older codes with <span class="mono">IA1</span> and activities are still valid: they keep IA1 and the category is shown as derived information.') ?></li>
    <li><?= tr('Si un código lleva una letra y actividades, la letra tiene que coincidir con la calculada; si no, el código no es válido. Un requisito puede fijar solo la categoría máxima, sin actividades: <span class="mono">LI-IGB-EV0</span>.', 'If a code has a letter and activities, the letter must match the calculated one; otherwise the code is not valid. A requirement may set only the maximum category, without activities: <span class="mono">LI-IGB-EV0</span>.') ?></li>
  </ul>
  <div class="card honor"><p><?= h(tr('Las categorías describen tramos de utilización estimada de IA. No son calificaciones de calidad, mérito, originalidad ni autoría. Como se calculan con el punto medio, una categoría no garantiza que todos los valores del intervalo estimado queden dentro de su tramo: el intervalo completo se puede consultar en el desglose del cálculo.', 'Categories describe bands of estimated AI use. They are not ratings of quality, merit, originality or authorship. As they are calculated from the midpoint, a category does not guarantee that every value of the estimated range falls within its band: the full range is available in the calculation breakdown.')) ?></p></div>
  </section>

  <h2><?= h(tr('Cuándo no se calcula', 'When it is not calculated')) ?></h2>
  <ul class="muted">
    <li><?= h(tr('Si se usó IA pero no se detallan actividades: no hay información suficiente, y no se asigna un 0.', 'If AI was used but no activities are given: there is not enough information, and no 0 is assigned.')) ?></li>
    <li><?= h(tr('Si se usó IA pero todas las actividades marcadas dicen «No utilicé IA»: el uso está fuera de las actividades y hay que explicarlo junto al código.', 'If AI was used but every ticked activity says “I did not use AI”: the use is outside the activities and must be explained next to the code.')) ?></li>
    <li><?= h(tr('Sin IA (IA0) no se asigna categoría: IA0 no equivale a IGA.', 'Without AI (IA0) no category is assigned: IA0 is not the same as IGA.')) ?></li>
  </ul>

  <h2><?= h(tr('Ejemplos', 'Examples')) ?></h2>
  <table class="factor-table"><thead><tr><th><?= h(tr('Caso', 'Case')) ?></th><th>G</th><th>P</th><th>IG</th></tr></thead><tbody>
    <?php foreach ([
        [tr('Obra sin IA', 'Work without AI'), 0, 0],
        [tr('Novela documentada íntegramente con IA (factor 1)', 'Novel fully researched with AI (factor 1)'), 0, 0.05],
        [tr('Obra generada íntegramente con IA, sin cambios', 'Work fully generated with AI, unchanged'), 100, 0],
        [tr('Obra generada íntegramente y reelaborada creativamente', 'Work fully generated and creatively reworked'), 20, 0],
        [tr('Obra generada al 50 %, sin cambios', 'Work 50% generated, unchanged'), 50, 0],
        [tr('G = 50 con influencia auxiliar del 5 %', 'G = 50 with 5% auxiliary influence'), 50, 0.05],
        [tr('Sin generación directa, P = 0,40', 'No direct generation, P = 0.40'), 0, 0.40],
    ] as [$case, $G, $P]): ?>
      <tr><td><?= h($case) ?></td><td class="n"><?= num($G) ?></td><td class="n"><?= num($P, 2) ?></td><td class="n"><?= num(ig_combine($G, $P)) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>

  <h2><?= h(tr('Limitaciones', 'Limitations')) ?></h2>
  <ul class="muted">
    <li><?= h(tr('Los factores y los pesos son convenciones metodológicas iniciales, no porcentajes medidos ni demostrados.', 'The factors and weights are initial methodological conventions, not measured or proven percentages.')) ?></li>
    <li><?= h(tr('El índice depende de lo declarado: ORIDE no verifica el uso real de IA.', 'The index depends on what is declared: ORIDE does not verify the actual use of AI.')) ?></li>
    <li><?= h(tr('No es un porcentaje de autoría ni una medida de originalidad o de titularidad.', 'It is not a percentage of authorship, nor a measure of originality or ownership.')) ?></li>
    <li><?= h(tr('Los pesos de las disciplinas distintas de la literatura son provisionales.', 'The weights for disciplines other than literature are provisional.')) ?></li>
  </ul>

  <h2><?= h(tr('Versiones', 'Versions')) ?></h2>
  <p class="muted"><?= h(tr('La versión actual es ' . IG_CURRENT . ' (taxonomía ' . ORIDE_TAXONOMY . '). Cualquier cambio de factores o pesos crea una versión nueva. Las categorías tienen su propia versión (' . IG_CAT_VERSION . '): cambiar los umbrales crea una versión nueva de las categorías sin tocar el algoritmo. Los enlaces a un código llevan las versiones con que se calculó (ig=… e igc=…), y el cálculo se puede descargar en JSON con todos sus valores, así que un índice ya publicado no se recalcula en silencio con reglas nuevas.', 'The current version is ' . IG_CURRENT . ' (taxonomy ' . ORIDE_TAXONOMY . '). Any change to factors or weights creates a new version. Categories have their own version (' . IG_CAT_VERSION . '): changing the thresholds creates a new category version without touching the algorithm. Links to a code carry the versions used (ig=… and igc=…), and the calculation can be downloaded as JSON with all its values, so a published index is never silently recalculated with new rules.')) ?></p>
  <p><a class="btn btn-primary" href="<?= u('crear.php') ?>"><?= h(t('nav_create')) ?></a></p>
</div>
<?php page_end();
