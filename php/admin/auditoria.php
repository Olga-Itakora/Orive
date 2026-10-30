<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

require_admin();
$action = mb_substr(get('accion'), 0, 60);
$page = max(1, (int) get('page', '1'));
$per = 50;
$where = $action !== '' ? 'WHERE a.action LIKE ?' : '';
$args = $action !== '' ? [$action . '%'] : [];
$total = (int) qv("SELECT COUNT(*) FROM audit_log a $where", $args);
$rows = q("SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.actor_id $where ORDER BY a.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args)->fetchAll();
$actions = q('SELECT DISTINCT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
page_start('Auditoría', ['area' => 'admin']);
page_header('Registro de auditoría', 'Solo lectura. Las direcciones IP se guardan como huella no reversible.');
?>
<div class="container stack">
  <form class="filters" method="get" action="<?= u('admin/auditoria.php') ?>">
    <?= field('accion', 'Acción', ['type' => 'select', 'options' => ['' => 'Todas'] + array_combine($actions, $actions), 'value' => $action]) ?>
    <button class="btn btn-secondary">Filtrar</button>
  </form>
  <div class="table-frame">
    <?php if (!$rows): ?><div class="empty"><h2>No hay eventos<?= $action ? ' de este tipo' : '' ?></h2><?php if ($action): ?><a class="btn btn-secondary" href="<?= u('admin/auditoria.php') ?>">Ver todos</a><?php endif; ?></div>
    <?php else: ?><div class="table-scroll"><table class="small">
      <thead><tr><th scope="col">Fecha</th><th scope="col">Acción</th><th scope="col">Objeto</th><th scope="col">Quién</th><th scope="col">Detalles</th></tr></thead>
      <tbody><?php foreach ($rows as $r): ?><tr><td class="muted"><?= fecha($r['created_at'], true) ?></td><td class="mono"><?= h($r['action']) ?></td><td><?= h($r['entity']) ?> <span class="mono"><?= h((string) $r['entity_id']) ?></span></td><td><?= h($r['email'] ?? '—') ?></td><td class="mono meta break"><?= h((string) $r['metadata']) ?></td></tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
    <?= pagination($page, (int) ceil($total / $per), fn($p) => u('admin/auditoria.php') . '?' . http_build_query(array_filter(['accion' => $action, 'page' => $p]))) ?>
  </div>
</div>
<?php page_end();
