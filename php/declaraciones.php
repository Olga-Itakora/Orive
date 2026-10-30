<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/declarations.php';

$q = mb_substr(get('q'), 0, 100);
$page = max(1, (int) get('page', '1'));
$per = 20;
$r = list_public($q, $page, $per);
page_start('Declaraciones públicas');
?>
<div class="container stack">
  <h1>Declaraciones públicas</h1>
  <p class="muted prose">Solo se muestran las declaraciones que sus autores han decidido publicar, y únicamente los datos que han autorizado.</p>
  <?= search_form('declaraciones.php', $q, 'Buscar declaraciones', 'Por título, autor, identificador (ORD-…) o código (p. ej. A4B2C1D2).') ?>
  <p class="small muted" aria-live="polite"><?= $r['total'] ?> declaraci<?= $r['total'] === 1 ? 'ón' : 'ones' ?><?= $q !== '' ? ' para «' . h($q) . '»' : '' ?></p>
  <div class="table-frame">
    <?php if (!$r['items']): ?>
      <div class="empty">
        <?php if ($q !== ''): ?>
          <h2>Ninguna declaración pública coincide con «<?= h($q) ?>»</h2><p class="muted small">Comprueba el identificador o prueba con parte del título.</p><a class="btn btn-secondary" href="<?= u('declaraciones.php') ?>">Borrar la búsqueda</a>
        <?php else: ?>
          <h2>Todavía no hay declaraciones públicas</h2><p class="muted small">Cuando las autoras y los autores publiquen sus declaraciones, podrás consultarlas aquí.</p><a class="btn btn-primary" href="<?= u('autor/obra.php') ?>">Crear una declaración</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-scroll"><table>
        <caption class="sr-only">Declaraciones públicas, de la más reciente a la más antigua</caption>
        <thead><tr><th scope="col">Obra</th><th scope="col">Declarante</th><th scope="col">Código o perfil ORIDE</th><th scope="col" aria-sort="descending">Emitida</th><th scope="col">Estado</th></tr></thead>
        <tbody><?php foreach ($r['items'] as $i): ?>
          <tr><td><a class="row-link" href="<?= u('verificar.php?id=' . $i['public_id']) ?>"><?= h($i['title'] ?? 'Obra no publicada') ?></a><br><span class="muted"><?= h($i['category'] ?? '—') ?> · <span class="mono"><?= h($i['public_id']) ?></span></span></td>
          <td><?= $i['declarant'] ? h($i['declarant']) : '<span class="muted">No publicado</span>' ?></td><td class="mono"><?= h($i['code'] ?? $i['notation'] ?? '—') ?></td><td class="muted"><?= fecha($i['issued_at']) ?></td>
          <td><?= $i['status'] === 'ISSUED' ? 'Vigente' : badge($i['status'] === 'REVOKED' ? 'Revocada' : 'Sustituida', $i['status'] === 'REVOKED' ? 'danger' : 'neutral') ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
    <?= pagination($page, (int) ceil($r['total'] / $per), fn($p) => u('declaraciones.php') . '?' . http_build_query(array_filter(['q' => $q, 'page' => $p]))) ?>
  </div>
</div>
<?php page_end();
