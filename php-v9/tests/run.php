<?php
/** Pruebas de ORIDE v9. Uso: php tests/run.php */
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
check('tríada con IA, sin actividades: IA1 (sin categoría)', $code(['type' => 'SC', 'ia' => 'si', 'ev' => 'si']) === 'SC-IA1-EV1');
check('IA0 ignora actividades', $code(['type' => 'LI', 'ia' => 'no', 'ev' => 'si', 'acts' => ['GEN'], 'u' => ['GEN' => '5']]) === 'LI-IA0-EV1');
check('dos líneas: tríada y actividades', oride_compute($lit(['DOC', 'GEN'], ['DOC' => '4', 'GEN' => '2'], ['DOC' => '4', 'GEN' => '4'], 'si'))['lines'] === ['LI-IGB-EV1', 'DOC44-GEN24']);
check('en una línea, EV en la tríada', $code($lit(['DOC'], ['DOC' => '4'], ['DOC' => '4'], 'si')) === 'LI-IGA-EV1-DOC44');
check('sin actividades, una sola línea', oride_compute($lit([], [], [], 'si'))['lines'] === ['LI-IA1-EV1']);
check('grado 0 y 1: segunda cifra 0', $code($lit(['DOC', 'IDE'], ['DOC' => '1', 'IDE' => '0'])) === 'LI-IGA-EV0-DOC10-IDE00');
check('grado 2 pide alcance', oride_compute($lit(['GEN'], ['GEN' => '2']))['status'] === 'pending');
check('actividades en el orden del tipo', $code($lit(['FOR', 'DOC'], ['FOR' => '5', 'DOC' => '4'], ['FOR' => '0', 'DOC' => '4'], 'si')) === 'LI-IGA-EV1-DOC44-FOR50');
check('actividad de otro tipo se ignora', $code($lit(['COD'], ['COD' => '5'])) === 'LI-IA1-EV0');

echo "Aviso: IA declarada pero todas las actividades a 0\n";
check('todas a 0 → aviso', oride_compute($lit(['DOC', 'GEN'], ['DOC' => '0', 'GEN' => '0']))['all_zero'] === true);
check('una a 1 → sin aviso (se usó y se descartó)', oride_compute($lit(['DOC', 'GEN'], ['DOC' => '0', 'GEN' => '1']))['all_zero'] === false);
check('sin actividades → sin aviso', oride_compute($lit([], []))['all_zero'] === false);
check('IA0 → sin aviso', oride_compute(['type' => 'LI', 'ia' => 'no', 'ev' => 'no'])['all_zero'] === false);
check('el aviso pide explicar el uso fuera de las actividades', str_contains(t('all_zero'), 'fuera de las actividades establecidas'));

echo "Lectura de códigos y compatibilidad\n";
$x = fn(string $c) => oride_explain($c);
check('formato actual en una línea', $x('LI-IGB-EV1-DOC44-GEN24')['result']['code'] === 'LI-IGB-EV1-DOC44-GEN24');
check('formato v8 (IA1 con actividades) se conserva', $x('LI-IA1-EV1-DOC44-GEN24')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('formato actual en dos líneas', $x("LI-IGB-EV1\nDOC44-GEN24")['result']['code'] === 'LI-IGB-EV1-DOC44-GEN24');
check('formato anterior, V al final', $x('LI-IA1-DOC44-GEN24-EV1')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('formato anterior, sin V', $x('AU-IA1')['result']['code'] === 'AU-IA1' && $x('TR-IA0')['valid']);
check('IA0 con evidencias', $x('lit-ia0-v1')['result']['code'] === 'LI-IA0-EV1');
check('actividades en cualquier orden', $x('LI-IA1-EV0-GEN24-DOC44')['result']['code'] === 'LI-IA1-EV0-DOC44-GEN24');
check('forma corta sin IA1', $x('LIT:DOC44-GEN24-V')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('rechaza tipo desconocido', !$x('XXX-IA1')['valid']);
check('rechaza IA0 con actividades', !$x('LI-IA0-EV1-DOC44')['valid']);
check('rechaza IG con una letra que no es categoría', !$x('LI-IGZ-EV1-DOC44')['valid'] && !$x('LI-IG-EV1')['valid']);
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

echo "Códigos de dos letras y EV, compatibilidad\n";
check('tríada con EV', $code(['type' => 'DS', 'ia' => 'si', 'ev' => 'si']) === 'DS-IA1-EV1');
check('Diseño es DS', isset(WORK_TYPES['DS']) && LEGACY_TYPES['DES'] === 'DS');
check('lee códigos de la v7 (tipos de tres letras y V)', $x('LIT-IA1-V1-DOC44-GEN24')['result']['code'] === 'LI-IA1-EV1-DOC44-GEN24');
check('lee códigos de la v5 (V al final, SOF e ILU)', $x('SOF-IA1-COD44-V1')['result']['code'] === 'SW-IA1-EV1-COD44' && $x('ILU-IA0')['result']['code'] === 'IL-IA0');
check('«EV» solo equivale a EV1', $x('MU-IA1-EV')['result']['code'] === 'MU-IA1-EV1');
check('actividad TRA de ilustración intacta', $x('IL-IA1-EV0-TRA32')['result']['code'] === 'IL-IA1-EV0-TRA32');
check('TRA como tipo antiguo = TR', $x('TRA-IA1-EV1-GEN54')['result']['code'] === 'TR-IA1-EV1-GEN54');

echo "Categorías del Índice Global (ORIDE-IG-CAT-1.0)\n";
check('versión de las categorías', IG_CAT_VERSION === 'ORIDE-IG-CAT-1.0');
$want = [[0, 'A'], [5, 'A'], [10, 'A'], [10.01, 'B'], [25, 'B'], [25.01, 'C'], [50, 'C'], [50.01, 'D'], [75, 'D'], [75.01, 'E'], [90, 'E'], [90.01, 'F'], [100, 'F']];
foreach ($want as [$val, $cat]) check("valor $val → $cat", ig_category((float) $val) === $cat);
foreach ([[0, 3, 'A'], [12, 20, 'B'], [18, 32, 'B'], [30, 42, 'C'], [45, 65, 'D'], [68, 82, 'D'], [80, 95, 'E'], [92, 98, 'F']] as [$lo, $hi, $cat]) {
    check("intervalo {$lo}–{$hi} (punto medio " . (($lo + $hi) / 2) . ") → $cat", ig_classify_range((float) $lo, (float) $hi) === $cat);
}
check('punto medio sin redondear', ig_midpoint(17.71, 23.4) === (17.71 + 23.4) / 2 && ig_classify_range(10.0, 10.02) === 'B');
$bad = 0;
foreach ([[null, 20.0], [10.0, null], [null, null], [30.0, 20.0], [-1.0, 5.0], [5.0, 101.0], [NAN, 5.0]] as [$lo, $hi]) { try { ig_classify_range($lo, $hi); } catch (InvalidArgumentException) { $bad++; } }
check('validación: incompleto, invertido o fuera de 0–100 → sin categoría', $bad === 7);
$bad = 0; foreach ([-0.01, 100.01, NAN] as $val) { try { ig_category($val); } catch (InvalidArgumentException) { $bad++; } }
check('validación: valor fuera de 0–100', $bad === 3);
check('orden A < B < … < F; el requisito es el máximo', ig_compare_cats('C', 'B') === 'ok' && ig_compare_cats('C', 'C') === 'ok' && ig_compare_cats('C', 'D') === 'fail' && ig_compare_cats('A', 'F') === 'fail');
check('etiquetas en español e inglés, con la misma letra', ig_cat_label('B', 'es') === 'B — Bajo' && ig_cat_label('B', 'en') === 'B — Low' && array_keys(IG_CAT_LABELS['es']) === array_keys(IG_CAT_LABELS['en']));
check('versiones separadas: taxonomía, algoritmo, categorías y verificador', count(array_unique([ORIDE_TAXONOMY, IG_CURRENT, IG_CAT_VERSION, VERIFY_VERSION])) === 4);

echo "Categoría en el código\n";
check('ejemplo: LI-IGB-EV1 + GEN24', implode(' + ', oride_compute($lit(['GEN'], ['GEN' => '2'], ['GEN' => '4'], 'si'))['lines']) === 'LI-IGB-EV1 + GEN24');
check('IA0 se conserva y nunca es IGA', $code(['type' => 'LI', 'ia' => 'no', 'ev' => 'no']) === 'LI-IA0-EV0' && oride_compute(['type' => 'LI', 'ia' => 'no', 'ev' => 'no'])['cat'] === null);
check('IA con índice 0 → IGA, no IA0', $code($lit(['DOC'], ['DOC' => '1'])) === 'LI-IGA-EV0-DOC10' && oride_compute($lit(['DOC'], ['DOC' => '1']))['cat'] === 'A');
check('IA sin actividades → IA1 (sin categoría)', $code($lit([], [])) === 'LI-IA1-EV0' && oride_compute($lit([], []))['cat'] === null);
check('IA con todo a 0 → IA1 (sin categoría)', $code($lit(['DOC'], ['DOC' => '0'])) === 'LI-IA1-EV0-DOC00');
$same = true;
foreach (['LI-IA1-EV1-GEN24-DOC44-EST54', 'LI-IA1-EV1-GEN44-DOC44', 'LI-IA1-EV0-GEN42', 'LI-IA1-EV0-GEN54', 'SW-IA1-EV1-REQ21-ARQ44-COD44-REF33-PRU54-DOC34', 'IL-IA1-EV1-RET40', 'MU-IA1-EV0-COM24-GEN54', 'SC-IA1-EV1-RED51'] as $c) {
    $r = $x($c)['result'];
    $ig = ig_from_result($r);
    $new = code_lines(['keep_ia1' => false] + $r)[0];
    if ($new !== $r['type'] . '-IG' . ig_classify_range($ig['IG'][0], $ig['IG'][1]) . '-EV' . $r['ev'] || !$x(implode('-', code_lines(['keep_ia1' => false] + $r)))['valid']) $same = false;
}
check('la categoría del segundo término coincide con la calculada', $same);
check('código anterior IA1 + actividades: conserva IA1, categoría derivada', ($r = $x('LI-IA1-EV1-GEN24')['result'])['code'] === 'LI-IA1-EV1-GEN24' && $r['cat'] === 'B' && $r['cat_source'] === 'derived' && $x('LI-IA1-EV1-GEN24')['legacy']);
check('letra que no coincide con las actividades → no válido', !$x('LI-IGC-EV1-GEN24')['valid'] && str_contains($x('LI-IGC-EV1-GEN24')['error'], 'IGB'));
check('letra con todo a 0 → no válido', !$x('LI-IGA-EV0-GEN00')['valid']);
check('letra sin actividades → categoría declarada (requisitos)', ($r = $x('LI-IGB-EV0')['result'])['code'] === 'LI-IGB-EV0' && $r['cat'] === 'B' && $r['cat_source'] === 'declared');
check('grados 3 y 4 con los textos nuevos', LEVELS['es'][3] === 'Incorporé los resultados transformados sustancialmente' && LEVELS['es'][4] === 'Incorporé los resultados transformados parcialmente' && LEVELS_READER['es'][3] === 'Se incorporaron los resultados transformados sustancialmente');

echo "Caja del índice\n";
$panel = fn(array $in, array $extra = []) => index_panel(ig_from_result(oride_compute($in), $extra), oride_compute($in), $extra);
check('índice entero en porcentaje (para el detalle)', ig_percent(['IG' => [52.5, 52.5]]) === '53 %' && ig_percent(['IG' => [17.71, 23.4]]) === '18–23 %');
check('caja visible y pendiente sin tipo', str_contains($panel([]), t('ig_pending')));
check('pendiente con IA y sin actividades', str_contains($panel(['type' => 'LI', 'ia' => 'si', 'ev' => 'no']), 'Pendiente de los valores de las actividades'));
check('pendiente si falta el grado de una actividad', str_contains($panel(['type' => 'LI', 'ia' => 'si', 'ev' => 'no', 'acts' => ['GEN']]), t('ig_pending')));
check('IA0: «No se utilizó IA», sin categoría', str_contains($p0 = $panel(['type' => 'LI', 'ia' => 'no', 'ev' => 'no']), 'No se utilizó IA') && !str_contains($p0, 'ig-scale'));
$p = $panel($lit(['DOC', 'GEN'], ['DOC' => '4', 'GEN' => '2'], ['DOC' => '4', 'GEN' => '4']));
check('categoría como valor principal: «B — Bajo» y la nota del punto medio', str_contains($p, '<span class="ig-letter">B</span> — Bajo') && str_contains($p, 'Categoría calculada a partir del punto medio del índice estimado.'));
check('escala A–F con la categoría señalada (no solo por color)', substr_count($p, '<li') >= 6 && str_contains($p, 'aria-current="true"><span class="ig-scale-l">B</span>') && str_contains($p, 'esta declaración'));
check('detalle: intervalo, punto medio, categoría y versiones', str_contains($p, 'Intervalo estimado: 17,71–23,4 %') && str_contains($p, 'Punto medio: 20,55 %') && str_contains($p, 'Categoría: B — Bajo') && str_contains($p, IG_CAT_VERSION) && str_contains($p, IG_CURRENT) && str_contains($p, ORIDE_TAXONOMY));
check('copiar índice con la categoría', str_contains($p, 'data-copy-index="Índice Global ORIDE: B — Bajo (18–23 % · ORIDE-IG-CAT-1.0 · ORIDE-IG-1.0-experimental)"'));
check('la descarga JSON está dentro del desglose', strpos($p, 'format=json') > strpos($p, '<summary>'));
$rec = ig_record(($r = oride_compute($lit(['GEN'], ['GEN' => '2'], ['GEN' => '4']))), ig_from_result($r), []);
check('JSON: punto medio, categoría y versión', $rec['category']['category'] === 'B' && $rec['category']['midpoint'] === 17.5 && $rec['category']['version'] === IG_CAT_VERSION);
check('ES/EN: misma letra', ig_cat_label('D', 'es')[0] === ig_cat_label('D', 'en')[0]);

echo "Verificador ORIDE-VERIFY-1.2\n";
// Código en el formato actual a partir de uno con IA1 (para escribir las pruebas con actividades).
$nc = fn(string $c) => implode('-', code_lines(['keep_ia1' => false] + oride_explain($c)['result']));
$vf = fn(string $req, string $decl, string $mode = 'global') => verify(oride_explain($req), oride_explain($decl), ['mode' => $mode]);
$vd = fn(string $req, string $decl, string $mode = 'global') => $vf($req, $decl, $mode)['verdict'];
$row = fn(array $v, string $act) => array_values(array_filter($v['rows'], fn($r) => $r['act'] === $act))[0] ?? null;
check('versión de las reglas', VERIFY_VERSION === 'ORIDE-VERIFY-1.2');
check('modalidad predeterminada: Índices Globales', verify_policy_from_get()['mode'] === 'global');
$req = $nc('LI-IA1-EV1-DOC44-GEN34'); $decl = $nc('LI-IA1-EV1-DOC20-GEN24');
check(' 1. cumple en las tres modalidades', $req === 'LI-IGC-EV1-DOC44-GEN34' && $vd($req, $decl) === 'compatible' && $vd($req, $decl, 'weighted') === 'compatible' && $vd($req, $decl, 'digits') === 'compatible');
check(' 2. categoría superior → no compatible', $vd($nc('LI-IA1-EV0-GEN32'), $nc('LI-IA1-EV0-GEN42')) === 'incompatible' && $vf($nc('LI-IA1-EV0-GEN32'), $nc('LI-IA1-EV0-GEN42'))['parts']['category'] === 'fail');
check(' 3. Índices Globales: solo categorías, sin detalle de actividades', $vf($req, $decl)['rows'] === [] && $vf($req, $decl)['parts']['activities'] === 'na');
$v = $vf($nc('LI-IA1-EV1-GEN24'), $nc('LI-IA1-EV1-GEN30'), 'digits');
check(' 4. una categoría baja no compensa una actividad que supera su límite', $v['verdict'] === 'incompatible' && $v['parts']['category'] === 'ok' && $v['parts']['activities'] === 'fail' && $row($v, 'GEN')['level'] === 'fail' && str_contains($v['explain'][0], 'Una categoría baja no compensa'));
check('    la misma pareja es compatible solo por categorías', $vd($nc('LI-IA1-EV1-GEN24'), $nc('LI-IA1-EV1-GEN30')) === 'compatible');
$v = $vf($nc('LI-IA1-EV0-GEN42'), $nc('LI-IA1-EV0-GEN24'), 'digits');
check(' 5. supera solo el alcance', $v['verdict'] === 'incompatible' && $row($v, 'GEN')['level'] === 'ok' && $row($v, 'GEN')['scope'] === 'fail');
check(' 6. ponderado: un grado mayor se compensa con un alcance menor', $vd($nc('LI-IA1-EV1-GEN24'), $nc('LI-IA1-EV1-GEN30'), 'weighted') === 'compatible');
$v = $vf($nc('LI-IA1-EV0-GEN24'), $nc('LI-IA1-EV0-GEN24-DOC44'), 'digits');
check(' 7. actividad declarada que el requisito no menciona → aviso de revisión, no cambia el resultado', $v['verdict'] === 'compatible' && $row($v, 'DOC')['state'] === 'alert' && $v['alerts'] === ['DOC'] && str_contains(implode(' ', $v['explain']), 'Revisión manual'));
$v = $vf($nc('LI-IA1-EV0-GEN24-DOC44'), $nc('LI-IA1-EV0-GEN24'), 'weighted');
check(' 8. actividad del requisito ausente → se entiende que no se usó', $v['verdict'] === 'compatible' && $row($v, 'DOC')['state'] === 'ok');
check(' 9. declaración sin categoría frente a un requisito con categoría → no determinable', $vd('LI-IGB-EV0', 'LI-IA1-EV0') === 'indeterminate' && $vd('LI-IGB-EV0', 'LI-IA1-EV0-GEN00') === 'indeterminate');
check('10. requisito por categoría, sin actividades', $vd('LI-IGB-EV0', $nc('LI-IA1-EV0-GEN24')) === 'compatible' && $vd('LI-IGB-EV0', $nc('LI-IA1-EV0-GEN42')) === 'incompatible');
check('11. requisito sin categoría (IA1): no se compara la categoría', ($v = $vf('LI-IA1-EV0', $nc('LI-IA1-EV0-GEN54')))['verdict'] === 'compatible' && $v['parts']['category'] === 'na');
check('12. sin IA: IA0 cumple cualquier categoría; un requisito IA0 no admite IA', $vd('LI-IGB-EV0', 'LI-IA0-EV0') === 'compatible' && $vd('LI-IA0-EV0', 'LI-IA0-EV1') === 'compatible' && $vd('LI-IA0-EV0', $nc('LI-IA1-EV0-DOC10')) === 'incompatible');
check('13. IA con índice 0 (IGA) no se confunde con IA0', $nc('LI-IA1-EV0-DOC10') === 'LI-IGA-EV0-DOC10' && $vd('LI-IA0-EV0', 'LI-IGA-EV0-DOC10') === 'incompatible');
check('14. código no válido', $vd('LI-IGB-EV0-GEN24', 'LI-IGB-EV0-GEN99') === 'invalid' && $vd('XX-IA1', 'LI-IA1-EV0') === 'invalid' && $vd('LI-IGB-EV0', 'LI-IGC-EV0-GEN24') === 'invalid' && $vf('LI-IGB-EV0', 'LI-IGC-EV0-GEN24')['parts']['codes'] === 'invalid');
$v = $vf('LI-IGB-EV1-GEN24', 'LIT-IA1-GEN24-V1', 'digits');
check('15. código de una versión anterior: válido, con categoría derivada', $v['decl']['state'] === 'legacy' && $v['category']['decl'] === 'B' && $v['verdict'] === 'compatible');
check('16. distintas disciplinas: no compatible', $vd($nc('LI-IA1-EV0-GEN24'), $nc('SW-IA1-EV0-COD24')) === 'incompatible');
check('    mismo tipo de obra: no se muestra la línea del tipo', !array_filter($vf($nc('LI-IA1-EV0-GEN24'), $nc('LI-IA1-EV0-GEN24'))['checks'], fn($c) => str_contains($c[1], 'Tipo de obra')));
check('17. evidencias exigidas y no declaradas', $vd($nc('LI-IA1-EV1-GEN24'), $nc('LI-IA1-EV0-GEN24')) === 'incompatible');
check('18. ejemplo de ilustración (RET10 / RET40): misma categoría, no cumple por grado', ($v = $vf($nc('IL-IA1-EV1-REF10-RET10'), $nc('IL-IA1-EV1-REF10-RET40'), 'digits'))['verdict'] === 'incompatible' && $v['parts']['category'] === 'ok' && $row($v, 'RET')['level'] === 'fail');
check('19. GEN30 es válido (alcance 0 = menos del 5 %)', ($v = $vf('LI-IGB-EV1', $nc('LI-IA1-EV1-GEN30')))['decl']['state'] === 'valid' && (bool) array_filter($v['decl']['issues'], fn($i) => $i[0] === 'info' && str_contains($i[1], 'no «sin alcance»')));
check('20. resultado para copiar', str_contains($t = verify_text($vf($req, $decl), oride_explain($req), oride_explain($decl)), 'Resultado: Declaración Compatible') && str_contains($t, VERIFY_VERSION) && str_contains($t, $req));
check('ponderado: misma fórmula que el índice', ig_act_contrib('LI', 'DOC', 5, 4)['value'] === ig_from_result(oride_compute(['type' => 'LI', 'ia' => 'si', 'ev' => 'no', 'acts' => ['DOC'], 'u' => ['DOC' => '5'], 's' => ['DOC' => '4']]))['rows'][0]['contrib']);
check('etiquetas del resultado', t('vf_compatible') === 'Declaración Compatible' && t('vf_incompatible') === 'Declaración No Compatible');

echo "Textos e idioma\n";
$r = oride_compute($lit(['DOC'], ['DOC' => '4'], ['DOC' => '4'], 'si'));
$txt = oride_text($r);
check('texto: código en dos líneas, tipo y actividad', str_starts_with($txt, "Código ORIDE LI-IGA-EV1\nDOC44\nTipo de obra: Literatura\n") && str_contains($txt, 'Documentación: Incorporé los resultados transformados parcialmente. Parte de la obra afectada: del 75% al 100%.'));
check('texto: enlace que lo explica, con versiones del índice y de las categorías', str_contains($txt, 'oride.php?LI-IGA-EV1-DOC44&ig=1.0-experimental&igc=1.0&lang=es'));
check('texto de instituciones', str_starts_with(oride_text($r, t('inst_max')), 'El autor debe garantizar que el uso de la IA que hace no supera el siguiente código:'));
check('sin nota de marca registrada', !isset(STR['es']['trademark']) && !isset(STR['en']['trademark']));
check('por defecto, español', lang() === 'es');
check('los enlaces llevan el idioma', u('crear.php') === '/crear.php?lang=es' && u('oride.php?LI-IA0-EV0') === '/oride.php?LI-IA0-EV0&lang=es');
check('números con coma decimal', num(52.5) === '52,5' && num(100) === '100' && num_range([3.75, 5.0], 2) === '3,75–5');

echo "\n$ok correctas, $fail fallidas\n";
exit($fail ? 1 : 0);
