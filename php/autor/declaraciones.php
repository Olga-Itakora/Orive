<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$user = require_login();
$q = mb_substr(get('q'), 0, 100);
$page = max(1, (int) get('page', '1'));
$per = 25;
$where = 'd.owner_id = ?' . ($q !== '' ? ' AND (w.title LIKE ? OR v.public_id LIKE ?)' : '');
$params = $q !== '' ? [$user['id'], "%$q%", '%' . strtoupper($q) . '%'] : [$user['id']];
$from = 'FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id JOIN works w ON w.id = d.work_id';
$total = (int) qv("SELECT COUNT(*) $from WHERE $where", $params);
$rows = q("SELECT v.id, v.version_number, v.public_id, v.status, v.visibility, v.issued_at, v.updated_at, v.last_step, w.title $from WHERE $where ORDER BY v.updated_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();

page_start('Mis declaraciones', ['area' => 'author']);
page_header('Mis declaraciones', 'Todas las versiones, emitidas y en borrador. El estado documental (emitida, sustituida, revocada) es independiente de la visibilidad (privada, pública, retirada).');
?>
<div class="container stack">
  <?= search_form('autor/declaraciones.php', $q, 'Buscar declaraciones', 'Por título de la obra o identificador ORD-…') ?>
  <div class="table-frame">
    <?php if (!$rows): ?>
      <div class="empty">
        <?php if ($q !== ''): ?>
          <h2>Ninguna declaración coincide con «<?= h($q) ?>»</h2><a class="btn btn-secondary" href="<?= u('autor/declaraciones.php') ?>">Borrar la búsqueda</a>
        <?php else: ?>
          <h2>Aquí aparecerán tus declaraciones y sus versiones</h2><p class="muted small">Una declaración describe cómo se usó la IA en una obra y puede verificarla cualquiera.</p><a class="btn btn-primary" href="<?= u('autor/obras.php') ?>">Elegir una obra para declarar</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-scroll"><table>
        <caption class="sr-only">Declaraciones, ordenadas por última modificación</caption>
        <thead><tr><th scope="col">Obra</th><th scope="col">Versión</th><th scope="col">Identificador</th><th scope="col" aria-sort="descending">Fecha</th><th scope="col">Estado</th></tr></thead>
        <tbody><?php foreach ($rows as $v): $href = $v['status'] === 'DRAFT' ? 'autor/paso.php?id=' . $v['id'] . '&p=' . ($v['last_step'] ?: 'obra') : 'autor/declaracion.php?id=' . $v['id']; ?>
          <tr><td><a class="row-link" href="<?= u($href) ?>"><?= h($v['title']) ?></a></td><td><?= (int) $v['version_number'] ?></td><td class="mono"><?= h($v['public_id'] ?? '—') ?></td><td class="muted"><?= fecha($v['issued_at'] ?? $v['updated_at']) ?></td>
          <td><span class="badges"><?= status_badge($v['status']) . ($v['status'] !== 'DRAFT' ? visibility_badge($v['visibility']) : '') ?></span></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
    <?= pagination($page, (int) ceil($total / $per), fn($p) => u('autor/declaraciones.php') . '?' . http_build_query(array_filter(['q' => $q, 'page' => $p]))) ?>
  </div>
</div>
<?php page_end();
