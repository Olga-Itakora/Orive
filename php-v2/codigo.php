<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$c = mb_substr(get('c'), 0, 60);
$e = $c !== '' ? oride_explain($c) : null;
page_start('Código ORIDE', 'Qué significa cada letra y cada número del Código ORIDE.');
?>
<div class="container page">
  <div class="narrow-text">
    <h1>Código ORIDE</h1>
    <p class="lead">Tres letras, tres preguntas. Los números son categorías, no puntuaciones.</p>
    <p class="muted">Si hay varias respuestas en una dimensión, sus cifras se juntan en orden: <span class="mono">A1</span> y <span class="mono">A3</span> se escriben <span class="mono">A13</span>.</p>
  </div>

  <form class="card decoder" method="get" action="<?= u('codigo.php') ?>" data-decoder data-taxonomy="<?= h(json_encode(ORIDE_DIMS, JSON_UNESCAPED_UNICODE)) ?>">
    <label for="c">Escribe un código para leerlo</label>
    <div class="search-row"><input id="c" name="c" class="mono" value="<?= h($c) ?>" maxlength="60" placeholder="A24B13C15" autocomplete="off" spellcheck="false"><button class="btn btn-primary">Leer código</button></div>
  </form>
  <div data-decoder-out>
    <?php if ($e && !$e['valid']) echo '<div class="alert alert-warn" role="status">' . h($e['error']) . '</div>'; ?>
    <?php if ($e && $e['valid']): ?>
      <section class="code-card"><div class="inner"><p class="label">Código ORIDE</p>
        <p class="big code-line"><?php foreach (['A', 'B', 'C'] as $d) echo '<span class="seg" data-seg="' . $d . '">' . h(join_values($d, $e['parts'][$d])) . '</span>'; ?></p>
        <div class="dims"><?= explain_list($e['parts']['A'], $e['parts']['B'], $e['parts']['C'], false) ?></div>
      </div></section>
    <?php endif; ?>
  </div>

  <div class="dim-tables">
    <?php foreach (ORIDE_DIMS as $dim => $d): ?>
      <section class="card">
        <h2><span class="dim-tag"><?= $dim ?></span><?= h($d['name']) ?></h2>
        <dl class="values">
          <?php foreach ($d['values'] as $k => $v): ?>
            <div><dt class="mono"><?= $k ?></dt><dd><b><?= h($v[0]) ?></b><?= $v[1] !== '' ? '<br><span class="muted small">' . h($v[1]) . '</span>' : '' ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </section>
    <?php endforeach; ?>
  </div>

  <div class="narrow-text">
    <h2>Reglas</h2>
    <ul class="rules-list muted">
      <li><span class="mono">A0B0C0</span> significa que no se usó IA.</li>
      <li>Si no se incorporó nada a la obra, B y C son <span class="mono">B0C0</span>.</li>
      <li>Cada tipo de aportación (B) tiene su intervención (C).</li>
      <li>Próximamente: dimensión D, documentación y trazabilidad.</li>
    </ul>
  </div>
</div>
<?php page_end();
