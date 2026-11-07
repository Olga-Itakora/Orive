<?php
/**
 * Instituciones › Verificar una entrega: compatibilidad entre un requisito y una declaración (ORIDE-VERIFY-1.2).
 * Dos columnas: a la izquierda, los códigos y la modalidad de comparación; a la derecha, el resultado.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/builder.php';

$rawReq = mb_substr(get('r'), 0, 200);
$rawDecl = mb_substr(get('d'), 0, 200);
$policy = verify_policy_from_get();
$req = $rawReq !== '' ? oride_explain($rawReq) : null;
$decl = $rawDecl !== '' ? oride_explain($rawDecl) : null;
$v = $req && $decl && $req['valid'] && $decl['valid'] ? verify($req, $decl, $policy) : null;
$mode = $policy['mode'];

$verdictTone = ['compatible' => 'ok', 'incompatible' => 'fail', 'indeterminate' => 'unknown', 'invalid' => 'fail'];
$icons = ['ok' => '✓', 'fail' => '✗', 'unknown' => '?', 'info' => 'i', 'warn' => '!', 'error' => '✗', 'alert' => '!', 'na' => '–'];
$stateLabel = fn(string $st) => t(['ok' => 'vf_r_ok', 'fail' => 'vf_r_fail', 'unknown' => 'vf_r_unknown', 'alert' => 'vf_r_alert'][$st] ?? 'vf_r_info');
$partLabel = fn(string $st) => t(['ok' => 'vf_p_ok', 'fail' => 'vf_p_fail', 'unknown' => 'vf_p_unknown', 'na' => 'vf_p_na'][$st] ?? 'vf_p_na');

$field = function (string $name, string $label, ?array $parsed, string $raw) {
    $err = $parsed && !$parsed['valid'] ? $parsed['error'] : '';
    return '<div class="field"><label for="' . $name . '">' . h($label) . '</label>'
        . '<textarea id="' . $name . '" name="' . $name . '" class="mono" rows="2" maxlength="200" spellcheck="false" placeholder="LI-IGB-EV1&#10;DOC44-GEN24" aria-describedby="' . $name . '-error"' . ($err ? ' aria-invalid="true"' : '') . '>' . h($raw) . '</textarea>'
        . '<p class="field-error" id="' . $name . '-error"' . ($err ? '' : ' hidden') . '>' . h($err) . '</p></div>';
};
$codeLines = fn(array $parsed) => implode('<br>', array_map('h', $parsed['result']['lines']));
$level = fn(?array $x) => $x === null ? '—' : $x[0] . ' · ' . levels()[$x[0]];
$scope = fn(?array $x) => $x === null || $x[0] < SCOPE_FROM ? '—' : $x[1] . ' · ' . scope_labels()[$x[1]];
$catText = fn(array $r) => $r['ia'] === 0 ? t('ig_no_ai') : ($r['cat'] !== null ? ig_cat_label($r['cat']) : t('ig_pending'));

page_start(t('cmp_title'), tr('Comprueba si el Código ORIDE de una entrega es compatible con el requisito que fijaste.', 'Check whether the ORIDE Code of a submission is compatible with the requirement you set.'));
?>
<div class="container page">
  <?= inst_header('comprobar.php') ?>
  <div class="vf-layout">
    <form class="card vf-form" method="get" action="<?= u('comprobar.php') ?>">
      <?= lang_field() ?>
      <?= $field('r', t('cmp_req'), $req, $rawReq) ?>
      <?= $field('d', t('cmp_decl'), $decl, $rawDecl) ?>
      <fieldset class="choices q"><legend><?= h(t('vf_mode')) ?></legend><div class="choice-list">
        <?php foreach (VERIFY_MODES as $m) echo choice('mode', $m, 'radio', $mode === $m, h(t('vf_mode_' . $m)), t('vf_mode_' . $m . '_d')); ?>
      </div></fieldset>
      <button class="btn btn-primary"><?= h(t('cmp_go')) ?></button>
    </form>

    <div class="vf-out">
      <?php if (!$v): ?>
        <div class="card vf-empty" role="status"><p><?= h(t(($req && !$req['valid']) || ($decl && !$decl['valid']) ? 'vf_fix_codes' : ($req || $decl ? 'vf_both_codes' : 'vf_empty'))) ?></p></div>
      <?php else: $tone = $verdictTone[$v['verdict']]; ?>
        <section class="cmp-result cmp-<?= $tone ?>" aria-labelledby="vf-verdict">
          <div class="vf-head">
            <h2 id="vf-verdict"><span aria-hidden="true"><?= $icons[$tone] ?></span> <?= h(t('vf_' . $v['verdict'])) ?></h2>
            <button type="button" class="btn btn-secondary" data-copy-text="<?= h(verify_text($v, $req, $decl)) ?>"><?= h(t('vf_copy_result')) ?></button>
          </div>
          <?php foreach ($v['explain'] as $line): ?><p class="vf-explain"><?= h($line) ?></p><?php endforeach; ?>

          <?php $issues = array_merge(array_map(fn($i) => [$i[0], t('vf_s_req') . ': ' . $i[1]], $v['req']['issues']), array_map(fn($i) => [$i[0], t('vf_s_decl') . ': ' . $i[1]], $v['decl']['issues'])); ?>
          <?php if ($issues): ?>
            <ul class="cmp-checks vf-issues"><?php foreach ($issues as [$sev, $text]): ?><li class="cmp-<?= ['error' => 'fail', 'warn' => 'unknown', 'info' => 'info'][$sev] ?>"><span aria-hidden="true"><?= $icons[$sev] ?></span> <?= h($text) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>

          <?php if ($v['category']): $c = $v['category']; ?>
            <h3><?= h(t('vf_cat_title')) ?></h3>
            <ul class="cmp-checks"><li class="cmp-<?= $c['state'] === 'na' ? 'info' : $c['state'] ?>"><span aria-hidden="true"><?= $icons[$c['state']] ?></span> <span class="sr-only"><?= h($partLabel($c['state'])) ?>:</span> <?= h($c['reason']) ?></li></ul>
            <?php if ($c['req'] !== null): ?><p class="vf-precision"><?= h(t('vf_cat_precision')) ?></p><?php endif; ?>
          <?php endif; ?>

          <h3><?= h(t('vf_checks')) ?></h3>
          <ul class="cmp-checks"><?php foreach ($v['checks'] as [$st, $text]): ?><li class="cmp-<?= $st ?>"><span aria-hidden="true"><?= $icons[$st] ?></span> <span class="sr-only"><?= h($stateLabel($st)) ?>:</span> <?= h($text) ?></li><?php endforeach; ?></ul>

          <?php if ($v['rows']): ?>
            <h3><?= h(t('vf_acts_title')) ?></h3>
            <div class="table-scroll" role="region" aria-labelledby="vf-table-cap" tabindex="0">
              <table class="vf-table">
                <caption id="vf-table-cap"><?= h(t('vf_t_caption')) ?> · <?= h(t('vf_mode_' . $mode)) ?></caption>
                <thead><tr>
                  <th scope="col"><?= h(t('vf_t_act')) ?></th>
                  <?php if ($mode === 'weighted'): ?><th scope="col"><?= h(t('vf_t_w')) ?></th>
                  <?php else: ?><th scope="col"><?= h(t('vf_t_lr')) ?></th><th scope="col"><?= h(t('vf_t_ld')) ?></th><th scope="col"><?= h(t('vf_t_sr')) ?></th><th scope="col"><?= h(t('vf_t_sd')) ?></th><?php endif; ?>
                  <th scope="col"><?= h(t('vf_t_res')) ?></th><th scope="col"><?= h(t('vf_t_why')) ?></th>
                </tr></thead>
                <tbody>
                  <?php foreach ($v['rows'] as $row): ?>
                    <tr class="vf-row-<?= $row['state'] ?>">
                      <th scope="row"><span class="mono"><?= h($row['act']) ?></span> <?= h($row['name']) ?></th>
                      <?php if ($mode === 'weighted'): ?>
                        <td><?= $row['contrib'] ? h(num_range($row['contrib'][0], 3) . ' / ' . num_range($row['contrib'][1], 3)) : '—' ?></td>
                      <?php else: ?>
                        <td><?= h($level($row['req'])) ?></td>
                        <td class="vf-cell-<?= $row['level'] ?>"><?= h($level($row['decl'])) ?></td>
                        <td><?= h($scope($row['req'])) ?></td>
                        <td class="vf-cell-<?= $row['scope'] ?>"><?= h($scope($row['decl'])) ?></td>
                      <?php endif; ?>
                      <td class="vf-state"><span aria-hidden="true"><?= $icons[$row['state']] ?></span> <?= h($stateLabel($row['state'])) ?></td>
                      <td><?= h($row['reason']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <details class="vf-sum-details">
            <summary><?= h(t('vf_summary')) ?></summary>
            <dl class="vf-summary">
              <div><dt><?= h(t('vf_s_req')) ?></dt><dd><span class="mono"><?= $codeLines($req) ?></span><br><?= h(t('ig_box_title_max') . ': ' . $catText($req['result'])) ?></dd></div>
              <div><dt><?= h(t('vf_s_decl')) ?></dt><dd><span class="mono"><?= $codeLines($decl) ?></span><br><?= h(t('ig_box_title') . ': ' . $catText($decl['result'])) ?></dd></div>
              <div><dt><?= h(t('vf_s_mode')) ?></dt><dd><?= h(t('vf_mode_' . $mode)) ?></dd></div>
              <div><dt><?= h(t('vf_p_codes')) ?></dt><dd><?= h(t('vf_p_valid')) ?></dd></div>
              <div><dt><?= h(t('vf_p_cat')) ?></dt><dd><?= h($partLabel($v['parts']['category'])) ?></dd></div>
              <div><dt><?= h(t('vf_p_acts')) ?></dt><dd><?= h($partLabel($v['parts']['activities'])) ?></dd></div>
              <div><dt><?= h(t('vf_s_result')) ?></dt><dd><b><?= h(t('vf_' . $v['verdict'])) ?></b></dd></div>
            </dl>
          </details>
          <p class="vf-note"><?= h(t('vf_note')) ?></p>
          <p class="meta"><?= h(t('vf_version', VERIFY_VERSION, IG_CAT_VERSION . ' · ' . IG_CURRENT)) ?></p>
        </section>
      <?php endif; ?>
      <p><a class="link-24" href="<?= u('instituciones.php') ?>"><?= h(tr('Crear un requisito', 'Create a requirement')) ?></a></p>
    </div>
  </div>
</div>
<?php page_end();
