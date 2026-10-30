<?php
/**
 * Instantánea emitida: contenido congelado de una versión.
 * - Sin correo, datos de acceso ni identificadores internos.
 * - De los colaboradores (terceros) solo se incluyen sus roles.
 */
declare(strict_types=1);

function snap_refs(array $f): array
{
    $r = ['component' => [], 'tool' => [], 'use' => [], 'contribution' => [], 'evidence' => []];
    foreach ($f['components'] as $i => $c) $r['component'][$c['id']] = 'K' . ($i + 1);
    foreach ($f['tools'] as $i => $t) $r['tool'][$t['id']] = 'T' . ($i + 1);
    foreach ($f['uses'] as $i => $u) {
        $r['use'][$u['id']] = 'U' . ($i + 1);
        foreach ($u['contributions'] as $c) $r['contribution'][$c['id']] = 'U' . ($i + 1) . '-' . $c['ctype'];
    }
    foreach ($f['evidence'] as $i => $e) $r['evidence'][$e['id']] = 'E' . ($i + 1);
    return $r;
}

function snap_s(?string $s): ?string
{
    $s = trim((string) $s);
    return $s === '' ? null : $s;
}

function coverage_statement(array $f): string
{
    $v = $f['v'];
    $ids = json_decode($v['scope_component_ids'] ?: '[]', true) ?: [];
    $names = [];
    foreach ($f['components'] as $c) if (in_array((int) $c['id'], array_map('intval', $ids), true)) $names[] = '«' . $c['name'] . '»';
    $others = $f['work']['is_collective'] || $f['contributors'];
    switch ($v['scope']) {
        case 'WHOLE_WORK':
            return $others
                ? 'La declaración se refiere a la obra completa, dentro del ámbito de conocimiento y responsabilidad del declarante. En una obra colectiva no acredita por sí misma el proceso seguido por los demás participantes.'
                : 'La declaración se refiere a la obra completa.';
        case 'COMPONENTS':
            return 'La declaración se limita a: ' . ($names ? implode(', ', $names) : 'los componentes indicados') . '. No abarca necesariamente otros componentes de la obra' . ($others ? ' ni las aportaciones de los demás participantes' : '') . '.';
        case 'OWN_CONTRIBUTION':
            return 'La declaración se limita a la contribución del declarante' . ($v['declarant_role'] ? " ({$v['declarant_role']})" : '') . ($names ? ' en ' . implode(', ', $names) : '') . '. No abarca necesariamente otras partes de la obra ni las aportaciones de los demás participantes.';
    }
    return 'Alcance no indicado.';
}

function summary_text(array $f, array $profile, array $refs): string
{
    $w = $f['work'];
    $head = "Obra: «{$w['title']}» ({$w['category_label']}).";
    $eff = effective_usage($f['v']);
    if ($eff === 'NO') return "$head El declarante afirma que no se utilizaron sistemas de inteligencia artificial en el ámbito declarado. " . coverage_statement($f);
    if ($eff === 'UNCERTAIN') {
        $note = snap_s($f['v']['uncertainty_note']);
        return "$head El declarante no puede determinar con certeza si se utilizaron sistemas de inteligencia artificial." . ($note ? " Motivo indicado: $note" : '') . ' ' . coverage_statement($f);
    }
    $lines = [];
    foreach ($profile['uses'] as $p) {
        $use = null;
        foreach ($f['uses'] as $u) if ((int) $u['id'] === (int) $p['ref']) $use = $u;
        $fn = implode(', ', array_map(fn($x) => mb_strtolower(oride_label($x)), $p['functions'])) ?: 'función no indicada';
        $pairs = implode('; ', array_map(fn($x) => $x['b'] === 'B0' ? 'sin incorporación a la obra' : mb_strtolower(oride_label($x['b'])) . ' con ' . ($x['c'] ? mb_strtolower(oride_label($x['c'])) : 'intervención no precisada'), $p['pairs'])) ?: 'incorporación no precisada';
        $lines[] = $refs['use'][$p['ref']] . " «{$use['title']}»: $fn; $pairs.";
    }
    $n = count($profile['uses']);
    return "$head Se declaran $n uso" . ($n === 1 ? '' : 's') . ' de IA. ' . implode(' ', $lines) . ' ' . coverage_statement($f);
}

/** @return array{0:object,1:array} instantánea y mapa de referencias */
function build_snapshot(array $f, array $profile, array $ctx): array
{
    $v = $f['v'];
    $w = $f['work'];
    $refs = snap_refs($f);
    $a50answers = oride_article50_answers(json_decode($v['article50_answers'] ?: '{}', true) ?: []);
    $a50 = oride_article50($a50answers);
    $eff = effective_usage($v);
    $scopeIds = array_map('intval', json_decode($v['scope_component_ids'] ?: '[]', true) ?: []);
    $cref = fn($id) => $id === null ? null : ($refs['component'][$id] ?? null);

    $data = [
        'meta' => [
            'platform' => 'ORIDE', 'documentType' => 'ORIDE Declaration', 'publicId' => $ctx['public_id'], 'versionNumber' => (int) $v['version_number'],
            'issuedAt' => $ctx['issued_at'], 'schemeVersion' => ORIDE_SCHEME_VERSION, 'questionnaireVersion' => $v['questionnaire_version'],
            'supersedesPublicId' => $f['supersedes']['public_id'] ?? null, 'verifyUrl' => $ctx['verify_url'],
        ],
        'work' => [
            'title' => $w['title'], 'subtitle' => snap_s($w['subtitle']), 'category' => ['key' => $w['category_key'], 'label' => $w['category_label']],
            'description' => snap_s($w['description']), 'creationPeriod' => snap_s($w['creation_period']), 'status' => $w['status'],
            'publicationDate' => $w['publication_date'] ?: null, 'language' => snap_s($w['language']), 'workVersion' => snap_s($w['work_version']),
            'publisher' => snap_s($w['publisher']), 'identifierType' => snap_s($w['identifier_type']), 'identifier' => snap_s($w['identifier']), 'url' => snap_s($w['url']),
            'isCollective' => (bool) $w['is_collective'],
            'components' => array_map(fn($c) => ['ref' => $refs['component'][$c['id']], 'name' => $c['name'], 'kind' => $c['kind'], 'kindLabel' => L_COMPONENT_KIND[$c['kind']], 'description' => snap_s($c['description'])], $f['components']),
            'contributorRoles' => array_map(fn($c) => ['role' => $c['role'], 'componentRefs' => array_values(array_filter(array_map(fn($id) => $cref((int) $id), json_decode($c['component_ids'] ?: '[]', true) ?: [])))], $f['contributors']),
        ],
        'declarant' => ['name' => (string) $v['declarant_name'], 'artisticName' => snap_s($v['declarant_artistic_name']), 'organization' => snap_s($v['declarant_organization']), 'role' => snap_s($v['declarant_role'])],
        'scope' => [
            'scope' => $v['scope'], 'scopeLabel' => $v['scope'] ? L_SCOPE[$v['scope']] : null, 'componentRefs' => array_values(array_filter(array_map($cref, $scopeIds))),
            'responsibilityNote' => snap_s($v['responsibility_note']), 'aiUsage' => $v['ai_usage'], 'ownContributionAiUsage' => $v['own_ai_usage'],
            'effectiveAiUsage' => $eff, 'effectiveAiUsageLabel' => $eff ? L_AI_USAGE[$eff] : null, 'uncertaintyNote' => snap_s($v['uncertainty_note']),
            'coverageStatement' => coverage_statement($f),
        ],
        'classification' => [
            'scheme' => 'ORIDE Code', 'schemeVersion' => ORIDE_SCHEME_VERSION, 'computedAt' => $ctx['issued_at'], 'status' => $profile['status'], 'code' => $profile['code'],
            'compositeNotation' => $profile['composite_notation'], 'composite' => $profile['composite'], 'documentation' => $profile['documentation'],
            'insufficientUseCount' => $profile['insufficient_use_count'], 'rulesApplied' => $profile['rules_applied'],
            'uses' => array_map(fn($u) => [
                'ref' => $refs['use'][$u['ref']], 'componentRef' => $cref($u['component_ref']), 'status' => $u['status'], 'code' => $u['code'], 'notation' => $u['notation'],
                'functions' => $u['functions'], 'compactFunction' => $u['compact_function'], 'documentation' => $u['documentation'], 'rulesApplied' => $u['rules_applied'],
                'pairs' => array_map(fn($p) => ['contributionRef' => $p['contribution_ref'] ? ($refs['contribution'][$p['contribution_ref']] ?? null) : null, 'b' => $p['b'], 'c' => $p['c']], $u['pairs']),
            ], $profile['uses']),
            'components' => array_map(fn($c) => ['componentRef' => $cref($c['component_ref']), 'status' => $c['status'], 'code' => $c['code'], 'compositeNotation' => $c['composite_notation'], 'useRefs' => array_map(fn($r) => $refs['use'][$r], $c['use_refs'])], $profile['components']),
        ],
        'summary' => ['text' => summary_text($f, $profile, $refs)],
        'process' => [
            'processNarrative' => snap_s($v['process_narrative']), 'observations' => snap_s($v['observations']),
            'tools' => array_map(fn($t) => ['ref' => $refs['tool'][$t['id']], 'identified' => (bool) $t['identified'], 'name' => $t['name'], 'provider' => snap_s($t['provider']), 'modelVersion' => snap_s($t['model_version']), 'systemType' => snap_s($t['system_type']), 'purpose' => snap_s($t['purpose']), 'usagePeriod' => snap_s($t['usage_period'])], $f['tools']),
            'uses' => array_map(function ($u) use ($refs, $cref, $profile) {
                $p = null;
                foreach ($profile['uses'] as $x) if ((int) $x['ref'] === (int) $u['id']) $p = $x;
                $phases = $u['phases_list'];
                sort($phases);
                $fns = $u['functions_list'];
                sort($fns);
                return [
                    'ref' => $refs['use'][$u['id']], 'title' => $u['title'], 'componentRef' => $cref($u['component_id'] === null ? null : (int) $u['component_id']),
                    'toolRef' => $u['tool_id'] ? ($refs['tool'][$u['tool_id']] ?? null) : null, 'parentRef' => $u['parent_use_id'] ? ($refs['use'][$u['parent_use_id']] ?? null) : null,
                    'phases' => $phases, 'phaseLabels' => array_map(fn($x) => ORIDE_PHASES[$x] ?? $x, $phases), 'functions' => $fns, 'primaryFunction' => $u['primary_function'],
                    'objective' => snap_s($u['objective']), 'activityDescription' => snap_s($u['activity']), 'resultDescription' => snap_s($u['result']), 'incorporation' => $u['incorporation'],
                    'contributions' => array_map(function ($c) use ($refs, $p, $u) {
                        $code = null;
                        foreach ($p['pairs'] ?? [] as $pp) if ((int) $pp['contribution_ref'] === (int) $c['id']) $code = $pp['c'];
                        return ['ref' => $refs['contribution'][$c['id']], 'type' => $c['ctype'], 'interventionAnswer' => $c['intervention_answer'], 'retainsRecognizableElements' => $c['recognizable'], 'interventionCode' => $code, 'interventionDescription' => snap_s($c['description'])];
                    }, $u['contributions']),
                    'evidenceRefs' => array_map(fn($e) => $refs['evidence'][$e], $u['evidence_ids']),
                ];
            }, $f['uses']),
        ],
        'article50' => ['answers' => $a50answers, 'answered' => $a50['answered'], 'findings' => $a50['findings'], 'disclaimer' => $a50['disclaimer']],
        'statement' => [
            'legalTextKey' => $ctx['statement']['tkey'], 'legalTextVersion' => $ctx['statement']['version'], 'locale' => 'es', 'text' => $ctx['statement']['content'],
            'textSha256' => $ctx['statement']['content_hash'], 'acceptedAt' => $ctx['issued_at'],
            'acceptanceMethod' => 'Aceptación expresa mediante acción autenticada en la cuenta del declarante (no constituye firma electrónica cualificada).',
        ],
        'evidence' => [
            'note' => 'Las evidencias no han sido auditadas por ORIDE. Su registro no acredita su autenticidad.',
            'items' => array_map(fn($e) => ['ref' => $refs['evidence'][$e['id']], 'kind' => $e['kind'], 'description' => $e['description'], 'useRef' => $e['use_id'] ? ($refs['use'][$e['use_id']] ?? null) : null, 'fileName' => $e['file_name'], 'mimeType' => $e['mime_type'], 'sizeBytes' => $e['size_bytes'] === null ? null : (int) $e['size_bytes'], 'sha256' => $e['sha256']], $f['evidence']),
        ],
    ];

    $sections = new stdClass();
    foreach (ORIDE_SECTIONS as $n) {
        // Normaliza a la forma JSON (objetos stdClass, listas array) para que la huella sea reproducible.
        $normalized = json_decode(json_encode($data[$n], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        oride_canonical($normalized);
        $sections->$n = (object) ['salt' => oride_salt(), 'data' => $normalized];
    }
    return [(object) ['format' => ORIDE_SNAPSHOT_FORMAT, 'sections' => $sections], $refs];
}
