<?php
/**
 * Índice Global ORIDE — motor de cálculo (independiente de la interfaz).
 *
 *   IG = G + (100 − G) · P
 *   G  = 100 · Σ w_j · q_j · f(H_j)        generación efectiva de contenido con IA (0–100)
 *   P  = min(1, Σ w_i · f(H_i) · S_i)      influencia de la IA en actividades auxiliares (0–1)
 *
 * H es el primer dígito de la actividad (grado de uso) y S el alcance (segundo dígito).
 * Cuando la información son intervalos (tramos de alcance o de proporción), el resultado
 * es un intervalo: no se convierte un tramo en un número exacto. No hay redondeos intermedios.
 *
 * Toda la metodología (factores, conversiones y pesos) está en IG_VERSIONS, versionada:
 * cambiar un valor exige una versión nueva, y los cálculos guardan la versión con que se hicieron.
 */
declare(strict_types=1);

require_once __DIR__ . '/taxonomy.php';

const IG_CURRENT = 'ORIDE-IG-1.0-experimental';

/** Versión de la taxonomía (códigos y actividades), independiente de la del algoritmo. */
const ORIDE_TAXONOMY = 'ORIDE Code 3.0';

/** Peso de un tipo de actividad. 'gen' = genera contenido de la obra final (cuenta en G); 'aux' = auxiliar (cuenta en P). */
const IG_VERSIONS = [
    'ORIDE-IG-1.0-experimental' => [
        'published' => '2026-11-07',
        // Factor de IA según el primer dígito (grado de uso). Convención metodológica, no un porcentaje medido.
        //   0 no se usó IA · 1 se consultaron los resultados y se descartaron o solo sirvieron de referencia
        //   2 creación propia / reelaboración creativa · 3 transformación sustancial · 4 modificación parcial · 5 sin cambios
        'factors' => [0 => 0.0, 1 => 0.0, 2 => 0.20, 3 => 0.50, 4 => 0.85, 5 => 1.00],
        // Conversión del segundo dígito (alcance) a una fracción de la obra. En ORIDE Code 2.0 el alcance
        // son tramos de porcentaje (0–4), así que se usa el intervalo del tramo en lugar de s/5.
        'scope' => [0 => [0.0, 0.05], 1 => [0.05, 0.25], 2 => [0.25, 0.50], 3 => [0.50, 0.75], 4 => [0.75, 1.0]],
        // Pregunta adicional: proporción del contenido final que procede de material generado con IA.
        'q_bands' => [0 => [0.0, 0.0], 1 => [0.0, 0.10], 2 => [0.10, 0.25], 3 => [0.25, 0.50], 4 => [0.50, 0.75], 5 => [0.75, 0.99], 6 => [1.0, 1.0]],
        // Pesos por disciplina. Los de literatura son los de la especificación (techo auxiliar 0,45 aquí,
        // porque ORIDE Code 2.0 no tiene «Traducción» como actividad de literatura). El resto son
        // provisionales, por analogía con literatura y con el mismo techo de 0,50; cada uno lleva su justificación.
        'weights' => [
            'LI' => ['basis' => 'spec', 'acts' => [
                'DOC' => ['aux', 0.05, 'Documentación (especificación)'],
                'IDE' => ['aux', 0.10, 'Generación de ideas (especificación)'],
                'EST' => ['aux', 0.15, 'Estructuración (especificación)'],
                'GEN' => ['gen', null, 'Produce el texto final'],
                'REV' => ['aux', 0.10, 'Revisión del contenido (especificación)'],
                'FOR' => ['aux', 0.05, 'Revisión formal (especificación)'],
            ]],
            'SC' => ['basis' => 'analogy', 'acts' => [
                'DOC' => ['aux', 0.05, 'Como documentación'],
                'HIP' => ['aux', 0.10, 'Como generación de ideas'],
                'MET' => ['aux', 0.10, 'Como estructuración (diseño del estudio)'],
                'DAT' => ['aux', 0.05, 'Preparación de datos: apoyo técnico'],
                'ANA' => ['aux', 0.05, 'Análisis: apoyo técnico'],
                'INT' => ['aux', 0.05, 'Interpretación: apoyo a ideas'],
                'RED' => ['gen', null, 'Produce el texto científico final'],
                'REV' => ['aux', 0.05, 'Como revisión del contenido'],
                'FOR' => ['aux', 0.05, 'Como revisión formal'],
            ]],
            'MU' => ['basis' => 'analogy', 'acts' => [
                'IDE' => ['aux', 0.10, 'Como generación de ideas'],
                'COM' => ['gen', null, 'Produce música incorporada a la obra'],
                'ARR' => ['gen', null, 'Produce arreglos incorporados a la obra'],
                'GEN' => ['gen', null, 'Produce sonido incorporado a la obra'],
                'INT' => ['gen', null, 'Produce interpretaciones incorporadas a la obra'],
                'MIX' => ['aux', 0.10, 'Como revisión del contenido (mezcla)'],
                'MAS' => ['aux', 0.05, 'Como revisión formal (masterización)'],
                'RES' => ['aux', 0.05, 'Como revisión formal (restauración)'],
            ]],
            'PH' => ['basis' => 'analogy', 'acts' => [
                'PLA' => ['aux', 0.10, 'Como estructuración (planificación de la toma)'],
                'CAP' => ['aux', 0.10, 'Apoyo técnico a la captura'],
                'REV' => ['aux', 0.05, 'Como revisión formal (revelado)'],
                'RET' => ['aux', 0.10, 'Como revisión del contenido (retoque)'],
                'MOD' => ['gen', null, 'Añade o sustituye elementos de la imagen final'],
                'GEN' => ['gen', null, 'Genera la imagen o partes de ella'],
                'REC' => ['gen', null, 'Genera regiones o detalle de la imagen final'],
            ]],
            'IL' => ['basis' => 'analogy', 'acts' => [
                'REF' => ['aux', 0.05, 'Como documentación'],
                'IDE' => ['aux', 0.10, 'Como generación de ideas'],
                'COM' => ['aux', 0.15, 'Como estructuración (composición)'],
                'GEN' => ['gen', null, 'Genera la imagen o partes de ella'],
                'TRA' => ['gen', null, 'Transforma la imagen final con IA'],
                'RET' => ['aux', 0.05, 'Como revisión formal (retoque)'],
                'AMP' => ['gen', null, 'Genera regiones de la imagen final'],
                'VAR' => ['aux', 0.05, 'Adaptación de versiones'],
                'REV' => ['aux', 0.05, 'Como revisión del contenido'],
                'FIN' => ['aux', 0.05, 'Como revisión formal (preparación final)'],
            ]],
            'DS' => ['basis' => 'analogy', 'acts' => [
                'IDE' => ['aux', 0.10, 'Como generación de ideas'],
                'VAR' => ['aux', 0.10, 'Como generación de ideas (alternativas)'],
                'COM' => ['aux', 0.15, 'Como estructuración (composición)'],
                'GEN' => ['gen', null, 'Genera elementos del diseño final'],
                'MOD' => ['aux', 0.05, 'Edición de elementos existentes'],
                'ADA' => ['aux', 0.05, 'Adaptación de formatos'],
                'FIN' => ['aux', 0.05, 'Como revisión formal (producción final)'],
            ]],
            'AU' => ['basis' => 'analogy', 'acts' => [
                'GUI' => ['aux', 0.15, 'Como estructuración (guion)'],
                'STO' => ['aux', 0.10, 'Como generación de ideas (storyboard)'],
                'GEN' => ['gen', null, 'Genera imágenes en movimiento de la obra final'],
                'VOZ' => ['gen', null, 'Genera voces o sonido de la obra final'],
                'MON' => ['aux', 0.10, 'Como revisión del contenido (montaje)'],
                'EFX' => ['gen', null, 'Genera efectos de la obra final'],
                'DOB' => ['gen', null, 'Genera voces dobladas o subtítulos'],
                'POS' => ['aux', 0.05, 'Como revisión formal (posproducción)'],
            ]],
            'SW' => ['basis' => 'analogy', 'acts' => [
                'REQ' => ['aux', 0.05, 'Como documentación (requisitos)'],
                'ARQ' => ['aux', 0.15, 'Como estructuración (arquitectura)'],
                'COD' => ['gen', null, 'Genera el código final'],
                'REF' => ['aux', 0.10, 'Como revisión del contenido (refactorización)'],
                'DEP' => ['aux', 0.10, 'Como revisión del contenido (depuración)'],
                'PRU' => ['aux', 0.05, 'Validación'],
                'DOC' => ['aux', 0.05, 'Como revisión formal (documentación)'],
            ]],
            'TR' => ['basis' => 'analogy', 'acts' => [
                'DOC' => ['aux', 0.05, 'Como documentación'],
                'INT' => ['aux', 0.10, 'Como generación de ideas (interpretación)'],
                'GEN' => ['gen', null, 'Genera la traducción final'],
                'ADA' => ['aux', 0.10, 'Como revisión del contenido (adaptación)'],
                'REV' => ['aux', 0.10, 'Como revisión del contenido'],
                'STY' => ['aux', 0.05, 'Como revisión formal (estilo)'],
                'LOC' => ['aux', 0.05, 'Adaptación de formatos'],
                'VAL' => ['aux', 0.05, 'Validación'],
            ]],
        ],
    ],
];

/** Traducción de las justificaciones de los pesos (indice.php). */
const IG_WHY_EN = [
    'Documentación (especificación)' => 'Research (methodology)',
    'Generación de ideas (especificación)' => 'Idea generation (methodology)',
    'Estructuración (especificación)' => 'Structuring (methodology)',
    'Produce el texto final' => 'Produces the final text',
    'Revisión del contenido (especificación)' => 'Content review (methodology)',
    'Revisión formal (especificación)' => 'Form review (methodology)',
    'Como documentación' => 'Like research',
    'Como generación de ideas' => 'Like idea generation',
    'Como estructuración (diseño del estudio)' => 'Like structuring (study design)',
    'Preparación de datos: apoyo técnico' => 'Data preparation: technical support',
    'Análisis: apoyo técnico' => 'Analysis: technical support',
    'Interpretación: apoyo a ideas' => 'Interpretation: support for ideas',
    'Produce el texto científico final' => 'Produces the final scientific text',
    'Como revisión del contenido' => 'Like content review',
    'Como revisión formal' => 'Like form review',
    'Produce música incorporada a la obra' => 'Produces music included in the work',
    'Produce arreglos incorporados a la obra' => 'Produces arrangements included in the work',
    'Produce sonido incorporado a la obra' => 'Produces sound included in the work',
    'Produce interpretaciones incorporadas a la obra' => 'Produces performances included in the work',
    'Como revisión del contenido (mezcla)' => 'Like content review (mixing)',
    'Como revisión formal (masterización)' => 'Like form review (mastering)',
    'Como revisión formal (restauración)' => 'Like form review (restoration)',
    'Como estructuración (planificación de la toma)' => 'Like structuring (shot planning)',
    'Apoyo técnico a la captura' => 'Technical support for capture',
    'Como revisión formal (revelado)' => 'Like form review (development)',
    'Como revisión del contenido (retoque)' => 'Like content review (retouching)',
    'Añade o sustituye elementos de la imagen final' => 'Adds or replaces elements of the final image',
    'Genera la imagen o partes de ella' => 'Generates the image or parts of it',
    'Genera regiones o detalle de la imagen final' => 'Generates regions or detail of the final image',
    'Como estructuración (composición)' => 'Like structuring (composition)',
    'Transforma la imagen final con IA' => 'Transforms the final image with AI',
    'Como revisión formal (retoque)' => 'Like form review (retouching)',
    'Genera regiones de la imagen final' => 'Generates regions of the final image',
    'Adaptación de versiones' => 'Adapting versions',
    'Como revisión formal (preparación final)' => 'Like form review (final preparation)',
    'Como generación de ideas (alternativas)' => 'Like idea generation (alternatives)',
    'Genera elementos del diseño final' => 'Generates elements of the final design',
    'Edición de elementos existentes' => 'Editing existing elements',
    'Adaptación de formatos' => 'Format adaptation',
    'Como revisión formal (producción final)' => 'Like form review (final production)',
    'Como estructuración (guion)' => 'Like structuring (script)',
    'Como generación de ideas (storyboard)' => 'Like idea generation (storyboard)',
    'Genera imágenes en movimiento de la obra final' => 'Generates moving images of the final work',
    'Genera voces o sonido de la obra final' => 'Generates voices or sound of the final work',
    'Como revisión del contenido (montaje)' => 'Like content review (editing)',
    'Genera efectos de la obra final' => 'Generates effects of the final work',
    'Genera voces dobladas o subtítulos' => 'Generates dubbed voices or subtitles',
    'Como revisión formal (posproducción)' => 'Like form review (post-production)',
    'Como documentación (requisitos)' => 'Like research (requirements)',
    'Como estructuración (arquitectura)' => 'Like structuring (architecture)',
    'Genera el código final' => 'Generates the final code',
    'Como revisión del contenido (refactorización)' => 'Like content review (refactoring)',
    'Como revisión del contenido (depuración)' => 'Like content review (debugging)',
    'Validación' => 'Validation',
    'Como revisión formal (documentación)' => 'Like form review (documentation)',
    'Como generación de ideas (interpretación)' => 'Like idea generation (interpretation)',
    'Genera la traducción final' => 'Generates the final translation',
    'Como revisión del contenido (adaptación)' => 'Like content review (adaptation)',
    'Como revisión formal (estilo)' => 'Like form review (style)',
];

function ig_why(string $why): string
{
    return en() ? (IG_WHY_EN[$why] ?? $why) : $why;
}

/** Configuración de una versión del algoritmo, o null si no existe. */
function ig_config(?string $version = null): ?array
{
    return IG_VERSIONS[$version ?? IG_CURRENT] ?? null;
}

/** IG = G + (100 − G) · P. G en [0, 100], P en [0, 1]. */
function ig_combine(float $G, float $P): float
{
    if (is_nan($G) || is_nan($P) || $G < 0 || $G > 100 || $P < 0 || $P > 1) throw new InvalidArgumentException('G debe estar entre 0 y 100 y P entre 0 y 1.');
    return min(100.0, $G + (100.0 - $G) * $P);
}

/** Lo mismo con intervalos: la fórmula crece con G y con P, así que basta con los extremos. */
function ig_combine_range(array $G, array $P): array
{
    return [ig_combine($G[0], $P[0]), ig_combine($G[1], $P[1])];
}

/**
 * G para una obra con uno o varios componentes.
 * @param array<int,array{w:float, q:array{0:float,1:float}, f:array{0:float,1:float}}> $components
 *   w: proporción del componente en la obra (deben sumar 1); q: proporción del componente generada con IA; f: factor de IA.
 * @return array{0:float,1:float}
 */
function ig_g(array $components): array
{
    if (!$components) return [0.0, 0.0];
    $sumW = array_sum(array_column($components, 'w'));
    if (abs($sumW - 1.0) > 1e-9) throw new InvalidArgumentException('Los pesos de los componentes deben sumar 1.');
    $lo = $hi = 0.0;
    foreach ($components as $c) {
        foreach ([$c['q'][0], $c['q'][1], $c['f'][0], $c['f'][1], $c['w']] as $v) {
            if ($v < 0 || $v > 1) throw new InvalidArgumentException('w, q y f deben estar entre 0 y 1.');
        }
        $lo += $c['w'] * $c['q'][0] * $c['f'][0];
        $hi += $c['w'] * $c['q'][1] * $c['f'][1];
    }
    return [100.0 * $lo, 100.0 * $hi];
}

/**
 * P a partir de las actividades auxiliares. Cada actividad cuenta una sola vez (la última declaración).
 * @param array<string,array{w:float, f:float, S:array{0:float,1:float}}> $aux  clave = actividad
 * @return array{0:float,1:float}
 */
function ig_p(array $aux): array
{
    $lo = $hi = 0.0;
    foreach ($aux as $a) {
        $lo += $a['w'] * $a['f'] * $a['S'][0];
        $hi += $a['w'] * $a['f'] * $a['S'][1];
    }
    return [min(1.0, $lo), min(1.0, $hi)];
}

/**
 * Índice a partir de un código calculado (oride_compute) y de la pregunta adicional.
 * @param array $r      resultado de oride_compute()
 * @param array $extra  ['q' => tramo 0–6 o null, 'qp' => porcentaje exacto 0–100 o null, 'version' => …]
 * @return array{status:string, reason:?string, version:string, taxonomy:?string, date:string,
 *               G:?array, P:?array, IG:?array, q:?array, q_source:?string, needs_q:bool, rows:array}
 *   status: ok | not_calculable | na (código incompleto)
 */
function ig_from_result(array $r, array $extra = []): array
{
    $version = $extra['version'] ?? IG_CURRENT;
    $cfg = ig_config($version);
    $out = ['status' => 'not_calculable', 'reason' => null, 'version' => $version, 'taxonomy' => ORIDE_TAXONOMY, 'date' => gmdate('Y-m-d'),
        'G' => null, 'P' => null, 'IG' => null, 'q' => null, 'q_source' => null, 'needs_q' => false, 'rows' => []];
    if (!$cfg) return ['reason' => 'version'] + $out;
    if (($r['status'] ?? '') !== 'complete') return ['status' => 'na'] + $out;
    if ($r['ia'] === 0) return ['status' => 'ok', 'G' => [0.0, 0.0], 'P' => [0.0, 0.0], 'IG' => [0.0, 0.0]] + $out;
    if (!$r['acts']) return ['reason' => 'no_acts'] + $out;
    if ($r['all_zero']) return ['reason' => 'all_zero'] + $out;
    $weights = $cfg['weights'][$r['type']]['acts'] ?? null;
    if (!$weights) return ['reason' => 'config'] + $out;

    $gen = $aux = $rows = [];
    foreach ($r['acts'] as [$act, $level, $scope]) {
        if (!isset($weights[$act]) || !isset($cfg['factors'][$level])) return ['reason' => 'config'] + $out;
        [$role, $w] = $weights[$act];
        $f = $cfg['factors'][$level];
        $S = $scope === null ? [0.0, 0.0] : $cfg['scope'][$scope];
        $row = ['act' => $act, 'role' => $role, 'level' => $level, 'scope' => $scope, 'factor' => $f, 'S' => $S, 'weight' => $w, 'contrib' => null];
        if ($role === 'gen') {
            $gen[$act] = ['f' => $f, 'S' => $S, 'scope' => $scope];
        } else {
            if ($w === null) return ['reason' => 'config'] + $out;
            $aux[$act] = ['w' => $w, 'f' => $f, 'S' => $S];
            $row['contrib'] = [$w * $f * $S[0], $w * $f * $S[1]];
        }
        $rows[] = $row;
    }

    // G: la obra es un único componente; si hay varias actividades generativas, el factor es el
    // intervalo entre la que menos y la que más incorpora (no se inventan pesos entre ellas).
    $G = [0.0, 0.0];
    $genUsed = array_filter($gen, fn($g) => $g['f'] > 0);
    if ($genUsed) {
        $out['needs_q'] = true;
        $fs = array_column($genUsed, 'f');
        $f = [min($fs), max($fs)];
        $qp = $extra['qp'] ?? null;
        $qb = $extra['q'] ?? null;
        if ($qp !== null && $qp !== '' && is_numeric($qp) && (float) $qp >= 0 && (float) $qp <= 100) {
            $q = [(float) $qp / 100, (float) $qp / 100];
            $out['q_source'] = 'exact';
        } elseif ($qb !== null && $qb !== '' && isset($cfg['q_bands'][(int) $qb]) && preg_match('/^\d$/', (string) $qb)) {
            $q = $cfg['q_bands'][(int) $qb];
            $out['q_source'] = 'question';
        } else {
            // Sin respuesta a la pregunta adicional: el alcance declarado de las actividades generativas.
            $q = [min(array_map(fn($g) => $g['S'][0], $genUsed)), max(array_map(fn($g) => $g['S'][1], $genUsed))];
            $out['q_source'] = 'scope';
        }
        $out['q'] = $q;
        $G = ig_g([['w' => 1.0, 'q' => $q, 'f' => $f]]);
    }
    $P = ig_p($aux);
    return ['status' => 'ok', 'G' => $G, 'P' => $P, 'IG' => ig_combine_range($G, $P), 'rows' => $rows] + $out;
}

/** Registro completo del cálculo, para descargar y conservar (JSON). */
function ig_record(array $r, array $ig, array $extra): array
{
    $cfg = ig_config($ig['version']);
    return [
        'code' => $r['code'], 'lines' => $r['lines'], 'type' => $r['type'],
        'algorithm' => $ig['version'], 'taxonomy' => $ig['taxonomy'], 'date' => $ig['date'],
        'status' => $ig['status'], 'reason' => $ig['reason'],
        'inputs' => ['q_band' => $extra['q'] ?? null, 'q_exact_percent' => $extra['qp'] ?? null, 'q_source' => $ig['q_source']],
        'result' => ['IG' => $ig['IG'], 'G' => $ig['G'], 'P' => $ig['P'], 'q' => $ig['q']],
        'activities' => $ig['rows'],
        'parameters' => ['factors' => $cfg['factors'] ?? null, 'scope' => $cfg['scope'] ?? null, 'q_bands' => $cfg['q_bands'] ?? null,
            'weights' => $cfg['weights'][$r['type']] ?? null],
        'notice' => 'Estimación metodológica. No es un porcentaje certificado de autoría de IA ni determina la originalidad o titularidad de la obra.',
    ];
}
