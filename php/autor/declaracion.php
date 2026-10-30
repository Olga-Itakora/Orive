<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$user = require_login();
$uid = (int) $user['id'];
$vid = (int) (get('id') ?: post('id'));
$v = version_or_404($user, $vid);
if ($v['status'] === 'DRAFT') redirect('autor/paso.php?id=' . $vid . '&p=' . ($v['last_step'] ?: 'obra'));
$self = 'autor/declaracion.php?id=' . $vid;

if (is_post()) {
    csrf_check();
    try {
        switch (post('action')) {
            case 'publish':
                $on = fn($k) => post($k) === 'on';
                publish_version($uid, $vid, ['name' => $on('show_name'), 'work' => $on('show_work'), 'code' => $on('show_code'), 'summary' => $on('show_summary'), 'full' => $on('show_full'), 'evidence' => $on('show_evidence'), 'consent' => $on('evidence_consent')]);
                flash('ok', 'Publicación actualizada.');
                redirect($self . '&tab=publicacion');
            case 'withdraw':
                withdraw_publication($vid, $uid, $uid);
                flash('ok', 'Publicación retirada. La declaración sigue emitida y verificable por quien tenga el documento.');
                redirect($self . '&tab=publicacion');
            case 'new_version':
                redirect('autor/paso.php?id=' . new_version($uid, (int) $v['declaration_id']) . '&p=declarante');
            case 'revoke':
                revoke_version($uid, $vid, post('reason'));
                flash('ok', 'Declaración revocada.');
                redirect($self . '&tab=versiones');
        }
    } catch (DomainError $e) {
        form_fail([], $self . '&tab=' . (post('action') === 'revoke' ? 'versiones' : 'publicacion'), $e->getMessage());
    }
    redirect($self);
}

$f = load_full($vid);
$snap = json_decode($f['v']['snapshot']);
$sections = snapshot_sections($snap);
$pub = current_publication($vid);
$verifyUrl = abs_url('verificar.php?id=' . $v['public_id']);
$versions = q('SELECT id, version_number, public_id, status FROM declaration_versions WHERE declaration_id = ? ORDER BY version_number DESC', [$v['declaration_id']])->fetchAll();
$hasDraft = $versions[0]['status'] === 'DRAFT';
$flags = $pub && $pub['action'] !== 'WITHDRAW' ? $pub : ['show_name' => 0, 'show_work' => 1, 'show_code' => 1, 'show_summary' => 1, 'show_full' => 0, 'show_evidence' => 0];
$tabs = ['declaracion' => 'Declaración', 'proceso' => 'Proceso declarado', 'publicacion' => 'Publicación y distintivo', 'versiones' => 'Integridad y versiones'];
$tab = isset($tabs[get('tab')]) ? get('tab') : 'declaracion';

page_start($f['work']['title'], ['area' => 'author']);
page_header($f['work']['title'], status_badge($v['status']) . ' ' . visibility_badge($v['visibility']) . ' <span class="mono">' . h($v['public_id']) . '</span> · versión ' . (int) $v['version_number'] . ' · emitida ' . fecha($v['issued_at'], true), ['autor/obra.php?id=' . $f['work']['id'], $f['work']['title']],
    '<a class="btn btn-secondary" href="' . u('autor/descargar.php?id=' . $vid . '&tipo=pdf') . '" download>Descargar PDF completo</a><a class="btn btn-secondary" href="' . u('autor/descargar.php?id=' . $vid . '&tipo=resumen') . '" download>Descargar resumen</a><a class="btn btn-secondary" href="' . u('autor/descargar.php?id=' . $vid . '&tipo=json') . '" download>Descargar JSON</a>');
echo '<div class="container">';
if ($v['status'] === 'SUPERSEDED' && $f['superseded_by']) echo alert('Esta versión ha sido sustituida por la <a href="' . u('autor/declaracion.php?id=' . $f['superseded_by']['id']) . '">versión ' . (int) $f['superseded_by']['version_number'] . '</a>. Se conserva sin cambios.', 'info', null, true);
if ($v['status'] === 'REVOKED') echo alert(fecha($v['revoked_at'], true) . ' · Motivo: ' . $v['revocation_reason'], 'danger', 'Declaración revocada');
echo '<nav class="tabs" aria-label="Secciones de la declaración"><ul>';
foreach ($tabs as $k => $label) echo '<li><a href="' . u($self . ($k === 'declaracion' ? '' : '&tab=' . $k)) . '"' . ($tab === $k ? ' aria-current="page"' : '') . '>' . h($label) . '</a></li>';
echo '</ul></nav>';

if ($tab === 'declaracion') echo '<div class="prose-wide">' . render_snapshot($sections, ['meta', 'work', 'declarant', 'scope', 'classification', 'summary']) . '</div>';
if ($tab === 'proceso') echo '<div class="prose-wide">' . render_snapshot($sections, ['process', 'article50', 'statement', 'evidence'], false) . '</div>';

if ($tab === 'publicacion') {
    $box = fn($name, $label, $help, $checked, $forced = false) => '<label class="choice"><input type="checkbox" name="' . $name . '"' . ($checked ? ' checked' : '') . '><span><span class="choice-label">' . h($label) . '</span><span class="choice-desc">' . h($help) . '</span></span></label>';
    echo '<div class="grid-2"><section class="card"><div class="card-head"><h2>Qué se puede consultar públicamente</h2></div>';
    if ($v['visibility'] === 'PUBLIC') echo '<div class="alert alert-info"><p class="alert-title">Publicada en</p><p><a href="' . h($verifyUrl) . '" target="_blank">' . h($verifyUrl) . '</a></p><button type="button" class="btn btn-secondary" data-copy="' . h($verifyUrl) . '">Copiar enlace</button></div>';
    if ($v['visibility'] === 'WITHDRAWN') echo '<p class="muted small">Publicación retirada. La declaración sigue emitida y verificable por quien tenga el documento.</p>';
    echo '<form method="post" action="' . u($self) . '">' . csrf_field() . '<input type="hidden" name="action" value="publish"><fieldset class="choices"><legend>¿Qué quieres que se pueda consultar públicamente?</legend><div class="choice-list">'
        . $box('show_code', 'Código o perfil ORIDE y alcance', 'La clasificación y qué cubre la declaración.', $flags['show_code'] || $flags['show_full'])
        . $box('show_work', 'Datos de la obra', 'Título, tipo, editorial, identificadores…', $flags['show_work'] || $flags['show_full'])
        . $box('show_summary', 'Resumen', 'Descripción sintética de los usos declarados.', $flags['show_summary'] || $flags['show_full'])
        . $box('show_name', 'Mi nombre profesional', 'Nombre, nombre artístico, organización y rol. Nunca el correo electrónico.', (bool) $flags['show_name'])
        . $box('show_full', 'Declaración completa', 'Herramientas, usos, intervenciones, orientación del art. 50 y texto aceptado. Incluye obra, código y resumen.', (bool) $flags['show_full'])
        . '</div></fieldset>';
    if ($f['evidence']) {
        echo '<div class="alert alert-warn"><label class="check"><input type="checkbox" name="show_evidence"' . ($flags['show_evidence'] ? ' checked' : '') . '><span><strong>Metadatos de las evidencias</strong><br>Tipo, descripción, nombre de fichero y huella SHA-256. Los ficheros nunca se publican.</span></label>'
            . '<label class="check"><input type="checkbox" name="evidence_consent"><span>Consiento expresamente la publicación de estos metadatos y confirmo que no contienen datos personales de terceros ni información confidencial.</span></label></div>';
    }
    echo '<p class="meta">Nunca se publican tu correo, tus datos de acceso, prompts o conversaciones privadas ni datos de terceros. Puedes retirar la publicación en cualquier momento.</p>'
        . '<button class="btn btn-primary">' . ($v['visibility'] === 'PUBLIC' ? 'Actualizar publicación' : 'Publicar declaración') . '</button></form>';
    if ($v['visibility'] === 'PUBLIC') echo '<form method="post" action="' . u($self) . '" class="spaced">' . csrf_field() . '<input type="hidden" name="action" value="withdraw"><button class="btn btn-secondary">Retirar publicación</button><p class="meta">Deja de ser consultable públicamente. No revoca ni elimina la declaración, y puedes volver a publicarla.</p></form>';
    echo '</section><section class="card"><div class="card-head"><h2>Compartir y distintivo</h2></div>';
    if ($v['visibility'] === 'PUBLIC') {
        $embed = '<a href="' . $verifyUrl . '" rel="noopener"><img src="' . abs_url('distintivo.php?id=' . $v['public_id']) . '" alt="ORIDE ' . ($sections['classification']->code ?? 'Declaration') . ' — ' . $v['public_id'] . '" height="28"></a>';
        echo '<p class="muted small">Código QR de verificación (también incluido en los PDF):</p><img class="qr" src="' . u('qr.php?id=' . $v['public_id']) . '" alt="Código QR que enlaza con ' . h($verifyUrl) . '">'
            . '<p class="muted small">Distintivo para webs. Enlaza con esta declaración; no es un sello de certificación.</p><img src="' . u('distintivo.php?id=' . $v['public_id']) . '" alt="Distintivo ORIDE" height="28">'
            . '<pre class="config">' . h($embed) . '</pre><button type="button" class="btn btn-secondary" data-copy="' . h($embed) . '">Copiar código HTML</button>';
    } else {
        echo '<p class="muted small">Cuando la publiques, aquí tendrás el enlace, el código QR y un distintivo para incrustar en tu web o en la página de la obra.</p>';
    }
    echo '</section></div>';
}

if ($tab === 'versiones') {
    echo '<div class="grid-2"><section class="card"><div class="card-head"><h2>Integridad</h2></div><p class="muted small">Huella SHA-256 del contenido registrado:</p><p class="mono small break">' . h($v['content_hash']) . '</p>'
        . '<p class="muted small">Acredita que el contenido no ha cambiado desde su emisión. No acredita identidad, veracidad, originalidad, titularidad ni cumplimiento legal.</p></section>';
    echo '<section class="card"><div class="card-head"><h2>Versiones</h2></div><ul class="list-plain">';
    foreach ($versions as $x) {
        $href = $x['status'] === 'DRAFT' ? 'autor/paso.php?id=' . $x['id'] . '&p=declarante' : 'autor/declaracion.php?id=' . $x['id'] . '&tab=versiones';
        echo '<li><a class="link-44" href="' . u($href) . '">' . ((int) $x['id'] === $vid ? '<strong>' : '') . 'Versión ' . (int) $x['version_number'] . ((int) $x['id'] === $vid ? '</strong>' : '') . ($x['public_id'] ? ' · <span class="mono meta">' . h($x['public_id']) . '</span>' : '') . '</a>' . status_badge($x['status']) . '</li>';
    }
    echo '</ul>';
    if ($v['status'] === 'ISSUED' || $hasDraft) {
        echo '<form method="post" action="' . u($self) . '" class="spaced">' . csrf_field() . '<input type="hidden" name="action" value="new_version"><button class="btn btn-secondary">' . ($hasDraft ? 'Continuar la nueva versión' : 'Crear nueva versión') . '</button><p class="meta">La versión actual se conservará intacta y quedará enlazada a la nueva.</p></form>';
    }
    if (in_array($v['status'], ['ISSUED', 'SUPERSEDED'], true)) {
        echo '<div class="spaced">' . confirm_button($self, ['action' => 'revoke'], 'Revocar esta declaración', "¿Revocar la declaración «{$f['work']['title']}» ({$v['public_id']}, versión {$v['version_number']})?",
            'Indicará públicamente que ya no es válida. Se conservará y seguirá siendo verificable, marcada como revocada. No se puede deshacer. Si solo quieres dejar de mostrarla, retira la publicación.', 'Revocar declaración', false,
            field('reason', 'Motivo de la revocación', ['type' => 'textarea', 'required' => true, 'min' => 5, 'max' => 1000, 'rows' => 2, 'hint' => 'Se mostrará junto a la declaración revocada.'])) . '</div>';
    }
    echo '</section></div>';
}
echo '</div>';
page_end();
