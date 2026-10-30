<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$sent = false;
if (is_post()) {
    csrf_check();
    if (!rate_limit('forgot', client_ip(), 5, 3600)) form_fail([], 'recuperar.php', 'Se ha alcanzado el límite de solicitudes por hora. Vuelve a intentarlo más tarde.');
    $email = strtolower(post('email'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) form_fail(['email' => 'Escribe un correo con el formato nombre@dominio.com.'], 'recuperar.php');
    $u = q1('SELECT * FROM users WHERE email = ? AND disabled_at IS NULL', [$email]);
    if ($u) {
        $token = bin2hex(random_bytes(32));
        q('INSERT INTO password_resets (token_hash, user_id, expires_at, created_at) VALUES (?, ?, ?, ?)', [hash('sha256', $token), $u['id'], gmdate('Y-m-d H:i:s', time() + 3600), now()]);
        send_mail($u['email'], 'Recupera tu acceso a ORIDE', "Hola:\n\nPara elegir una nueva contraseña, abre este enlace (válido durante una hora y de un solo uso):\n\n" . abs_url('restablecer.php?t=' . $token) . "\n\nSi no lo has pedido tú, ignora este mensaje.\n\nORIDE");
        audit('user.password_reset_requested', 'user', (string) $u['id']);
    }
    $sent = true;
}
[$errors, $old] = form_state();
page_start('Recuperar acceso', ['area' => 'auth']);
?>
<div class="card">
  <h1>Recuperar acceso</h1>
  <?php if ($sent): ?>
    <?= alert('Si existe una cuenta con ese correo, recibirás un enlace para restablecer la contraseña. El enlace caduca en una hora.', 'ok') ?>
  <?php else: ?>
    <p class="muted small">Te enviaremos un enlace de un solo uso, válido durante una hora.</p>
    <form method="post" action="<?= u('recuperar.php') ?>">
      <?= csrf_field() ?>
      <?= field('email', 'Correo electrónico', ['type' => 'email', 'required' => true, 'value' => $old['email'] ?? '', 'error' => $errors['email'] ?? null, 'attrs' => ' autocomplete="email"']) ?>
      <button class="btn btn-primary btn-lg">Enviar enlace de recuperación</button>
    </form>
  <?php endif; ?>
</div>
<p class="after"><a class="link-44" href="<?= u('entrar.php') ?>">Volver a iniciar sesión</a></p>
<?php page_end();
