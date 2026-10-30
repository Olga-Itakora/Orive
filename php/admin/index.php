<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';

require_admin();
$stats = [
    ['Usuarios', qv('SELECT COUNT(*) FROM users'), 'admin/usuarios.php'],
    ['Obras', qv('SELECT COUNT(*) FROM works'), null],
    ['Declaraciones emitidas', qv("SELECT COUNT(*) FROM declaration_versions WHERE status <> 'DRAFT'"), null],
    ['Declaraciones públicas', qv("SELECT COUNT(*) FROM declaration_versions WHERE visibility = 'PUBLIC'"), 'declaraciones.php'],
    ['Incidencias abiertas', qv("SELECT COUNT(*) FROM incidents WHERE status IN ('OPEN','IN_PROGRESS')"), 'admin/incidencias.php'],
    ['Textos pendientes de revisión jurídica', qv('SELECT COUNT(*) FROM legal_texts WHERE active = 1 AND pending_review = 1'), 'admin/textos.php'],
];
$codes = q("SELECT p.code, COUNT(*) n FROM profiles p JOIN declaration_versions v ON v.id = p.version_id WHERE v.status <> 'DRAFT' AND p.code IS NOT NULL GROUP BY p.code ORDER BY n DESC LIMIT 10")->fetchAll();
$recent = q('SELECT action, entity, entity_id, created_at FROM audit_log ORDER BY id DESC LIMIT 8')->fetchAll();
page_start('Administración', ['area' => 'admin']);
page_header('Administración', 'Estado general de la plataforma. Las declaraciones emitidas no pueden modificarse desde aquí.');
?>
<div class="container stack">
  <div class="stat-grid"><?php foreach ($stats as [$label, $n, $href]): ?>
    <div class="card stat"><p class="stat-n"><?= (int) $n ?></p><p class="muted small"><?= $href ? '<a href="' . u($href) . '">' . h($label) . '</a>' : h($label) ?></p></div>
  <?php endforeach; ?></div>
  <div class="grid-2">
    <section class="card"><h2>Códigos más frecuentes</h2>
      <?php if (!$codes): ?><p class="muted small">Aún no hay declaraciones emitidas con código simple.</p><?php else: ?>
      <table class="small"><thead><tr><th scope="col">Código</th><th scope="col" class="t-num">Declaraciones</th></tr></thead><tbody>
        <?php foreach ($codes as $c): ?><tr><td><a class="mono" href="<?= u('codigo.php?c=' . $c['code']) ?>"><?= h($c['code']) ?></a></td><td class="t-num"><?= (int) $c['n'] ?></td></tr><?php endforeach; ?>
      </tbody></table><?php endif; ?>
    </section>
    <section class="card"><h2>Actividad reciente</h2>
      <?php if (!$recent): ?><p class="muted small">Sin actividad registrada.</p><?php else: ?>
      <ul class="list-plain small"><?php foreach ($recent as $r): ?><li><span class="mono"><?= h($r['action']) ?></span><span class="muted"><?= fecha($r['created_at'], true) ?></span></li><?php endforeach; ?></ul>
      <a class="link-44" href="<?= u('admin/auditoria.php') ?>">Ver el registro completo</a><?php endif; ?>
    </section>
  </div>
</div>
<?php page_end();
