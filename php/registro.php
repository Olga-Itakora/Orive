<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) redirect('autor/index.php');

if (is_post()) {
    csrf_check();
    if (!rate_limit('register', client_ip(), 10, 3600)) form_fail([], 'registro.php', 'Se ha alcanzado el límite de registros por hora desde esta conexión. Vuelve a intentarlo más tarde.');
    $name = post('display_name');
    $email = strtolower(post('email'));
    $pass = (string) ($_POST['password'] ?? '');
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 160) $errors['display_name'] = 'Indica tu nombre profesional.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $errors['email'] = 'Escribe un correo con el formato nombre@dominio.com.';
    if (mb_strlen($pass) < 10 || mb_strlen($pass) > 128) $errors['password'] = 'La contraseña debe tener entre 10 y 128 caracteres.';
    if (post('accept') !== 'on') $errors['accept'] = 'Para crear la cuenta, acepta las condiciones de uso y confirma que has leído la política de privacidad.';
    if (!$errors && q1('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors['email'] = 'No se ha podido crear la cuenta con este correo. Si ya tienes cuenta, inicia sesión o recupera el acceso.';
    }
    if ($errors) form_fail($errors, 'registro.php');
    q('INSERT INTO users (email, password_hash, display_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)', [$email, password_hash($pass, PASSWORD_DEFAULT), $name, now(), now()]);
    $u = q1('SELECT * FROM users WHERE id = ?', [db()->lastInsertId()]);
    audit('user.register', 'user', (string) $u['id'], (int) $u['id']);
    login_user($u);
    flash('ok', 'Cuenta creada. Empieza registrando una obra; todo es privado hasta que decidas publicarlo.');
    redirect('autor/index.php');
}

[$errors, $old] = form_state();
page_start('Crear cuenta', ['area' => 'auth']);
?>
<div class="card">
  <h1>Crea tu cuenta ORIDE</h1>
  <p class="muted small">Documenta tu proceso creativo y emite declaraciones verificables. Todo es privado hasta que decidas publicarlo.</p>
  <form method="post" action="<?= u('registro.php') ?>">
    <?= csrf_field() ?>
    <?= field('display_name', 'Nombre profesional', ['required' => true, 'min' => 2, 'max' => 160, 'value' => $old['display_name'] ?? '', 'error' => $errors['display_name'] ?? null, 'hint' => 'Nombre con el que firmas tus obras. Podrás decidir si se publica en cada declaración.', 'attrs' => ' autocomplete="name"']) ?>
    <?= field('email', 'Correo electrónico', ['type' => 'email', 'required' => true, 'value' => $old['email'] ?? '', 'error' => $errors['email'] ?? null, 'hint' => 'Privado. Nunca se publica.', 'attrs' => ' autocomplete="email"']) ?>
    <?= field('password', 'Contraseña', ['type' => 'password', 'required' => true, 'min' => 10, 'error' => $errors['password'] ?? null, 'hint' => 'Al menos 10 caracteres. Una frase larga es más segura y fácil de recordar.', 'attrs' => ' autocomplete="new-password"']) ?>
    <fieldset>
      <label class="check"><input type="checkbox" name="accept" id="f-accept" aria-describedby="f-accept-error"<?= ($old['accept'] ?? '') === 'on' ? ' checked' : '' ?>><span>Acepto las <a href="<?= u('legal.php?doc=condiciones') ?>" target="_blank">condiciones de uso</a> y he leído la <a href="<?= u('legal.php?doc=privacidad') ?>" target="_blank">política de privacidad</a>.</span></label>
      <p class="field-error" id="f-accept-error" role="alert"<?= isset($errors['accept']) ? '' : ' hidden' ?>><?= h($errors['accept'] ?? '') ?></p>
    </fieldset>
    <button class="btn btn-primary btn-lg">Crear cuenta</button>
  </form>
</div>
<p class="after">¿Ya tienes cuenta? <a class="link-44" href="<?= u('entrar.php') ?>">Iniciar sesión</a></p>
<p class="after"><a class="link-44" href="<?= u('index.php') ?>">Volver al inicio</a></p>
<?php page_end();
