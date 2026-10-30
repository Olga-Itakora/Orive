<?php
/**
 * ORIDE Code v1.0 — motor de clasificación determinista.
 *
 * - Se deriva exclusivamente de respuestas estructuradas; sin modelos generativos.
 * - Nunca se elige un valor «por ser el número más alto».
 * - Cada uso conserva sus propias parejas B–C.
 * - Sin información suficiente no se emite código.
 *
 * Entradas (arrays):
 *   uso: ref, component_ref, tool_identified (bool|null), phases[], functions[], primary_function (A#|'NONE'|null),
 *        objective, activity, result, incorporation ('NONE'|'INCORPORATED'|'UNKNOWN'|null),
 *        contributions[] (ref, type B1-3, answer, recognizable, description), evidence_count
 *   declaración: ai_usage ('NO'|'YES'|'UNCERTAIN'|null), process_narrative, uses[], declaration_evidence_count
 */
declare(strict_types=1);

const ORIDE_D_THRESHOLDS = ['activity' => 10, 'result' => 10, 'intervention' => 10, 'narrative' => 100];

function oride_len(?string $s): int
{
    return mb_strlen(trim((string) $s));
}

function oride_sort_codes(array $codes, string $dim): array
{
    $codes = array_values(array_unique($codes));
    usort($codes, fn($a, $b) => strcmp($a, $b)); // A0<A1<…: orden de presentación, no de importancia
    return $codes;
}

/** C a partir de la respuesta original. */
function oride_derive_intervention(?string $answer, ?string $recognizable): array
{
    switch ($answer) {
        case 'ACCEPTED':
            return ['C1', 'OK', 'R-C-01'];
        case 'MINOR_EDITS':
            return ['C2', 'OK', 'R-C-02'];
        case 'TRANSFORMED':
            if ($recognizable === 'YES') return ['C3', 'OK', 'R-C-03'];
            if ($recognizable === 'NO') return ['C4', 'OK', 'R-C-04'];
            if ($recognizable === 'UNSURE') return [null, 'UNSURE', 'R-C-07'];
            return [null, 'MISSING', 'R-C-07'];
        case 'OWN_EXPRESSION':
            return ['C5', 'OK', 'R-C-05'];
        case 'UNSURE':
            return [null, 'UNSURE', 'R-C-06'];
        default:
            return [null, 'MISSING', 'R-C-08'];
    }
}

function oride_use_documentation(array $u, int $declEvidence): array
{
    $d1 = ($u['tool_identified'] ?? null) === true && count($u['functions']) > 0 && oride_len($u['activity'] ?? '') >= ORIDE_D_THRESHOLDS['activity'];
    if (!$d1) return ['D0', ['R-D-00']];
    $rules = ['R-D-01'];
    $contribOk = ($u['incorporation'] ?? null) !== 'INCORPORATED'
        || (count($u['contributions']) > 0 && count(array_filter($u['contributions'], fn($c) => oride_len($c['description'] ?? '') < ORIDE_D_THRESHOLDS['intervention'])) === 0);
    $d2 = count($u['phases']) > 0 && oride_len($u['result'] ?? '') >= ORIDE_D_THRESHOLDS['result'] && ($u['incorporation'] ?? null) !== null && $contribOk;
    if (!$d2) return ['D1', $rules];
    $rules[] = 'R-D-02';
    if (($u['evidence_count'] ?? 0) + $declEvidence > 0) {
        $rules[] = 'R-D-03';
        return ['D3', $rules];
    }
    return ['D2', $rules];
}

function oride_issue(string $rule, string $field, string $severity, string $message): array
{
    return compact('rule', 'field', 'severity', 'message');
}

/** classifyAIUse */
function oride_classify_use(array $u, int $declEvidence = 0): array
{
    $issues = [];
    $rules = [];
    $missing = false;
    $unsure = false;

    $functions = oride_sort_codes($u['functions'] ?? [], 'A');
    if (in_array('A0', $functions, true)) {
        $issues[] = oride_issue('R-A-03', 'functions', 'error', 'A0 solo puede usarse en declaraciones sin utilización de IA.');
        $missing = true;
    }
    if (!$functions) {
        $issues[] = oride_issue('R-A-01', 'functions', 'error', 'Indica al menos una función de la IA en este uso.');
        $missing = true;
    } else {
        $rules[] = 'R-A-01';
    }

    $compact = null;
    $primary = $u['primary_function'] ?? null;
    if (count($functions) === 1) {
        $compact = $functions[0];
    } elseif (count($functions) > 1) {
        if ($primary && $primary !== 'NONE') {
            if (in_array($primary, $functions, true)) {
                $compact = $primary;
                $rules[] = 'R-A-02';
            } else {
                $issues[] = oride_issue('R-A-02', 'primary_function', 'error', 'La función principal debe estar entre las funciones seleccionadas.');
                $missing = true;
            }
        } else {
            $rules[] = 'R-A-04';
        }
    }

    $pairs = [];
    $contribs = $u['contributions'] ?? [];
    switch ($u['incorporation'] ?? null) {
        case 'NONE':
            $pairs[] = ['contribution_ref' => null, 'b' => 'B0', 'c' => 'C0'];
            array_push($rules, 'R-B-01', 'R-BC-01');
            if ($contribs) {
                $issues[] = oride_issue('R-B-03', 'contributions', 'error', 'La respuesta «No se incorporó nada» (B0) es incompatible con otras aportaciones.');
                $missing = true;
            }
            break;
        case 'INCORPORATED':
            $rules[] = 'R-B-02';
            if (!$contribs) {
                $issues[] = oride_issue('R-B-02', 'contributions', 'error', 'Indica qué tipo de aportación de la IA se incorporó.');
                $missing = true;
            }
            $seen = [];
            foreach ($contribs as $c) {
                if (isset($seen[$c['type']])) {
                    $issues[] = oride_issue('R-B-04', 'contributions', 'error', "El tipo {$c['type']} aparece repetido. Si los resultados son heterogéneos, divide el uso en subusos.");
                    $missing = true;
                }
                $seen[$c['type']] = true;
                [$code, $state, $rule] = oride_derive_intervention($c['answer'] ?? null, $c['recognizable'] ?? null);
                $rules[] = $rule;
                if ($state === 'MISSING') {
                    $issues[] = oride_issue($rule, 'contribution.' . $c['type'], 'error', "Indica qué hiciste con la aportación {$c['type']}.");
                    $missing = true;
                } elseif ($state === 'UNSURE') {
                    $unsure = true;
                }
                $pairs[] = ['contribution_ref' => $c['ref'] ?? null, 'b' => $c['type'], 'c' => $code];
                foreach (oride_compat_warnings($functions, $c['type'], $code, (string) ($c['ref'] ?? '')) as $w) {
                    $issues[] = $w;
                }
            }
            break;
        case 'UNKNOWN':
            $rules[] = 'R-B-05';
            $unsure = true;
            break;
        default:
            $issues[] = oride_issue('R-B-06', 'incorporation', 'error', 'Indica si algo procedente de este uso se incorporó a la obra.');
            $missing = true;
    }

    [$doc, $docRules] = oride_use_documentation($u + ['functions' => $functions], $declEvidence);
    $rules = array_merge($rules, $docRules);
    $status = $missing ? 'PENDING' : ($unsure ? 'INSUFFICIENT' : 'COMPLETE');

    $code = null;
    if ($status === 'COMPLETE' && $compact && count($pairs) === 1 && $pairs[0]['c']) {
        $code = $compact . $pairs[0]['b'] . $pairs[0]['c'] . $doc;
        $rules[] = 'R-CODE-01';
    }

    $a = $functions ? implode('+', $functions) : 'A?';
    $bc = $pairs ? implode(' + ', array_map(fn($p) => $p['b'] . ($p['c'] ?? 'C?'), $pairs)) : 'B?C?';
    $prim = ($compact && count($functions) > 1) ? " (principal $compact)" : '';
    $suffix = $status === 'COMPLETE' ? '' : ($status === 'INSUFFICIENT' ? ' · información insuficiente' : ' · clasificación pendiente');

    return [
        'ref' => $u['ref'],
        'component_ref' => $u['component_ref'] ?? null,
        'status' => $status,
        'functions' => $functions,
        'compact_function' => $compact,
        'multi_function' => count($functions) > 1,
        'pairs' => $pairs,
        'documentation' => $doc,
        'code' => $code,
        'notation' => "$a$prim · $bc · $doc$suffix",
        'rules_applied' => array_values(array_unique($rules)),
        'issues' => $issues,
    ];
}

function oride_compat_warnings(array $functions, string $b, ?string $c, string $ref): array
{
    $out = [];
    if ($b === 'B3' && $c === 'C5') {
        $out[] = oride_issue('W-BC-01', "contribution.$ref", 'warning', 'Si solo tomaste la idea y realizaste tú toda la expresión final, la aportación quizá sea conceptual (B1) y no de contenido expresivo (B3).');
    }
    if ($b === 'B2' && in_array($c, ['C4', 'C5'], true)) {
        $out[] = oride_issue('W-BC-02', "contribution.$ref", 'warning', 'Combinación poco habitual: una aportación formal (B2) suele aceptarse o editarse (C1/C2).');
    }
    if ($b === 'B3' && !in_array('A5', $functions, true) && !in_array('A4', $functions, true)) {
        $out[] = oride_issue('W-AB-01', "contribution.$ref", 'warning', 'Hay contenido expresivo incorporado (B3) sin la función de generación (A5) ni de transformación (A4).');
    }
    return $out;
}

function oride_min_doc(array $codes): string
{
    if (!$codes) return 'D0';
    sort($codes);
    return $codes[0];
}

function oride_group_profile(array $uses, string $d): array
{
    if (!$uses) {
        return ['status' => 'PENDING', 'code' => null, 'composite' => null, 'composite_notation' => null, 'insufficient_use_count' => 0];
    }
    $pending = (bool) array_filter($uses, fn($u) => $u['status'] === 'PENDING');
    $insufficient = count(array_filter($uses, fn($u) => $u['status'] === 'INSUFFICIENT'));
    $A = oride_sort_codes(array_merge(...array_map(fn($u) => $u['functions'], $uses)), 'A');
    $allPairs = array_merge(...array_map(fn($u) => $u['pairs'], $uses));
    $B = oride_sort_codes(array_column($allPairs, 'b'), 'B');
    $C = oride_sort_codes(array_filter(array_column($allPairs, 'c')), 'C');
    $fmt = fn($xs) => $xs ? implode('+', $xs) : '—';
    $notation = $fmt($A) . ' | ' . $fmt($B) . ' | ' . $fmt($C) . ' | ' . $d;
    if ($insufficient > 0) {
        $notation .= " (+$insufficient uso" . ($insufficient > 1 ? 's' : '') . ' con información insuficiente)';
    }
    $composite = ['A' => $A, 'B' => $B, 'C' => $C, 'D' => $d];
    if ($pending) {
        return ['status' => 'PENDING', 'code' => null, 'composite' => $composite, 'composite_notation' => $notation, 'insufficient_use_count' => $insufficient];
    }
    $code = null;
    if ($insufficient === 0) {
        $sigs = array_unique(array_map(fn($u) => ($u['compact_function'] && count($u['pairs']) === 1 && $u['pairs'][0]['c']) ? $u['compact_function'] . $u['pairs'][0]['b'] . $u['pairs'][0]['c'] : '', $uses));
        $single = !array_filter($uses, fn($u) => count($u['functions']) !== 1);
        if (count($sigs) === 1 && reset($sigs) !== '' && $single) {
            $code = reset($sigs) . $d;
        }
    }
    return ['status' => $insufficient > 0 ? 'PARTIAL' : 'COMPLETE', 'code' => $code, 'composite' => $composite, 'composite_notation' => $notation, 'insufficient_use_count' => $insufficient];
}

/** generateCompositeProfile */
function oride_classify_declaration(array $in): array
{
    $base = ['scheme_version' => ORIDE_SCHEME_VERSION, 'ai_usage' => $in['ai_usage'] ?? null];
    $declEv = (int) ($in['declaration_evidence_count'] ?? 0);
    $narrDoc = function () use ($in, $declEv) {
        if (oride_len($in['process_narrative'] ?? '') < ORIDE_D_THRESHOLDS['narrative']) return 'D0';
        return $declEv > 0 ? 'D3' : 'D2';
    };

    $usage = $in['ai_usage'] ?? null;
    if ($usage === null) {
        return $base + ['status' => 'PENDING', 'code' => null, 'composite' => null, 'composite_notation' => null, 'insufficient_use_count' => 0, 'documentation' => 'D0', 'uses' => [], 'components' => [], 'rules_applied' => [],
            'issues' => [oride_issue('R-G-00', 'ai_usage', 'error', 'Indica si se utilizaron sistemas de IA.')]];
    }
    if ($usage === 'NO') {
        $d = $narrDoc();
        $issues = [];
        if (!empty($in['uses'])) {
            $issues[] = oride_issue('R-G-02', 'uses', 'error', 'La declaración indica que no se utilizó IA, pero contiene usos registrados. Elimina los usos o cambia la respuesta.');
        }
        return $base + ['status' => $issues ? 'PENDING' : 'COMPLETE', 'code' => $issues ? null : "A0B0C0$d", 'composite' => ['A' => ['A0'], 'B' => ['B0'], 'C' => ['C0'], 'D' => $d],
            'composite_notation' => "A0 | B0 | C0 | $d", 'insufficient_use_count' => 0, 'documentation' => $d, 'uses' => [], 'components' => [], 'rules_applied' => ['R-G-01', 'R-D-NOAI'], 'issues' => $issues];
    }
    $uses = array_map(fn($u) => oride_classify_use($u, $declEv), $in['uses'] ?? []);
    if ($usage === 'UNCERTAIN') {
        $errors = [];
        foreach ($uses as $u) foreach ($u['issues'] as $i) if ($i['severity'] === 'error') $errors[] = $i;
        return $base + ['status' => 'UNDETERMINED', 'code' => null, 'composite' => null, 'composite_notation' => null, 'insufficient_use_count' => 0, 'documentation' => $narrDoc(), 'uses' => $uses, 'components' => [], 'rules_applied' => ['R-G-03', 'R-D-NOAI'], 'issues' => $errors];
    }
    $issues = [];
    if (!$uses) {
        $issues[] = oride_issue('R-G-04', 'uses', 'error', 'Falta registrar al menos un uso de IA para describir cómo se utilizó.');
    }
    $d = oride_min_doc(array_column($uses, 'documentation'));
    $group = oride_group_profile($uses, $d);
    $components = [];
    $refs = [];
    foreach ($uses as $u) {
        $key = $u['component_ref'] === null ? '' : (string) $u['component_ref'];
        if (!array_key_exists($key, $refs)) $refs[$key] = $u['component_ref'];
    }
    foreach ($refs as $key => $ref) {
        $cu = array_values(array_filter($uses, fn($u) => (string) ($u['component_ref'] ?? '') === (string) $key));
        $components[] = ['component_ref' => $ref, 'use_refs' => array_column($cu, 'ref')] + oride_group_profile($cu, oride_min_doc(array_column($cu, 'documentation')));
    }
    $all = $issues;
    foreach ($uses as $u) foreach ($u['issues'] as $i) $all[] = $i;
    return $base + $group + [
        'status' => $issues ? 'PENDING' : $group['status'],
        'code' => $issues ? null : $group['code'],
        'documentation' => $d,
        'uses' => $uses,
        'components' => $components,
        'rules_applied' => ['R-G-05', 'R-D-MIN'],
        'issues' => $all,
    ];
}

/** validateTransparencyProfile */
function oride_validate_profile(array $p): array
{
    $errors = array_values(array_filter($p['issues'], fn($i) => $i['severity'] === 'error'));
    $warnings = array_values(array_filter($p['issues'], fn($i) => $i['severity'] === 'warning'));
    return ['can_issue' => !$errors && $p['status'] !== 'PENDING', 'errors' => $errors, 'warnings' => $warnings];
}

/** validateORIDECode */
function oride_validate_code(string $raw): array
{
    $code = strtoupper(preg_replace('/^ORIDE\s+/i', '', trim($raw)));
    if (!preg_match('/^A([0-5])B([0-3])C([0-5])D([0-3])$/', $code, $m)) {
        return ['valid' => false, 'code' => $code, 'errors' => ['Formato no válido. Se espera A#B#C#D#, por ejemplo A5B3C4D2.']];
    }
    [$a, $b, $c, $d] = ["A$m[1]", "B$m[2]", "C$m[3]", "D$m[4]"];
    $e = [];
    if ($a === 'A0' && ($b !== 'B0' || $c !== 'C0')) $e[] = 'A0 (sin IA) exige B0 y C0.';
    if ($b === 'B0' && $c !== 'C0') $e[] = 'B0 (nada incorporado) exige C0.';
    if ($b !== 'B0' && $c === 'C0') $e[] = 'C0 solo es aplicable cuando no se incorporó ninguna aportación (B0).';
    if ($a === 'A0' && $d === 'D1') $e[] = 'D1 no es aplicable a una declaración sin IA en v1.0.';
    return ['valid' => !$e, 'code' => $code, 'errors' => $e, 'parts' => [$a, $b, $c, $d]];
}

/** explainORIDECode */
function oride_explain_code(string $raw): array
{
    $code = strtoupper(preg_replace('/^ORIDE\s+/i', '', trim($raw)));
    $v = preg_match('/^[ABCD]\d$/', $code) ? ['valid' => oride_label($code) !== $code, 'errors' => []] : oride_validate_code($code);
    preg_match_all('/[ABCD]\d/', $code, $mm);
    $dims = oride_dimensions();
    $segments = [];
    foreach ($mm[0] as $t) {
        if (!isset($dims[$t[0]]['values'][$t])) continue;
        $segments[] = ['dimension' => $t[0], 'dimension_name' => $dims[$t[0]]['name'], 'code' => $t, 'label' => oride_label($t), 'description' => oride_description($t)];
    }
    return ['valid' => $v['valid'], 'scheme_version' => ORIDE_SCHEME_VERSION, 'code' => $code, 'segments' => $segments, 'errors' => $v['errors'] ?? [],
        'disclaimer' => 'El código ORIDE es una clasificación descriptiva del proceso declarado. No es un porcentaje de autoría, una puntuación de originalidad ni una certificación de cumplimiento legal. Los números son categorías, no magnitudes.'];
}
