<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

require_admin();
$schemes = q('SELECT version, name, status, published_at FROM schemes ORDER BY version DESC')->fetchAll();
page_start('Taxonomía ORIDE', ['area' => 'admin']);
page_header('Taxonomía ORIDE', 'Solo lectura. Una versión publicada del esquema no se modifica: los cambios requieren una nueva versión, para que las declaraciones antiguas sigan interpretándose con el esquema con que se emitieron.');
?>
<div class="container stack">
  <section class="card"><h2>Versiones del esquema</h2>
    <table class="small"><thead><tr><th scope="col">Versión</th><th scope="col">Nombre</th><th scope="col">Estado</th><th scope="col">Publicada</th></tr></thead><tbody>
      <?php foreach ($schemes as $s): ?><tr><td class="mono"><?= h($s['version']) ?></td><td><?= h($s['name']) ?></td><td><?= h($s['status']) ?></td><td class="muted"><?= fecha($s['published_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </section>
  <?php foreach (oride_dimensions() as $dim => $vals): ?>
  <section class="card"><h2><?= h($dim) ?> · <?= h($vals['name']) ?></h2><p class="muted small"><?= h($vals['question']) ?> <?= h($vals['not']) ?></p>
    <table class="small"><thead><tr><th scope="col">Valor</th><th scope="col">Etiqueta</th><th scope="col">Descripción</th></tr></thead><tbody>
      <?php foreach ($vals['values'] as $k => $v): ?><tr><td class="mono"><?= h($k) ?></td><td><?= h($v[0]) ?></td><td class="muted"><?= h($v[1]) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </section>
  <?php endforeach; ?>
  <section class="card"><h2>Reglas de clasificación</h2><dl class="small rules"><?php foreach (ORIDE_RULES as $id => $r): ?><dt class="mono"><?= h($id) ?></dt><dd><?= h($r) ?></dd><?php endforeach; ?></dl></section>
</div>
<?php page_end();
