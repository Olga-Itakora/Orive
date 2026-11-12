<?php
/**
 * Categorías del Índice Global ORIDE (ORIDE-IG-CAT-1.0).
 *
 * Capa adicional sobre el resultado del algoritmo (ORIDE-IG-1.0-experimental), que no cambia:
 * el índice se sigue calculando como intervalo [mín, máx] y se clasifica por su punto medio,
 * sin redondear, en una única categoría A–F. Todos los umbrales están aquí.
 *
 * Las letras describen tramos de utilización estimada de IA. No son calificaciones de calidad,
 * mérito, originalidad ni autoría.
 */
declare(strict_types=1);

const IG_CAT_VERSION = 'ORIDE-IG-CAT-1.0';

/** Categoría => [mínimo excluido (salvo A, incluido), máximo incluido], en %. */
const IG_CATS = [
    'A' => [0.0, 10.0],
    'B' => [10.0, 25.0],
    'C' => [25.0, 50.0],
    'D' => [50.0, 75.0],
    'E' => [75.0, 90.0],
    'F' => [90.0, 100.0],
];

const IG_CAT_LABELS = [
    'es' => ['A' => 'Muy bajo', 'B' => 'Bajo', 'C' => 'Moderado', 'D' => 'Alto', 'E' => 'Muy alto', 'F' => 'Extremo'],
    'en' => ['A' => 'Very low', 'B' => 'Low', 'C' => 'Moderate', 'D' => 'High', 'E' => 'Very high', 'F' => 'Extreme'],
];

/** Punto medio de un intervalo [mín, máx] de 0 a 100. Lanza una excepción si el intervalo no es válido. */
function ig_midpoint(?float $min, ?float $max): float
{
    if ($min === null || $max === null) throw new InvalidArgumentException('Intervalo incompleto.');
    if (is_nan($min) || is_nan($max) || $min < 0 || $max > 100) throw new InvalidArgumentException('El intervalo debe estar entre 0 y 100.');
    if ($min > $max) throw new InvalidArgumentException('Intervalo invertido.');
    return ($min + $max) / 2;
}

/** Categoría de un valor de 0 a 100: A si 0 ≤ v ≤ 10; B si 10 < v ≤ 25; … ; F si 90 < v ≤ 100. */
function ig_category(float $value): string
{
    if (is_nan($value) || $value < 0 || $value > 100) throw new InvalidArgumentException('El valor debe estar entre 0 y 100.');
    foreach (IG_CATS as $cat => [, $hi]) {
        if ($value <= $hi) return $cat;
    }
    return 'F';
}

/** Categoría de un intervalo, por su punto medio (sin redondear). */
function ig_classify_range(?float $min, ?float $max): string
{
    return ig_category(ig_midpoint($min, $max));
}

/** «B — Bajo» / «B — Low». La letra es la misma en todos los idiomas. */
function ig_cat_label(string $cat, ?string $lang = null): string
{
    return $cat . ' — ' . (IG_CAT_LABELS[$lang ?? lang()][$cat] ?? '');
}

function ig_valid_cat(string $cat): bool
{
    return isset(IG_CATS[$cat]);
}

/** Compara categorías (A < B < … < F): ok si la declarada no supera la máxima del requisito. */
function ig_compare_cats(string $required, string $declared): string
{
    if (!ig_valid_cat($required) || !ig_valid_cat($declared)) throw new InvalidArgumentException('Categoría no válida.');
    return strcmp($declared, $required) <= 0 ? 'ok' : 'fail';
}

/** Rango de una categoría para mostrar: «0–10 %», «más de 10 % hasta 25 %». */
function ig_cat_range_text(string $cat): string
{
    [$lo, $hi] = IG_CATS[$cat];
    return $cat === 'A' ? tr('de 0 % a 10 %', 'from 0% to 10%') : tr('más de ' . num($lo, 0) . ' % hasta ' . num($hi, 0) . ' %', 'over ' . num($lo, 0) . '% up to ' . num($hi, 0) . '%');
}
