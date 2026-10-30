<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$user = require_login();
$uid = (int) $user['id'];
$self = 'autor/perfil.php';

if (get('exportar')) {
    // Acceso y portabilidad (arts. 15 y 20 RGPD).
    $works = q('SELECT * FROM works WHERE owner_id = ?', [$uid])->fetchAll();
    foreach ($works as &$w) {
        $w['components'] = q('SELECT name, kind, description FROM work_components WHERE work_id = ?', [$w['id']])->fetchAll();
        $w['contributors'] = q('SELECT name, role FROM work_contributors WHERE work_id = ?', [$w['id']])->fetchAll();
        $w['declarations'] = q('SELECT v.version_number, v.public_id, v.status, v.visibility, v.issued_at, v.content_hash, v.snapshot, v.declarant_name, v.declarant_role, v.scope, v.ai_usage, v.uncertainty_note, v.process_narrative, v.observations, v.article50_answers FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id WHERE d.work_id = ?', [$w['id']])->fetchAll();
        foreach ($w['declarations'] as &$d) $d['snapshot'] = $d['snapshot'] ? json_decode($d['snapshot']) : null;
        unset($d);
    }
    unset($w);
    audit('user.data_export', 'user', (string) $uid, $uid);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="oride-mis-datos.json"');
    header('Cache-Control: private, no-store');
    echo json_encode(['exportedAt' => gmdate('c'), 'format' => 'oride-personal-data-export/1', 'account' => array_intersect_key($user, array_flip(['email', 'role', 'display_name', 'artistic_name', 'organization', 'bio', 'website', 'created_at'])), 'works' => $works,
        'acceptances' => q('SELECT a.accepted_at, a.statement_hash, t.tkey, t.version FROM acceptances a JOIN legal_texts t ON t.id = a.legal_text_id WHERE a.user_id = ?', [$uid])->fetchAll()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if (is_post()) {
    csrf_check();
    switch (post('action')) {
        case 'profile':
            $name = mb_substr(post('display_name'), 0, 160);
            $web = nullable(post('website'), 500);
            $errors = [];
            if (mb_strlen($name) < 2) $errors['display_name'] = 'Indica tu nombre profesional.';
            if ($web && !preg_match('#^https?://\S+$#i', $web)) $errors['website'] = 'Escribe una dirección completa que empiece por https://.';
            if ($errors) form_fail($errors, $self);
            q('UPDATE users SET display_name=?, artistic_name=?, organization=?, bio=?, website=?, updated_at=? WHERE id=?', [$name, nullable(post('artistic_name'), 160), nullable(post('organization'), 200), nullable(post('bio'), 2000), $web, now(), $uid]);
            flash('ok', 'Perfil actualizado. Los cambios no afectan a las declaraciones ya emitidas.');
            break;
        case 'password':
            if (!rate_limit('change-password', (string) $uid, 10, 3600)) form_fail([], $self, 'Demasiados intentos. Vuelve a intentarlo más tarde.');
            $pass = (string) ($_POST['password'] ?? '');
            if (!password_verify((string) ($_POST['current'] ?? ''), $user['password_hash'])) form_fail(['current' => 'La contraseña actual no es correcta.'], $self);
            if (mb_strlen($pass) < 10 || mb_strlen($pass) > 128) form_fail(['password' => 'La contraseña debe tener entre 10 y 128 caracteres.'], $self);
            if ($pass !== (string) ($_POST['confirm'] ?? '')) form_fail(['confirm' => 'Las dos contraseñas no coinciden. Escríbela de nuevo.'], $self);
            q('UPDATE users SET password_hash = ?, session_version = session_version + 1, updated_at = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), now(), $uid]);
            $_SESSION['sv'] = (int) $user['session_version'] + 1;
            audit('user.password_change', 'user', (string) $uid, $uid);
            flash('ok', 'Contraseña actualizada. Se han cerrado las demás sesiones.');
            break;
        case 'sessions':
            q('UPDATE users SET session_version = session_version + 1 WHERE id = ?', [$uid]);
            $_SESSION['sv'] = (int) $user['session_version'] + 1;
            flash('ok', 'Se han cerrado las sesiones abiertas en otros dispositivos.');
            break;
        case 'erasure':
            if (post('confirm_erasure') !== 'on') form_fail(['confirm_erasure' => 'Marca la casilla para confirmar la solicitud.'], $self);
            if (!qv('SELECT COUNT(*) FROM incidents WHERE user_id = ? AND type = "DATA_ERASURE" AND status IN ("OPEN","IN_PROGRESS")', [$uid])) {
                q('INSERT INTO incidents (type, email, user_id, message, created_at, updated_at) VALUES ("DATA_ERASURE", ?, ?, ?, ?, ?)', [$user['email'], $uid, nullable(post('details'), 2000) ?? 'Solicitud de supresión de la cuenta desde el perfil.', now(), now()]);
                audit('user.erasure_requested', 'user', (string) $uid, $uid);
            }
            flash('ok', 'Solicitud de supresión registrada. Te responderemos en el plazo legal de un mes. Las declaraciones emitidas pueden conservarse de forma limitada si existe una base legítima; te informaremos de ello.');
            break;
    }
    redirect($self);
}
[$errors, $old] = form_state();
$val = fn($k) => (string) ($old[$k] ?? $user[$k] ?? '');
page_start('Perfil y datos', ['area' => 'author']);
page_header('Perfil y datos', 'Cuenta: ' . h($user['email']) . ' (privado)');
?>
<div class="container grid-2">
  <section class="card">
    <div class="card-head"><h2>Perfil profesional</h2><p class="meta">Se usa para rellenar nuevas declaraciones. Solo se publica si lo decides en cada declaración.</p></div>
    <form method="post" action="<?= u($self) ?>" data-draft="perfil"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <?= field('display_name', 'Nombre profesional', ['required' => true, 'min' => 2, 'max' => 160, 'value' => $val('display_name'), 'error' => $errors['display_name'] ?? null]) ?>
      <?= field('artistic_name', 'Nombre artístico', ['optional' => true, 'max' => 160, 'value' => $val('artistic_name')]) ?>
      <?= field('organization', 'Institución u organización', ['optional' => true, 'max' => 200, 'value' => $val('organization')]) ?>
      <?= field('website', 'Web', ['type' => 'url', 'optional' => true, 'max' => 500, 'value' => $val('website'), 'error' => $errors['website'] ?? null]) ?>
      <?= field('bio', 'Presentación breve', ['type' => 'textarea', 'optional' => true, 'max' => 2000, 'value' => $val('bio')]) ?>
      <button class="btn btn-primary">Guardar perfil</button>
    </form>
  </section>
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>Seguridad</h2></div>
      <form method="post" action="<?= u($self) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <?= field('current', 'Contraseña actual', ['type' => 'password', 'required' => true, 'error' => $errors['current'] ?? null, 'attrs' => ' autocomplete="current-password"']) ?>
        <?= field('password', 'Nueva contraseña', ['type' => 'password', 'required' => true, 'min' => 10, 'error' => $errors['password'] ?? null, 'hint' => 'Al menos 10 caracteres.', 'attrs' => ' autocomplete="new-password"']) ?>
        <?= field('confirm', 'Repite la nueva contraseña', ['type' => 'password', 'required' => true, 'error' => $errors['confirm'] ?? null, 'attrs' => ' autocomplete="new-password"']) ?>
        <button class="btn btn-secondary">Cambiar contraseña</button>
      </form>
      <form method="post" action="<?= u($self) ?>" class="spaced"><?= csrf_field() ?><input type="hidden" name="action" value="sessions"><button class="btn btn-secondary">Cerrar las sesiones de otros dispositivos</button></form>
    </section>
    <section class="card">
      <div class="card-head"><h2>Tus datos</h2></div>
      <p class="muted small">Descarga una copia de tus datos personales, obras y declaraciones en formato JSON (derechos de acceso y portabilidad).</p>
      <a class="btn btn-secondary" href="<?= u($self . '?exportar=1') ?>" download>Descargar mis datos</a>
    </section>
  </div>
</div>
<section class="container spaced" aria-labelledby="danger">
  <div class="card danger-zone">
    <h2 id="danger">Zona de peligro: supresión de la cuenta</h2>
    <p class="muted small prose">Retirar la publicación de una declaración no elimina tus datos. Para suprimir tu cuenta, envía una solicitud; se atenderá en el plazo legal teniendo en cuenta las obligaciones legítimas de conservación de las declaraciones emitidas.</p>
    <form method="post" action="<?= u($self) ?>" class="narrow"><?= csrf_field() ?><input type="hidden" name="action" value="erasure">
      <?= field('details', 'Detalles', ['type' => 'textarea', 'optional' => true, 'max' => 2000, 'rows' => 2]) ?>
      <fieldset><label class="check"><input type="checkbox" name="confirm_erasure" id="f-confirm_erasure" aria-describedby="f-confirm_erasure-error"><span>Quiero solicitar la supresión de mi cuenta y de mis datos personales.</span></label>
      <p class="field-error" id="f-confirm_erasure-error" role="alert"<?= isset($errors['confirm_erasure']) ? '' : ' hidden' ?>><?= h($errors['confirm_erasure'] ?? '') ?></p></fieldset>
      <button class="btn btn-danger">Solicitar la supresión de mi cuenta</button>
    </form>
  </div>
</section>
<?php page_end();
