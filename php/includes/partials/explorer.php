<?php
/** Explicador interactivo de un código (pestañas por segmento; funciona también sin JS). */
declare(strict_types=1);

function explorer(string $code): void
{
    $dims = oride_dimensions();
    $segs = str_split($code, 2);
    $id = 'x' . substr(md5($code . random_int(0, 99999)), 0, 6);
    echo '<div class="card" data-segments><div class="segments" role="tablist" aria-label="Segmentos del código ORIDE"><span class="logo-word small-word">ORIDE</span>';
    foreach ($segs as $i => $s) {
        echo '<button type="button" role="tab" id="' . $id . '-t' . $i . '" aria-controls="' . $id . '-p' . $i . '" aria-selected="' . ($i === 0 ? 'true' : 'false') . '" tabindex="' . ($i === 0 ? '0' : '-1') . '">' . h($s) . '</button>';
    }
    echo '</div>';
    foreach ($segs as $i => $s) {
        $d = $dims[$s[0]];
        echo '<div class="seg-panel" role="tabpanel" id="' . $id . '-p' . $i . '" aria-labelledby="' . $id . '-t' . $i . '"' . ($i === 0 ? '' : ' hidden') . '>'
            . '<p class="eyebrow">Dimensión ' . h($s[0]) . ' · ' . h($d['name']) . '</p><p><strong>' . h($s) . ' — ' . h(oride_label($s)) . '</strong></p><p>' . h(oride_description($s)) . '</p><p class="meta">' . h($d['not']) . '</p></div>';
    }
    echo '</div>';
}
