<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$user = require_login();
$q = mb_substr(get('q'), 0, 100);
$page = max(1, (int) get('page', '1'));
$per = 20;
$where = 'w.owner_id = ?' . ($q !== '' ? ' AND (w.title LIKE ? OR w.subtitle LIKE ?)' : '');
$params = $q !== '' ? [$user['id'], "%$q%", "%$q%"] : [$user['id']];
$total = (int) qv("SELECT COUNT(*) FROM works w WHERE $where", $params);
$rows = q("SELECT w.*, c.label_es, (SELECT COUNT(*) FROM work_components k WHERE k.work_id = w.id) AS ncomp, (SELECT COUNT(*) FROM declarations d WHERE d.work_id = w.id) AS ndecl
           FROM works w JOIN work_categories c ON c.ckey = w.category_key WHERE $where ORDER BY w.updated_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();
$any = $q === '' ? $total : (int) qv('SELECT COUNT(*) FROM works WHERE owner_id = ?', [$user['id']]);

page_start('Mis obras', ['area' => 'author']);
page_header('Mis obras', 'Cada obra puede tener componentes (texto, imágenes, audio…) y una o varias declaraciones con sus versiones.', null, '<a class="btn btn-primary" href="' . u('autor/obra.php') . '">Registrar obra</a>');
?>
<div class="container stack">
  <?= search_form('autor/obras.php', $q, 'Buscar obras') ?>
  <div class="table-frame">
    <?php if (!$rows): ?>
      <div class="empty">
        <?php if (!$any): ?>
          <h2>Aquí aparecerán tus obras</h2><p class="muted small">Registra una obra para describir su proceso creativo y emitir su declaración de transparencia.</p><a class="btn btn-primary" href="<?= u('autor/obra.php') ?>">Registrar mi primera obra</a>
        <?php else: ?>
          <h2>Ninguna obra coincide con «<?= h($q) ?>»</h2><p class="muted small">Prueba con otra palabra del título o del subtítulo.</p><a class="btn btn-secondary" href="<?= u('autor/obras.php') ?>">Borrar la búsqueda</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-scroll"><table>
        <caption class="sr-only">Obras, ordenadas por última modificación</caption>
        <thead><tr><th scope="col">Obra</th><th scope="col">Tipo</th><th scope="col">Estado</th><th scope="col">Componentes</th><th scope="col">Declaraciones</th><th scope="col" aria-sort="descending">Modificada</th></tr></thead>
        <tbody><?php foreach ($rows as $w): ?>
          <tr><td><a class="row-link" href="<?= u('autor/obra.php?id=' . $w['id']) ?>"><?= h($w['title']) ?></a><?= $w['subtitle'] ? '<br><span class="muted">' . h($w['subtitle']) . '</span>' : '' ?></td>
          <td><?= h($w['label_es']) ?></td><td><?= h(L_WORK_STATUS[$w['status']]) ?></td><td><?= (int) $w['ncomp'] ?></td><td><?= (int) $w['ndecl'] ?></td><td class="muted"><?= fecha($w['updated_at']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
    <?= pagination($page, (int) ceil($total / $per), fn($p) => u('autor/obras.php') . '?' . http_build_query(array_filter(['q' => $q, 'page' => $p]))) ?>
  </div>
</div>
<?php page_end();
