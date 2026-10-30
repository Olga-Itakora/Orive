<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$token = get('t') ?: post('t');
$record = preg_match('/^[a-f0-9]{64}$/', $token) ? q1('SELECT * FROM password_resets WHERE token_hash = ?', [hash('sha256', $token)]) : null;
$valid = $record && !$record['used_at'] && strtotime($record['expires_at'] . ' UTC') > time();

if (is_post() && $valid) {
    csrf_check();
    $pass = (string) ($_POST['password'] ?? '');
    if (mb_strlen($pass) < 10 || mb_strlen($pass) > 128) form_fail(['password' => 'La contraseña debe tener entre 10 y 128 caracteres.'], 'restablecer.php?t=' . $token);
    if ($pass !== (string) ($_POST['confirm'] ?? '')) form_fail(['confirm' => 'Las dos contraseñas no coinciden. Escríbela de nuevo.'], 'restablecer.php?t=' . $token);
    tx(function () use ($record, $pass) {
        q('UPDATE users SET password_hash = ?, session_version = session_version + 1, updated_at = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), now(), $record['user_id']]);
        q('UPDATE password_resets SET used_at = ? WHERE token_hash = ?', [now(), $record['token_hash']]);
    });
    audit('user.password_reset', 'user', (string) $record['user_id'], (int) $record['user_id']);
    redirect('entrar.php?reset=1');
}
[$errors] = form_state();
page_start('Nueva contraseña', ['area' => 'auth']);
?>
<div class="card">
  <h1>Elige una nueva contraseña</h1>
  <?php if (!$valid): ?>
    <?= alert('El enlace no es válido o ha caducado. Solicita uno nuevo.', 'danger') ?>
    <p><a class="btn btn-primary" href="<?= u('recuperar.php') ?>">Solicitar un enlace nuevo</a></p>
  <?php else: ?>
    <p class="muted small">Al cambiarla se cerrarán todas las sesiones abiertas.</p>
    <form method="post" action="<?= u('restablecer.php') ?>">
      <?= csrf_field() ?><input type="hidden" name="t" value="<?= h($token) ?>">
      <?= field('password', 'Nueva contraseña', ['type' => 'password', 'required' => true, 'min' => 10, 'error' => $errors['password'] ?? null, 'hint' => 'Al menos 10 caracteres.', 'attrs' => ' autocomplete="new-password"']) ?>
      <?= field('confirm', 'Repite la contraseña', ['type' => 'password', 'required' => true, 'error' => $errors['confirm'] ?? null, 'attrs' => ' autocomplete="new-password"']) ?>
      <button class="btn btn-primary btn-lg">Restablecer contraseña</button>
    </form>
  <?php endif; ?>
</div>
<p class="after"><a class="link-44" href="<?= u('entrar.php') ?>">Volver a iniciar sesión</a></p>
<?php page_end();
