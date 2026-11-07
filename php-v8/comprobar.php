<?php
/** Instituciones › Verificar una entrega: compatibilidad entre un requisito y una declaración (ORIDE-VERIFY-1.1). */
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/builder.php';

$rawReq = mb_substr(get('r'), 0, 200);
$rawDecl = mb_substr(get('d'), 0, 200);
$policy = verify_policy_from_get();
$req = $rawReq !== '' ? oride_explain($rawReq) : null;
$decl = $rawDecl !== '' ? oride_explain($rawDecl) : null;
$v = $req && $decl ? verify($req, $decl, $policy) : null;
$igReqHint = $req && $req['valid'] ? ig_from_result($req['result']) : null;

$verdictTone = ['compatible' => 'ok', 'incompatible' => 'fail', 'indeterminate' => 'unknown', 'invalid' => 'fail'];
$icons = ['ok' => '✓', 'fail' => '✗', 'unknown' => '?', 'info' => 'i', 'warn' => '!', 'error' => '✗'];
$stateLabel = fn(string $st) => t(['ok' => 'vf_r_ok', 'fail' => 'vf_r_fail', 'unknown' => 'vf_r_unknown', 'info' => 'vf_r_info'][$st] ?? 'vf_r_info');

$field = function (string $name, string $label, ?array $parsed, string $raw) {
    $err = $parsed && !$parsed['valid'] ? $parsed['error'] : '';
    return '<div class="field"><label for="' . $name . '">' . h($label) . '</label>'
        . '<textarea id="' . $name . '" name="' . $name . '" class="mono" rows="2" maxlength="200" spellcheck="false" placeholder="LI-IA1-EV1&#10;DOC44-GEN24" aria-describedby="' . $name . '-error"' . ($err ? ' aria-invalid="true"' : '') . '>' . h($raw) . '</textarea>'
        . '<p class="field-error" id="' . $name . '-error"' . ($err ? '' : ' hidden') . '>' . h($err) . '</p></div>';
};
$radio = fn(string $name, string $value, string $current, string $label, string $desc = '') => choice($name, $value, 'radio', $current === $value, h($label), $desc);
$codeLines = fn(array $parsed) => $parsed['valid'] ? implode('<br>', array_map('h', $parsed['result']['lines'])) : h($parsed['code']);
$level = fn(?array $x) => $x === null ? '—' : $x[0] . ' · ' . levels()[$x[0]];
$scope = fn(?array $x) => $x === null || $x[0] < SCOPE_FROM ? '—' : $x[1] . ' · ' . scope_labels()[$x[1]];

page_start(t('cmp_title'), tr('Comprueba si el Código ORIDE de una entrega es compatible con el requisito que fijaste.', 'Check whether the ORIDE Code of a submission is compatible with the requirement you set.'));
?>
<div class="container page">
  <?= inst_header('comprobar.php') ?>
  <div class="narrow-text">
    <p class="lead"><?= h(tr('Pega el código que fijaste como requisito y el que declaró la persona autora.', 'Paste the code you set as a requirement and the one the author declared.')) ?></p>
    <form class="card" method="get" action="<?= u('comprobar.php') ?>">
      <?= lang_field() ?>
      <?= $field('r', t('cmp_req'), $req, $rawReq) ?>
      <?= $field('d', t('cmp_decl'), $decl, $rawDecl) ?>
      <details class="vf-options"<?= ($policy['mode'] !== 'digits' || $policy['global'] || $policy['unspecified'] !== 'allow' || $policy['absent'] !== 'review') ? ' open' : '' ?>>
        <summary><?= h(t('vf_options')) ?></summary>
        <fieldset class="choices q"><legend><?= h(t('vf_mode')) ?></legend><div class="choice-list">
          <?= $radio('mode', 'digits', $policy['mode'], t('vf_mode_digits'), t('vf_mode_digits_d')) . $radio('mode', 'weighted', $policy['mode'], t('vf_mode_weighted'), t('vf_mode_weighted_d')) ?>
        </div></fieldset>
        <fieldset class="choices q"><legend><?= h(t('vf_global_title')) ?></legend><div class="choice-list">
          <?= choice('g', '1', 'checkbox', $policy['global'], h(t('vf_global'))) ?>
        </div>
          <div class="field qp-field"><label for="gl"><?= h(t('vf_global_limit')) ?></label><input id="gl" name="gl" type="number" min="0" max="100" step="1" inputmode="numeric" value="<?= $policy['limit'] === null ? '' : h(num($policy['limit'], 1)) ?>" aria-describedby="gl-hint"><span class="qp-unit">%</span>
          <p class="hint" id="gl-hint"><?= h($igReqHint && $igReqHint['status'] === 'ok' ? t('vf_global_hint', ig_percent($igReqHint)) : tr('Opcional. Solo cuenta si marcas la casilla anterior.', 'Optional. It only counts if you tick the box above.')) ?></p></div>
        </fieldset>
        <fieldset class="choices q"><legend><?= h(t('vf_unspec')) ?></legend><div class="choice-list">
          <?= $radio('unspec', 'allow', $policy['unspecified'], t('vf_unspec_o_allow')) . $radio('unspec', 'forbid', $policy['unspecified'], t('vf_unspec_o_forbid')) . $radio('unspec', 'review', $policy['unspecified'], t('vf_unspec_o_review')) ?>
        </div></fieldset>
        <fieldset class="choices q"><legend><?= h(t('vf_absent')) ?></legend><div class="choice-list">
          <?= $radio('absent', 'review', $policy['absent'], t('vf_absent_o_review')) . $radio('absent', 'unused', $policy['absent'], t('vf_absent_o_unused')) ?>
        </div></fieldset>
      </details>
      <button class="btn btn-primary"><?= h(t('cmp_go')) ?></button>
    </form>
  </div>

  <?php if ($v): $tone = $verdictTone[$v['verdict']]; ?>
    <section class="cmp-result cmp-<?= $tone ?>" aria-labelledby="vf-verdict">
      <h2 id="vf-verdict"><span aria-hidden="true"><?= $icons[$tone] ?></span> <?= h(t('vf_' . $v['verdict'])) ?></h2>
      <?php foreach ($v['explain'] as $line): ?><p class="vf-explain"><?= h($line) ?></p><?php endforeach; ?>

      <?php $issues = array_merge(array_map(fn($i) => [$i[0], t('vf_s_req') . ': ' . $i[1]], $v['req']['issues']), array_map(fn($i) => [$i[0], t('vf_s_decl') . ': ' . $i[1]], $v['decl']['issues'])); ?>
      <?php if ($issues): ?>
        <ul class="cmp-checks vf-issues"><?php foreach ($issues as [$sev, $text]): ?><li class="cmp-<?= ['error' => 'fail', 'warn' => 'unknown', 'info' => 'info'][$sev] ?>"><span aria-hidden="true"><?= $icons[$sev] ?></span> <?= h($text) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>

      <?php if ($v['verdict'] !== 'invalid'): ?>
        <h3><?= h(t('vf_summary')) ?></h3>
        <dl class="vf-summary">
          <div><dt><?= h(t('vf_s_req')) ?></dt><dd class="mono"><?= $codeLines($req) ?></dd></div>
          <div><dt><?= h(t('vf_s_decl')) ?></dt><dd class="mono"><?= $codeLines($decl) ?></dd></div>
          <div><dt><?= h(t('vf_s_mode')) ?></dt><dd><?= h(t($policy['mode'] === 'weighted' ? 'vf_s_mode_weighted' : 'vf_s_mode_digits') . ($policy['global'] ? t('vf_s_plus_global') : '')) ?></dd></div>
          <div><dt><?= h(t('vf_s_ig_req')) ?></dt><dd><?= h($v['igReq']['status'] === 'ok' ? ig_percent($v['igReq']) : t('ig_pending')) ?></dd></div>
          <?php if ($policy['global']): ?><div><dt><?= h(t('vf_s_ig_limit')) ?></dt><dd><?= h($policy['limit'] === null ? '—' : num($policy['limit'], 1) . tr(' %', '%')) ?></dd></div><?php endif; ?>
          <div><dt><?= h(t('vf_s_ig_decl')) ?></dt><dd><?= h($v['igDecl']['status'] === 'ok' ? ig_percent($v['igDecl']) : t('ig_pending')) ?></dd></div>
          <div><dt><?= h(t('vf_s_codes')) ?></dt><dd><?= h(t('vf_s_req') . ': ' . t('vf_st_' . $v['req']['state']) . ' · ' . t('vf_s_decl') . ': ' . t('vf_st_' . $v['decl']['state'])) ?></dd></div>
          <div><dt><?= h(t('vf_s_result')) ?></dt><dd><b><?= h(t('vf_' . $v['verdict'])) ?></b></dd></div>
        </dl>

        <h3><?= h(t('vf_checks')) ?></h3>
        <ul class="cmp-checks"><?php foreach ($v['checks'] as [$st, $text]): ?><li class="cmp-<?= $st ?>"><span aria-hidden="true"><?= $icons[$st] ?></span> <span class="sr-only"><?= h($stateLabel($st)) ?>:</span> <?= h($text) ?></li><?php endforeach; ?></ul>

        <?php if ($v['rows']): ?>
          <div class="table-scroll" role="region" aria-labelledby="vf-table-cap" tabindex="0">
            <table class="vf-table">
              <caption id="vf-table-cap"><?= h(t('vf_t_caption')) ?></caption>
              <thead><tr>
                <th scope="col"><?= h(t('vf_t_act')) ?></th>
                <?php if ($policy['mode'] === 'weighted'): ?><th scope="col"><?= h(t('vf_t_w')) ?></th>
                <?php else: ?><th scope="col"><?= h(t('vf_t_lr')) ?></th><th scope="col"><?= h(t('vf_t_ld')) ?></th><th scope="col"><?= h(t('vf_t_sr')) ?></th><th scope="col"><?= h(t('vf_t_sd')) ?></th><?php endif; ?>
                <th scope="col"><?= h(t('vf_t_res')) ?></th><th scope="col"><?= h(t('vf_t_why')) ?></th>
              </tr></thead>
              <tbody>
                <?php foreach ($v['rows'] as $row): ?>
                  <tr class="vf-row-<?= $row['state'] ?>">
                    <th scope="row"><span class="mono"><?= h($row['act']) ?></span> <?= h($row['name']) ?></th>
                    <?php if ($policy['mode'] === 'weighted'): ?>
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

        <?php if ($v['global']): ?>
          <h3><?= h(t('vf_global_title')) ?></h3>
          <ul class="cmp-checks"><li class="cmp-<?= $v['global']['state'] ?>"><span aria-hidden="true"><?= $icons[$v['global']['state']] ?></span> <span class="sr-only"><?= h($stateLabel($v['global']['state'])) ?>:</span> <?= h($v['global']['reason']) ?></li></ul>
        <?php endif; ?>
      <?php endif; ?>
      <p class="vf-note"><?= h(t('vf_note')) ?></p>
      <p class="meta"><?= h(t('vf_version', VERIFY_VERSION, IG_CURRENT)) ?></p>
    </section>
  <?php endif; ?>

  <div class="narrow-text">
    <details class="faq spaced"><summary><?= h(t('vf_rules')) ?></summary>
      <?php if (en()): ?>
        <p>Activities are compared by their identifier. By default, each declared activity may not exceed the requirement’s degree of use (first digit) or scope (second digit), each separately. In the weighted mode, the activity’s contribution to the ORIDE Global Index is compared instead, so a higher degree can be offset by a smaller scope. The global limit only applies if you set it: the requirement’s estimated index is shown for reference. If the declared index is a range that falls on both sides of the limit, the result is undetermined. The global index never offsets a failed activity. A requirement with IA0 does not allow AI; with EV1 it requires evidence. Scope 0 means “less than 5%” of the work, so codes such as GEN30 are valid.</p>
      <?php else: ?>
        <p>Las actividades se comparan por su identificador. Por defecto, cada actividad declarada no puede superar el grado de uso (primera cifra) ni el alcance (segunda cifra) del requisito, cada uno por separado. En el modo ponderado se compara, en cambio, la contribución de la actividad al Índice Global ORIDE, de modo que un grado mayor se puede compensar con un alcance menor. El límite global solo se aplica si lo fijas: el índice estimado del requisito se muestra como referencia. Si el índice declarado es un intervalo que queda a ambos lados del límite, el resultado es indeterminado. El índice global nunca compensa una actividad que no cumple. Un requisito con IA0 no permite IA; con EV1 exige evidencias. El alcance 0 significa «menos del 5 %» de la obra, así que códigos como GEN30 son válidos.</p>
      <?php endif; ?>
    </details>
    <p><a class="link-24" href="<?= u('instituciones.php') ?>"><?= h(tr('Crear un requisito', 'Create a requirement')) ?></a></p>
  </div>
</div>
<?php page_end();
