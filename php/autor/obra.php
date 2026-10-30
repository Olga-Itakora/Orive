<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$user = require_login();
$uid = (int) $user['id'];
$wid = (int) (get('id') ?: post('id'));
$work = $wid ? work_or_404($user, $wid) : null;
$self = 'autor/obra.php' . ($work ? '?id=' . $work['id'] : '');

if (is_post()) {
    csrf_check();
    $action = post('action');
    if ($action === 'save') {
        $d = work_payload();
        $errors = work_errors($d);
        if ($errors) form_fail($errors, $self, 'Revisa los campos marcados.');
        if ($work) {
            q('UPDATE works SET title=?, subtitle=?, category_key=?, description=?, creation_period=?, status=?, publication_date=?, language=?, work_version=?, publisher=?, identifier_type=?, identifier=?, url=?, is_collective=?, updated_at=? WHERE id=? AND owner_id=?',
                array_merge(array_values($d), [now(), $work['id'], $uid]));
            flash('ok', 'Obra guardada.');
            $next = post('next');
            redirect(preg_match('#^autor/paso\.php\?id=\d+&p=[a-z0-9]+$#', $next) ? $next : $self);
        }
        q('INSERT INTO works (owner_id, title, subtitle, category_key, description, creation_period, status, publication_date, language, work_version, publisher, identifier_type, identifier, url, is_collective, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            array_merge([$uid], array_values($d), [now(), now()]));
        $newId = (int) db()->lastInsertId();
        audit('work.create', 'work', (string) $newId, $uid);
        if (post('then') === 'declare') {
            $vid = create_declaration($user, $newId);
            redirect('autor/paso.php?id=' . $vid . '&p=obra');
        }
        flash('ok', 'Obra guardada. Si tiene partes con procesos distintos, añádelas como componentes.');
        redirect('autor/obra.php?id=' . $newId);
    }
    if (!$work) not_found();
    switch ($action) {
        case 'add_component':
            $name = mb_substr(post('name'), 0, 120);
            $kind = post('kind');
            if ($name === '') form_fail(['comp-name' => 'Indica el nombre del componente.'], $self);
            if (!isset(L_COMPONENT_KIND[$kind])) $kind = 'OTHER';
            $n = (int) qv('SELECT COUNT(*) FROM work_components WHERE work_id = ?', [$work['id']]);
            if ($n >= 50) form_fail([], $self, 'Máximo 50 componentes por obra.');
            q('INSERT INTO work_components (work_id, name, kind, sort_order) VALUES (?, ?, ?, ?)', [$work['id'], $name, $kind, $n]);
            flash('ok', "Componente «{$name}» añadido.");
            break;
        case 'delete_component':
            $cid = (int) post('component_id');
            if (qv('SELECT COUNT(*) FROM ai_uses WHERE component_id = ?', [$cid])) {
                flash('danger', 'Este componente está vinculado a usos de IA de alguna declaración. Desvincúlalo antes de eliminarlo.');
                break;
            }
            q('DELETE FROM work_components WHERE id = ? AND work_id = ?', [$cid, $work['id']]);
            flash('ok', 'Componente eliminado.');
            break;
        case 'add_contributor':
            $name = mb_substr(post('cname'), 0, 120);
            $role = mb_substr(post('crole'), 0, 120);
            $errors = [];
            if ($name === '') $errors['cname'] = 'Indica el nombre del participante.';
            if ($role === '') $errors['crole'] = 'Indica su rol.';
            if ($errors) form_fail($errors, $self);
            $valid = array_map('intval', array_column(q('SELECT id FROM work_components WHERE work_id = ?', [$work['id']])->fetchAll(), 'id'));
            $ids = array_values(array_intersect(array_map('intval', post_list('component_ids')), $valid));
            q('INSERT INTO work_contributors (work_id, name, role, component_ids) VALUES (?, ?, ?, ?)', [$work['id'], $name, $role, json_encode($ids)]);
            q('UPDATE works SET is_collective = 1 WHERE id = ?', [$work['id']]);
            flash('ok', 'Participante añadido.');
            break;
        case 'delete_contributor':
            q('DELETE FROM work_contributors WHERE id = ? AND work_id = ?', [(int) post('contributor_id'), $work['id']]);
            flash('ok', 'Participante eliminado.');
            break;
        case 'delete_work':
            if (qv('SELECT COUNT(*) FROM declarations WHERE work_id = ?', [$work['id']])) {
                flash('danger', 'No se puede eliminar una obra que tiene declaraciones. Las declaraciones emitidas se conservan de forma inalterable.');
                break;
            }
            q('DELETE FROM works WHERE id = ? AND owner_id = ?', [$work['id'], $uid]);
            audit('work.delete', 'work', (string) $work['id'], $uid);
            flash('ok', 'Obra eliminada.');
            redirect('autor/obras.php');
        case 'start':
            redirect('autor/paso.php?id=' . create_declaration($user, (int) $work['id']) . '&p=obra');
    }
    redirect($self);
}

[$errors, $old] = form_state();
$values = $old ? $old + ($work ?? []) : ($work ?? []);
if ($old) $values['is_collective'] = isset($old['is_collective']);

if (!$work) {
    page_start('Nueva obra', ['area' => 'author']);
    page_header('Paso 1 · Identifica tu obra', 'Solo el título y el tipo de obra son obligatorios. Después podrás añadir componentes (por ejemplo, texto e ilustraciones) y empezar la declaración.', ['autor/obras.php', 'Mis obras']);
    echo '<div class="container"><form class="card" method="post" action="' . u('autor/obra.php') . '" data-draft="obra-nueva">' . csrf_field() . '<input type="hidden" name="action" value="save">'
        . work_form($values, $errors, 'Guardar obra', 'obra-nueva', '<button class="btn btn-secondary" name="then" value="declare">Guardar y empezar la declaración</button>') . '</form></div>';
    page_end();
    exit;
}

$components = q('SELECT * FROM work_components WHERE work_id = ? ORDER BY sort_order, id', [$work['id']])->fetchAll();
$contributors = q('SELECT * FROM work_contributors WHERE work_id = ? ORDER BY id', [$work['id']])->fetchAll();
$versions = q('SELECT v.id, v.declaration_id, v.version_number, v.public_id, v.status, v.visibility, v.issued_at, v.updated_at, v.last_step FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id WHERE d.work_id = ? ORDER BY d.id DESC, v.version_number DESC', [$work['id']])->fetchAll();

page_start($work['title'], ['area' => 'author']);
page_header($work['title'], h($work['category_label'] . ($work['subtitle'] ? ' · ' . $work['subtitle'] : '')), ['autor/obras.php', 'Mis obras'],
    '<form method="post" action="' . u($self) . '">' . csrf_field() . '<input type="hidden" name="action" value="start"><button class="btn btn-primary">Nueva declaración</button></form>');
?>
<div class="container grid-main-aside">
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>Declaraciones</h2></div>
      <?php if (!$versions): ?>
        <p class="muted small">Esta obra aún no tiene declaraciones. Pulsa «Nueva declaración» para empezar.</p>
      <?php else: ?>
        <ul class="list-plain"><?php foreach ($versions as $v): $href = $v['status'] === 'DRAFT' ? 'autor/paso.php?id=' . $v['id'] . '&p=' . ($v['last_step'] ?: 'obra') : 'autor/declaracion.php?id=' . $v['id']; ?>
          <li><a class="link-44" href="<?= u($href) ?>">Versión <?= (int) $v['version_number'] ?><?= $v['public_id'] ? ' · <span class="mono meta">' . h($v['public_id']) . '</span>' : '' ?></a>
          <span class="badges"><span class="meta"><?= fecha($v['issued_at'] ?? $v['updated_at']) ?></span><?= status_badge($v['status']) . ($v['status'] !== 'DRAFT' ? visibility_badge($v['visibility']) : '') ?></span></li>
        <?php endforeach; ?></ul>
      <?php endif; ?>
    </section>
    <section class="card">
      <div class="card-head"><h2>Datos de la obra</h2><p class="meta">Los cambios no afectan a las declaraciones ya emitidas, que conservan los datos del momento de emisión.</p></div>
      <form method="post" action="<?= u($self) ?>" data-draft="obra-<?= (int) $work['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="save">
        <?= work_form($values, $errors, 'Guardar cambios', 'obra-' . $work['id']) ?>
      </form>
    </section>
  </div>
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>Componentes</h2><p class="meta">Partes de una obra híbrida que pueden tener procesos distintos.</p></div>
      <?php if ($components): ?><ul class="list-plain"><?php foreach ($components as $c): ?>
        <li><span><strong><?= h($c['name']) ?></strong> <span class="muted">· <?= h(L_COMPONENT_KIND[$c['kind']]) ?></span></span>
        <?= confirm_button($self, ['action' => 'delete_component', 'component_id' => $c['id']], "Eliminar componente «{$c['name']}»", "¿Eliminar el componente «{$c['name']}»?", 'Se quitará de la obra. Si algún uso de IA de un borrador lo utiliza, primero tendrás que desvincularlo. Las declaraciones emitidas no cambian.', 'Eliminar componente', true) ?></li>
      <?php endforeach; ?></ul><?php endif; ?>
      <form method="post" action="<?= u($self) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="add_component">
        <?= field('name', 'Nombre del componente', ['id' => 'comp-name', 'required' => true, 'max' => 120, 'error' => $errors['comp-name'] ?? null, 'hint' => 'Por ejemplo: «Texto», «Ilustraciones de cubierta», «Banda sonora».']) ?>
        <?= field('kind', 'Tipo', ['type' => 'select', 'options' => L_COMPONENT_KIND, 'value' => 'TEXT', 'id' => 'comp-kind']) ?>
        <button class="btn btn-secondary">Añadir componente</button>
      </form>
    </section>
    <section class="card">
      <div class="card-head"><h2>Otros participantes</h2><p class="meta">Solo para obras colectivas. Sus nombres nunca se publican ni se incluyen en la declaración.</p></div>
      <?php if ($contributors): ?><ul class="list-plain"><?php foreach ($contributors as $c): ?>
        <li><span><strong><?= h($c['name']) ?></strong> <span class="muted">· <?= h($c['role']) ?></span></span>
        <?= confirm_button($self, ['action' => 'delete_contributor', 'contributor_id' => $c['id']], "Eliminar participante «{$c['name']}»", "¿Eliminar al participante «{$c['name']}»?", 'Se quitará de la lista de participantes de la obra. Las declaraciones emitidas no cambian.', 'Eliminar participante', true) ?></li>
      <?php endforeach; ?></ul><?php endif; ?>
      <form method="post" action="<?= u($self) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="add_contributor">
        <?= field('cname', 'Nombre', ['required' => true, 'max' => 120, 'error' => $errors['cname'] ?? null]) ?>
        <?= field('crole', 'Rol', ['required' => true, 'max' => 120, 'error' => $errors['crole'] ?? null, 'hint' => 'Por ejemplo: ilustrador/a, coautor/a.']) ?>
        <?php if ($components) echo choices('component_ids', 'Componentes en los que participa', array_column($components, 'name', 'id'), ['type' => 'checkbox']); ?>
        <button class="btn btn-secondary">Añadir participante</button>
      </form>
    </section>
    <?php if (!$versions) echo confirm_button($self, ['action' => 'delete_work'], 'Eliminar esta obra', "¿Eliminar la obra «{$work['title']}»?", 'Se eliminarán la obra, sus componentes y sus participantes. Esta acción no se puede deshacer.', 'Eliminar obra'); ?>
  </div>
</div>
<?php page_end();
