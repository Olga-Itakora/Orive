<?php
/**
 * Verificador de compatibilidad entre un requisito ORIDE y una declaración (ORIDE-VERIFY-1.1).
 *
 * La compatibilidad depende de las reglas que elige quien fija el requisito (la «política»):
 *
 *  A. Actividades — modo «digits» (predeterminado): en cada actividad del requisito, el grado de uso
 *     declarado (1.ª cifra) y el alcance declarado (2.ª cifra) no pueden superar los del requisito,
 *     cada uno por separado.
 *  B. Actividades — modo «weighted»: se compara la contribución ponderada de cada actividad
 *     (ig_act_contrib(), la misma fórmula del Índice Global ORIDE) con la máxima del requisito.
 *     Permite compensar más grado con menos alcance dentro de una actividad.
 *  C. Límite global (opcional): un Índice global máximo en %, fijado expresamente. El índice estimado
 *     del código de requisito se muestra como referencia, pero no se usa como límite por sí solo.
 *
 * Además: tipo de obra, uso de IA (IA0 en el requisito prohíbe IA1) y evidencias (EV1 exige EV1).
 * Actividades del requisito que no aparecen en la declaración: «review» (indeterminado, predeterminado)
 * o «unused» (se entiende que no se usó IA en ellas). Actividades declaradas que el requisito no
 * menciona: «allow» (predeterminado; cuentan en el índice), «forbid» o «review».
 *
 * El índice global nunca compensa el incumplimiento de una actividad.
 */
declare(strict_types=1);

const VERIFY_VERSION = 'ORIDE-VERIFY-1.1';

/** Política de verificación a partir de la URL (comprobar.php). */
function verify_policy_from_get(): array
{
    $limit = str_replace(',', '.', get('gl'));
    return [
        'mode' => get('mode') === 'weighted' ? 'weighted' : 'digits',
        'global' => get('g') === '1',
        'limit' => is_numeric($limit) && (float) $limit >= 0 && (float) $limit <= 100 ? (float) $limit : null,
        'unspecified' => in_array(get('unspec'), ['forbid', 'review'], true) ? get('unspec') : 'allow',
        'absent' => get('absent') === 'unused' ? 'unused' : 'review',
    ];
}

/**
 * Estado de un código para el verificador.
 * @return array{state:string, issues:array<int,array{0:string,1:string}>}
 *   state: valid | legacy (válido, de una versión anterior) | incomplete (falta la tríada completa) | invalid
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
    // Límites independientes: cada cifra por separado.
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
 * Verificación completa.
 * @return array{version:string, verdict:string, req:array, decl:array, checks:array, rows:array, global:?array, explain:array}
 *   verdict: compatible | incompatible | indeterminate | invalid
 */
function verify(array $reqParsed, array $declParsed, array $policy): array
{
    $out = ['version' => VERIFY_VERSION, 'policy' => $policy, 'req' => verify_code_state($reqParsed), 'decl' => verify_code_state($declParsed),
        'checks' => [], 'rows' => [], 'global' => null, 'explain' => [], 'verdict' => 'invalid'];
    if ($out['req']['state'] === 'invalid' || $out['decl']['state'] === 'invalid') return $out;
    $req = $reqParsed['result'];
    $decl = $declParsed['result'];
    $out['igReq'] = ig_from_result($req);
    $out['igDecl'] = ig_from_result($decl);

    // 1. Tipo de obra, uso de IA y evidencias
    if ($req['type'] !== $decl['type']) {
        $out['checks'][] = ['fail', t('cmp_type_diff', type_name($req['type']), type_name($decl['type']))];
        $out['verdict'] = 'incompatible';
        return $out;
    }
    $out['checks'][] = ['ok', t('cmp_type') . ': ' . type_name($req['type']) . '.'];
    $out['checks'][] = $req['ia'] === 0 && $decl['ia'] === 1 ? ['fail', t('cmp_ia_forbidden')] : ['ok', t('cmp_ia_ok')];
    if ($req['ev'] === 1) $out['checks'][] = $decl['ev'] === 1 ? ['ok', t('cmp_ev_ok')] : ($decl['ev'] === null ? ['unknown', t('cmp_ev_unknown')] : ['fail', t('cmp_ev_missing')]);

    // 2. Actividades, por su identificador (no por su posición en el código)
    if ($req['ia'] === 1 && $decl['ia'] === 1) {
        $reqActs = $declActs = [];
        foreach ($req['acts'] as [$a, $l, $s]) $reqActs[$a] = [$l, $s];
        foreach ($decl['acts'] as [$a, $l, $s]) $declActs[$a] = [$l, $s];
        foreach (array_keys(WORK_TYPES[$req['type']]['acts']) as $a) {
            if (!isset($reqActs[$a]) && !isset($declActs[$a])) continue;
            $row = ['act' => $a, 'name' => acts($req['type'])[$a][0], 'req' => $reqActs[$a] ?? null, 'decl' => $declActs[$a] ?? null];
            if (isset($reqActs[$a], $declActs[$a])) {
                $row += verify_activity($req['type'], $a, $reqActs[$a], $declActs[$a], $policy['mode']);
            } elseif (isset($reqActs[$a])) {
                $row += $policy['absent'] === 'unused'
                    ? ['state' => 'ok', 'reason' => t('vf_absent_unused')]
                    : ['state' => 'unknown', 'reason' => t('vf_absent_review')];
            } else {
                $row += ['state' => ['allow' => 'ok', 'forbid' => 'fail', 'review' => 'unknown'][$policy['unspecified']],
                    'reason' => t(['allow' => 'vf_unspec_allow', 'forbid' => 'vf_unspec_forbid', 'review' => 'vf_unspec_review'][$policy['unspecified']])];
                if ($row['state'] === 'fail') $row['cause'] = 'unspecified';
            }
            $out['rows'][] = $row + ['level' => 'na', 'scope' => 'na', 'contrib' => null, 'cause' => null];
        }
        if (!$decl['acts'] && $req['acts']) $out['checks'][] = ['unknown', t('vf_decl_no_acts')];
    }

    // 3. Límite global (solo si se ha fijado expresamente)
    if ($policy['global']) {
        if ($policy['limit'] === null) {
            $out['global'] = ['state' => 'unknown', 'reason' => t('vf_g_nolimit')];
        } elseif ($out['igDecl']['status'] !== 'ok') {
            $out['global'] = ['state' => 'unknown', 'reason' => t('vf_g_decl_nc')];
        } else {
            // Se compara lo que se muestra: el índice redondeado a enteros.
            $shown = [round($out['igDecl']['IG'][0]), round($out['igDecl']['IG'][1])];
            $st = verify_interval($shown, $policy['limit']);
            $out['global'] = ['state' => $st, 'reason' => t(['ok' => 'vf_g_ok', 'fail' => 'vf_g_fail', 'unknown' => 'vf_g_unknown'][$st], ig_percent($out['igDecl']), num($policy['limit'], 1) . tr(' %', '%'))];
        }
    }

    // 4. Resultado: cualquier incumplimiento → no compatible; si no, cualquier duda → indeterminado.
    $states = array_merge(array_column($out['checks'], 0), array_column($out['rows'], 'state'), $out['global'] ? [$out['global']['state']] : []);
    $out['verdict'] = in_array('fail', $states, true) ? 'incompatible' : (in_array('unknown', $states, true) ? 'indeterminate' : 'compatible');
    // Un código incoherente no permite un resultado definitivo.
    $warn = array_filter(array_merge($out['req']['issues'], $out['decl']['issues']), fn($i) => $i[0] === 'warn');
    if ($warn) $out['verdict'] = 'indeterminate';

    $out['explain'] = verify_explain($out);
    return $out;
}

/** Explicación en lenguaje claro del resultado. */
function verify_explain(array $v): array
{
    $lines = [];
    $failed = array_values(array_filter($v['rows'], fn($r) => $r['state'] === 'fail'));
    $igLower = $v['igDecl']['status'] === 'ok' && $v['igReq']['status'] === 'ok' && $v['igDecl']['IG'][1] <= $v['igReq']['IG'][1] + 1e-9;
    if ($failed && ($igLower || ($v['global']['state'] ?? null) === 'ok')) {
        $f = $failed[0];
        $what = t(['level' => 'vf_cause_level', 'scope' => 'vf_cause_scope', 'weighted' => 'vf_cause_weighted', 'unspecified' => 'vf_cause_unspec'][$f['cause'] ?? 'level']);
        $lines[] = t('vf_explain_lower', $f['act'], $what);
    }
    if (array_filter(array_merge($v['req']['issues'], $v['decl']['issues']), fn($i) => $i[0] === 'warn')) $lines[] = t('vf_explain_incoherent');
    return $lines;
}
