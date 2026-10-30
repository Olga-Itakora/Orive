<?php
/**
 * Integridad: JSON canónico (subconjunto de RFC 8785), huellas SHA-256 y
 * divulgación selectiva por secciones con sal.
 *
 *   resumen_sección = SHA-256( canonical({section, salt, data}) )
 *   huella          = SHA-256( canonical({format, sections: {nombre: resumen}}) )
 *
 * Mismo algoritmo que assets/js/verificar.js (navegador) y que la versión Next.js.
 * Qué demuestra: que el contenido no ha cambiado desde su registro.
 * Qué NO demuestra: identidad, veracidad, originalidad, titularidad ni cumplimiento legal.
 */
declare(strict_types=1);

const ORIDE_SNAPSHOT_FORMAT = 'oride-declaration/1';
const ORIDE_HASH_ALGORITHM = 'SHA-256';
const ORIDE_CANONICALIZATION = 'RFC8785-subset(integers-only)';
const ORIDE_SECTIONS = ['meta', 'work', 'declarant', 'scope', 'classification', 'summary', 'process', 'article50', 'statement', 'evidence'];
const ORIDE_PUBLIC_ID_RE = '/^ORD-[0-9A-HJKMNP-TV-Z]{4}-[0-9A-HJKMNP-TV-Z]{4}-[0-9A-HJKMNP-TV-Z]{4}$/';

/** Serialización canónica. Los objetos deben ser stdClass o arrays asociativos; las listas, arrays. */
function oride_canonical(mixed $v): string
{
    if ($v === null) return 'null';
    if (is_bool($v)) return $v ? 'true' : 'false';
    if (is_int($v)) return (string) $v;
    if (is_float($v)) throw new InvalidArgumentException('JSON canónico: solo se admiten enteros.');
    if (is_string($v)) return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS | JSON_THROW_ON_ERROR);
    if (is_array($v) && array_is_list($v)) return '[' . implode(',', array_map('oride_canonical', $v)) . ']';
    if (is_array($v) || $v instanceof stdClass) {
        $a = (array) $v;
        $keys = array_map('strval', array_keys($a));
        sort($keys, SORT_STRING);
        $parts = [];
        foreach ($keys as $k) {
            $parts[] = json_encode($k, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS) . ':' . oride_canonical($a[$k]);
        }
        return '{' . implode(',', $parts) . '}';
    }
    throw new InvalidArgumentException('JSON canónico: tipo no admitido.');
}

function oride_section_digest(string $name, object $section): string
{
    return hash('sha256', oride_canonical((object) ['section' => $name, 'salt' => $section->salt, 'data' => $section->data]));
}

function oride_root_hash(array $digests): string
{
    return hash('sha256', oride_canonical((object) ['format' => ORIDE_SNAPSHOT_FORMAT, 'sections' => (object) $digests]));
}

/** @return array{0:string,1:array<string,string>} */
function oride_content_hash(object $snapshot): array
{
    $digests = [];
    foreach (ORIDE_SECTIONS as $n) $digests[$n] = oride_section_digest($n, $snapshot->sections->$n);
    return [oride_root_hash($digests), $digests];
}

function oride_disclose(object $snapshot, array $digests, array $visible): object
{
    $sections = new stdClass();
    foreach ($visible as $n) $sections->$n = $snapshot->sections->$n;
    return (object) ['format' => ORIDE_SNAPSHOT_FORMAT, 'sections' => $sections, 'digests' => (object) $digests];
}

function oride_verify_view(object $view, string $expected): array
{
    if (($view->format ?? null) !== ORIDE_SNAPSHOT_FORMAT) return ['ok' => false, 'computed' => '', 'mismatched' => []];
    $given = isset($view->digests) ? (array) $view->digests : null;
    $digests = [];
    $mismatched = [];
    foreach (ORIDE_SECTIONS as $n) {
        if (isset($view->sections->$n)) {
            $digests[$n] = oride_section_digest($n, $view->sections->$n);
            if ($given && ($given[$n] ?? null) !== $digests[$n]) $mismatched[] = $n;
        } elseif ($given && isset($given[$n])) {
            $digests[$n] = $given[$n];
        } else {
            return ['ok' => false, 'computed' => '', 'mismatched' => [$n]];
        }
    }
    $computed = oride_root_hash($digests);
    return ['ok' => hash_equals($expected, $computed) && !$mismatched, 'computed' => $computed, 'mismatched' => $mismatched];
}

function oride_salt(): string
{
    return bin2hex(random_bytes(16));
}

/** Identificador público: ORD-XXXX-XXXX-XXXX (60 bits aleatorios, base32 de Crockford). */
function oride_public_id(): string
{
    $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    $bytes = random_bytes(12);
    $out = '';
    for ($i = 0; $i < 12; $i++) $out .= $alphabet[ord($bytes[$i]) & 31];
    return 'ORD-' . substr($out, 0, 4) . '-' . substr($out, 4, 4) . '-' . substr($out, 8, 4);
}

function oride_normalize_public_id(string $input): ?string
{
    $raw = strtoupper(trim($input));
    $raw = preg_replace('/^ORD-?/', '', $raw);
    $raw = str_replace(['-', ' ', 'O', 'I', 'L'], ['', '', '0', '1', '1'], $raw);
    if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{12}$/', $raw)) return null;
    return 'ORD-' . substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);
}
