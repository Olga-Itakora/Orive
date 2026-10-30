<?php
/**
 * Pruebas unitarias (sin dependencias): php tests/run.php
 * Cubren el motor ORIDE Code, la integridad (JSON canónico + huellas) y la orientación del art. 50.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/taxonomy.php';
require_once __DIR__ . '/../includes/engine.php';
require_once __DIR__ . '/../includes/integrity.php';
require_once __DIR__ . '/../includes/article50.php';

$GLOBALS['t'] = ['pass' => 0, 'fail' => 0];
function test(string $name, callable $fn): void
{
    try {
        $fn();
        $GLOBALS['t']['pass']++;
        echo "  ✓ $name\n";
    } catch (Throwable $e) {
        $GLOBALS['t']['fail']++;
        echo "  ✗ $name\n      " . $e->getMessage() . "\n";
    }
}
function eq($actual, $expected, string $msg = ''): void
{
    if ($actual !== $expected) {
        throw new RuntimeException(($msg ? "$msg: " : '') . 'esperado ' . var_export($expected, true) . ', obtenido ' . var_export($actual, true));
    }
}
function ok($cond, string $msg = 'condición falsa'): void
{
    if (!$cond) throw new RuntimeException($msg);
}

function use_(array $o = []): array
{
    return $o + [
        'ref' => 'u1', 'component_ref' => null, 'tool_identified' => true, 'phases' => ['EVALUATION'], 'functions' => ['A3'], 'primary_function' => null,
        'objective' => 'Obtener una lectura crítica', 'activity' => 'Se pidió una revisión crítica del manuscrito completo.',
        'result' => 'Una lista de observaciones sobre el ritmo.', 'incorporation' => 'NONE', 'contributions' => [], 'evidence_count' => 0,
    ];
}
function contrib(array $o = []): array
{
    return $o + ['ref' => 'c1', 'type' => 'B2', 'answer' => 'ACCEPTED', 'recognizable' => null, 'description' => 'Se revisaron y aceptaron las correcciones.'];
}
function decl(array $o = []): array
{
    return $o + ['ai_usage' => 'YES', 'process_narrative' => '', 'uses' => [], 'declaration_evidence_count' => 0];
}

echo "Motor ORIDE Code\n";
test('Caso 1 — sin IA: A0B0C0D0 emitible', function () {
    $p = oride_classify_declaration(decl(['ai_usage' => 'NO']));
    eq($p['code'], 'A0B0C0D0');
    ok(oride_validate_profile($p)['can_issue']);
});
test('Caso 1 — D2 con relato, D3 con evidencias, D0 si solo hay evidencias', function () {
    $n = str_repeat('Escribí la novela a mano durante dos años, con cuadernos y borradores. ', 3);
    eq(oride_classify_declaration(decl(['ai_usage' => 'NO', 'process_narrative' => $n]))['code'], 'A0B0C0D2');
    eq(oride_classify_declaration(decl(['ai_usage' => 'NO', 'process_narrative' => $n, 'declaration_evidence_count' => 1]))['code'], 'A0B0C0D3');
    eq(oride_classify_declaration(decl(['ai_usage' => 'NO', 'declaration_evidence_count' => 2]))['code'], 'A0B0C0D0');
});
test('Sin IA con usos registrados: no emitible', function () {
    $p = oride_classify_declaration(decl(['ai_usage' => 'NO', 'uses' => [use_()]]));
    eq($p['code'], null);
    ok(!oride_validate_profile($p)['can_issue']);
});
test('Caso 2 — revisión descartada: A3B0C0', function () {
    $u = oride_classify_use(use_());
    eq($u['code'], 'A3B0C0D2');
    eq($u['pairs'], [['contribution_ref' => null, 'b' => 'B0', 'c' => 'C0']]);
});
test('Caso 3 — corrección aceptada: A4B2C1', function () {
    eq(oride_classify_use(use_(['functions' => ['A4'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib()]]))['code'], 'A4B2C1D2');
});
test('Caso 4 — desarrollo conceptual: A2B1C5 sin avisos', function () {
    $u = oride_classify_use(use_(['functions' => ['A2'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['type' => 'B1', 'answer' => 'OWN_EXPRESSION'])]]));
    eq($u['code'], 'A2B1C5D2');
    eq(count(array_filter($u['issues'], fn($i) => $i['severity'] === 'warning')), 0);
});
test('Caso 5 — ilustración sin cambios: A5B3C1', function () {
    eq(oride_classify_use(use_(['functions' => ['A5'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['type' => 'B3'])]]))['code'], 'A5B3C1D2');
});
test('Caso 6 — transformación: C3 / C4 / pendiente / insuficiente', function () {
    $mk = fn($r) => oride_classify_use(use_(['functions' => ['A5'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['type' => 'B3', 'answer' => 'TRANSFORMED', 'recognizable' => $r])]]));
    eq($mk('YES')['code'], 'A5B3C3D2');
    eq($mk('NO')['code'], 'A5B3C4D2');
    eq($mk(null)['status'], 'PENDING');
    eq($mk('UNSURE')['status'], 'INSUFFICIENT');
    eq($mk('UNSURE')['pairs'][0]['c'], null);
});
test('Caso 7 — obra híbrida: perfil compuesto sin fabricar código', function () {
    $uses = [
        use_(['ref' => 'rev', 'component_ref' => 'text', 'functions' => ['A4'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib()]]),
        use_(['ref' => 'idea', 'component_ref' => 'text', 'functions' => ['A2'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['type' => 'B1', 'answer' => 'OWN_EXPRESSION'])]]),
        use_(['ref' => 'ill', 'component_ref' => 'img', 'functions' => ['A5'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['type' => 'B3', 'answer' => 'TRANSFORMED', 'recognizable' => 'YES'])]]),
    ];
    $p = oride_classify_declaration(decl(['uses' => $uses]));
    eq(array_column($p['uses'], 'code'), ['A4B2C1D2', 'A2B1C5D2', 'A5B3C3D2']);
    eq($p['code'], null);
    eq($p['composite_notation'], 'A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2');
    eq($p['status'], 'COMPLETE');
    eq($p['components'][0]['use_refs'], ['rev', 'idea']);
    eq($p['components'][0]['composite_notation'], 'A2+A4 | B1+B2 | C1+C5 | D2');
    eq($p['components'][1]['code'], 'A5B3C3D2');
    eq(oride_classify_declaration(decl(['uses' => [$uses[0], ['ref' => 'r2'] + $uses[0]]]))['code'], 'A4B2C1D2');
});
test('Caso 9 — incertidumbre', function () {
    eq(oride_classify_use(use_(['tool_identified' => false]))['code'], 'A3B0C0D0');
    $p = oride_classify_declaration(decl(['uses' => [use_(['incorporation' => 'UNKNOWN'])]]));
    eq($p['status'], 'PARTIAL');
    eq($p['code'], null);
    ok(oride_validate_profile($p)['can_issue']);
    $u = oride_classify_declaration(decl(['ai_usage' => 'UNCERTAIN']));
    eq($u['status'], 'UNDETERMINED');
    ok(oride_validate_profile($u)['can_issue']);
    eq(oride_classify_declaration(decl(['uses' => [use_(['incorporation' => null])]]))['status'], 'PENDING');
    eq(oride_classify_declaration(decl())['status'], 'PENDING');
});
test('Multifunción: nunca elige el número mayor', function () {
    $u = oride_classify_use(use_(['functions' => ['A3', 'A2'], 'primary_function' => 'NONE']));
    eq($u['code'], null);
    ok(str_starts_with($u['notation'], 'A2+A3 ·'));
    eq(oride_classify_use(use_(['functions' => ['A2', 'A3'], 'primary_function' => 'A2']))['code'], 'A2B0C0D2');
    eq(oride_classify_use(use_(['functions' => ['A2', 'A3'], 'primary_function' => 'A5']))['status'], 'PENDING');
});
test('Parejas B–C por aportación, avisos y repetidos', function () {
    $u = oride_classify_use(use_(['functions' => ['A4'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['ref' => 'x', 'type' => 'B1', 'answer' => 'OWN_EXPRESSION']), contrib(['ref' => 'y'])]]));
    eq(array_map(fn($p) => $p['b'] . $p['c'], $u['pairs']), ['B1C5', 'B2C1']);
    eq($u['code'], null);
    $w = oride_classify_use(use_(['functions' => ['A5'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['type' => 'B3', 'answer' => 'OWN_EXPRESSION'])]]));
    eq($w['status'], 'COMPLETE');
    ok(in_array('W-BC-01', array_column($w['issues'], 'rule'), true));
    eq(oride_classify_use(use_(['incorporation' => 'INCORPORATED', 'contributions' => [contrib(['ref' => 'a']), contrib(['ref' => 'b'])]]))['status'], 'PENDING');
    eq(oride_classify_use(use_(['incorporation' => 'NONE', 'contributions' => [contrib()]]))['status'], 'PENDING');
});
test('Dimensión D: criterios objetivos y mínimo de la obra', function () {
    eq(oride_classify_use(use_(['phases' => []]))['documentation'], 'D1');
    eq(oride_classify_use(use_(['functions' => ['A4'], 'incorporation' => 'INCORPORATED', 'contributions' => [contrib(['description' => ''])]]))['documentation'], 'D1');
    eq(oride_classify_use(use_(['evidence_count' => 1]))['documentation'], 'D3');
    eq(oride_classify_use(use_(), 1)['documentation'], 'D3');
    eq(oride_classify_declaration(decl(['uses' => [use_(['ref' => 'a', 'evidence_count' => 1]), use_(['ref' => 'b', 'phases' => []])]]))['documentation'], 'D1');
});
test('validar y explicar códigos', function () {
    foreach (['A5B3C4D2' => true, 'ORIDE A0B0C0D0' => true, 'a4b2c1d2' => true, 'A6B3C4D2' => false, 'A5B0C3D2' => false, 'A5B3C0D2' => false, 'A0B3C1D0' => false, 'A5B3C4' => false] as $c => $v) {
        eq(oride_validate_code($c)['valid'], $v, $c);
    }
    eq(array_column(oride_explain_code('A5B3C4D2')['segments'], 'label'), ['Generación de contenido creativo', 'Aportación de contenido expresivo', 'Reelaboración creativa', 'Proceso creativo descrito en detalle']);
});

echo "\nIntegridad\n";
test('JSON canónico: orden de claves, sin espacios, enteros', function () {
    eq(oride_canonical(json_decode('{"b":1,"a":{"d":[1,"x"],"c":null}}')), '{"a":{"c":null,"d":[1,"x"]},"b":1}');
    eq(oride_canonical(json_decode('{"t":"ñ — «x» /  "}')), '{"t":"ñ — «x» / ' . "\u{2028}" . '"}');
    $thrown = false;
    try {
        oride_canonical(json_decode('{"a":1.5}'));
    } catch (Throwable) {
        $thrown = true;
    }
    ok($thrown, 'debería rechazar decimales');
});
test('SHA-256 conocido', fn() => eq(hash('sha256', 'abc'), 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad'));
test('Divulgación selectiva: verifica completa y parcial; detecta alteraciones', function () {
    $sections = [];
    foreach (ORIDE_SECTIONS as $i => $n) $sections[$n] = ['salt' => str_pad("s$i", 32, '0'), 'data' => ['n' => $n, 'v' => $i, 'x' => ['b' => 1, 'a' => [true, null, 'ñ']]]];
    $snap = json_decode(json_encode(['format' => ORIDE_SNAPSHOT_FORMAT, 'sections' => $sections]));
    [$hash, $digests] = oride_content_hash($snap);
    ok(oride_verify_view($snap, $hash)['ok']);
    $view = oride_disclose($snap, $digests, ['meta', 'classification']);
    ok(!isset($view->sections->declarant));
    ok(oride_verify_view($view, $hash)['ok']);
    $bad = json_decode(json_encode($view));
    $bad->sections->meta->data->v = 99;
    ok(!oride_verify_view($bad, $hash)['ok']);
});
test('Compatibilidad con la versión Next.js: misma huella para la misma entrada', function () {
    // Vector calculado con src/lib/integrity/disclosure.ts (sectionDigest de "meta").
    $d = oride_section_digest('meta', json_decode('{"salt":"00","data":{"a":1,"b":"ñ"}}'));
    eq($d, hash('sha256', '{"data":{"a":1,"b":"ñ"},"salt":"00","section":"meta"}'));
});
test('Identificadores ORD-XXXX-XXXX-XXXX', function () {
    $ids = [];
    for ($i = 0; $i < 2000; $i++) $ids[oride_public_id()] = true;
    eq(count($ids), 2000);
    foreach (array_keys($ids) as $id) ok(preg_match(ORIDE_PUBLIC_ID_RE, $id) === 1, $id);
    eq(oride_normalize_public_id('ord-7f29-c4a8-km3z'), 'ORD-7F29-C4A8-KM3Z');
    eq(oride_normalize_public_id('ORD-OOOO-IIII-LLLL'), 'ORD-0000-1111-1111');
    eq(oride_normalize_public_id('../../etc'), null);
});

echo "\nOrientación art. 50\n";
$a50 = fn(array $o) => array_column(oride_article50($o)['findings'], 'id');
test('ultrasuplantación profesional → posible obligación', fn() => ok(in_array('DEEPFAKE', $a50(['role' => 'OWN_AUTHORITY', 'context' => 'PROFESSIONAL', 'syntheticMedia' => 'YES', 'realisticResemblance' => 'YES', 'evidentlyCreative' => 'NO']), true)));
test('obra creativa → obligación limitada', fn() => ok(in_array('DEEPFAKE_CREATIVE', $a50(['role' => 'OWN_AUTHORITY', 'context' => 'PROFESSIONAL', 'syntheticMedia' => 'YES', 'realisticResemblance' => 'YES', 'evidentlyCreative' => 'YES']), true)));
test('imagen no realista no es ultrasuplantación', function () use ($a50) {
    $f = $a50(['role' => 'OWN_AUTHORITY', 'context' => 'PROFESSIONAL', 'syntheticMedia' => 'YES', 'realisticResemblance' => 'NO']);
    ok(in_array('SYNTHETIC_NOT_DEEPFAKE', $f, true) && in_array('NOT_IDENTIFIED', $f, true) && !in_array('DEEPFAKE', $f, true));
});
test('uso personal no profesional', function () use ($a50) {
    $f = $a50(['role' => 'OWN_AUTHORITY', 'context' => 'PERSONAL_NON_PROFESSIONAL', 'syntheticMedia' => 'YES', 'realisticResemblance' => 'YES']);
    ok(in_array('PERSONAL_USE', $f, true) && !in_array('DEEPFAKE', $f, true));
});
test('texto de interés público con control editorial → posible excepción', fn() => ok(in_array('PUBLIC_TEXT_EXCEPTION', $a50(['role' => 'FOR_ORGANIZATION', 'context' => 'PROFESSIONAL', 'publicInterestText' => 'YES', 'humanReview' => 'YES', 'editorialResponsibility' => 'YES']), true)));
test('nunca afirma cumplimiento', function () {
    $r = oride_article50(['role' => 'OWN_AUTHORITY', 'context' => 'PROFESSIONAL', 'syntheticMedia' => 'NO', 'publicInterestText' => 'NO']);
    ok(str_contains(end($r['findings'])['text'], 'no equivale a una certificación de cumplimiento legal'));
});

$t = $GLOBALS['t'];
echo "\n{$t['pass']} correctas, {$t['fail']} fallidas\n";
exit($t['fail'] ? 1 : 0);
