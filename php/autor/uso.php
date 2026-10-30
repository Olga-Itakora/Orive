<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';
require_once __DIR__ . '/../includes/wizard.php';

$user = require_login();
$vid = (int) (get('id') ?: post('id'));
$f = wiz_load($user, $vid);
if (effective_usage($f['v']) !== 'YES') redirect(wiz_url($vid, 'uso'));
$useId = (int) (get('uso') ?: post('uso'));
$use = null;
foreach ($f['uses'] as $u) if ((int) $u['id'] === $useId) $use = $u;
if ($useId && !$use) not_found();
$self = 'autor/uso.php?id=' . $vid . ($use ? '&uso=' . $use['id'] : '');

const USE_FUNCTIONS = [
    'A1' => ['Consultar, documentarme o explorar información', 'Buscar datos, contexto o referencias.'],
    'A2' => ['Obtener ideas, sugerencias o alternativas', 'Tramas, títulos, composiciones, enfoques…'],
    'A3' => ['Recibir una revisión, crítica o evaluación', 'Una lectura crítica, comentarios sobre una versión.'],
    'A4' => ['Corregir, editar, traducir o transformar algo existente', 'Ortografía, estilo, traducción, retoque, limpieza de audio…'],
    'A5' => ['Generar contenido', 'Texto, imagen, música, sonido, vídeo, código u otros elementos.'],
];
const USE_CONTRIBUTIONS = [
    'B1' => ['Ideas, conceptos o estructuras', 'Que después desarrollaste tú. Ej.: una trama, un enfoque, un índice.'],
    'B2' => ['Cambios formales', 'Correcciones, reformulaciones, traducciones o cambios de estilo.'],
    'B3' => ['Contenido expresivo generado', 'Textos, imágenes, música, sonidos, vídeos o código creativo generados con IA.'],
];
const USE_INTERVENTIONS = [
    'ACCEPTED' => ['Lo seleccioné o lo acepté sin cambios significativos', 'Elegir entre varias opciones también es una decisión creativa.'],
    'MINOR_EDITS' => ['Hice ajustes menores', 'Retoques puntuales: algunas palabras, colores, detalles.'],
    'TRANSFORMED' => ['Lo transformé', 'Lo modifiqué considerablemente o lo usé como punto de partida.'],
    'OWN_EXPRESSION' => ['Solo tomé la idea o la referencia; la expresión final la realicé yo', 'Ej.: escribí la novela a partir de una idea surgida en la conversación.'],
    'UNSURE' => ['No puedo precisarlo', 'Se registrará como información insuficiente, sin inventar una clasificación.'],
];

if (is_post()) {
    csrf_check();
    $title = mb_substr(post('title'), 0, 160);
    $errors = [];
    if ($title === '') $errors['title'] = 'Pon un título breve a este uso (por ejemplo, «Revisión ortográfica»).';
    $componentIds = array_map(fn($c) => (int) $c['id'], $f['components']);
    $toolIds = array_map(fn($t) => (int) $t['id'], $f['tools']);
    $useIds = array_map(fn($u) => (int) $u['id'], $f['uses']);
    $component = in_array((int) post('component_id'), $componentIds, true) ? (int) post('component_id') : null;
    $tool = in_array((int) post('tool_id'), $toolIds, true) ? (int) post('tool_id') : null;
    $parent = in_array((int) post('parent_use_id'), $useIds, true) && (int) post('parent_use_id') !== $useId ? (int) post('parent_use_id') : null;
    $phases = array_values(array_intersect(post_list('phases'), array_keys(ORIDE_PHASES)));
    $functions = array_values(array_intersect(post_list('functions'), array_keys(USE_FUNCTIONS)));
    $primary = post('primary_function');
    $primary = count($functions) > 1 ? (in_array($primary, $functions, true) ? $primary : 'NONE') : null;
    $incorporation = in_array(post('incorporation'), ['NONE', 'INCORPORATED', 'UNKNOWN'], true) ? post('incorporation') : null;
    $types = $incorporation === 'INCORPORATED' ? array_values(array_intersect(post_list('contrib_types'), array_keys(USE_CONTRIBUTIONS))) : [];
    if ($errors) form_fail($errors, $self, 'Revisa los campos marcados.');
    $newId = tx(function () use ($use, $vid, $f, $title, $component, $tool, $parent, $phases, $functions, $primary, $incorporation, $types) {
        $data = [$component, $tool, $parent, $title, json_encode($functions), $primary, json_encode($phases), nullable(post('objective'), 2000), nullable(post('activity'), 4000), nullable(post('result'), 4000), $incorporation];
        if ($use) {
            q('UPDATE ai_uses SET component_id=?, tool_id=?, parent_use_id=?, title=?, functions=?, primary_function=?, phases=?, objective=?, activity=?, result=?, incorporation=? WHERE id=? AND version_id=?', array_merge($data, [$use['id'], $vid]));
            $id = (int) $use['id'];
            q('DELETE FROM contributions WHERE use_id = ?', [$id]);
        } else {
            if (count($f['uses']) >= 100) throw new DomainError('Máximo 100 usos por declaración.');
            q('INSERT INTO ai_uses (component_id, tool_id, parent_use_id, title, functions, primary_function, phases, objective, activity, result, incorporation, version_id, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($data, [$vid, count($f['uses'])]));
            $id = (int) db()->lastInsertId();
        }
        foreach ($types as $t) {
            $ans = in_array(post("answer_$t"), array_keys(USE_INTERVENTIONS), true) ? post("answer_$t") : null;
            $rec = $ans === 'TRANSFORMED' && in_array(post("recognizable_$t"), ['YES', 'NO', 'UNSURE'], true) ? post("recognizable_$t") : null;
            q('INSERT INTO contributions (use_id, ctype, intervention_answer, recognizable, description) VALUES (?,?,?,?,?)', [$id, $t, $ans, $rec, nullable(post("description_$t"), 3000)]);
        }
        q('UPDATE declaration_versions SET last_step = "usos", updated_at = ? WHERE id = ?', [now(), $vid]);
        return $id;
    });
    flash('ok', 'Uso guardado.');
    redirect(post('intent') === 'stay' ? 'autor/uso.php?id=' . $vid . '&uso=' . $newId : wiz_url($vid, 'usos'));
}

[$errors, $old] = form_state();
$contribSrc = [];
if ($use) foreach ($use['contributions'] as $c) $contribSrc += ["answer_{$c['ctype']}" => $c['intervention_answer'], "recognizable_{$c['ctype']}" => $c['recognizable'], "description_{$c['ctype']}" => $c['description']];
$src = $old ?: ($use ? [
    'title' => $use['title'], 'component_id' => $use['component_id'], 'tool_id' => $use['tool_id'], 'parent_use_id' => $use['parent_use_id'],
    'phases' => $use['phases_list'], 'functions' => $use['functions_list'], 'primary_function' => $use['primary_function'] ?? 'NONE',
    'objective' => $use['objective'], 'activity' => $use['activity'], 'result' => $use['result'], 'incorporation' => $use['incorporation'],
    'contrib_types' => array_column($use['contributions'], 'ctype'),
] + $contribSrc
 : ['component_id' => count($f['components']) === 1 ? $f['components'][0]['id'] : '', 'tool_id' => count($f['tools']) === 1 ? $f['tools'][0]['id'] : '', 'primary_function' => 'NONE']);
$s = fn(string $k) => (string) ($src[$k] ?? '');
$hints = json_decode($f['work']['phase_hints'] ?: '{}', true) ?: [];
$profile = $use ? classify_full($f) : null;
$preview = null;
if ($profile) foreach ($profile['uses'] as $p) if ((int) $p['ref'] === (int) $use['id']) $preview = $p;

page_start($use ? 'Uso: ' . $use['title'] : 'Nuevo uso de IA', ['area' => 'author']);
wiz_header($f, 'usos', $use ? 'Uso: ' . $use['title'] : 'Nuevo uso de IA');
?>
<div class="container grid-main-aside">
  <form method="post" action="<?= u('autor/uso.php') ?>" data-draft="uso-<?= $vid ?>-<?= $use['id'] ?? 'nuevo' ?>" class="stack">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $vid ?>"><?php if ($use): ?><input type="hidden" name="uso" value="<?= (int) $use['id'] ?>"><?php endif; ?>

    <section class="card">
      <h2>1. ¿Qué uso estás describiendo?</h2>
      <?= field('title', 'Título breve del uso', ['required' => true, 'max' => 160, 'value' => $s('title'), 'error' => $errors['title'] ?? null, 'hint' => 'Ej.: «Revisión crítica del ensayo», «Ilustración de cubierta», «Traducción al inglés».']) ?>
      <div class="form-grid">
        <?= field('component_id', 'Parte de la obra', ['type' => 'select', 'options' => ['' => 'La obra en general'] + array_column($f['components'], 'name', 'id'), 'value' => $s('component_id')]) ?>
        <?= field('tool_id', 'Herramienta utilizada', ['type' => 'select', 'options' => ['' => 'Selecciona…'] + array_combine(array_column($f['tools'], 'id'), array_map(fn($t) => $t['identified'] ? $t['name'] : $t['name'] . ' (no identificada)', $f['tools'])), 'value' => $s('tool_id'), 'hint' => '¿No aparece? Regístrala en el paso «Herramientas» (también puede ser «desconocida»).']) ?>
      </div>
      <?php $others = array_filter($f['uses'], fn($u) => (int) $u['id'] !== (int) ($use['id'] ?? 0) && !$u['parent_use_id']); if ($others): ?>
        <?= field('parent_use_id', '¿Es un subuso de otro uso?', ['type' => 'select', 'optional' => true, 'options' => ['' => 'No'] + array_combine(array_column($others, 'id'), array_map(fn($u) => 'Subuso de: ' . $u['title'], $others)), 'value' => $s('parent_use_id'), 'hint' => 'Divide un uso en subusos cuando produjo resultados heterogéneos (por ejemplo, varias imágenes tratadas de forma distinta).']) ?>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>2. ¿En qué fases del proceso?</h2>
      <fieldset><legend class="sr-only">Fases creativas</legend><div class="phase-grid">
        <?php foreach (ORIDE_PHASES as $k => $label): ?>
          <label class="choice"><input type="checkbox" name="phases[]" value="<?= $k ?>"<?= in_array($k, (array) ($src['phases'] ?? []), true) ? ' checked' : '' ?>><span><span class="choice-label"><?= h($label) ?></span><?= isset($hints[$k]) ? '<span class="choice-desc">Ej.: ' . h($hints[$k]) . '</span>' : '' ?></span></label>
        <?php endforeach; ?>
      </div></fieldset>
    </section>

    <section class="card">
      <h2>3. ¿Para qué utilizaste la IA? (dimensión A)</h2>
      <?= choices('functions', 'Marca todas las que correspondan', array_combine(array_keys(USE_FUNCTIONS), array_map(fn($k, $v) => ["$k · {$v[0]}", $v[1]], array_keys(USE_FUNCTIONS), USE_FUNCTIONS)), ['type' => 'checkbox', 'value' => (array) ($src['functions'] ?? [])]) ?>
      <?= choices('primary_function', '¿Alguna fue la función principal? (solo si marcaste varias)', ['NONE' => 'No hay una función principal'] + array_combine(array_keys(USE_FUNCTIONS), array_map(fn($k) => $k . ' · ' . oride_label($k), array_keys(USE_FUNCTIONS))), ['value' => $s('primary_function') ?: 'NONE', 'hint' => 'Si no hay una principal clara, el uso se mostrará como multifunción. Nunca se elige automáticamente el número más alto.']) ?>
    </section>

    <section class="card">
      <h2>4. Describe la actividad</h2>
      <p class="hint">No es necesario copiar prompts completos ni conversaciones privadas. Describe lo esencial.</p>
      <?= field('objective', 'Objetivo', ['optional' => true, 'max' => 2000, 'value' => $s('objective'), 'hint' => 'Qué querías conseguir.']) ?>
      <?= field('activity', 'Qué hiciste con la IA', ['type' => 'textarea', 'required' => true, 'max' => 4000, 'value' => $s('activity')]) ?>
      <?= field('result', 'Qué resultado obtuviste', ['type' => 'textarea', 'optional' => true, 'max' => 4000, 'value' => $s('result')]) ?>
    </section>

    <section class="card">
      <h2>5. ¿Se incorporó a la obra final algo procedente de este uso? (dimensión B)</h2>
      <?= choices('incorporation', 'Incorporación a la obra', ['NONE' => ['No, nada', 'Por ejemplo, leíste las sugerencias pero las descartaste todas.'], 'INCORPORATED' => 'Sí, algo se incorporó', 'UNKNOWN' => ['No puedo precisarlo', 'Se registrará como información insuficiente.']], ['value' => $s('incorporation')]) ?>
      <div data-show-if="incorporation=INCORPORATED">
        <?= choices('contrib_types', '¿Qué tipo de aportación procedente de la IA está en la obra?', array_combine(array_keys(USE_CONTRIBUTIONS), array_map(fn($k, $v) => ["$k · {$v[0]}", $v[1]], array_keys(USE_CONTRIBUTIONS), USE_CONTRIBUTIONS)), ['type' => 'checkbox', 'value' => (array) ($src['contrib_types'] ?? []), 'hint' => 'Puedes marcar varias. Para cada una indicarás qué hiciste con ella.']) ?>
        <?php foreach (USE_CONTRIBUTIONS as $t => [$label]): ?>
          <div class="sub-card" data-show-if="contrib_types=<?= $t ?>">
            <h3>6. ¿Qué hiciste con la aportación «<?= h(mb_strtolower($label)) ?>»? (dimensión C)</h3>
            <?= choices("answer_$t", 'Tu intervención', USE_INTERVENTIONS, ['value' => $s("answer_$t")]) ?>
            <div data-show-if="answer_<?= $t ?>=TRANSFORMED">
              <?= choices("recognizable_$t", 'Si alguien comparase el resultado original de la IA con tu versión final, ¿reconocería elementos del original (composición, figuras, frases, melodía…)?', ['YES' => 'Sí, se reconocen elementos del original', 'NO' => 'No, el resultado es una expresión considerablemente diferente', 'UNSURE' => 'No sabría decirlo'], ['value' => $s("recognizable_$t"), 'hint' => 'Esto distingue una transformación sustancial (C3) de una reelaboración creativa (C4). Ninguna implica más mérito que la otra.']) ?>
            </div>
            <?= field("description_$t", 'Describe tu intervención', ['type' => 'textarea', 'max' => 3000, 'rows' => 2, 'value' => $s("description_$t"), 'hint' => 'Qué decidiste, qué cambiaste o qué desarrollaste a partir de la aportación.']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="sticky-actions">
      <a class="link-44 back" href="<?= u(wiz_url($vid, 'usos')) ?>">← Volver a la lista de usos</a>
      <button class="btn btn-secondary" name="intent" value="stay">Guardar y ver la clasificación</button>
      <button class="btn btn-primary" name="intent" value="list">Guardar uso</button>
    </div>
  </form>

  <aside class="stack" aria-label="Clasificación de este uso">
    <div class="code-card"><div class="inner">
      <p class="label">Clasificación de este uso</p>
      <?php if ($preview): ?>
        <p class="big"><?= h($preview['code'] ?? '—') ?></p>
        <p class="mono small"><?= h($preview['notation']) ?></p>
        <p>A · <?= h(implode('; ', array_map('oride_label', $preview['functions'])) ?: 'Sin indicar') ?></p>
        <?php foreach ($preview['pairs'] as $pp): ?><p>B · <?= h(oride_label($pp['b'])) ?><br>C · <?= h($pp['c'] ? oride_label($pp['c']) : 'Sin precisar') ?></p><?php endforeach; ?>
        <p>D · <?= h(oride_label($preview['documentation'])) ?></p>
      <?php else: ?>
        <p>Guarda el uso para ver su clasificación ORIDE. Puedes guardarlo tantas veces como quieras antes de emitir.</p>
      <?php endif; ?>
    </div></div>
    <?php if ($preview) foreach ($preview['issues'] as $i) echo alert(($i['severity'] === 'error' ? 'Pendiente: ' : 'Aviso: ') . $i['message'], $i['severity'] === 'error' ? 'warn' : 'info'); ?>
    <p class="meta">La clasificación se calcula con reglas deterministas a partir de tus respuestas. No puedes editarla directamente: si no te parece correcta, revisa las respuestas.</p>
  </aside>
</div>
<?php page_end();
