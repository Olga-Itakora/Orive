<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

// Código ORIDE de este sitio web.
const SITE_CODE = 'SW-IA1-COD44-V1';
$siteCode = oride_explain(SITE_CODE)['result'];

page_start(t('nav_about'), tr('Quién está detrás de ORIDE, cómo nació y cómo contactar.', 'Who is behind ORIDE, how it started and how to get in touch.'));
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_about')) ?></h1>
  <?php if (en()): ?>
    <p class="lead">ORIDE is a personal project by Olga Revilla Muñoz. There is no academic institution or company behind it.</p>
    <h2>How the ORIDE Code was born</h2>
    <p>The author of this site works in literary writing, software and scientific research. She has been gradually bringing different AI tools into her work and delegating more and more to them.</p>
    <p>That is why she felt the need to find an honest, transparent way to tell the community which part of a work is personal and which part is automated.</p>
    <p>She hopes this originality declaration system will be useful, and that a community of creators will grow around applying and developing the idea.</p>
    <h2>A first version</h2>
    <p>This is a first version. Any suggestion is welcome.</p>
  <?php else: ?>
    <p class="lead">ORIDE es un proyecto personal de Olga Revilla Muñoz. No hay ninguna entidad académica o empresarial detrás.</p>
    <h2>Cómo nació el Código ORIDE</h2>
    <p>La autora de este sitio se dedica a la creación literaria, de software y de investigación científica. De forma progresiva está incorporando diferentes herramientas de IA en su trabajo, delegando cada vez más.</p>
    <p>Por ello sintió la necesidad de encontrar una forma de decir a la comunidad qué parte es personal y qué parte es automatizada, de una manera honesta y transparente.</p>
    <p>Espera que este sistema de declaración de originalidad sea útil y que se cree una comunidad de creadores interesados en aplicar y desarrollar la idea.</p>
    <h2>Una primera versión</h2>
    <p>Esta es una primera versión. Cualquier sugerencia es bienvenida.</p>
  <?php endif; ?>
  <ul class="link-list">
    <li><a href="https://www.linkedin.com/in/itakora/" rel="noopener">LinkedIn</a></li>
    <li><a href="https://itakora.com" rel="noopener"><?= h(tr('Sitio web personal', 'Personal website')) ?> (itakora.com)</a></li>
    <li><?= h(t('contact')) ?>: <a href="mailto:info@itakora.com">info@itakora.com</a></li>
  </ul>

  <h2><?= h(tr('El Código ORIDE de este sitio', 'This site’s ORIDE Code')) ?></h2>
  <p><?= h(tr('El código ORIDE de este sitio web es', 'The ORIDE code of this website is')) ?> <a class="mono" href="<?= u('oride.php?' . SITE_CODE) ?>">ORIDE <?= SITE_CODE ?></a>.</p>
  <?= code_box($siteCode, ['title' => t('code_label')]) ?>
  <?= honor_note() ?>
  <p class="small muted"><?= h(t('trademark')) ?></p>
  <p><a class="btn btn-secondary" href="<?= u('mejora.php') ?>"><?= h(t('nav_feedback')) ?></a></p>
</div>
<?php page_end();
