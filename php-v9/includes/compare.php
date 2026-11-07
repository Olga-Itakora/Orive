<?php
/**
 * Verificador de compatibilidad entre un requisito ORIDE y una declaración (ORIDE-VERIFY-1.2).
 *
 * Los dos códigos tienen que ser válidos. Después se comprueba, por este orden:
 *
 *  1. Tipo de obra, uso de IA (IA0 en el requisito no admite IA en la declaración) y evidencias
 *     (EV1 en el requisito exige EV1).
 *  2. Categoría del Índice Global (ORIDE-IG-CAT-1.0), en todas las modalidades. Orden A < B < … < F;
 *     la categoría del requisito es la máxima permitida. Una declaración IA0 no supera ninguna.
 *     Si el requisito fija categoría y la declaración no la tiene (IA1 sin actividades o todas a 0),
 *     no es posible determinar la compatibilidad.
 *  3. Actividades, según la modalidad elegida:
 *     - global (predeterminada, «Índices Globales»): solo se comparan las categorías.
 *     - weighted («Actividades ponderadas»): la contribución ponderada de cada actividad
 *       (ig_act_contrib(), la misma fórmula del índice) no puede superar la del requisito.
 *     - digits («Actividades por grado y alcance»): el grado de uso y el alcance no pueden superar,
 *       cada uno por separado, los del requisito.
 *     Actividades del requisito que no aparecen en la declaración: se entiende que no se usó IA en ellas.
 *     Actividades declaradas que el requisito no menciona: aviso para revisión manual (no cambian el resultado).
 *
 * Una categoría baja nunca compensa una actividad que supera su límite.
 */
declare(strict_types=1);

const VERIFY_VERSION = 'ORIDE-VERIFY-1.2';
const VERIFY_MODES = ['global', 'weighted', 'digits'];

/** Política de verificación a partir de la URL (comprobar.php). */
function verify_policy_from_get(): array
{
    return ['mode' => in_array(get('mode'), VERIFY_MODES, true) ? get('mode') : 'global'];
}

/**
 * Estado de un código para el verificador.
 * @return array{state:string, issues:array<int,array{0:string,1:string}>}
 *   state: valid | legacy (válido, de una versión anterior) | incomplete (sin evidencias) | invalid
 *   issues: [error|warn|info, texto]
 */
function verify_code_state(array $parsed): array
{
    if (!$parsed['valid']) return ['state' => 'invalid', 'issues' => [['error', $parsed['error']]]];
    $r = $parsed['result'];
    $issues = [];
    if ($r['all_zero']) $issues[] = ['warn', t('vf_all_zero')];
    foreach ($r['acts'] as [$a, $l, $s]) {
        // En ORIDE Code 3.0 el alcance 0 es «menos del 5 %», no «sin alcance»: ACT30 es válido.
        if ($l >= SCOPE_FROM && $s === 0) $issues[] = ['info', t('vf_scope0', act_part($a, $l, $s), mb_strtolower(scope_labels()[0]))];
    }
    if ($parsed['incomplete']) $issues[] = ['info', t('vf_incomplete')];
    if ($parsed['legacy']) $issues[] = ['info', t('vf_legacy', $r['code'])];
    $state = $parsed['incomplete'] ? 'incomplete' : ($parsed['legacy'] ? 'legacy' : 'valid');
    return ['state' => $state, 'issues' => $issues];
}

/** Compara un intervalo declarado con un máximo: ok | fail | unknown. */
function verify_interval(array $decl, float $max): string
{
    if ($decl[1] <= $max + 1e-9) return 'ok';
    if ($decl[0] > $max + 1e-9) return 'fail';
    return 'unknown';
}

/** Comparación de una actividad que aparece en el requisito y en la declaración. */
function verify_activity(string $type, string $act, array $req, array $decl, string $mode): array
{
    [$lr, $sr] = $req;
    [$ld, $sd] = $decl;
    $row = ['level' => 'na', 'scope' => 'na', 'contrib' => null, 'state' => 'ok', 'reason' => '', 'cause' => null];
    if ($mode === 'weighted') {
        $cr = ig_act_contrib($type, $act, $lr, $sr);
        $cd = ig_act_contrib($type, $act, $ld, $sd);
        if (!$cr || !$cd) return ['state' => 'unknown', 'reason' => t('vf_no_weights')] + $row;
        $row['contrib'] = [$cr['value'], $cd['value']];
        $row['state'] = verify_interval($cd['value'], $cr['value'][1]);
        $row['reason'] = t(['ok' => 'vf_w_ok', 'fail' => 'vf_w_fail', 'unknown' => 'vf_w_unknown'][$row['state']], num_range($cd['value'], 3), num($cr['value'][1], 3));
        if ($row['state'] === 'fail') $row['cause'] = 'weighted';
        return $row;
    }
    // Grado y alcance: cada cifra por separado.
    $row['level'] = $ld <= $lr ? 'ok' : 'fail';
    if ($ld >= SCOPE_FROM && $lr >= SCOPE_FROM) $row['scope'] = $sd <= $sr ? 'ok' : 'fail';
    if ($row['level'] === 'fail') {
        $row['state'] = 'fail';
        $row['cause'] = 'level';
        $row['reason'] = t('vf_level_fail', levels()[$lr], levels()[$ld]);
        if ($row['scope'] === 'fail') $row['reason'] .= ' ' . t('vf_scope_fail', mb_strtolower(scope_labels()[$sr]), mb_strtolower(scope_labels()[$sd]));
    } elseif ($row['scope'] === 'fail') {
        $row['state'] = 'fail';
        $row['cause'] = 'scope';
        $row['reason'] = t('vf_scope_fail', mb_strtolower(scope_labels()[$sr]), mb_strtolower(scope_labels()[$sd]));
    } else {
        $row['reason'] = t('vf_within');
    }
    return $row;
}

/**
 * Comparación de categorías. state: ok | fail | unknown | na (el requisito no fija categoría).
 * @return array{state:string, req:?string, decl:?string, reason:string}
 */
function verify_category(array $req, array $decl): array
{
    $out = ['req' => $req['cat'], 'decl' => $decl['cat']];
    if ($req['ia'] === 0 || $req['cat'] === null) return $out + ['state' => 'na', 'reason' => t($req['ia'] === 0 ? 'vf_cat_req_ia0' : 'vf_cat_req_none')];
    if ($decl['ia'] === 0) return $out + ['state' => 'ok', 'reason' => t('vf_cat_decl_ia0', ig_cat_label($req['cat']))];
    if ($decl['cat'] === null) return $out + ['state' => 'unknown', 'reason' => t('vf_cat_decl_none')];
    $st = ig_compare_cats($req['cat'], $decl['cat']);
    return $out + ['state' => $st, 'reason' => t($st === 'ok' ? 'vf_cat_ok' : 'vf_cat_fail', ig_cat_label($decl['cat']), ig_cat_label($req['cat']))];
}

/**
 * Verificación completa.
 * @return array{version:string, policy:array, verdict:string, req:array, decl:array, checks:array, category:?array, rows:array, alerts:array, parts:array, explain:array}
 *   verdict: compatible | incompatible | indeterminate | invalid
 *   parts: resultado por separado de códigos, categoría y actividades
 */
function verify(array $reqParsed, array $declParsed, array $policy): array
{
    $mode = in_array($policy['mode'] ?? '', VERIFY_MODES, true) ? $policy['mode'] : 'global';
    $out = ['version' => VERIFY_VERSION, 'policy' => ['mode' => $mode], 'req' => verify_code_state($reqParsed), 'decl' => verify_code_state($declParsed),
        'checks' => [], 'category' => null, 'rows' => [], 'alerts' => [], 'explain' => [], 'verdict' => 'invalid',
        'parts' => ['codes' => 'invalid', 'category' => 'na', 'activities' => 'na']];
    if ($out['req']['state'] === 'invalid' || $out['decl']['state'] === 'invalid') return $out;
    $out['parts']['codes'] = 'ok';
    $req = $reqParsed['result'];
    $decl = $declParsed['result'];
    $out['igReq'] = ig_from_result($req);
    $out['igDecl'] = ig_from_result($decl);

    // 1. Tipo de obra, uso de IA y evidencias
    if ($req['type'] !== $decl['type']) {
        $out['checks'][] = ['fail', t('cmp_type_diff', type_name($req['type']), type_name($decl['type']))];
        $out['verdict'] = 'incompatible';
        $out['explain'] = verify_explain($out);
        return $out;
    }
    $out['checks'][] = ['ok', t('cmp_type') . ': ' . type_name($req['type']) . '.'];
    $out['checks'][] = $req['ia'] === 0 && $decl['ia'] === 1 ? ['fail', t('cmp_ia_forbidden')] : ['ok', t('cmp_ia_ok')];
    if ($req['ev'] === 1) $out['checks'][] = $decl['ev'] === 1 ? ['ok', t('cmp_ev_ok')] : ($decl['ev'] === null ? ['unknown', t('cmp_ev_unknown')] : ['fail', t('cmp_ev_missing')]);

    // 2. Categoría del Índice Global (todas las modalidades)
    $out['category'] = verify_category($req, $decl);
    $out['parts']['category'] = $out['category']['state'];

    // 3. Actividades, por su identificador (no por su posición en el código)
    if ($mode !== 'global' && $req['ia'] === 1 && $decl['ia'] === 1) {
        $reqActs = $declActs = [];
        foreach ($req['acts'] as [$a, $l, $s]) $reqActs[$a] = [$l, $s];
        foreach ($decl['acts'] as [$a, $l, $s]) $declActs[$a] = [$l, $s];
        foreach (array_keys(WORK_TYPES[$req['type']]['acts']) as $a) {
            if (!isset($reqActs[$a]) && !isset($declActs[$a])) continue;
            $row = ['act' => $a, 'name' => acts($req['type'])[$a][0], 'req' => $reqActs[$a] ?? null, 'decl' => $declActs[$a] ?? null];
            if (isset($reqActs[$a], $declActs[$a])) {
                $row += verify_activity($req['type'], $a, $reqActs[$a], $declActs[$a], $mode);
            } elseif (isset($reqActs[$a])) {
                $row += ['state' => 'ok', 'reason' => t('vf_absent_unused')];
            } else {
                // No la limita el requisito: aviso para revisión manual, sin cambiar el resultado.
                $row += ['state' => 'alert', 'reason' => t('vf_unspec_alert')];
                $out['alerts'][] = $a;
            }
            $out['rows'][] = $row + ['level' => 'na', 'scope' => 'na', 'contrib' => null, 'cause' => null];
        }
        $rowStates = array_column($out['rows'], 'state');
        $out['parts']['activities'] = in_array('fail', $rowStates, true) ? 'fail' : (in_array('unknown', $rowStates, true) ? 'unknown' : 'ok');
    }

    // 4. Resultado: cualquier incumplimiento → no compatible; si no, una categoría que falta → no determinable.
    $states = array_merge(array_column($out['checks'], 0), [$out['category']['state']], array_column($out['rows'], 'state'));
    $out['verdict'] = in_array('fail', $states, true) ? 'incompatible' : (in_array('unknown', $states, true) ? 'indeterminate' : 'compatible');
    $out['explain'] = verify_explain($out);
    return $out;
}

/** Explicación en lenguaje claro del resultado. */
function verify_explain(array $v): array
{
    $lines = [];
    $failed = array_values(array_filter($v['rows'], fn($r) => $r['state'] === 'fail'));
    if ($failed && ($v['category']['state'] ?? null) === 'ok') {
        $f = $failed[0];
        $what = t(['level' => 'vf_cause_level', 'scope' => 'vf_cause_scope', 'weighted' => 'vf_cause_weighted'][$f['cause'] ?? 'level']);
        $lines[] = t('vf_explain_cat_act', ig_cat_label($v['category']['decl']), ig_cat_label($v['category']['req']), $f['act'], $what);
    }
    if ($v['alerts']) $lines[] = t('vf_alerts', implode(', ', $v['alerts']));
    if (array_filter(array_merge($v['req']['issues'], $v['decl']['issues']), fn($i) => $i[0] === 'warn')) $lines[] = t('vf_explain_incoherent');
    return $lines;
}

/** Resultado en texto plano, para el botón «Copiar resultado». */
function verify_text(array $v, array $reqParsed, array $declParsed): string
{
    $lines = [t('vf_text_title') . ' (' . VERIFY_VERSION . ')',
        t('vf_s_req') . ': ' . $reqParsed['result']['code'],
        t('vf_s_decl') . ': ' . $declParsed['result']['code'],
        t('vf_s_mode') . ': ' . t('vf_mode_' . $v['policy']['mode']),
        t('vf_s_result') . ': ' . t('vf_' . $v['verdict'])];
    foreach ($v['checks'] as [, $text]) $lines[] = '- ' . $text;
    if ($v['category']) $lines[] = '- ' . $v['category']['reason'];
    foreach ($v['rows'] as $row) $lines[] = '- ' . act_part($row['act'], ...($row['decl'] ?? [null, null])) . ' (' . $row['name'] . '): ' . $row['reason'];
    foreach ($v['explain'] as $line) $lines[] = $line;
    $lines[] = t('vf_version', VERIFY_VERSION, IG_CAT_VERSION . ' · ' . IG_CURRENT);
    return implode("\n", $lines);
}
