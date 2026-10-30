<?php
/**
 * Servicios de dominio de las declaraciones. Todas las funciones que reciben
 * $uid comprueban la propiedad en la propia consulta (protección frente a IDOR).
 */
declare(strict_types=1);

require_once __DIR__ . '/snapshot.php';
require_once __DIR__ . '/pdf.php';

final class DomainError extends RuntimeException
{
}

function jarr(?string $s): array
{
    $d = json_decode($s ?: '[]', true);
    return is_array($d) ? $d : [];
}

function owned_work(int $uid, int $wid): ?array
{
    return q1('SELECT w.*, c.label_es AS category_label FROM works w JOIN work_categories c ON c.ckey = w.category_key WHERE w.id = ? AND w.owner_id = ?', [$wid, $uid]);
}

function owned_version(int $uid, int $vid): ?array
{
    return q1('SELECT v.*, d.work_id, d.owner_id FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id WHERE v.id = ? AND d.owner_id = ?', [$vid, $uid]);
}

/** Carga completa de una versión (ya comprobada la propiedad o pública). */
function load_full(int $vid): array
{
    $v = q1('SELECT v.*, d.work_id, d.owner_id FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id WHERE v.id = ?', [$vid]);
    if (!$v) throw new DomainError('Declaración no encontrada.');
    $work = q1('SELECT w.*, c.label_es AS category_label, c.phase_hints FROM works w JOIN work_categories c ON c.ckey = w.category_key WHERE w.id = ?', [$v['work_id']]);
    $uses = q('SELECT * FROM ai_uses WHERE version_id = ? ORDER BY sort_order, id', [$vid])->fetchAll();
    $contribs = q('SELECT c.* FROM contributions c JOIN ai_uses u ON u.id = c.use_id WHERE u.version_id = ? ORDER BY c.ctype', [$vid])->fetchAll();
    $evidence = q('SELECT id, version_id, use_id, kind, description, file_name, mime_type, size_bytes, sha256, created_at FROM evidence WHERE version_id = ? ORDER BY id', [$vid])->fetchAll();
    $tools = q('SELECT * FROM ai_tools WHERE version_id = ? ORDER BY sort_order, id', [$vid])->fetchAll();
    $toolById = array_column($tools, null, 'id');
    foreach ($uses as &$u) {
        $u['functions_list'] = jarr($u['functions']);
        $u['phases_list'] = jarr($u['phases']);
        $u['contributions'] = array_values(array_filter($contribs, fn($c) => (int) $c['use_id'] === (int) $u['id']));
        $u['evidence_ids'] = array_values(array_map(fn($e) => (int) $e['id'], array_filter($evidence, fn($e) => (int) $e['use_id'] === (int) $u['id'])));
        $u['tool'] = $u['tool_id'] ? ($toolById[$u['tool_id']] ?? null) : null;
    }
    unset($u);
    return [
        'v' => $v,
        'work' => $work,
        'components' => q('SELECT * FROM work_components WHERE work_id = ? ORDER BY sort_order, id', [$v['work_id']])->fetchAll(),
        'contributors' => q('SELECT * FROM work_contributors WHERE work_id = ? ORDER BY id', [$v['work_id']])->fetchAll(),
        'tools' => $tools,
        'uses' => $uses,
        'evidence' => $evidence,
        'supersedes' => $v['supersedes_id'] ? q1('SELECT id, public_id, version_number FROM declaration_versions WHERE id = ?', [$v['supersedes_id']]) : null,
        'superseded_by' => q1('SELECT id, public_id, version_number, status, visibility FROM declaration_versions WHERE supersedes_id = ?', [$vid]),
    ];
}

function effective_usage(array $v): ?string
{
    if ($v['ai_usage'] === 'OWN_CONTRIBUTION_ONLY') return in_array($v['own_ai_usage'], ['NO', 'YES', 'UNCERTAIN'], true) ? $v['own_ai_usage'] : null;
    return $v['ai_usage'] ?: null;
}

function engine_input(array $f): array
{
    $declEv = count(array_filter($f['evidence'], fn($e) => $e['use_id'] === null));
    return [
        'ai_usage' => effective_usage($f['v']),
        'process_narrative' => (string) $f['v']['process_narrative'],
        'declaration_evidence_count' => $declEv,
        'uses' => array_map(fn($u) => [
            'ref' => (int) $u['id'],
            'component_ref' => $u['component_id'] === null ? null : (int) $u['component_id'],
            'tool_identified' => $u['tool'] ? (bool) $u['tool']['identified'] : null,
            'phases' => $u['phases_list'],
            'functions' => $u['functions_list'],
            'primary_function' => $u['primary_function'],
            'objective' => (string) $u['objective'],
            'activity' => (string) $u['activity'],
            'result' => (string) $u['result'],
            'incorporation' => $u['incorporation'],
            'contributions' => array_map(fn($c) => ['ref' => (int) $c['id'], 'type' => $c['ctype'], 'answer' => $c['intervention_answer'], 'recognizable' => $c['recognizable'], 'description' => (string) $c['description']], $u['contributions']),
            'evidence_count' => count($u['evidence_ids']),
        ], $f['uses']),
    ];
}

function classify_full(array $f): array
{
    return oride_classify_declaration(engine_input($f));
}

/** Comprobaciones de completitud no relacionadas con la clasificación: [paso, mensaje]. */
function completeness(array $f): array
{
    $v = $f['v'];
    $out = [];
    if (!trim((string) $v['declarant_name'])) $out[] = ['declarante', 'Indica el nombre del declarante.'];
    if (!trim((string) $v['declarant_role'])) $out[] = ['declarante', 'Indica tu rol en la obra.'];
    if (!$v['scope']) $out[] = ['declarante', 'Indica el alcance de la declaración.'];
    if ($v['scope'] === 'COMPONENTS' && !jarr($v['scope_component_ids'])) $out[] = ['declarante', 'Selecciona los componentes que cubre la declaración.'];
    if (!$v['ai_usage']) $out[] = ['uso', 'Indica si se utilizó IA.'];
    if ($v['ai_usage'] === 'OWN_CONTRIBUTION_ONLY' && !$v['own_ai_usage']) $out[] = ['uso', 'Indica si se utilizó IA en tu contribución.'];
    if (effective_usage($v) === 'YES') {
        foreach ($f['uses'] as $u) {
            if (!$u['tool_id']) $out[] = ['usos', "El uso «{$u['title']}» no tiene herramienta asociada (puedes registrarla como desconocida)."];
            if (!trim((string) $u['activity'])) $out[] = ['usos', "Describe la actividad del uso «{$u['title']}»."];
        }
    }
    return $out;
}

function readiness(array $f): array
{
    $profile = classify_full($f);
    $val = oride_validate_profile($profile);
    $comp = completeness($f);
    return ['profile' => $profile, 'validation' => $val, 'completeness' => $comp, 'can_issue' => $val['can_issue'] && !$comp];
}

// ---------------------------------------------------------------------------
// Creación y versionado
// ---------------------------------------------------------------------------

function create_declaration(array $user, int $wid): int
{
    if (!owned_work((int) $user['id'], $wid)) throw new DomainError('Obra no encontrada.');
    return tx(function () use ($user, $wid) {
        q('INSERT INTO declarations (work_id, owner_id, created_at) VALUES (?, ?, ?)', [$wid, $user['id'], now()]);
        $did = (int) db()->lastInsertId();
        q('INSERT INTO declaration_versions (declaration_id, version_number, declarant_name, declarant_artistic_name, declarant_organization, scope_component_ids, created_at, updated_at) VALUES (?, 1, ?, ?, ?, ?, ?, ?)',
            [$did, $user['display_name'], $user['artistic_name'], $user['organization'], '[]', now(), now()]);
        return (int) db()->lastInsertId();
    });
}

/** Nueva versión (borrador) a partir de la última emitida; la anterior se conserva intacta. */
function new_version(int $uid, int $did): int
{
    $versions = q('SELECT v.* FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id WHERE d.id = ? AND d.owner_id = ? ORDER BY v.version_number DESC', [$did, $uid])->fetchAll();
    if (!$versions) throw new DomainError('Declaración no encontrada.');
    if ($versions[0]['status'] === 'DRAFT') return (int) $versions[0]['id'];
    $base = null;
    foreach ($versions as $x) if ($x['status'] === 'ISSUED') { $base = $x; break; }
    if (!$base) throw new DomainError('No hay una versión emitida vigente a partir de la cual crear una nueva versión.');
    $f = load_full((int) $base['id']);
    $s = $f['v'];
    return tx(function () use ($f, $s, $did, $versions) {
        $cols = ['declarant_name', 'declarant_artistic_name', 'declarant_organization', 'declarant_role', 'scope', 'scope_component_ids', 'responsibility_note', 'ai_usage', 'own_ai_usage', 'uncertainty_note', 'process_narrative', 'observations', 'article50_answers'];
        q('INSERT INTO declaration_versions (declaration_id, version_number, supersedes_id, ' . implode(',', $cols) . ', created_at, updated_at) VALUES (?, ?, ?, ' . implode(',', array_fill(0, count($cols), '?')) . ', ?, ?)',
            array_merge([$did, (int) $versions[0]['version_number'] + 1, $s['id']], array_map(fn($c) => $s[$c], $cols), [now(), now()]));
        $nv = (int) db()->lastInsertId();
        $toolMap = [];
        foreach ($f['tools'] as $t) {
            q('INSERT INTO ai_tools (version_id, identified, name, provider, model_version, system_type, purpose, usage_period, sort_order) VALUES (?,?,?,?,?,?,?,?,?)', [$nv, $t['identified'], $t['name'], $t['provider'], $t['model_version'], $t['system_type'], $t['purpose'], $t['usage_period'], $t['sort_order']]);
            $toolMap[$t['id']] = (int) db()->lastInsertId();
        }
        $useMap = [];
        $ordered = array_merge(array_filter($f['uses'], fn($u) => !$u['parent_use_id']), array_filter($f['uses'], fn($u) => $u['parent_use_id']));
        foreach ($ordered as $u) {
            q('INSERT INTO ai_uses (version_id, component_id, tool_id, parent_use_id, title, functions, primary_function, phases, objective, activity, result, incorporation, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $nv, $u['component_id'], $u['tool_id'] ? ($toolMap[$u['tool_id']] ?? null) : null, $u['parent_use_id'] ? ($useMap[$u['parent_use_id']] ?? null) : null,
                $u['title'], $u['functions'], $u['primary_function'], $u['phases'], $u['objective'], $u['activity'], $u['result'], $u['incorporation'], $u['sort_order'],
            ]);
            $useMap[$u['id']] = $nu = (int) db()->lastInsertId();
            foreach ($u['contributions'] as $c) {
                q('INSERT INTO contributions (use_id, ctype, intervention_answer, recognizable, description) VALUES (?,?,?,?,?)', [$nu, $c['ctype'], $c['intervention_answer'], $c['recognizable'], $c['description']]);
            }
        }
        foreach ($f['evidence'] as $e) {
            q('INSERT INTO evidence (version_id, use_id, kind, description, file_name, mime_type, size_bytes, sha256, created_at) VALUES (?,?,?,?,?,?,?,?,?)',
                [$nv, $e['use_id'] ? ($useMap[$e['use_id']] ?? null) : null, $e['kind'], $e['description'], $e['file_name'], $e['mime_type'], $e['size_bytes'], $e['sha256'], $e['created_at']]);
        }
        return $nv;
    });
}

function delete_draft(int $uid, int $vid): int
{
    $v = owned_version($uid, $vid);
    if (!$v || $v['status'] !== 'DRAFT') throw new DomainError('Solo se pueden descartar borradores.');
    tx(function () use ($v) {
        q('DELETE FROM declaration_versions WHERE id = ?', [$v['id']]);
        if (!qv('SELECT COUNT(*) FROM declaration_versions WHERE declaration_id = ?', [$v['declaration_id']])) {
            q('DELETE FROM declarations WHERE id = ?', [$v['declaration_id']]);
        }
    });
    return (int) $v['work_id'];
}

function active_statement(): array
{
    $t = q1("SELECT * FROM legal_texts WHERE tkey = 'declaration_statement' AND active = 1 ORDER BY published_at DESC LIMIT 1");
    if (!$t) throw new DomainError('No hay texto de declaración responsable activo. Ejecuta la instalación.');
    return $t;
}

// ---------------------------------------------------------------------------
// Emisión (transaccional e inmutable)
// ---------------------------------------------------------------------------

function issue_version(int $uid, int $vid, bool $accepted, string $statementHash): string
{
    if (!$accepted) throw new DomainError('Debes aceptar expresamente la declaración responsable.');
    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            return tx(function () use ($uid, $vid, $statementHash) {
                // Bloqueo de la fila: impide emitir dos veces el mismo borrador.
                $locked = q1('SELECT v.status FROM declaration_versions v JOIN declarations d ON d.id = v.declaration_id WHERE v.id = ? AND d.owner_id = ? FOR UPDATE', [$vid, $uid]);
                if (!$locked) throw new DomainError('Declaración no encontrada.');
                if ($locked['status'] !== 'DRAFT') throw new DomainError('Esta versión ya ha sido emitida.');
                $statement = active_statement();
                if (!hash_equals($statement['content_hash'], $statementHash)) {
                    throw new DomainError('El texto de la declaración responsable ha cambiado. Revísalo de nuevo antes de aceptar.');
                }
                $f = load_full($vid);
                $r = readiness($f);
                if (!$r['can_issue']) throw new DomainError('La declaración no está completa. Revisa los avisos de la página de revisión.');
                $profile = $r['profile'];

                $publicId = oride_public_id();
                $issuedAt = gmdate('Y-m-d\TH:i:s\Z');
                $issuedDb = gmdate('Y-m-d H:i:s', strtotime($issuedAt));
                [$snapshot, $refs] = build_snapshot($f, $profile, ['public_id' => $publicId, 'issued_at' => $issuedAt, 'verify_url' => abs_url('verificar.php?id=' . $publicId), 'statement' => $statement]);
                [$hash, $digests] = oride_content_hash($snapshot);
                $pdfFull = oride_pdf_full($snapshot, $hash);
                $pdfSummary = oride_pdf_summary($snapshot, $hash);

                // Perfiles y C derivado: antes del cambio de estado.
                $input = engine_input($f);
                foreach ($profile['uses'] as $p) {
                    $inp = null;
                    foreach ($input['uses'] as $x) if ($x['ref'] === $p['ref']) $inp = $x;
                    q('INSERT INTO profiles (version_id, scope, use_id, component_id, status, code, notation, functions, pairs, documentation, rules_applied, inputs, scheme_version, computed_at) VALUES (?, "USE", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                        $vid, $p['ref'], $p['component_ref'], $p['status'], $p['code'], $p['notation'], json_encode($p['functions']), json_encode($p['pairs']), $p['documentation'], json_encode($p['rules_applied']), json_encode($inp, JSON_UNESCAPED_UNICODE), ORIDE_SCHEME_VERSION, $issuedDb,
                    ]);
                    foreach ($p['pairs'] as $pp) {
                        if ($pp['contribution_ref']) q('UPDATE contributions SET intervention_code = ? WHERE id = ?', [$pp['c'], $pp['contribution_ref']]);
                    }
                }
                foreach ($profile['components'] as $c) {
                    q('INSERT INTO profiles (version_id, scope, component_id, status, code, notation, functions, pairs, documentation, rules_applied, inputs, scheme_version, computed_at) VALUES (?, "COMPONENT", ?, ?, ?, ?, ?, "[]", ?, "[]", ?, ?, ?)', [
                        $vid, $c['component_ref'], $c['status'], $c['code'], $c['composite_notation'], json_encode($c['composite']['A'] ?? []), $c['composite']['D'] ?? 'D0', json_encode(['use_refs' => $c['use_refs']]), ORIDE_SCHEME_VERSION, $issuedDb,
                    ]);
                }
                q('INSERT INTO profiles (version_id, scope, status, code, notation, functions, pairs, documentation, rules_applied, inputs, scheme_version, computed_at) VALUES (?, "WORK", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    $vid, $profile['status'], $profile['code'], $profile['composite_notation'], json_encode($profile['composite']['A'] ?? []),
                    json_encode(array_merge(...array_map(fn($u) => array_map(fn($p) => ['use' => $refs['use'][$u['ref']], 'b' => $p['b'], 'c' => $p['c']], $u['pairs']), $profile['uses'] ?: [['pairs' => [], 'ref' => 0]]))),
                    $profile['documentation'], json_encode($profile['rules_applied']), json_encode(['ai_usage' => $input['ai_usage'], 'declaration_evidence_count' => $input['declaration_evidence_count']]), ORIDE_SCHEME_VERSION, $issuedDb,
                ]);

                q('UPDATE declaration_versions SET status = "ISSUED", public_id = ?, issued_at = ?, snapshot = ?, content_hash = ?, section_digests = ?, pdf_full = ?, pdf_full_hash = ?, pdf_summary = ?, pdf_summary_hash = ?, scheme_version = ?, updated_at = ? WHERE id = ?', [
                    $publicId, $issuedDb, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $hash, json_encode($digests), $pdfFull, hash('sha256', $pdfFull), $pdfSummary, hash('sha256', $pdfSummary), ORIDE_SCHEME_VERSION, now(), $vid,
                ]);
                q('INSERT INTO acceptances (version_id, user_id, legal_text_id, statement_hash, accepted_at, ip_fingerprint) VALUES (?, ?, ?, ?, ?, ?)', [$vid, $uid, $statement['id'], $statement['content_hash'], $issuedDb, fingerprint(client_ip(), 'ip')]);
                if ($f['v']['supersedes_id']) {
                    q('UPDATE declaration_versions SET status = "SUPERSEDED", updated_at = ? WHERE id = ? AND status = "ISSUED"', [now(), $f['v']['supersedes_id']]);
                }
                audit('declaration.issue', 'declaration_version', (string) $vid, $uid, ['public_id' => $publicId, 'content_hash' => $hash]);
                return $publicId;
            });
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'uq_version_public')) continue; // colisión (improbable) del identificador
            throw $e;
        }
    }
    throw new DomainError('No se pudo generar un identificador único. Inténtalo de nuevo.');
}

// ---------------------------------------------------------------------------
// Publicación y revocación
// ---------------------------------------------------------------------------

function current_publication(int $vid): ?array
{
    return q1('SELECT * FROM publications WHERE version_id = ? ORDER BY id DESC LIMIT 1', [$vid]);
}

/** La declaración completa implica obra, código y resumen; las evidencias exigen consentimiento. */
function publish_version(int $uid, int $vid, array $flags): void
{
    $v = owned_version($uid, $vid);
    if (!$v) throw new DomainError('Declaración no encontrada.');
    if ($v['status'] === 'DRAFT') throw new DomainError('Solo se pueden publicar declaraciones emitidas.');
    if (!empty($flags['evidence']) && empty($flags['consent'])) throw new DomainError('Publicar las evidencias requiere tu consentimiento específico.');
    $full = !empty($flags['full']);
    $n = ['name' => !empty($flags['name']), 'work' => !empty($flags['work']) || $full, 'code' => !empty($flags['code']) || $full, 'summary' => !empty($flags['summary']) || $full, 'full' => $full, 'evidence' => !empty($flags['evidence'])];
    if (!$n['code'] && !$n['summary'] && !$n['work']) throw new DomainError('Selecciona al menos qué información quieres publicar.');
    tx(function () use ($v, $uid, $n) {
        q('INSERT INTO publications (version_id, actor_id, action, show_name, show_work, show_code, show_summary, show_full, show_evidence, evidence_consent_at, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
            $v['id'], $uid, $v['visibility'] === 'PUBLIC' ? 'UPDATE' : 'PUBLISH', (int) $n['name'], (int) $n['work'], (int) $n['code'], (int) $n['summary'], (int) $n['full'], (int) $n['evidence'], $n['evidence'] ? now() : null, now(),
        ]);
        q('UPDATE declaration_versions SET visibility = "PUBLIC", updated_at = ? WHERE id = ?', [now(), $v['id']]);
        audit('declaration.publish', 'declaration_version', (string) $v['id'], $uid, $n);
    });
}

function withdraw_publication(int $vid, int $actorId, ?int $ownerId, string $reason = ''): void
{
    $v = $ownerId === null ? q1('SELECT * FROM declaration_versions WHERE id = ?', [$vid]) : owned_version($ownerId, $vid);
    if (!$v || $v['visibility'] !== 'PUBLIC') throw new DomainError('La declaración no está publicada.');
    tx(function () use ($v, $actorId, $ownerId, $reason) {
        q('INSERT INTO publications (version_id, actor_id, action, created_at) VALUES (?, ?, "WITHDRAW", ?)', [$v['id'], $actorId, now()]);
        q('UPDATE declaration_versions SET visibility = "WITHDRAWN", updated_at = ? WHERE id = ?', [now(), $v['id']]);
        audit($ownerId === null ? 'admin.declaration_withdraw' : 'declaration.withdraw', 'declaration_version', (string) $v['id'], $actorId, $reason ? ['reason' => $reason] : []);
    });
}

/** Revocación: deja de estar vigente, pero se conserva y sigue siendo verificable. */
function revoke_version(int $uid, int $vid, string $reason): void
{
    $v = owned_version($uid, $vid);
    if (!$v || !in_array($v['status'], ['ISSUED', 'SUPERSEDED'], true)) throw new DomainError('Solo se pueden revocar declaraciones emitidas.');
    if (mb_strlen(trim($reason)) < 5) throw new DomainError('Indica brevemente el motivo de la revocación.');
    q('UPDATE declaration_versions SET status = "REVOKED", revoked_at = ?, revocation_reason = ?, updated_at = ? WHERE id = ?', [now(), mb_substr(trim($reason), 0, 1000), now(), $vid]);
    audit('declaration.revoke', 'declaration_version', (string) $vid, $uid);
}

// ---------------------------------------------------------------------------
// Consulta pública (solo lo publicado; misma respuesta si no existe o es privada)
// ---------------------------------------------------------------------------

function visible_sections(array $p): array
{
    $out = ['meta'];
    if ($p['show_work'] || $p['show_full']) $out[] = 'work';
    if ($p['show_name']) $out[] = 'declarant';
    if ($p['show_code'] || $p['show_full']) array_push($out, 'scope', 'classification');
    if ($p['show_summary'] || $p['show_full']) $out[] = 'summary';
    if ($p['show_full']) array_push($out, 'process', 'article50', 'statement');
    if ($p['show_evidence']) $out[] = 'evidence';
    return $out;
}

function public_declaration(string $raw): ?array
{
    $id = oride_normalize_public_id($raw);
    if (!$id) return null;
    $v = q1('SELECT id, declaration_id, version_number, public_id, status, visibility, issued_at, revoked_at, revocation_reason, snapshot, content_hash, section_digests, pdf_full_hash, pdf_summary_hash, supersedes_id FROM declaration_versions WHERE public_id = ?', [$id]);
    if (!$v || $v['visibility'] !== 'PUBLIC' || $v['status'] === 'DRAFT') return null;
    $pub = current_publication((int) $v['id']);
    if (!$pub || $pub['action'] === 'WITHDRAW') return null;
    $visible = visible_sections($pub);
    $snapshot = json_decode($v['snapshot']);
    $view = oride_disclose($snapshot, json_decode($v['section_digests'], true), $visible);
    $succ = q1('SELECT public_id, visibility, status FROM declaration_versions WHERE supersedes_id = ? AND status <> "DRAFT"', [$v['id']]);
    $prev = $v['supersedes_id'] ? q1('SELECT public_id, visibility FROM declaration_versions WHERE id = ?', [$v['supersedes_id']]) : null;
    return [
        'public_id' => $id, 'status' => $v['status'], 'issued_at' => $v['issued_at'], 'revoked_at' => $v['revoked_at'], 'revocation_reason' => $v['revocation_reason'],
        'content_hash' => $v['content_hash'], 'pdf_full_hash' => $v['pdf_full_hash'], 'pdf_summary_hash' => $v['pdf_summary_hash'],
        'view' => $view, 'visible' => $visible, 'publication' => $pub,
        'superseded_by' => $succ ? ['public_id' => $succ['public_id'], 'is_public' => $succ['visibility'] === 'PUBLIC'] : null,
        'supersedes' => $prev ? ['public_id' => $prev['public_id'], 'is_public' => $prev['visibility'] === 'PUBLIC'] : null,
    ];
}

/** Comprueba un fichero (PDF o JSON) contra el registro; no revela nada más. */
function check_file(string $raw, string $bytes): array
{
    $id = oride_normalize_public_id($raw);
    $none = ['kind' => 'none', 'match' => false];
    if (!$id) return $none;
    $v = q1('SELECT status, content_hash, pdf_full_hash, pdf_summary_hash FROM declaration_versions WHERE public_id = ? AND status <> "DRAFT"', [$id]);
    if (!$v) return $none;
    $h = hash('sha256', $bytes);
    if (hash_equals($v['pdf_full_hash'], $h)) return ['kind' => 'pdf-full', 'match' => true, 'status' => $v['status']];
    if (hash_equals($v['pdf_summary_hash'], $h)) return ['kind' => 'pdf-summary', 'match' => true, 'status' => $v['status']];
    $doc = json_decode($bytes);
    if (is_object($doc)) {
        $snap = $doc->snapshot ?? $doc->view ?? $doc;
        if (is_object($snap) && isset($snap->sections) && oride_verify_view($snap, $v['content_hash'])['ok']) return ['kind' => 'json', 'match' => true, 'status' => $v['status']];
    }
    return $none;
}

function list_public(string $q, int $page, int $per = 20): array
{
    $rows = q('SELECT id, public_id, status, issued_at, snapshot FROM declaration_versions WHERE visibility = "PUBLIC" AND status <> "DRAFT" ORDER BY issued_at DESC LIMIT 500')->fetchAll();
    $items = [];
    foreach ($rows as $r) {
        $pub = current_publication((int) $r['id']);
        if (!$pub || $pub['action'] === 'WITHDRAW') continue;
        $vis = visible_sections($pub);
        $s = json_decode($r['snapshot']);
        $get = fn($n) => in_array($n, $vis, true) ? $s->sections->$n->data : null;
        $w = $get('work');
        $d = $get('declarant');
        $c = $get('classification');
        $items[] = ['public_id' => $r['public_id'], 'status' => $r['status'], 'issued_at' => $r['issued_at'], 'title' => $w->title ?? null, 'category' => $w->category->label ?? null,
            'declarant' => $d->name ?? null, 'artistic' => $d->artisticName ?? null, 'code' => $c->code ?? null, 'notation' => $c->compositeNotation ?? null];
    }
    if ($q !== '') {
        $ql = mb_strtolower($q);
        $code = strtoupper(preg_replace('/^ORIDE\s+/i', '', $q));
        $items = array_values(array_filter($items, function ($i) use ($ql, $code) {
            foreach (['title', 'declarant', 'artistic', 'public_id'] as $k) if ($i[$k] && str_contains(mb_strtolower($i[$k]), $ql)) return true;
            return $i['code'] === $code || ($i['notation'] && str_contains($i['notation'], $code));
        }));
    }
    return ['total' => count($items), 'items' => array_slice($items, ($page - 1) * $per, $per)];
}
