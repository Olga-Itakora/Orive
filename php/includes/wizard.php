<?php
/** Asistente de declaración: pasos, carga del borrador y cabecera. */
declare(strict_types=1);

const WIZ_STEPS = [
    'obra' => 'Obra',
    'declarante' => 'Declarante y alcance',
    'uso' => 'Uso de IA',
    'herramientas' => 'Herramientas',
    'usos' => 'Usos de IA',
    'evidencias' => 'Evidencias',
    'art50' => 'Art. 50',
    'revision' => 'Revisión y emisión',
];

function wiz_steps(?string $eff): array
{
    $all = array_keys(WIZ_STEPS);
    return $eff === 'YES' ? $all : array_values(array_diff($all, ['herramientas', 'usos']));
}

function wiz_next(string $step, ?string $eff): ?string
{
    $s = wiz_steps($eff);
    return $s[array_search($step, $s, true) + 1] ?? null;
}

function wiz_prev(string $step, ?string $eff): ?string
{
    $s = wiz_steps($eff);
    $i = array_search($step, $s, true);
    return $i > 0 ? $s[$i - 1] : null;
}

function wiz_url(int $vid, string $step): string
{
    return "autor/paso.php?id=$vid&p=$step";
}

/** Borrador del usuario; si ya está emitido, a su ficha. */
function wiz_load(array $user, int $vid): array
{
    $v = owned_version((int) $user['id'], $vid);
    if (!$v) not_found();
    if ($v['status'] !== 'DRAFT') redirect('autor/declaracion.php?id=' . $vid);
    return load_full($vid);
}

function wiz_header(array $f, string $step, string $title, ?string $intro = null): void
{
    $v = $f['v'];
    $eff = effective_usage($v);
    $steps = wiz_steps($eff);
    $idx = array_search($step, $steps, true);
    $vid = (int) $v['id'];
    echo '<div class="container">';
    echo '<div class="page-head-row"><p class="muted small">Declaración sobre <strong>' . h($f['work']['title']) . '</strong> · versión ' . (int) $v['version_number'] . ' · <span class="badge badge-warn">Borrador</span></p></div>';
    echo '<nav aria-label="Pasos de la declaración"><progress class="steps-progress" max="' . count($steps) . '" value="' . ($idx + 1) . '" aria-label="Paso ' . ($idx + 1) . ' de ' . count($steps) . '"></progress><ol class="steps">';
    foreach ($steps as $i => $s) {
        $cls = $i < $idx ? ' class="done"' : '';
        $cur = $i === $idx ? ' aria-current="step"' : '';
        echo '<li><a href="' . u(wiz_url($vid, $s)) . '"' . $cls . $cur . '><span class="mono">' . ($i + 1) . '</span>' . h(WIZ_STEPS[$s]) . '</a></li>';
    }
    echo '</ol></nav><h1>' . h($title) . '</h1>' . ($intro ? '<p class="muted prose">' . h($intro) . '</p>' : '') . '</div>';
}

function wiz_nav(int $vid, string $step, ?string $eff, string $stayLabel = 'Guardar borrador'): string
{
    $prev = wiz_prev($step, $eff);
    return '<div class="sticky-actions">' . ($prev ? '<a class="link-44 back" href="' . u(wiz_url($vid, $prev)) . '">← Paso anterior</a>' : '')
        . '<button class="btn btn-secondary" name="intent" value="stay">' . h($stayLabel) . '</button><button class="btn btn-primary" name="intent" value="next">Guardar y continuar</button></div>';
}
