<?php
/**
 * Instalador de ORIDE (una sola vez). Crea config.php, las tablas, los
 * disparadores de inmutabilidad, los datos iniciales y el administrador.
 * Después de instalar queda bloqueado (storage/instalado.lock).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/install.php';

$lock = ORIDE_ROOT . '/storage/instalado.lock';
if (is_file($lock)) {
    page_start('Instalación', ['area' => 'auth']);
    echo '<div class="card"><h1>ORIDE ya está instalado</h1><p>Por seguridad, el instalador está bloqueado. Puedes eliminar el fichero <code>instalar.php</code> del servidor.</p><p><a class="btn btn-primary" href="' . u('index.php') . '">Ir al inicio</a></p></div>';
    page_end();
    exit;
}

// Requisitos del servidor (en IONOS se eligen en «Hosting» → «Versión de PHP»).
$missing = [];
if (PHP_VERSION_ID < 80200) $missing[] = 'PHP 8.2 o superior (versión actual: ' . PHP_VERSION . ')';
foreach (['pdo_mysql' => 'PDO MySQL', 'gd' => 'GD (imágenes de los códigos QR)', 'mbstring' => 'mbstring', 'json' => 'JSON'] as $ext => $label) {
    if (!extension_loaded($ext)) $missing[] = 'Extensión ' . $label;
}
if (!is_writable(ORIDE_ROOT) && !is_file(ORIDE_ROOT . '/config.php')) $missing[] = 'Permiso de escritura en la carpeta del sitio (para crear config.php)';
if (!is_writable(ORIDE_ROOT . '/storage')) $missing[] = 'Permiso de escritura en la carpeta storage/';
if ($missing) {
    page_start('Instalación', ['area' => 'auth']);
    echo '<div class="card"><h1>Falta algo en el servidor</h1><p>Antes de instalar ORIDE hay que resolver lo siguiente:</p><ul>';
    foreach ($missing as $m) echo '<li>' . h($m) . '</li>';
    echo '</ul><p class="muted small">En IONOS, la versión de PHP se cambia en el panel: «Hosting» → «Versión de PHP». Después recarga esta página.</p></div>';
    page_end();
    exit;
}

$errors = [];
$result = null;
$defaults = [
    'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'app_url' => (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . oride_base_path(),
    'mail_from' => 'ORIDE <no-reply@' . preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost') . '>', 'admin_name' => '', 'admin_email' => '',
];

if (is_post()) {
    csrf_check();
    $in = array_map(fn($k) => post($k), array_combine(array_keys($defaults), array_keys($defaults)));
    $pass = (string) ($_POST['admin_password'] ?? '');
    if (!filter_var($in['admin_email'], FILTER_VALIDATE_EMAIL)) $errors['admin_email'] = 'Escribe un correo válido.';
    if (mb_strlen($pass) < 12) $errors['admin_password'] = 'La contraseña del administrador debe tener al menos 12 caracteres.';
    if ($in['admin_name'] === '') $errors['admin_name'] = 'Indica un nombre.';
    if (!preg_match('#^https?://#', $in['app_url'])) $errors['app_url'] = 'Escribe la URL completa, p. ej. https://oridecode.com';
    foreach (['db_host', 'db_name', 'db_user'] as $k) if ($in[$k] === '') $errors[$k] = 'Este dato es obligatorio.';
    if (!$errors) {
        $config = [
            'db_host' => $in['db_host'], 'db_port' => (int) $in['db_port'], 'db_name' => $in['db_name'], 'db_user' => $in['db_user'], 'db_pass' => (string) ($_POST['db_pass'] ?? ''),
            'app_url' => rtrim($in['app_url'], '/'), 'app_secret' => bin2hex(random_bytes(32)), 'mail_from' => $in['mail_from'],
            'evidence_dir' => ORIDE_ROOT . '/storage/evidencias', 'evidence_max_bytes' => 10 * 1024 * 1024, 'debug' => false,
        ];
        try {
            $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_port'], $config['db_name']), $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("SET time_zone = '+00:00'");
            run_sql_file($pdo, ORIDE_ROOT . '/sql/schema.sql');
            $triggers = true;
            $triggerError = '';
            try {
                run_sql_file($pdo, ORIDE_ROOT . '/sql/triggers.sql');
            } catch (PDOException $e) {
                $triggers = false;
                $triggerError = $e->getMessage();
            }
            oride_seed($pdo);
            $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $exists->execute([strtolower($in['admin_email'])]);
            if (!$exists->fetch()) {
                $pdo->prepare('INSERT INTO users (email, password_hash, role, display_name, created_at, updated_at) VALUES (?, ?, "ADMIN", ?, ?, ?)')
                    ->execute([strtolower($in['admin_email']), password_hash($pass, PASSWORD_DEFAULT), $in['admin_name'], now(), now()]);
            }
            $written = @file_put_contents(ORIDE_ROOT . '/config.php', config_php($config)) !== false;
            if ($written) {
                @chmod(ORIDE_ROOT . '/config.php', 0600);
                file_put_contents($lock, gmdate('c'));
            }
            $result = ['triggers' => $triggers, 'trigger_error' => $triggerError, 'written' => $written, 'config' => config_php($config)];
        } catch (PDOException $e) {
            $errors['_db'] = 'No se ha podido conectar o crear las tablas: ' . $e->getMessage();
        }
    }
} else {
    $in = $defaults;
}

page_start('Instalación', ['area' => 'auth']);
echo '<div class="card wide-card">';
if ($result) {
    echo '<h1>Instalación completada</h1>';
    echo alert('Tablas y datos iniciales creados. Ya puedes iniciar sesión con la cuenta de administrador.', 'ok');
    echo $result['triggers']
        ? alert('Los disparadores de inmutabilidad se han creado: la base de datos impide modificar declaraciones emitidas.', 'ok')
        : alert('Tu proveedor no ha permitido crear disparadores (' . $result['trigger_error'] . '). La aplicación impide igualmente modificar declaraciones emitidas; puedes pedir a IONOS que habilite el permiso TRIGGER o importar sql/triggers.sql desde phpMyAdmin más adelante.', 'warn');
    if (!$result['written']) {
        echo alert('No se ha podido escribir config.php en el servidor. Crea ese fichero en la carpeta de ORIDE con este contenido y vuelve a cargar la página:', 'warn');
        echo '<pre class="config">' . h($result['config']) . '</pre>';
    } else {
        echo '<p>Por seguridad, elimina ahora <code>instalar.php</code> del servidor (ya está bloqueado).</p><p><a class="btn btn-primary" href="' . u('entrar.php') . '">Iniciar sesión</a></p>';
    }
} else {
    echo '<h1>Instalar ORIDE</h1><p class="muted">Necesitas los datos de la base de datos MySQL que has creado en IONOS (panel → Hosting → Bases de datos).</p>';
    if (isset($errors['_db'])) echo alert($errors['_db'], 'danger');
    echo '<form method="post">' . csrf_field();
    echo '<h2>Base de datos</h2><div class="form-grid">';
    echo field('db_host', 'Servidor (host)', ['required' => true, 'value' => $in['db_host'], 'error' => $errors['db_host'] ?? null, 'hint' => 'En IONOS suele ser algo como db5000000000.hosting-data.io']);
    echo field('db_port', 'Puerto', ['value' => $in['db_port'], 'optional' => true]);
    echo field('db_name', 'Nombre de la base de datos', ['required' => true, 'value' => $in['db_name'], 'error' => $errors['db_name'] ?? null]);
    echo field('db_user', 'Usuario', ['required' => true, 'value' => $in['db_user'], 'error' => $errors['db_user'] ?? null]);
    echo field('db_pass', 'Contraseña de la base de datos', ['type' => 'password', 'attrs' => ' autocomplete="off"']);
    echo '</div><h2>Sitio</h2>';
    echo field('app_url', 'URL pública del sitio', ['required' => true, 'value' => $in['app_url'], 'error' => $errors['app_url'] ?? null, 'hint' => 'Se imprime en los PDF y en los códigos QR. Usa https.']);
    echo field('mail_from', 'Remitente de los correos', ['value' => $in['mail_from'], 'optional' => true]);
    echo '<h2>Administrador</h2>';
    echo field('admin_name', 'Nombre', ['required' => true, 'value' => $in['admin_name'], 'error' => $errors['admin_name'] ?? null]);
    echo field('admin_email', 'Correo', ['type' => 'email', 'required' => true, 'value' => $in['admin_email'], 'error' => $errors['admin_email'] ?? null]);
    echo field('admin_password', 'Contraseña', ['type' => 'password', 'required' => true, 'min' => 12, 'error' => $errors['admin_password'] ?? null, 'hint' => 'Al menos 12 caracteres.', 'attrs' => ' autocomplete="new-password"']);
    echo '<button class="btn btn-primary btn-lg">Instalar ORIDE</button></form>';
}
echo '</div>';
page_end();
