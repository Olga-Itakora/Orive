<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

/*
 * Formulario de Contacto.
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
    $subject = '=?UTF-8?B?' . base64_encode('Contacto ORIDE · ' . $name) . '?=';
    $body = "Nombre y apellidos: $name\nEmail: $email\nIdioma: " . lang() . "\n\nMensaje:\n$comment\n\n--\nEnviado desde " . abs_url('mejora.php') . "\n";
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
    if ($name === '') $errors['nombre'] = tr('Escribe tu nombre y apellidos.', 'Enter your full name.');
    if ($email === '') $errors['email'] = tr('Escribe tu correo electrónico.', 'Enter your email address.');
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) $errors['email'] = t('v_email');
    if (mb_strlen($comment) < 5) $errors['comentario'] = tr('Escribe tu mensaje.', 'Write your message.');
    if (!$errors) {
        if (!token_ok(post('t'))) $failure = tr('El formulario ha caducado o se envió demasiado rápido. Vuelve a pulsar «Enviar mensaje».', 'The form expired or was sent too quickly. Press “Send message” again.');
        elseif (!under_limit()) $failure = tr('Ahora mismo hay demasiados mensajes. Inténtalo en un rato o escribe a info@itakora.com.', 'There are too many messages right now. Try again later or write to info@itakora.com.');
        elseif (!send_feedback($name, $email, $comment)) $failure = tr('No se ha podido enviar el mensaje. Tu texto sigue aquí: inténtalo de nuevo o escribe a info@itakora.com.', 'Your message could not be sent. Your text is still here: try again or write to info@itakora.com.');
        else { header('Location: ' . u('mejora.php?enviado=1'), true, 303); exit; }
    }
}

$field = function (string $id, string $label, string $type, string $value, array $extra = []) use ($errors) {
    $err = $errors[$id] ?? '';
    $attrs = ' id="' . $id . '" name="' . $id . '" required aria-describedby="' . $id . '-error"' . ($err ? ' aria-invalid="true"' : '') . ($extra['attrs'] ?? '');
    $input = $type === 'textarea' ? '<textarea' . $attrs . ' rows="6" maxlength="5000">' . h($value) . '</textarea>' : '<input type="' . $type . '"' . $attrs . ' value="' . h($value) . '">';
    return '<div class="field"><label for="' . $id . '">' . h($label) . '</label>' . $input . '<p class="field-error" id="' . $id . '-error"' . ($err ? '' : ' hidden') . '>' . h($err) . '</p></div>';
};

page_start(t('nav_feedback'), tr('Escribe a Olga Revilla para colaborar, proponer mejoras, avisar de errores o hacer preguntas sobre ORIDE.', 'Write to Olga Revilla to collaborate, suggest improvements, report bugs or ask questions about ORIDE.'));
?>
<div class="container narrow-form page">
  <h1><?= h(t('nav_feedback')) ?></h1>
  <?php if ($sent): ?>
    <?= alert(tr('Gracias. He recibido tu mensaje y te responderé pronto.', 'Thank you. I have received your message and will reply soon.'), 'ok') ?>
    <p><a class="btn btn-secondary" href="<?= u('index.php') ?>"><?= h(tr('Volver al inicio', 'Back to home')) ?></a></p>
  <?php else: ?>
    <p class="lead"><?= h(tr('Si quieres colaborar en el desarrollo de la idea, implementar una mejora, tienes alguna duda sobre cómo funciona, has encontrado un error, quieres avisarnos de que lo estás usando en tu trabajo o simplemente hacer una pregunta, contacta con Olga. Prometo leer y responder a todos los mensajes.', 'If you would like to collaborate on developing the idea, implement an improvement, have a question about how it works, have found a bug, want to let us know you are using it in your work, or simply want to ask something, get in touch with Olga. I promise to read and reply to every message.')) ?></p>
    <?php if ($failure) echo alert($failure, 'danger'); ?>
    <form class="card" method="post" action="<?= u('mejora.php') ?>" data-validate>
      <input type="hidden" name="t" value="<?= h(form_token()) ?>">
      <?= $field('nombre', tr('Nombre y apellidos', 'Full name'), 'text', $name, ['attrs' => ' maxlength="120" autocomplete="name"']) ?>
      <?= $field('email', 'Email', 'email', $email, ['attrs' => ' maxlength="254" autocomplete="email"']) ?>
      <?= $field('comentario', tr('Mensaje', 'Message'), 'textarea', $comment) ?>
      <div class="hp" aria-hidden="true"><label for="web"><?= h(tr('No rellenes este campo', 'Leave this field empty')) ?></label><input id="web" name="web" tabindex="-1" autocomplete="off"></div>
      <p class="small muted"><?= h(tr('Usaré estos datos solo para leer tu mensaje y responderte. No se guardan en este sitio.', 'I will use this data only to read your message and reply. It is not stored on this site.')) ?> <a href="<?= u('legal.php') ?>"><?= h(tr('Privacidad', 'Privacy')) ?></a></p>
      <button class="btn btn-primary"><?= h(tr('Enviar mensaje', 'Send message')) ?></button>
    </form>
  <?php endif; ?>
</div>
<?php page_end();
