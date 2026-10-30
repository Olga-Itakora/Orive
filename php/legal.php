<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$map = ['privacidad' => 'privacy', 'condiciones' => 'terms', 'aviso' => 'legal_notice'];
$key = $map[get('doc')] ?? null;
$t = $key ? q1('SELECT * FROM legal_texts WHERE tkey = ? AND active = 1 ORDER BY published_at DESC LIMIT 1', [$key]) : null;
if (!$t) not_found();
page_start($t['title']);
?>
<div class="container prose">
  <p class="eyebrow">Legal</p>
  <h1><?= h($t['title']) ?></h1>
  <p class="muted">Versión <?= h($t['version']) ?> · publicada el <?= fecha($t['published_at']) ?></p>
  <?php if ($t['pending_review']) echo alert('Texto provisional pendiente de revisión jurídica profesional.', 'warn'); ?>
  <?php foreach (preg_split('/\n{2,}/', $t['content']) as $para): ?><p class="pre-line"><?= h($para) ?></p><?php endforeach; ?>
</div>
<?php page_end();
