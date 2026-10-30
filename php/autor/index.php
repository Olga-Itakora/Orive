<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

$user = require_login();
$works = (int) qv('SELECT COUNT(*) FROM works WHERE owner_id = ?', [$user['id']]);
$drafts = q('SELECT v.id, v.version_number, v.last_step, v.updated_at, w.title FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id JOIN works w ON w.id = d.work_id WHERE d.owner_id = ? AND v.status = "DRAFT" ORDER BY v.updated_at DESC LIMIT 5', [$user['id']])->fetchAll();
$issued = q('SELECT v.id, v.public_id, v.status, v.visibility, w.title FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id JOIN works w ON w.id = d.work_id WHERE d.owner_id = ? AND v.status <> "DRAFT" ORDER BY v.issued_at DESC LIMIT 5', [$user['id']])->fetchAll();

page_start('Mi espacio', ['area' => 'author']);
page_header('Hola, ' . $user['display_name'], 'Documenta tu proceso creativo, obtén tu código ORIDE y emite declaraciones verificables.', null, '<a class="btn btn-primary" href="' . u('autor/obra.php') . '">Nueva declaración</a>');
?>
<div class="container grid-2">
  <section class="card">
    <div class="card-head"><h2>Borradores en curso</h2></div>
    <?php if (!$drafts): ?>
      <h3 class="small">Aquí verás las declaraciones que estés preparando</h3>
      <p class="muted small">Un borrador se guarda paso a paso y es privado hasta que lo emitas.</p>
      <a class="btn btn-primary" href="<?= u($works ? 'autor/obras.php' : 'autor/obra.php') ?>"><?= $works ? 'Elegir una obra para declarar' : 'Registrar mi primera obra' ?></a>
    <?php else: ?>
      <ul class="list-plain"><?php foreach ($drafts as $d): ?>
        <li><span><strong><?= h($d['title']) ?></strong><br><span class="meta">Versión <?= (int) $d['version_number'] ?> · actualizado <?= fecha($d['updated_at']) ?></span></span>
        <a class="link-44" href="<?= u('autor/paso.php?id=' . $d['id'] . '&p=' . ($d['last_step'] ?: 'obra')) ?>">Continuar</a></li>
      <?php endforeach; ?></ul>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head"><h2>Declaraciones emitidas</h2></div>
    <?php if (!$issued): ?>
      <h3 class="small">Aquí aparecerán tus declaraciones emitidas</h3>
      <p class="muted small">Al emitir obtienes un identificador, un PDF y una huella verificable que puedes compartir.</p>
      <a class="btn btn-secondary" href="<?= u('autor/obra.php') ?>">Empezar una declaración</a>
    <?php else: ?>
      <ul class="list-plain"><?php foreach ($issued as $d): ?>
        <li><span><a class="link-44" href="<?= u('autor/declaracion.php?id=' . $d['id']) ?>"><?= h($d['title']) ?></a><br><span class="mono meta"><?= h($d['public_id']) ?></span></span>
        <span class="badges"><?= status_badge($d['status']) . visibility_badge($d['visibility']) ?></span></li>
      <?php endforeach; ?></ul>
    <?php endif; ?>
  </section>
</div>
<p class="container small muted">Tienes <?= $works ?> obra<?= $works === 1 ? '' : 's' ?> registrada<?= $works === 1 ? '' : 's' ?>. <a class="link-44" href="<?= u('autor/obras.php') ?>">Ver mis obras</a></p>
<?php page_end();
