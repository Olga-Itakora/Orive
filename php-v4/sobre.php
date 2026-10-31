<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_about'), tr('Quién está detrás de ORIDE y cómo contactar.', 'Who is behind ORIDE and how to get in touch.'));
$siteCode = oride_explain('IA1-I2-C44-V1')['result'];
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_about')) ?></h1>
  <?php if (en()): ?>
    <p class="lead">ORIDE is a personal project by Olga Revilla Muñoz. There is no academic institution or company behind it.</p>
    <p>This is a first version. Any suggestion is welcome.</p>
  <?php else: ?>
    <p class="lead">ORIDE es un proyecto personal de Olga Revilla Muñoz. No hay ninguna entidad académica o empresarial detrás.</p>
    <p>Esta es una primera versión. Cualquier sugerencia es bienvenida.</p>
  <?php endif; ?>
  <ul class="link-list">
    <li><a href="https://www.linkedin.com/in/itakora/" rel="noopener">LinkedIn</a></li>
    <li><a href="https://itakora.com" rel="noopener"><?= h(tr('Sitio web personal', 'Personal website')) ?> (itakora.com)</a></li>
    <li><?= h(t('contact')) ?>: <a href="mailto:info@itakora.com">info@itakora.com</a></li>
  </ul>

  <h2><?= h(tr('El Código ORIDE de este sitio', 'This site’s ORIDE Code')) ?></h2>
  <p><?= h(tr('El código ORIDE de este sitio web es', 'The ORIDE code of this website is')) ?> <b class="mono">ORIDE IA1-I2-C44-V1</b>.</p>
  <?= code_box($siteCode, false, t('code_label')) ?>
  <?= honor_note() ?>
  <p><a class="btn btn-secondary" href="<?= u('mejora.php') ?>"><?= h(t('nav_feedback')) ?></a></p>
</div>
<?php page_end();
