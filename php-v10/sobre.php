<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

// Código ORIDE de este sitio web.
const SITE_CODE = 'SW-IGE-EV1-REQ21-ARQ44-COD44-REF33-PRU54-DOC34';
$siteCode = oride_explain(SITE_CODE)['result'];

page_start(t('nav_about'), tr('Quién está detrás de ORIDE, cómo nació y cómo contactar.', 'Who is behind ORIDE, how it started and how to get in touch.'));
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_about')) ?></h1>
  <?php if (en()): ?>
    <p class="lead">Hi, I’m Olga Revilla Muñoz. ORIDE is a personal project: there is no academic institution or company behind it, just me and the wish to do things well.</p>
    <h2>Why I created it</h2>
    <p>I write literature, I develop software and I do scientific research. Little by little I started bringing AI tools into my work, and every month I delegate a bit more to them.</p>
    <p>At some point I felt uneasy: when someone reads a text of mine or uses a program I wrote, how can they know which part is mine and which part is automated? I wanted to be able to say it honestly and transparently, without making excuses and without hiding anything.</p>
    <p>That is how ORIDE was born: a simple way of saying “this is how I made it”. I hope it is useful to you too, and that together we can build a community of creators who use it, question it and help it grow.</p>
    <h2>A first version</h2>
    <p>This is a first version, and it surely has a lot to improve. If you have an idea, a criticism or a doubt, I would love to read it.</p>
  <?php else: ?>
    <p class="lead">Hola, soy Olga Revilla Muñoz. ORIDE es un proyecto personal: no hay ninguna entidad académica ni empresarial detrás, solo yo y las ganas de hacer las cosas bien.</p>
    <h2>Por qué lo creé</h2>
    <p>Me dedico a la creación literaria, al desarrollo de software y a la investigación científica. Poco a poco he ido incorporando herramientas de IA a mi trabajo, y cada mes les delego un poco más.</p>
    <p>En algún momento me entró una inquietud: cuando alguien lee un texto mío o usa un programa que he escrito, ¿cómo puede saber qué parte es mía y qué parte está automatizada? Quería poder contarlo de forma honesta y transparente, sin justificarme y sin esconder nada.</p>
    <p>Así nació ORIDE: una manera sencilla de decir «así lo he hecho». Ojalá te resulte útil a ti también, y que entre todos formemos una comunidad de creadores que lo usen, lo cuestionen y lo hagan crecer.</p>
    <h2>Una primera versión</h2>
    <p>Esta es una primera versión y seguro que tiene mucho que mejorar. Si tienes una idea, una crítica o una duda, me encantará leerla.</p>
  <?php endif; ?>
  <ul class="link-list">
    <li><a href="https://www.linkedin.com/in/itakora/" rel="noopener">LinkedIn</a></li>
    <li><a href="https://itakora.com" rel="noopener"><?= h(tr('Mi web', 'My website')) ?> (itakora.com)</a></li>
    <li><?= h(tr('Escríbeme a', 'Write to me at')) ?> <a href="mailto:info@itakora.com">info@itakora.com</a></li>
  </ul>

  <h2><?= h(tr('El Código ORIDE de este sitio', 'This site’s ORIDE Code')) ?></h2>
  <p><?= h(tr('Como no podía ser de otra forma, este sitio también tiene su código:', 'Of course, this site has its own code too:')) ?></p>
  <p class="formula code-example"><a class="mono" href="<?= u('oride.php?' . SITE_CODE) ?>">ORIDE <?= implode('<br>', array_map('h', $siteCode['lines'])) ?></a></p>
  <?= code_box($siteCode, ['reader' => true]) ?>
  <?= honor_note() ?>
  <p><a class="btn btn-secondary" href="<?= u('mejora.php') ?>"><?= h(t('nav_feedback')) ?></a></p>
</div>
<?php page_end();
