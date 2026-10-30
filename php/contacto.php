<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$types = ['General' => ['CONTACT' => 'Consulta general', 'TECHNICAL' => 'Problema técnico', 'CONTENT_REPORT' => 'Comunicar una declaración pública'],
    'Protección de datos (RGPD)' => ['DATA_ACCESS' => 'Derecho de acceso', 'DATA_RECTIFICATION' => 'Derecho de rectificación', 'DATA_ERASURE' => 'Derecho de supresión', 'DATA_OBJECTION' => 'Derecho de oposición', 'DATA_RESTRICTION' => 'Derecho de limitación', 'DATA_PORTABILITY' => 'Derecho de portabilidad']];
if (is_post()) {
    csrf_check();
    if (post('website') !== '') redirect('contacto.php?ok=1'); // trampa anti-spam
    if (!rate_limit('contact', client_ip(), 5, 3600)) form_fail([], 'contacto.php', 'Se ha alcanzado el límite de mensajes por hora desde esta conexión. Vuelve a enviarlo dentro de un rato.');
    $type = post('type');
    $email = strtolower(post('email'));
    $msg = post('message');
    $errors = [];
    if (!isset(array_merge(...array_values($types))[$type])) $errors['type'] = 'Selecciona el motivo.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Escribe un correo con el formato nombre@dominio.com.';
    if (mb_strlen($msg) < 10 || mb_strlen($msg) > 5000) $errors['message'] = 'Describe tu consulta (entre 10 y 5000 caracteres).';
    if ($errors) form_fail($errors, 'contacto.php');
    $u = current_user();
    $pid = post('public_id');
    q('INSERT INTO incidents (type, name, email, public_id, user_id, message, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$type, nullable(post('name'), 120), $email, $pid !== '' ? (oride_normalize_public_id($pid) ?? mb_substr($pid, 0, 18)) : null, $u['id'] ?? null, $msg, now(), now()]);
    audit('incident.create', 'incident', (string) db()->lastInsertId(), $u['id'] ?? null, ['type' => $type]);
    redirect('contacto.php?ok=1');
}
[$errors, $old] = form_state();
$u = current_user();
page_start('Contacto');
?>
<div class="container narrow">
  <p class="eyebrow">Contacto</p>
  <h1>Contacto y solicitudes de derechos</h1>
  <p class="muted">Escríbenos para consultas, incidencias técnicas, comunicaciones sobre declaraciones públicas o para ejercer tus derechos de protección de datos.</p>
  <?php if (get('ok')): ?>
    <?= alert('Mensaje recibido. Te responderemos en el correo indicado. Las solicitudes de derechos de protección de datos se atienden en el plazo legal de un mes.', 'ok') ?>
  <?php else: ?>
  <form class="card" method="post" action="<?= u('contacto.php') ?>" data-draft="contacto">
    <?= csrf_field() ?>
    <?= field('type', 'Motivo', ['type' => 'select', 'required' => true, 'options' => $types, 'value' => $old['type'] ?? 'CONTACT', 'error' => $errors['type'] ?? null]) ?>
    <div class="form-grid">
      <?= field('name', 'Nombre', ['optional' => true, 'max' => 120, 'value' => $old['name'] ?? '', 'attrs' => ' autocomplete="name"']) ?>
      <?= field('email', 'Correo electrónico', ['type' => 'email', 'required' => true, 'value' => $old['email'] ?? ($u['email'] ?? ''), 'error' => $errors['email'] ?? null, 'attrs' => ' autocomplete="email"']) ?>
    </div>
    <?= field('public_id', 'Identificador de declaración', ['optional' => true, 'max' => 20, 'value' => $old['public_id'] ?? get('id'), 'placeholder' => 'ORD-7F29-C4A8-K2M3', 'hint' => 'Si tu mensaje se refiere a una declaración concreta.']) ?>
    <?= field('message', 'Mensaje', ['type' => 'textarea', 'required' => true, 'rows' => 6, 'min' => 10, 'max' => 5000, 'value' => $old['message'] ?? '', 'error' => $errors['message'] ?? null]) ?>
    <div class="hp" aria-hidden="true"><label>No rellenar <input name="website" tabindex="-1" autocomplete="off"></label></div>
    <button class="btn btn-primary">Enviar mensaje</button>
  </form>
  <?php endif; ?>
  <p class="small muted">Si tienes cuenta, también puedes descargar tus datos y solicitar la supresión de tu cuenta desde «Perfil y datos». Retirar la publicación de una declaración es distinto de eliminar datos personales.</p>
</div>
<?php page_end();
