<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$admin = require_admin();
if (is_post()) {
    csrf_check();
    $id = (int) post('user_id');
    $target = q1('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$target) not_found();
    if ($id === (int) $admin['id']) {
        flash('danger', 'No puedes cambiar tu propia cuenta desde aquí; pide a otra persona administradora que lo haga.');
        redirect('admin/usuarios.php');
    }
    switch (post('action')) {
        case 'role':
            $role = post('role') === 'ADMIN' ? 'ADMIN' : 'AUTHOR';
            q('UPDATE users SET role = ?, session_version = session_version + 1, updated_at = ? WHERE id = ?', [$role, now(), $id]);
            audit('user.role', 'user', (string) $id, (int) $admin['id'], ['role' => $role]);
            flash('ok', 'Rol actualizado. La persona tendrá que volver a iniciar sesión.');
            break;
        case 'disable':
            q('UPDATE users SET disabled_at = ?, session_version = session_version + 1, updated_at = ? WHERE id = ?', [now(), now(), $id]);
            audit('user.disable', 'user', (string) $id, (int) $admin['id']);
            flash('ok', 'Cuenta desactivada. Sus declaraciones emitidas se conservan y siguen siendo verificables.');
            break;
        case 'enable':
            q('UPDATE users SET disabled_at = NULL, updated_at = ? WHERE id = ?', [now(), $id]);
            audit('user.enable', 'user', (string) $id, (int) $admin['id']);
            flash('ok', 'Cuenta reactivada.');
            break;
    }
    redirect('admin/usuarios.php?' . http_build_query(array_filter(['q' => post('q'), 'page' => post('page')])));
}

$q = mb_substr(get('q'), 0, 100);
$page = max(1, (int) get('page', '1'));
$per = 25;
$where = $q !== '' ? 'WHERE u.email LIKE ? OR u.display_name LIKE ?' : '';
$args = $q !== '' ? ['%' . $q . '%', '%' . $q . '%'] : [];
$total = (int) qv("SELECT COUNT(*) FROM users u $where", $args);
$rows = q("SELECT u.*, (SELECT COUNT(*) FROM works w WHERE w.owner_id = u.id) works, (SELECT COUNT(*) FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id WHERE d.owner_id = u.id AND v.status <> 'DRAFT') issued FROM users u $where ORDER BY u.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args)->fetchAll();

page_start('Usuarios', ['area' => 'admin']);
page_header('Usuarios', 'Desactivar una cuenta impide el acceso, pero no borra sus declaraciones emitidas.');
?>
<div class="container stack">
  <?= search_form('admin/usuarios.php', $q, 'Buscar usuarios', 'Por correo o nombre.') ?>
  <div class="table-frame">
    <?php if (!$rows): ?>
      <div class="empty"><h2>Ningún usuario coincide con «<?= h($q) ?>»</h2><a class="btn btn-secondary" href="<?= u('admin/usuarios.php') ?>">Borrar la búsqueda</a></div>
    <?php else: ?>
    <div class="table-scroll"><table>
      <thead><tr><th scope="col">Persona</th><th scope="col">Rol</th><th scope="col" class="t-num">Obras</th><th scope="col" class="t-num">Emitidas</th><th scope="col">Alta</th><th scope="col">Estado</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
      <tbody><?php foreach ($rows as $r): $self = (int) $r['id'] === (int) $admin['id']; $hid = ['user_id' => $r['id'], 'q' => $q, 'page' => $page]; ?>
        <tr><td><?= h($r['display_name']) ?><br><span class="muted"><?= h($r['email']) ?></span></td>
        <td><?= $r['role'] === 'ADMIN' ? badge('Administración', 'info') : 'Autor/a' ?></td>
        <td class="t-num"><?= (int) $r['works'] ?></td><td class="t-num"><?= (int) $r['issued'] ?></td><td class="muted"><?= fecha($r['created_at']) ?></td>
        <td><?= $r['disabled_at'] ? badge('Desactivada', 'danger') : 'Activa' ?></td>
        <td class="row-actions"><?php if ($self): ?><span class="muted small">Tu cuenta</span><?php else: ?>
          <?= confirm_button('admin/usuarios.php', $hid + ['action' => 'role', 'role' => $r['role'] === 'ADMIN' ? 'AUTHOR' : 'ADMIN'], $r['role'] === 'ADMIN' ? 'Quitar administración' : 'Hacer administrador/a', ($r['role'] === 'ADMIN' ? '¿Quitar el rol de administración a ' : '¿Dar el rol de administración a ') . $r['display_name'] . '?', 'El cambio cierra sus sesiones abiertas.', $r['role'] === 'ADMIN' ? 'Quitar administración' : 'Dar administración') ?>
          <?php if ($r['disabled_at']): ?>
            <form method="post" action="<?= u('admin/usuarios.php') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="enable"><input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>"><button class="btn btn-link">Reactivar</button></form>
          <?php else: ?>
            <?= confirm_button('admin/usuarios.php', $hid + ['action' => 'disable'], 'Desactivar', '¿Desactivar la cuenta de ' . $r['display_name'] . '?', 'No podrá iniciar sesión y se cerrarán sus sesiones. Sus declaraciones emitidas se conservan y siguen siendo verificables.', 'Desactivar cuenta') ?>
          <?php endif; ?>
        <?php endif; ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
    <?= pagination($page, (int) ceil($total / $per), fn($p) => u('admin/usuarios.php') . '?' . http_build_query(array_filter(['q' => $q, 'page' => $p]))) ?>
  </div>
</div>
<?php page_end();
