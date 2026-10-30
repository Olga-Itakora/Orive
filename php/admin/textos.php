<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$admin = require_admin();
const TEXT_KEYS = ['declaration_statement' => 'Declaración responsable', 'privacy' => 'Política de privacidad', 'terms' => 'Condiciones de uso', 'legal_notice' => 'Aviso legal'];
$key = isset(TEXT_KEYS[get('k') ?: post('k')]) ? (get('k') ?: post('k')) : 'declaration_statement';
$self = 'admin/textos.php?k=' . $key;

if (is_post()) {
    csrf_check();
    $aid = (int) $admin['id'];
    switch (post('action')) {
        case 'create':
            $version = post('version');
            $title = mb_substr(post('title'), 0, 200);
            $content = str_replace("\r\n", "\n", post('content'));
            $errors = [];
            if (!preg_match('/^\d{1,3}\.\d{1,3}$/', $version)) $errors['version'] = 'Usa el formato número.número, por ejemplo 1.1.';
            elseif (qv('SELECT COUNT(*) FROM legal_texts WHERE tkey = ? AND version = ?', [$key, $version])) $errors['version'] = 'Ya existe la versión ' . $version . ' de este texto. Elige otro número.';
            if ($title === '') $errors['title'] = 'Indica el título.';
            if (mb_strlen($content) < 20) $errors['content'] = 'Escribe el texto completo (al menos 20 caracteres).';
            if ($errors) form_fail($errors, $self);
            q('INSERT INTO legal_texts (tkey, version, title, content, content_hash, active, pending_review, created_at) VALUES (?, ?, ?, ?, ?, 0, 1, ?)', [$key, $version, $title, $content, hash('sha256', $content), now()]);
            audit('legal_text.create', 'legal_text', $key . '@' . $version, $aid);
            flash('ok', "Versión $version guardada como borrador. Actívala cuando esté revisada.");
            break;
        case 'activate':
            $t = q1('SELECT * FROM legal_texts WHERE id = ? AND tkey = ?', [(int) post('id'), $key]);
            if (!$t) not_found();
            tx(function () use ($t) {
                q('UPDATE legal_texts SET active = 0 WHERE tkey = ?', [$t['tkey']]);
                q('UPDATE legal_texts SET active = 1, published_at = COALESCE(published_at, ?) WHERE id = ?', [now(), $t['id']]);
            });
            audit('legal_text.activate', 'legal_text', $key . '@' . $t['version'], $aid);
            flash('ok', "La versión {$t['version']} es ahora la vigente. Las declaraciones ya emitidas conservan el texto que aceptaron.");
            break;
        case 'reviewed':
            q('UPDATE legal_texts SET pending_review = 0 WHERE id = ? AND tkey = ?', [(int) post('id'), $key]);
            audit('legal_text.reviewed', 'legal_text', (string) post('id'), $aid);
            flash('ok', 'Texto marcado como revisado jurídicamente.');
            break;
        case 'delete':
            $t = q1('SELECT * FROM legal_texts WHERE id = ? AND tkey = ?', [(int) post('id'), $key]);
            if (!$t || $t['active'] || $t['published_at'] || qv('SELECT COUNT(*) FROM acceptances WHERE legal_text_id = ?', [$t['id']])) {
                flash('danger', 'Solo se pueden eliminar borradores que nunca han estado vigentes.');
                break;
            }
            q('DELETE FROM legal_texts WHERE id = ?', [$t['id']]);
            flash('ok', 'Borrador eliminado.');
            break;
    }
    redirect($self);
}

[$errors, $old] = form_state();
$texts = q('SELECT t.*, (SELECT COUNT(*) FROM acceptances a WHERE a.legal_text_id = t.id) uses FROM legal_texts t WHERE tkey = ? ORDER BY t.id DESC', [$key])->fetchAll();
$current = $texts[0] ?? null;
foreach ($texts as $t) if ($t['active']) $current = $t;
page_start('Textos legales', ['area' => 'admin']);
page_header('Textos legales', 'Los textos vigentes no se editan: se crea una versión nueva y se activa. Cada declaración guarda la versión exacta y la huella del texto que aceptó su autor.');
?>
<div class="container stack">
  <nav class="tabs" aria-label="Textos"><ul><?php foreach (TEXT_KEYS as $k => $label): ?><li><a href="<?= u('admin/textos.php?k=' . $k) ?>"<?= $k === $key ? ' aria-current="page"' : '' ?>><?= h($label) ?></a></li><?php endforeach; ?></ul></nav>
  <div class="table-frame"><div class="table-scroll"><table>
    <thead><tr><th scope="col">Versión</th><th scope="col">Título</th><th scope="col">Estado</th><th scope="col" class="t-num">Aceptaciones</th><th scope="col">Huella</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody><?php foreach ($texts as $t): ?>
      <tr><td class="mono"><?= h($t['version']) ?></td><td><?= h($t['title']) ?></td>
      <td><span class="badges"><?= $t['active'] ? badge('Vigente', 'ok') : ($t['published_at'] ? badge('Anterior', 'neutral') : badge('Borrador', 'warn')) ?><?= $t['pending_review'] ? badge('Revisión jurídica pendiente', 'warn') : '' ?></span></td>
      <td class="t-num"><?= (int) $t['uses'] ?></td><td class="mono meta"><?= h(substr($t['content_hash'], 0, 12)) ?>…</td>
      <td class="row-actions">
        <?php if (!$t['active']) echo confirm_button($self, ['action' => 'activate', 'id' => $t['id'], 'k' => $key], 'Activar', '¿Activar la versión ' . $t['version'] . '?', 'Pasará a ser el texto vigente. Las declaraciones ya emitidas no cambian.', 'Activar versión'); ?>
        <?php if ($t['pending_review']): ?><form method="post" action="<?= u($self) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="reviewed"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-link">Marcar como revisado</button></form><?php endif; ?>
        <?php if (!$t['active'] && !$t['published_at'] && !$t['uses']) echo confirm_button($self, ['action' => 'delete', 'id' => $t['id'], 'k' => $key], 'Eliminar borrador ' . $t['version'], '¿Eliminar el borrador ' . $t['version'] . '?', 'Esta acción no se puede deshacer.', 'Eliminar borrador', true); ?>
      </td></tr>
    <?php endforeach; ?></tbody>
  </table></div></div>
  <section class="card">
    <h2>Nueva versión</h2>
    <p class="muted small">Se parte del texto vigente. Al guardarla queda como borrador hasta que la actives.</p>
    <form method="post" action="<?= u($self) ?>" data-draft="texto-<?= h($key) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="create">
      <?= field('version', 'Número de versión', ['required' => true, 'max' => 7, 'value' => $old['version'] ?? '', 'error' => $errors['version'] ?? null, 'hint' => 'Por ejemplo 1.1. Vigente: ' . ($current['version'] ?? '—') . '.', 'class' => 'short']) ?>
      <?= field('title', 'Título', ['required' => true, 'max' => 200, 'value' => $old['title'] ?? $current['title'] ?? TEXT_KEYS[$key], 'error' => $errors['title'] ?? null]) ?>
      <?= field('content', 'Texto completo', ['type' => 'textarea', 'rows' => 16, 'required' => true, 'value' => $old['content'] ?? $current['content'] ?? '', 'error' => $errors['content'] ?? null, 'hint' => 'Separa los párrafos con una línea en blanco.']) ?>
      <div class="sticky-actions"><button class="btn btn-primary">Guardar como borrador</button></div>
    </form>
  </section>
</div>
<?php page_end();
