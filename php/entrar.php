<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) redirect('autor/index.php');
$next = get('next');
$safeNext = preg_match('#^' . preg_quote(oride_base_path(), '#') . '/(autor|admin)/#', $next) ? $next : u('autor/index.php');

if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    $pass = (string) ($_POST['password'] ?? '');
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Escribe un correo con el formato nombre@dominio.com.';
    if ($pass === '') $errors['password'] = 'Escribe tu contraseña.';
    if ($errors) form_fail($errors, 'entrar.php?next=' . rawurlencode($next));
    if (!rate_limit('login-ip', client_ip(), 30, 900) || !rate_limit('login-email', $email, 10, 900)) {
        form_fail([], 'entrar.php', 'Demasiados intentos. Espera unos minutos y vuelve a intentarlo.');
    }
    $u = q1('SELECT * FROM users WHERE email = ?', [$email]);
    if (!$u || $u['disabled_at'] || !password_verify($pass, $u['password_hash'])) {
        if (!$u) password_verify($pass, password_hash('oride-dummy', PASSWORD_DEFAULT)); // tiempo constante
        if ($u) audit('user.login_failed', 'user', (string) $u['id']);
        form_fail([], 'entrar.php?next=' . rawurlencode($next), 'El correo y la contraseña no coinciden con ninguna cuenta activa. Revísalos o recupera el acceso con «He olvidado mi contraseña».');
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
    }
    login_user($u);
    audit('user.login', 'user', (string) $u['id'], (int) $u['id']);
    header('Location: ' . $safeNext, true, 303);
    exit;
}

[$errors, $old] = form_state();
page_start('Iniciar sesión', ['area' => 'auth']);
?>
<div class="card">
  <h1>Iniciar sesión</h1>
  <p class="muted small">Accede a tus obras, borradores y declaraciones.</p>
  <?php if (get('reset')) echo alert('Contraseña restablecida. Ya puedes iniciar sesión.', 'ok'); ?>
  <form method="post" action="<?= h(u('entrar.php') . '?next=' . rawurlencode($next)) ?>">
    <?= csrf_field() ?>
    <?= field('email', 'Correo electrónico', ['type' => 'email', 'required' => true, 'value' => $old['email'] ?? '', 'error' => $errors['email'] ?? null, 'attrs' => ' autocomplete="email"']) ?>
    <?= field('password', 'Contraseña', ['type' => 'password', 'required' => true, 'error' => $errors['password'] ?? null, 'attrs' => ' autocomplete="current-password"']) ?>
    <button class="btn btn-primary btn-lg">Iniciar sesión</button>
    <p class="after"><a class="link-44" href="<?= u('recuperar.php') ?>">He olvidado mi contraseña</a></p>
  </form>
</div>
<p class="after">¿Aún no tienes cuenta? <a class="link-44" href="<?= u('registro.php') ?>">Crear cuenta</a></p>
<p class="after"><a class="link-44" href="<?= u('index.php') ?>">Volver al inicio</a></p>
<?php page_end();
