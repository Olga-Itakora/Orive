<?php
/**
 * Comprueba si una declaración cumple un requisito de una institución (uso máximo de IA).
 *
 * Una declaración NO cumple si:
 *  - es de otro tipo de obra;
 *  - el requisito es IA0 y la declaración dice que se usó IA;
 *  - el requisito exige evidencias (EV1) y la declaración no las tiene;
 *  - en una actividad que el requisito limita, la declaración tiene un grado de uso mayor,
 *    o el mismo grado (o menor, desde 2) con un alcance mayor;
 *  - su Índice Global es seguro mayor que el máximo del requisito.
 * Si los índices son intervalos que se solapan, o la declaración no permite calcular su índice,
 * el resultado es «no se puede verificar del todo».
 */
declare(strict_types=1);

/** @return array{verdict:string, checks:array<int,array{0:string,1:string}>} verdict: ok | fail | unknown; cada check: [ok|fail|unknown|info, texto] */
function oride_compare(array $req, array $decl): array
{
    $checks = [];
    if ($req['type'] !== $decl['type']) {
        return ['verdict' => 'fail', 'checks' => [['fail', t('cmp_type_diff', type_name($req['type']), type_name($decl['type']))]]];
    }
    $checks[] = ['ok', t('cmp_type') . ': ' . type_name($req['type']) . '.'];

    if ($req['ia'] === 0 && $decl['ia'] === 1) $checks[] = ['fail', t('cmp_ia_forbidden')];
    else $checks[] = ['ok', t('cmp_ia_ok')];

    if ($req['ev'] === 1) $checks[] = $decl['ev'] === 1 ? ['ok', t('cmp_ev_ok')] : [$decl['ev'] === null ? 'unknown' : 'fail', t('cmp_ev_missing')];

    if ($decl['ia'] === 1 && $req['ia'] === 1) {
        $reqActs = [];
        foreach ($req['acts'] as [$a, $l, $s]) $reqActs[$a] = [$l, $s];
        $free = [];
        foreach ($decl['acts'] as [$a, $l, $s]) {
            $name = acts($decl['type'])[$a][0];
            if (!isset($reqActs[$a])) { $free[] = $name; continue; }
            [$lr, $sr] = $reqActs[$a];
            $over = $l > $lr || ($l >= SCOPE_FROM && ($lr < SCOPE_FROM || $s > $sr));
            $checks[] = [$over ? 'fail' : 'ok', t($over ? 'cmp_act_over' : 'cmp_act_ok', $name, act_part($a, $l, $s), act_part($a, $lr, $sr))];
        }
        if ($free) $checks[] = ['info', t('cmp_act_free', implode(', ', $free))];
    }

    // Índice Global: el máximo es el extremo superior del índice del requisito.
    $igReq = ig_from_result($req);
    $igDecl = ig_from_result($decl);
    if ($req['ia'] === 1) {
        if ($igReq['status'] !== 'ok') {
            $checks[] = ['info', t('cmp_ig_none')];
        } elseif ($igDecl['status'] !== 'ok') {
            $checks[] = ['unknown', t('cmp_ig_decl_unknown')];
        } else {
            $max = $igReq['IG'][1];
            $d = ig_percent($igDecl);
            $m = ig_percent(['IG' => [$max, $max]]);
            if ($igDecl['IG'][1] <= $max + 1e-9) $checks[] = ['ok', t('cmp_ig_ok', $d, $m)];
            elseif ($igDecl['IG'][0] > $max + 1e-9) $checks[] = ['fail', t('cmp_ig_over', $d, $m)];
            else $checks[] = ['unknown', t('cmp_ig_overlap', $d, $m)];
        }
    }

    $states = array_column($checks, 0);
    $verdict = in_array('fail', $states, true) ? 'fail' : (in_array('unknown', $states, true) ? 'unknown' : 'ok');
    return ['verdict' => $verdict, 'checks' => $checks];
}
