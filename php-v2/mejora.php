<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

/*
 * Formulario «¿Cómo puede mejorar el Código ORIDE?».
 * Sin sesiones ni cookies: la protección antispam usa una marca de tiempo firmada,
 * un campo trampa y un límite global de mensajes por hora. Nada se guarda en el servidor.
 */

function form_token(): string
{
    $t = (string) time();
    return $t . '.' . hash_hmac('sha256', 'mejora|' . $t, app_secret());
}

function token_ok(string $tok): bool
{
    [$t, $sig] = array_pad(explode('.', $tok, 2), 2, '');
    if (!ctype_digit($t) || !hash_equals(hash_hmac('sha256', 'mejora|' . $t, app_secret()), $sig)) return false;
    $age = time() - (int) $t;
    return $age >= 3 && $age <= 86400;
}

/** Límite global (no guarda IP ni datos personales: solo un contador por hora). */
function under_limit(): bool
{
    $file = ORIDE_ROOT . '/storage/limite-correo.json';
    $fp = @fopen($file, 'c+');
    if (!$fp) return true;
    flock($fp, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fp), true) ?: [];
    $hour = gmdate('YmdH');
    $n = ($data['hour'] ?? '') === $hour ? (int) $data['n'] : 0;
    $ok = $n < (int) (cfg('mail_per_hour') ?? 30);
    if ($ok) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode(['hour' => $hour, 'n' => $n + 1]));
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}

function send_feedback(string $name, string $email, string $comment): bool
{
    $from = cfg('mail_from') ?: 'ORIDE <no-reply@' . preg_replace('/:\d+$/', '', preg_replace('/^www\./', '', host())) . '>';
    $subject = '=?UTF-8?B?' . base64_encode('Mejora del Código ORIDE · ' . $name) . '?=';
    $body = "Nombre y apellidos: $name\nEmail: $email\n\nComentario:\n$comment\n\n--\nEnviado desde " . abs_url('mejora.php') . "\n";
    $headers = "From: $from\r\nReply-To: $email\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
    if (cfg('debug')) {
        return file_put_contents(ORIDE_ROOT . '/storage/correo-pruebas.log', "To: " . cfg('mail_to') . "\r\n$headers\r\nSubject: $subject\r\n\r\n$body\n=====\n", FILE_APPEND | LOCK_EX) !== false;
    }
    return @mail((string) cfg('mail_to'), $subject, $body, $headers);
}

$errors = [];
$sent = get('enviado') === '1';
$name = $email = $comment = '';
$failure = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mb_substr(preg_replace('/[\r\n\t]+/', ' ', post('nombre')), 0, 120);
    $email = mb_substr(post('email'), 0, 254);
    $comment = mb_substr(post('comentario'), 0, 5000);
    if (post('web') !== '') { header('Location: ' . u('mejora.php?enviado=1'), true, 303); exit; } // trampa
    if ($name === '') $errors['nombre'] = 'Escribe tu nombre y apellidos.';
    if ($email === '') $errors['email'] = 'Escribe tu correo electrónico.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) $errors['email'] = 'Escribe un correo válido, por ejemplo nombre@dominio.com.';
    if (mb_strlen($comment) < 5) $errors['comentario'] = 'Escribe tu comentario.';
    if (!$errors) {
        if (!token_ok(post('t'))) $failure = 'El formulario ha caducado o se envió demasiado rápido. Vuelve a pulsar «Enviar comentario».';
        elseif (!under_limit()) $failure = 'Ahora mismo hay demasiados mensajes. Inténtalo en un rato o escribe a info@itakora.com.';
        elseif (!send_feedback($name, $email, $comment)) $failure = 'No se ha podido enviar el mensaje. Tu texto sigue aquí: inténtalo de nuevo o escribe a info@itakora.com.';
        else { header('Location: ' . u('mejora.php?enviado=1'), true, 303); exit; }
    }
}

$field = function (string $id, string $label, string $type, string $value, array $extra = []) use ($errors) {
    $err = $errors[$id] ?? '';
    $attrs = ' id="' . $id . '" name="' . $id . '" required aria-describedby="' . $id . '-error"' . ($err ? ' aria-invalid="true"' : '') . ($extra['attrs'] ?? '');
    $input = $type === 'textarea' ? '<textarea' . $attrs . ' rows="6" maxlength="5000">' . h($value) . '</textarea>' : '<input type="' . $type . '"' . $attrs . ' value="' . h($value) . '">';
    return '<div class="field"><label for="' . $id . '">' . h($label) . '</label>' . $input . '<p class="field-error" id="' . $id . '-error"' . ($err ? '' : ' hidden') . '>' . h($err) . '</p></div>';
};

page_start('¿Cómo puede mejorar el Código ORIDE?', 'Envíanos tus ideas para mejorar el Código ORIDE.');
?>
<div class="container narrow-form page">
  <h1>¿Cómo puede mejorar el Código ORIDE?</h1>
  <?php if ($sent): ?>
    <?= alert('Gracias. Hemos recibido tu comentario.', 'ok') ?>
    <p><a class="btn btn-secondary" href="<?= u('index.php') ?>">Volver al inicio</a></p>
  <?php else: ?>
    <p class="muted">Cuéntanos qué cambiarías. Te leemos todos.</p>
    <?php if ($failure) echo alert($failure, 'danger'); ?>
    <form class="card" method="post" action="<?= u('mejora.php') ?>" data-validate>
      <input type="hidden" name="t" value="<?= h(form_token()) ?>">
      <?= $field('nombre', 'Nombre y apellidos', 'text', $name, ['attrs' => ' maxlength="120" autocomplete="name"']) ?>
      <?= $field('email', 'Email', 'email', $email, ['attrs' => ' maxlength="254" autocomplete="email"']) ?>
      <?= $field('comentario', 'Comentario', 'textarea', $comment) ?>
      <div class="hp" aria-hidden="true"><label for="web">No rellenes este campo</label><input id="web" name="web" tabindex="-1" autocomplete="off"></div>
      <p class="small muted">Usaremos estos datos solo para leer tu propuesta y responderte. No se guardan en este sitio. <a href="<?= u('legal.php') ?>">Privacidad</a></p>
      <button class="btn btn-primary">Enviar comentario</button>
    </form>
  <?php endif; ?>
</div>
<?php page_end();
