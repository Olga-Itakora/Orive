<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';
require_once __DIR__ . '/../includes/declarations.php';
require_once __DIR__ . '/../includes/views.php';

$admin = require_admin();
const INC_STATUS = ['OPEN' => ['Abierta', 'warn'], 'IN_PROGRESS' => ['En curso', 'info'], 'RESOLVED' => ['Resuelta', 'ok'], 'REJECTED' => ['Desestimada', 'neutral']];
$id = (int) (get('id') ?: post('id'));

if (is_post()) {
    csrf_check();
    $inc = q1('SELECT * FROM incidents WHERE id = ?', [$id]);
    if (!$inc) not_found();
    $self = 'admin/incidencias.php?id=' . $id;
    if (post('action') === 'update') {
        $status = isset(INC_STATUS[post('status')]) ? post('status') : $inc['status'];
        q('UPDATE incidents SET status = ?, resolution = ?, updated_at = ? WHERE id = ?', [$status, nullable(post('resolution'), 4000), now(), $id]);
        audit('incident.update', 'incident', (string) $id, (int) $admin['id'], ['status' => $status]);
        flash('ok', 'Incidencia actualizada.');
    } elseif (post('action') === 'withdraw') {
        $vid = (int) qv('SELECT id FROM declaration_versions WHERE public_id = ?', [$inc['public_id']]);
        $reason = mb_substr(post('reason'), 0, 500);
        if ($reason === '') form_fail(['reason' => 'Indica el motivo; quedará en el registro de auditoría.'], $self);
        try {
            withdraw_publication($vid, (int) $admin['id'], null, $reason);
            q('UPDATE incidents SET status = "RESOLVED", resolution = CONCAT(COALESCE(resolution, ""), ?), updated_at = ? WHERE id = ?', ["\nPublicación retirada por administración: " . $reason, now(), $id]);
            flash('ok', 'Publicación retirada. La declaración se conserva y sigue siendo verificable con el documento.');
        } catch (DomainError $e) {
            flash('danger', $e->getMessage());
        }
    }
    redirect($self);
}

if ($id) {
    $inc = q1('SELECT i.*, u.email user_email FROM incidents i LEFT JOIN users u ON u.id = i.user_id WHERE i.id = ?', [$id]);
    if (!$inc) not_found();
    [$errors] = form_state();
    $pub = $inc['public_id'] ? q1('SELECT id, visibility, status FROM declaration_versions WHERE public_id = ?', [$inc['public_id']]) : null;
    page_start('Incidencia #' . $id, ['area' => 'admin']);
    page_header('Incidencia #' . $id . ' · ' . L_INCIDENT[$inc['type']], null, ['admin/incidencias.php', 'Incidencias']);
    ?>
    <div class="container grid-main-aside">
      <section class="card">
        <dl class="dl"><?= dl_row('Estado', badge(...INC_STATUS[$inc['status']])) ?><?= dl_row('Recibida', fecha($inc['created_at'], true)) ?><?= dl_row('Nombre', h($inc['name'] ?: '—')) ?><?= dl_row('Correo', h($inc['email'])) ?><?= $inc['user_email'] ? dl_row('Cuenta', h($inc['user_email'])) : '' ?>
        <?= $inc['public_id'] ? dl_row('Declaración', '<a class="mono" href="' . u('verificar.php?id=' . $inc['public_id']) . '">' . h($inc['public_id']) . '</a>' . ($pub ? ' · ' . visibility_badge($pub['visibility']) : ' · no existe')) : '' ?></dl>
        <h2>Mensaje</h2><p class="prewrap"><?= h($inc['message']) ?></p>
        <?php if ($pub && $pub['visibility'] === 'PUBLIC'): ?>
          <?= confirm_button('admin/incidencias.php', ['action' => 'withdraw', 'id' => $id], 'Retirar la publicación', '¿Retirar la publicación de ' . $inc['public_id'] . '?', 'Dejará de mostrarse públicamente. La declaración no se borra ni se altera y su autor podrá verla en su panel.', 'Retirar publicación', false, field('reason', 'Motivo', ['type' => 'textarea', 'rows' => 3, 'required' => true, 'max' => 500, 'id' => 'withdraw-reason', 'error' => $errors['reason'] ?? null])) ?>
        <?php endif; ?>
      </section>
      <section class="card"><h2>Gestión</h2>
        <form method="post" action="<?= u('admin/incidencias.php') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $id ?>">
          <?= field('status', 'Estado', ['type' => 'select', 'options' => array_map(fn($s) => $s[0], INC_STATUS), 'value' => $inc['status']]) ?>
          <?= field('resolution', 'Notas y resolución', ['type' => 'textarea', 'rows' => 6, 'optional' => true, 'value' => $inc['resolution'] ?? '', 'hint' => 'Uso interno. Para solicitudes RGPD, anota la respuesta y la fecha (plazo: un mes).']) ?>
          <button class="btn btn-primary">Guardar cambios</button>
        </form>
      </section>
    </div>
    <?php
    page_end();
    exit;
}

$status = isset(INC_STATUS[get('estado')]) ? get('estado') : '';
$page = max(1, (int) get('page', '1'));
$per = 25;
$where = $status ? 'WHERE status = ?' : "WHERE status IN ('OPEN','IN_PROGRESS')";
$args = $status ? [$status] : [];
$total = (int) qv("SELECT COUNT(*) FROM incidents $where", $args);
$rows = q("SELECT * FROM incidents $where ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args)->fetchAll();
page_start('Incidencias', ['area' => 'admin']);
page_header('Incidencias', 'Mensajes de contacto, comunicaciones de contenido y solicitudes de protección de datos.');
?>
<div class="container stack">
  <nav class="tabs" aria-label="Filtrar por estado"><ul>
    <li><a href="<?= u('admin/incidencias.php') ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>Pendientes</a></li>
    <?php foreach (INC_STATUS as $k => $s): ?><li><a href="<?= u('admin/incidencias.php?estado=' . $k) ?>"<?= $status === $k ? ' aria-current="page"' : '' ?>><?= h($s[0]) ?></a></li><?php endforeach; ?>
  </ul></nav>
  <div class="table-frame">
    <?php if (!$rows): ?><div class="empty"><h2>No hay incidencias <?= $status ? 'en este estado' : 'pendientes' ?></h2><p class="muted small">Las nuevas llegarán desde el formulario de contacto.</p></div>
    <?php else: ?><div class="table-scroll"><table>
      <thead><tr><th scope="col">Asunto</th><th scope="col">Tipo</th><th scope="col">Estado</th><th scope="col">Recibida</th></tr></thead>
      <tbody><?php foreach ($rows as $r): ?><tr>
        <td><a class="row-link" href="<?= u('admin/incidencias.php?id=' . $r['id']) ?>">#<?= (int) $r['id'] ?> · <?= h(mb_strimwidth($r['message'], 0, 70, '…')) ?></a><br><span class="muted"><?= h($r['email']) ?></span></td>
        <td><?= h(L_INCIDENT[$r['type']]) ?></td><td><?= badge(...INC_STATUS[$r['status']]) ?></td><td class="muted"><?= fecha($r['created_at']) ?></td>
      </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
    <?= pagination($page, (int) ceil($total / $per), fn($p) => u('admin/incidencias.php') . '?' . http_build_query(array_filter(['estado' => $status, 'page' => $p]))) ?>
  </div>
</div>
<?php page_end();
