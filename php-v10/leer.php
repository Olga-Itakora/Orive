<?php
/** Lee un código: se escribe un código y se abre su página explicada (oride.php). */
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_read'), tr('Escribe un Código ORIDE y descubre qué significa: cómo se usó la IA y cuánto aportó a la obra.', 'Type an ORIDE Code and find out what it means: how AI was used and how much it contributed to the work.'));
$examples = ['LI-IGB-EV1-DOC44-GEN24', 'PH-IGA-EV1-REV44-RET32', 'ACA-IGB-EV1-DOC21-GEN32-FOR32', 'MU-IA0-EV1'];
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_read')) ?></h1>
  <p class="lead"><?= h(tr('Escribe o pega un Código ORIDE para ver, con palabras sencillas, cómo se usó la inteligencia artificial y cuánto aportó a la obra.', 'Type or paste an ORIDE Code to see, in plain words, how artificial intelligence was used and how much it contributed to the work.')) ?></p>
  <form class="card decoder" method="get" action="<?= u('oride.php') ?>">
    <?= lang_field() ?>
    <label for="c"><?= h(t('code_label')) ?></label>
    <div class="search-row"><input id="c" name="c" class="mono" maxlength="200" placeholder="LI-IGB-EV1-GEN24" autocomplete="off" spellcheck="false" required aria-describedby="c-hint"><button class="btn btn-primary"><?= h(tr('Leer código', 'Read code')) ?></button></div>
    <p class="hint" id="c-hint"><?= h(tr('Vale en una o en dos líneas, con o sin la palabra ORIDE, y también los códigos de versiones anteriores.', 'It works on one or two lines, with or without the word ORIDE, and with codes from earlier versions.')) ?></p>
  </form>
  <h2 class="spaced"><?= h(tr('Ejemplos', 'Examples')) ?></h2>
  <ul class="example-list">
    <?php foreach ($examples as $c): $r = oride_explain($c)['result']; ?>
      <li><a class="mono" href="<?= h(u('oride.php?' . $c)) ?>"><?= h(implode(' ', $r['lines'])) ?></a> · <?= h(type_name($r['type'])) ?></li>
    <?php endforeach; ?>
  </ul>
  <p><a class="link-24" href="<?= u('codigo.php') ?>"><?= h(tr('Cómo se lee el código, parte por parte', 'How to read the code, part by part')) ?></a></p>
</div>
<?php page_end();
