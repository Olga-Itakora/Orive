<?php
/** Instituciones › Verificar una entrega: ¿supera el código declarado el requisito fijado? */
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/builder.php';

$rawReq = mb_substr(get('r'), 0, 200);
$rawDecl = mb_substr(get('d'), 0, 200);
$req = $rawReq !== '' ? oride_explain($rawReq) : null;
$decl = $rawDecl !== '' ? oride_explain($rawDecl) : null;
$cmp = $req && $decl && $req['valid'] && $decl['valid'] ? oride_compare($req['result'], $decl['result']) : null;
$verdicts = ['ok' => ['cmp_ok', 'ok'], 'fail' => ['cmp_fail', 'danger'], 'unknown' => ['cmp_unknown', 'warn']];
$icons = ['ok' => '✓', 'fail' => '✗', 'unknown' => '?', 'info' => 'i'];

$field = function (string $name, string $label, ?array $parsed, string $raw) {
    $err = $parsed && !$parsed['valid'] ? $parsed['error'] : '';
    return '<div class="field"><label for="' . $name . '">' . h($label) . '</label>'
        . '<textarea id="' . $name . '" name="' . $name . '" class="mono" rows="2" maxlength="200" spellcheck="false" placeholder="LI-IA1-EV1&#10;DOC44-GEN24" aria-describedby="' . $name . '-error"' . ($err ? ' aria-invalid="true"' : '') . '>' . h($raw) . '</textarea>'
        . '<p class="field-error" id="' . $name . '-error"' . ($err ? '' : ' hidden') . '>' . h($err) . '</p></div>';
};
$codeSummary = function (array $r) {
    $ig = ig_from_result($r);
    return '<p class="mono cmp-code">' . implode('<br>', array_map('h', $r['lines'])) . '</p>'
        . '<p class="small muted">' . h(t('ig_title')) . ': ' . h($ig['status'] === 'ok' ? ig_percent($ig) : t('ig_pending')) . ' · <a href="' . h(u('oride.php?' . $r['code'])) . '">' . h(t('what_means')) . '</a></p>';
};

page_start(t('cmp_title'), tr('Comprueba si el Código ORIDE de una entrega supera el requisito que fijaste.', 'Check whether the ORIDE Code of a submission exceeds the requirement you set.'));
?>
<div class="container page">
  <div class="narrow-text">
    <h1><?= h(t('nav_inst')) ?></h1>
    <?= inst_tabs('comprobar.php') ?>
    <p class="lead"><?= h(tr('Pega el código que fijaste como requisito y el que declaró la persona autora.', 'Paste the code you set as a requirement and the one the author declared.')) ?></p>
    <form class="card" method="get" action="<?= u('comprobar.php') ?>">
      <?= lang_field() ?>
      <?= $field('r', t('cmp_req'), $req, $rawReq) ?>
      <?= $field('d', t('cmp_decl'), $decl, $rawDecl) ?>
      <button class="btn btn-primary"><?= h(t('cmp_go')) ?></button>
    </form>

    <?php if ($cmp): [$label, $tone] = $verdicts[$cmp['verdict']]; ?>
      <section class="cmp-result cmp-<?= $cmp['verdict'] ?>" aria-live="polite">
        <h2><?= h(t($label)) ?></h2>
        <ul class="cmp-checks">
          <?php foreach ($cmp['checks'] as [$state, $text]): ?><li class="cmp-<?= $state ?>"><span aria-hidden="true"><?= $icons[$state] ?></span> <?= h($text) ?></li><?php endforeach; ?>
        </ul>
        <div class="grid-2 cmp-codes">
          <div><p class="small"><b><?= h(tr('Requisito', 'Requirement')) ?></b></p><?= $codeSummary($req['result']) ?></div>
          <div><p class="small"><b><?= h(tr('Declaración', 'Declaration')) ?></b></p><?= $codeSummary($decl['result']) ?></div>
        </div>
      </section>
    <?php endif; ?>

    <p class="small muted spaced"><?= h(tr('Una entrega no cumple si es de otro tipo de obra, si usa IA cuando no se permite, si no conserva evidencias cuando se exigen, si en alguna actividad limitada usa la IA con un grado o un alcance mayor que el máximo, o si su Índice Global supera el del requisito. La verificación compara códigos declarados: no comprueba el uso real de IA.', 'A submission does not meet the requirement if it is a different type of work, uses AI when it is not allowed, keeps no evidence when it is required, uses AI in a limited activity with a higher degree or scope than the maximum, or has a Global Index above the requirement’s. The check compares declared codes: it does not verify the actual use of AI.')) ?></p>
    <p><a href="<?= u('instituciones.php') ?>"><?= h(tr('Crear un requisito', 'Create a requirement')) ?></a></p>
  </div>
</div>
<?php page_end();
