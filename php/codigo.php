<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/partials/explorer.php';

$c = mb_substr(get('c'), 0, 40);
$lookup = $c !== '' ? oride_explain_code($c) : null;
page_start('ORIDE Code', ['description' => 'Qué significa cada dimensión y valor de ORIDE Code v1.0.']);
?>
<div class="container">
  <p class="eyebrow">ORIDE Code v<?= ORIDE_SCHEME_VERSION ?></p>
  <h1>Una clasificación descriptiva en cuatro dimensiones</h1>
  <p class="lead muted prose">ORIDE Code resume la función de la IA, el tipo de aportación incorporada, la modalidad de intervención humana y el nivel de documentación. Formato compacto: <span class="mono">A#B#C#D#</span>, por ejemplo <span class="mono">A5B3C4D2</span>.</p>
  <?= alert('El código no es un porcentaje de autoría ni una puntuación de originalidad, de mérito o de cumplimiento legal. No se suman ni se promedian. A5 no es «más IA» que A3; C5 no es «más autoría» que C4; D0 no es una declaración menos válida.', 'warn', 'Los números son categorías, no puntuaciones') ?>

  <div class="grid-2">
    <div>
      <h2>Consulta un código</h2>
      <form method="get" action="<?= u('codigo.php') ?>" data-validate>
        <?= field('c', 'Código ORIDE', ['value' => $c, 'max' => 40, 'placeholder' => 'A5B3C4D2']) ?>
        <button class="btn btn-primary">Explicar código</button>
      </form>
      <?php if ($lookup): ?>
        <div class="card" aria-live="polite">
          <?php if ($lookup['valid']): ?>
            <ul class="small"><?php foreach ($lookup['segments'] as $s): ?><li><span class="mono"><?= h($s['code']) ?></span> — <strong><?= h($s['label']) ?></strong><br><span class="muted"><?= h($s['dimension_name']) ?>. <?= h($s['description']) ?></span></li><?php endforeach; ?></ul>
          <?php else: ?>
            <p class="field-error"><?= h(implode(' ', $lookup['errors'])) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <p class="small muted">Un código puede compartirse entre muchas obras: no identifica una declaración. Para consultar una declaración concreta usa su identificador en <a href="<?= u('verificar.php') ?>">Verificar</a>.</p>
    </div>
    <div><?php explorer($lookup && $lookup['valid'] && strlen($lookup['code']) === 8 ? $lookup['code'] : 'A5B3C4D2'); ?></div>
  </div>

  <?php foreach (oride_dimensions() as $k => $dim): ?>
    <section class="section-tight" aria-labelledby="dim-<?= $k ?>">
      <h2 id="dim-<?= $k ?>"><span class="mono"><?= $k ?></span> · <?= h($dim['name']) ?></h2>
      <p class="muted"><?= h($dim['question']) ?> <em><?= h($dim['not']) ?></em></p>
      <div class="table-frame"><div class="table-scroll"><table>
        <thead><tr><th scope="col">Código</th><th scope="col">Significado</th><th scope="col">Ejemplo</th></tr></thead>
        <tbody><?php foreach ($dim['values'] as $code => [$label, $desc, $ex]): ?>
          <tr id="<?= $code ?>"><td class="mono"><?= $code ?></td><td><strong><?= h($label) ?></strong><br><span class="muted"><?= h($desc) ?></span></td><td class="muted"><?= h($ex ?? '—') ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div></div>
    </section>
  <?php endforeach; ?>

  <div class="prose section-tight">
    <h2>Relación entre B y C</h2>
    <p>B responde a «¿qué tipo de aportación de IA se incorporó a la obra?». C responde a «¿qué hizo la persona con esa aportación?». Cada uso conserva sus propias parejas B–C; nunca se empareja el B de un uso con el C de otro.</p>
    <ul>
      <li>La IA analiza un ensayo y sus sugerencias se descartan: <span class="mono">B0 · C0</span></li>
      <li>La IA propone una trama y el autor escribe la novela: <span class="mono">B1 · C5</span></li>
      <li>La IA corrige erratas y el autor acepta los cambios: <span class="mono">B2 · C1</span></li>
      <li>Una ilustración generada se incorpora directamente: <span class="mono">B3 · C1</span></li>
      <li>Una imagen generada se transforma sustancialmente: <span class="mono">B3 · C3</span></li>
      <li>Un texto generado se reelabora profundamente: <span class="mono">B3 · C4</span></li>
    </ul>
    <h2>Perfiles múltiples</h2>
    <p>Una obra puede contener varios usos de IA. ORIDE conserva la clasificación de cada uso, agrupa los usos por componente y genera una síntesis de la obra. Cuando todos los usos coinciden se muestra un código compacto; cuando no, ORIDE <strong>no fabrica</strong> un código eligiendo los números más altos: muestra un perfil compuesto, por ejemplo <span class="mono">A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2</span>, que es una síntesis de valores presentes y no una secuencia de parejas.</p>
    <h2>Cómo se calcula D</h2>
    <ul>
      <li>D1: herramienta identificada por su nombre, al menos una función y una descripción de la actividad (≥ 10 caracteres).</li>
      <li>D2: además, al menos una fase, el resultado descrito (≥ 10) y la intervención humana descrita en cada aportación (≥ 10).</li>
      <li>D3: además, al menos una evidencia registrada. Las evidencias no se auditan.</li>
      <li>La D de la obra es la menor de sus usos. Sin IA: D0, o D2 con una descripción del proceso de al menos 100 caracteres, y D3 si además hay evidencias.</li>
    </ul>
    <h2>Incertidumbre y versionado</h2>
    <p>Si faltan respuestas esenciales la clasificación queda «pendiente» y no puede emitirse. Si se indica que algo no puede precisarse, se registra como «información insuficiente», sin inventar valores. Cada clasificación registra la versión del esquema, la fecha, las respuestas y las reglas aplicadas; las nuevas versiones no modifican los códigos históricos.</p>
  </div>
</div>
<?php page_end();
