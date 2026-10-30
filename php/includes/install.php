<?php
/** Utilidades de instalación: ejecutar SQL (con DELIMITER) y cargar datos iniciales. */
declare(strict_types=1);

/** Divide un fichero SQL en sentencias, respetando las directivas DELIMITER (formato phpMyAdmin). */
function sql_statements(string $sql): array
{
    $out = [];
    $delim = ';';
    $buf = '';
    foreach (preg_split('/\R/', $sql) as $line) {
        if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $m)) {
            $delim = $m[1];
            continue;
        }
        if ($buf === '' && (trim($line) === '' || str_starts_with(ltrim($line), '--'))) continue;
        $buf .= $line . "\n";
        if (str_ends_with(rtrim($line), $delim)) {
            $stmt = trim(substr(rtrim($buf), 0, -strlen($delim)));
            if ($stmt !== '') $out[] = $stmt;
            $buf = '';
        }
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return $out;
}

function run_sql_file(PDO $pdo, string $file): void
{
    foreach (sql_statements((string) file_get_contents($file)) as $s) $pdo->exec($s);
}

/** Datos iniciales idempotentes. */
function oride_seed(PDO $pdo): void
{
    $d = require __DIR__ . '/seed-data.php';
    $now = gmdate('Y-m-d H:i:s');
    $st = $pdo->prepare('INSERT IGNORE INTO work_categories (ckey, label_es, label_en, grp, phase_hints, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($d['categories'] as $i => $c) $st->execute([$c['key'], $c['labelEs'], $c['labelEn'], $c['group'], json_encode((object) $c['phaseHints'], JSON_UNESCAPED_UNICODE), $i]);
    $titles = ['declaration_statement' => 'Declaración responsable de transparencia creativa', 'privacy' => 'Política de privacidad', 'terms' => 'Condiciones de uso', 'legal_notice' => 'Aviso legal'];
    $st = $pdo->prepare('INSERT IGNORE INTO legal_texts (tkey, version, title, content, content_hash, active, pending_review, published_at, created_at) VALUES (?, "1.0", ?, ?, ?, 1, 1, ?, ?)');
    foreach ($d['texts'] as $k => $content) $st->execute([$k, $titles[$k], $content, hash('sha256', $content), $now, $now]);
    $st = $pdo->prepare('INSERT IGNORE INTO legal_references (rkey, title, citation, url, summary, verification_note) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($d['refs'] as $r) $st->execute([$r['key'], $r['title'], $r['citation'], $r['url'], $r['summary'], $r['verificationNote'] ?? null]);
    $spec = json_encode(['dimensions' => oride_dimensions(), 'rules' => ORIDE_RULES, 'phases' => ORIDE_PHASES, 'format' => 'A#B#C#D#'], JSON_UNESCAPED_UNICODE);
    $pdo->prepare('INSERT IGNORE INTO schemes (version, name, status, spec, published_at) VALUES (?, ?, "PUBLISHED", ?, ?)')->execute([ORIDE_SCHEME_VERSION, 'ORIDE Code v' . ORIDE_SCHEME_VERSION, $spec, $now]);
}

function config_php(array $c): string
{
    return "<?php\n// Generado por instalar.php el " . gmdate('Y-m-d H:i') . " UTC. No lo publiques.\nreturn " . var_export($c, true) . ";\n";
}
