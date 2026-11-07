<?php
/** Pruebas de ORIDE v8. Uso: php tests/run.php */
declare(strict_types=1);
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SERVER['SCRIPT_NAME'] = '/tests/run.php';
require __DIR__ . '/../includes/app.php';
restore_exception_handler();

$ok = 0; $fail = 0;
function check(string $name, bool $cond): void
{
    global $ok, $fail;
    if ($cond) { $ok++; echo "  ✓ $name\n"; } else { $fail++; echo "  ✗ $name\n"; }
}
$code = fn(array $in) => oride_compute($in)['code'];
$lit = fn(array $acts, array $u, array $s = [], string $ev = '') => ['type' => 'LI', 'ia' => 'si', 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => $ev];

echo "Taxonomía\n";
check('nueve tipos de obra con los códigos pedidos', array_keys(WORK_TYPES) === ['SC', 'LI', 'MU', 'PH', 'IL', 'DS', 'AU', 'SW', 'TR']);
check('70 actividades, como en la hoja Excel', array_sum(array_map(fn($t) => count($t['acts']), WORK_TYPES)) === 70);
check('códigos de actividad de tres letras', !array_filter(WORK_TYPES, fn($t) => array_filter(array_keys($t['acts']), fn($a) => !preg_match('/^[A-Z]{3}$/', $a))));
check('cada actividad con nombre y descripción en los dos idiomas', !array_filter(WORK_TYPES, fn($t) => array_filter($t['acts'], fn($a) => count(array_filter($a, fn($x) => trim($x) !== '')) !== 4)));
check('seis grados y cinco alcances en los dos idiomas', count(LEVELS['es']) === 6 && count(LEVELS['en']) === 6 && count(SCOPE['es']) === 5 && count(SCOPE['en']) === 5);
check('mismas claves de texto en los dos idiomas', array_keys(STR['es']) === array_keys(STR['en']));

echo "Cálculo del código\n";
$lit = fn(array $acts, array $u, array $s = [], string $ev = 'no') => ['type' => 'LI', 'ia' => 'si', 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => $ev];
check('sin tipo: vacío', oride_compute([])['status'] === 'empty');
check('tipo sin IA: pendiente', oride_compute(['type' => 'LI'])['status'] === 'pending');
check('la tríada exige evidencias', oride_compute(['type' => 'LI', 'ia' => 'si'])['missing'] === t('miss_ev'));
check('tríada sin IA', $code(['type' => 'MU', 'ia' => 'no', 'ev' => 'no']) === 'MU-IA0-EV0');
check('tríada con IA', $code(['type' => 'SC', 'ia' => 'si', 'ev' => 'si']) === 'SC-IA1-EV1');
check('IA0 ignora actividades', $code(['type' => 'LI', 'ia' => 'no', 'ev' => 'si', 'acts' => ['GEN'], 'u' => ['GEN' => '5']]) === 'LI-IA0-EV1');
check('dos líneas: tríada y actividades', oride_compute($lit(['DOC', 'GEN'], ['DOC' => '4', 'GEN' => '2'], ['DOC' => '4', 'GEN' => '4'], 'si'))['lines'] === ['LI-IA1-EV1', 'DOC44-GEN24']);
check('en una línea, V en la tríada', $code($lit(['DOC'], ['DOC' => '4'], ['DOC' => '4'], 'si')) === 'LI-IA1-EV1-DOC44');
check('sin actividades, una sola línea', oride_compute($lit([], [], [], 'si'))['lines'] === ['LI-IA1-EV1']);
check('grado 0 y 1: segunda cifra 0', $code($lit(['DOC', 'IDE'], ['DOC' => '1', 'IDE' => '0'])) === 'LI-IA1-EV0-DOC10-IDE00');
check('grado 2 pide alcance', oride_compute($lit(['GEN'], ['GEN' => '2']))['status'] === 'pending');
check('actividades en el orden del tipo', $code($lit(['FOR', 'DOC'], ['FOR' => '5', 'DOC' => '4'], ['FOR' => '0', 'DOC' => '4'], 'si')) === 'LI-IA1-EV1-DOC44-FOR50');
check('actividad de otro tipo se ignora', $code($lit(['COD'], ['COD' => '5'])) === 'LI-IA1-EV0');

echo "Aviso: IA declarada pero todas las actividades a 0\n";
check('todas a 0 → aviso', oride_compute($lit(['DOC', 'GEN'], ['DOC' => '0', 'GEN' => '0']))['all_zero'] === true);
check('una a 1 → sin aviso (se usó y se descartó)', oride_compute($lit(['DOC', 'GEN'], ['DOC' => '0', 'GEN' => '1']))['all_zero'] === false);
check('sin actividades → sin aviso', oride_compute($lit([], []))['all_zero'] === false);
check('IA0 → sin aviso', oride_compute(['type' => 'LI', 'ia' => 'no', 'ev' => 'no'])['all_zero'] === false);
check('el aviso pide explicar el uso fuera de las actividades', str_contains(t('all_zero'), 'fuera de las actividades establecidas'));

echo "Lectura de códigos y compatibilidad\n";
$x = fn(string $c) => oride_explain($c);
check('formato actual en una línea', $x('LI-IA1-EV1-DOC44-GEN24')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('formato actual en dos líneas', $x("LI-IA1-EV1\nDOC44-GEN24")['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('formato anterior, V al final', $x('LI-IA1-DOC44-GEN24-EV1')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('formato anterior, sin V', $x('AU-IA1')['result']['code'] === 'AU-IA1' && $x('TR-IA0')['valid']);
check('IA0 con evidencias', $x('lit-ia0-v1')['result']['code'] === 'LI-IA0-EV1');
check('actividades en cualquier orden', $x('LI-IA1-EV0-GEN24-DOC44')['result']['code'] === 'LI-IA1-EV0-DOC44-GEN24');
check('forma corta sin IA1', $x('LIT:DOC44-GEN24-V')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('rechaza tipo desconocido', !$x('XXX-IA1')['valid']);
check('rechaza IA0 con actividades', !$x('LI-IA0-EV1-DOC44')['valid']);
check('rechaza V en medio de las actividades', !$x('LI-IA1-DOC44-EV1-GEN24')['valid']);
check('rechaza alcance > 4', !$x('LI-IA1-EV1-DOC45')['valid']);
check('rechaza segunda cifra distinta de 0 con grado 1', !$x('LI-IA1-EV1-DOC13')['valid']);
check('rechaza actividad repetida', !$x('LI-IA1-EV1-DOC44-DOC30')['valid']);

echo "Índice Global ORIDE: fórmula (casos de validación)\n";
$eq = fn(float $a, float $b) => abs($a - $b) < 1e-9;
foreach ([['obra sin IA', 0, 0, 0], ['novela documentada con IA, factor 1', 0, 0.05, 5], ['novela revisada formalmente, factor 1', 0, 0.05, 5],
    ['generada íntegramente sin cambios', 100, 0, 100], ['generada y reelaborada creativamente', 20, 0, 20], ['generada al 50 %', 50, 0, 50],
    ['G = 50, P = 0,05', 50, 0.05, 52.5], ['sin generación, P = 0,40', 0, 0.40, 40], ['G = 100, P = 0,40', 100, 0.40, 100]] as [$name, $G, $P, $want]) {
    check("IG: $name → $want", $eq(ig_combine($G, $P), $want));
}
check('monótono en G', ig_combine(60, 0.2) >= ig_combine(50, 0.2));
check('monótono en P', ig_combine(50, 0.3) >= ig_combine(50, 0.2));
check('nunca pasa de 100', ig_combine(100, 1) === 100.0);
$bad = 0;
foreach ([[-1, 0], [101, 0], [50, -0.1], [50, 1.1], [NAN, 0]] as [$G, $P]) { try { ig_combine($G, $P); } catch (InvalidArgumentException) { $bad++; } }
check('límites: rechaza valores fuera de rango', $bad === 5);

echo "Índice Global ORIDE: G, P y actividades\n";
$ig = fn(array $in, array $extra = []) => ig_from_result(oride_compute($in), $extra);
check('IA0 → 0 exacto', $ig(['type' => 'PH', 'ia' => 'no', 'ev' => 'no'])['IG'] === [0.0, 0.0]);
check('foto generada íntegramente, sin cambios → G = 100', $ig(['type' => 'PH', 'ia' => 'si', 'ev' => 'no', 'acts' => ['GEN'], 'u' => ['GEN' => '5'], 's' => ['GEN' => '4']], ['q' => '6'])['G'] === [100.0, 100.0]);
check('ilustración generada, edición puntual → G = 85', $eq($ig(['type' => 'IL', 'ia' => 'si', 'ev' => 'no', 'acts' => ['GEN'], 'u' => ['GEN' => '4'], 's' => ['GEN' => '4']], ['q' => '6'])['G'][0], 85));
check('ilustración generada, transformación sustancial → G = 50', $eq($ig(['type' => 'IL', 'ia' => 'si', 'ev' => 'no', 'acts' => ['GEN'], 'u' => ['GEN' => '3'], 's' => ['GEN' => '4']], ['q' => '6'])['G'][1], 50));
check('ilustración generada, reelaboración creativa → G = 20', $eq($ig(['type' => 'IL', 'ia' => 'si', 'ev' => 'no', 'acts' => ['GEN'], 'u' => ['GEN' => '2'], 's' => ['GEN' => '4']], ['qp' => '100'])['G'][0], 20));
$doc = $ig($lit(['DOC'], ['DOC' => '5'], ['DOC' => '4']));
check('novela escrita a mano, IA solo para documentarse → G = 0', $doc['G'] === [0.0, 0.0] && $eq($doc['P'][1], 0.05) && $eq($doc['IG'][1], 5));
check('el alcance en tramos da un intervalo (no se inventa un número)', $eq($doc['IG'][0], 3.75) && $eq($doc['IG'][1], 5));
check('porcentaje exacto → valor único', $ig($lit(['GEN'], ['GEN' => '5'], ['GEN' => '3']), ['qp' => '50'])['IG'] === [50.0, 50.0]);
check('tramo de proporción → intervalo', ($r = $ig($lit(['GEN'], ['GEN' => '5'], ['GEN' => '3']), ['q' => '3']))['q_source'] === 'question' && $eq($r['IG'][0], 25) && $eq($r['IG'][1], 50));
check('sin pregunta de proporción → alcance declarado', $ig($lit(['GEN'], ['GEN' => '5'], ['GEN' => '3']))['q_source'] === 'scope');
check('valor desconocido de proporción → se ignora', $ig($lit(['GEN'], ['GEN' => '5'], ['GEN' => '3']), ['q' => '9', 'qp' => 'abc'])['q_source'] === 'scope');
check('grado 1 (se descartó) cuenta 0 pero queda registrado', ($r = $ig($lit(['GEN', 'DOC'], ['GEN' => '1', 'DOC' => '1'])))['IG'] === [0.0, 0.0] && count($r['rows']) === 2);
check('P no pasa de 1', ig_p(['A' => ['w' => 0.8, 'f' => 1.0, 'S' => [1.0, 1.0]], 'B' => ['w' => 0.8, 'f' => 1.0, 'S' => [1.0, 1.0]]]) === [1.0, 1.0]);
check('usos repetidos de una actividad no suman dos veces', ig_from_result(oride_compute($lit(['DOC', 'DOC'], ['DOC' => '5'], ['DOC' => '4'])))['P'] === ig_from_result(oride_compute($lit(['DOC'], ['DOC' => '5'], ['DOC' => '4'])))['P']);
check('generación no cuenta también como auxiliar', ($r = $ig($lit(['GEN'], ['GEN' => '5'], ['GEN' => '4']), ['q' => '6']))['P'] === [0.0, 0.0] && $r['IG'] === [100.0, 100.0]);
check('varias actividades generativas → intervalo de factores', ($r = $ig(['type' => 'MU', 'ia' => 'si', 'ev' => 'no', 'acts' => ['COM', 'GEN'], 'u' => ['COM' => '2', 'GEN' => '5'], 's' => ['COM' => '4', 'GEN' => '4']], ['q' => '6'])) && $eq($r['G'][0], 20) && $eq($r['G'][1], 100));
check('con IA y sin actividades → no calculable (no se asigna 0)', $ig($lit([], []))['reason'] === 'no_acts');
check('con IA y todo a 0 → no calculable', $ig($lit(['DOC'], ['DOC' => '0']))['reason'] === 'all_zero');
check('código incompleto → no aplica', $ig(['type' => 'LI', 'ia' => 'si'])['status'] === 'na');

echo "Índice Global ORIDE: componentes, configuración y versiones\n";
check('varios componentes: G = 100 Σ w q f', $eq(ig_g([['w' => 0.6, 'q' => [1.0, 1.0], 'f' => [0.5, 0.5]], ['w' => 0.4, 'q' => [0.5, 0.5], 'f' => [1.0, 1.0]]])[0], 50));
$err = 0; try { ig_g([['w' => 0.7, 'q' => [1, 1], 'f' => [1, 1]], ['w' => 0.7, 'q' => [1, 1], 'f' => [1, 1]]]); } catch (InvalidArgumentException) { $err++; }
check('componentes solapados (pesos que no suman 1) → error', $err === 1);
check('versión actual, independiente de la taxonomía', IG_CURRENT === 'ORIDE-IG-1.0-experimental' && ORIDE_TAXONOMY === 'ORIDE Code 3.0' && !isset(ig_config()['taxonomy']));
check('versión desconocida → no calculable', ig_from_result(oride_compute($lit(['DOC'], ['DOC' => '5'], ['DOC' => '4'])), ['version' => 'ORIDE-IG-9.9'])['reason'] === 'version');
check('factores de la metodología', ig_config()['factors'] === [0 => 0.0, 1 => 0.0, 2 => 0.20, 3 => 0.50, 4 => 0.85, 5 => 1.00]);
check('pesos de literatura de la metodología', array_map(fn($a) => $a[1], ig_config()['weights']['LI']['acts']) === ['DOC' => 0.05, 'IDE' => 0.10, 'EST' => 0.15, 'GEN' => null, 'REV' => 0.10, 'FOR' => 0.05]);
$allOk = true;
foreach (WORK_TYPES as $t => $wt) {
    $cfgW = ig_config()['weights'][$t]['acts'] ?? [];
    if (array_keys($cfgW) !== array_keys($wt['acts'])) $allOk = false;                       // configuración completa
    if (array_sum(array_map(fn($a) => $a[1] ?? 0, $cfgW)) > 0.5 + 1e-9) $allOk = false;        // techo auxiliar 0,50
    if (!array_filter($cfgW, fn($a) => $a[0] === 'gen')) $allOk = false;                      // al menos una generativa
}
check('todas las disciplinas configuradas, con techo 0,50 y alguna generativa', $allOk);
$cmp = fn(string $t, string $a) => ig_from_result(oride_compute(['type' => $t, 'ia' => 'si', 'ev' => 'no', 'acts' => [$a], 'u' => [$a => '5'], 's' => [$a => '4']]))['P'];
check('comparación entre disciplinas: el mismo uso pesa según la disciplina', $cmp('LI', 'EST')[1] > $cmp('LI', 'DOC')[1] && $cmp('SW', 'ARQ')[1] === $cmp('LI', 'EST')[1]);
check('el registro guarda versión, parámetros y fecha', ($rec = ig_record(($r = oride_compute($lit(['DOC'], ['DOC' => '5'], ['DOC' => '4']))), ig_from_result($r), []))['algorithm'] === IG_CURRENT && isset($rec['parameters']['factors'], $rec['parameters']['weights'], $rec['date']));
check('compatibilidad: un código anterior también tiene índice', ig_from_result($x('LI-IA1-DOC54-EV1')['result'])['status'] === 'ok');

echo "v8: códigos de dos letras y EV, compatibilidad\n";
check('tríada con EV', $code(['type' => 'DS', 'ia' => 'si', 'ev' => 'si']) === 'DS-IA1-EV1');
check('Diseño es DS', isset(WORK_TYPES['DS']) && LEGACY_TYPES['DES'] === 'DS');
check('lee códigos de la v7 (tipos de tres letras y V)', $x('LIT-IA1-V1-DOC44-GEN24')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('lee códigos de la v5 (V al final, SOF e ILU)', $x('SOF-IA1-COD44-V1')['result']['code'] === 'SW-IA1-EV1-COD44' && $x('ILU-IA0')['result']['code'] === 'IL-IA0');
check('«EV» solo equivale a EV1', $x('MU-IA1-EV')['result']['code'] === 'MU-IA1-EV1');
check('actividad TRA de ilustración intacta', $x('IL-IA1-EV0-TRA32')['result']['code'] === 'IL-IA1-EV0-TRA32');
check('TRA como tipo antiguo = TR', $x('TRA-IA1-EV1-GEN54')['result']['code'] === 'TR-IA1-EV1-GEN54');

echo "v8: caja del índice\n";
$panel = fn(array $in, array $extra = []) => index_panel(ig_from_result(oride_compute($in), $extra), oride_compute($in), $extra);
check('índice entero en porcentaje', ig_percent(['IG' => [52.5, 52.5]]) === '53 %' && ig_percent(['IG' => [17.71, 23.4]]) === '18–23 %');
check('caja visible y pendiente sin tipo', str_contains($panel([]), t('ig_pending')));
check('pendiente con IA y sin actividades', str_contains($panel(['type' => 'LI', 'ia' => 'si', 'ev' => 'no']), 'Pendiente de los valores de las actividades'));
check('pendiente si falta el grado de una actividad', str_contains($panel(['type' => 'LI', 'ia' => 'si', 'ev' => 'no', 'acts' => ['GEN']]), t('ig_pending')));
$p = $panel($lit(['GEN'], ['GEN' => '5'], ['GEN' => '3']), ['qp' => '50']);
check('con valores: porcentaje, copiar índice y desglose', str_contains($p, '50 %') && str_contains($p, 'data-copy-index="Índice Global ORIDE: 50 % (ORIDE-IG-1.0-experimental)"') && str_contains($p, '<summary>Desglose del cálculo</summary>'));
check('la descarga JSON está dentro del desglose', strpos($p, 'format=json') > strpos($p, '<summary>'));
check('sin el texto explicativo largo en la caja', !str_contains($p, 'No representa un porcentaje certificado'));

echo "Verificador ORIDE-VERIFY-1.1\n";
$pol = fn(array $o = []) => $o + ['mode' => 'digits', 'global' => false, 'limit' => null, 'unspecified' => 'allow', 'absent' => 'review'];
$vf = fn(string $req, string $decl, array $o = []) => verify(oride_explain($req), oride_explain($decl), $pol($o));
$vd = fn(string $req, string $decl, array $o = []) => $vf($req, $decl, $o)['verdict'];
$row = fn(array $v, string $act) => array_values(array_filter($v['rows'], fn($r) => $r['act'] === $act))[0] ?? null;
check('versión de las reglas', VERIFY_VERSION === 'ORIDE-VERIFY-1.1');
check(' 1. cumple todos los límites', $vd('LI-IA1-EV1-DOC44-GEN34', 'LI-IA1-EV1-DOC20-GEN24') === 'compatible');
$v = $vf('LI-IA1-EV0-GEN32', 'LI-IA1-EV0-GEN42');
check(' 2. supera solo la 1.ª cifra', $v['verdict'] === 'incompatible' && $row($v, 'GEN')['level'] === 'fail' && $row($v, 'GEN')['scope'] === 'ok');
$v = $vf('LI-IA1-EV0-GEN32', 'LI-IA1-EV0-GEN34');
check(' 3. supera solo la 2.ª cifra', $v['verdict'] === 'incompatible' && $row($v, 'GEN')['level'] === 'ok' && $row($v, 'GEN')['scope'] === 'fail');
$v = $vf('LI-IA1-EV0-GEN32', 'LI-IA1-EV0-GEN44');
check(' 4. supera las dos cifras', $v['verdict'] === 'incompatible' && $row($v, 'GEN')['level'] === 'fail' && $row($v, 'GEN')['scope'] === 'fail');
$v = $vf('LI-IA1-EV1-GEN24', 'LI-IA1-EV1-GEN30', ['global' => true, 'limit' => 20.0]);
check(' 5. índice global inferior pero una actividad no cumple (GEN24 / GEN30)', $v['verdict'] === 'incompatible' && $row($v, 'GEN')['level'] === 'fail' && $row($v, 'GEN')['scope'] === 'ok' && $v['global']['state'] === 'ok' && str_contains($v['explain'][0], 'la actividad GEN supera el máximo permitido en el grado de incorporación de resultados de IA'));
check('    GEN30 es semánticamente válido (alcance 0 = menos del 5 %)', oride_explain('LI-IA1-EV1-GEN30')['valid'] && $v['decl']['state'] === 'valid' && (bool) array_filter($v['decl']['issues'], fn($i) => $i[0] === 'info' && str_contains($i[1], 'no «sin alcance»')));
check(' 6. actividades que cumplen pero índice global superior al máximo', $vd('LI-IA1-EV0-GEN54-DOC54', 'LI-IA1-EV0-GEN54-DOC54', ['global' => true, 'limit' => 20.0]) === 'incompatible');
check(' 7. compatible por ponderación, no compatible por cifras', $vd('LI-IA1-EV1-GEN24', 'LI-IA1-EV1-GEN30') === 'incompatible' && $vd('LI-IA1-EV1-GEN24', 'LI-IA1-EV1-GEN30', ['mode' => 'weighted']) === 'compatible');
check(' 8. índice parcialmente solapado con el límite: indeterminado', $vf('LI-IA1-EV0-GEN44', 'LI-IA1-EV0-GEN43', ['global' => true, 'limit' => 50.0])['global']['state'] === 'unknown' && $vd('LI-IA1-EV0-GEN44', 'LI-IA1-EV0-GEN43', ['global' => true, 'limit' => 50.0]) === 'indeterminate');
check('    intervalos: 8–12 y 18–20 cumplen, 19–24 indeterminado, 23–28 no cumple (límite 20)', verify_interval([8, 12], 20) === 'ok' && verify_interval([18, 20], 20) === 'ok' && verify_interval([19, 24], 20) === 'unknown' && verify_interval([23, 28], 20) === 'fail');
check(' 9. actividad adicional: permitida por defecto', $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0-GEN24-DOC44') === 'compatible');
check('    actividad adicional: no permitida / requiere revisión', $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0-GEN24-DOC44', ['unspecified' => 'forbid']) === 'incompatible' && $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0-GEN24-DOC44', ['unspecified' => 'review']) === 'indeterminate');
check('    actividad adicional cuenta en el límite global', $vd('LI-IA1-EV0-DOC10', 'LI-IA1-EV0-DOC10-GEN54', ['global' => true, 'limit' => 10.0]) === 'incompatible');
check('10. actividad ausente: requiere revisión por defecto (no se supone 0)', $row($vf('LI-IA1-EV0-GEN24-DOC44', 'LI-IA1-EV0-GEN24'), 'DOC')['state'] === 'unknown' && $vd('LI-IA1-EV0-GEN24-DOC44', 'LI-IA1-EV0-GEN24') === 'indeterminate');
check('    actividad ausente: se entiende que no se usó', $vd('LI-IA1-EV0-GEN24-DOC44', 'LI-IA1-EV0-GEN24', ['absent' => 'unused']) === 'compatible');
check('11. código no válido', $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0-GEN99') === 'invalid' && $vd('XX-IA1', 'LI-IA1-EV0') === 'invalid' && $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0-GEN24-GEN30') === 'invalid');
check('    código contradictorio (IA1 con todo a 0): sin resultado definitivo', $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0-GEN00') === 'indeterminate');
$v = $vf('LI-IA1-EV1-GEN24', 'LIT-IA1-GEN24-V1');
check('12. código de una versión anterior', $v['decl']['state'] === 'legacy' && $v['verdict'] === 'compatible');
check('    código antiguo sin evidencias: incompleto', $vf('LI-IA1-EV1', 'LIT-IA1')['decl']['state'] === 'incomplete' && $vd('LI-IA1-EV1', 'LIT-IA1') === 'indeterminate');
check('13. declaración sin IA', $vd('LI-IA1-EV0-GEN24', 'LI-IA0-EV0') === 'compatible' && $vd('LI-IA0-EV0', 'LI-IA0-EV1') === 'compatible' && $vd('LI-IA0-EV0', 'LI-IA1-EV0-DOC10') === 'incompatible');
check('14. distintas disciplinas: no compatible', $vd('LI-IA1-EV0-GEN24', 'SW-IA1-EV0-COD24') === 'incompatible');
check('    la misma actividad pesa distinto según la disciplina (ponderado)', ig_act_contrib('LI', 'EST', 5, 4)['value'][1] === ig_act_contrib('SW', 'ARQ', 5, 4)['value'][1] && ig_act_contrib('LI', 'EST', 5, 4)['value'][1] > ig_act_contrib('LI', 'DOC', 5, 4)['value'][1]);
check('15. información incompleta: declaración sin actividades frente a límites', $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0') === 'indeterminate');
check('    límite global sin valor: indeterminado', $vd('LI-IA1-EV0-GEN24', 'LI-IA1-EV0-GEN24', ['global' => true]) === 'indeterminate');
check('    evidencias exigidas y no declaradas', $vd('LI-IA1-EV1-GEN24', 'LI-IA1-EV0-GEN24') === 'incompatible');
check('ejemplo de ilustración (RET10 / RET40): no cumple por grado', ($v = $vf('IL-IA1-EV1-REF10-RET10', 'IL-IA1-EV1-REF10-RET40'))['verdict'] === 'incompatible' && $row($v, 'RET')['level'] === 'fail' && $row($v, 'REF')['state'] === 'ok');
check('el índice global no compensa una actividad que no cumple', $vd('LI-IA1-EV0-GEN32', 'LI-IA1-EV0-GEN42', ['global' => true, 'limit' => 100.0]) === 'incompatible');
check('el índice del requisito no se usa como límite si no se fija', $vf('LI-IA1-EV0-DOC10', 'LI-IA1-EV0-DOC10-GEN54')['global'] === null);
check('ponderado: misma fórmula que el índice', ig_act_contrib('LI', 'DOC', 5, 4)['value'] === ig_from_result(oride_compute(['type' => 'LI', 'ia' => 'si', 'ev' => 'no', 'acts' => ['DOC'], 'u' => ['DOC' => '5'], 's' => ['DOC' => '4']]))['rows'][0]['contrib']);

echo "Textos e idioma\n";
$r = oride_compute($lit(['DOC'], ['DOC' => '4'], ['DOC' => '4'], 'si'));
$txt = oride_text($r);
check('texto: código en dos líneas, tipo y actividad', str_starts_with($txt, "Código ORIDE LI-IA1-EV1\nDOC44\nTipo de obra: Literatura\n") && str_contains($txt, 'Documentación: Modifiqué parcialmente los resultados. Parte de la obra afectada: del 75% al 100%.'));
check('texto: enlace que lo explica, con versión del índice', str_contains($txt, 'oride.php?LI-IA1-EV1-DOC44&ig=1.0-experimental&lang=es'));
check('texto de instituciones', str_starts_with(oride_text($r, t('inst_max')), 'El autor debe garantizar que el uso de la IA que hace no supera el siguiente código:'));
check('sin nota de marca registrada', !isset(STR['es']['trademark']) && !isset(STR['en']['trademark']));
check('por defecto, español', lang() === 'es');
check('los enlaces llevan el idioma', u('crear.php') === '/crear.php?lang=es' && u('oride.php?LI-IA0-EV0') === '/oride.php?LI-IA0-EV0&lang=es');
check('números con coma decimal', num(52.5) === '52,5' && num(100) === '100' && num_range([3.75, 5.0], 2) === '3,75–5');

echo "\n$ok correctas, $fail fallidas\n";
exit($fail ? 1 : 0);
