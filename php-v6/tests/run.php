<?php
/** Pruebas de ORIDE v6. Uso: php tests/run.php */
declare(strict_types=1);
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SERVER['SCRIPT_NAME'] = '/tests/run.php';
require __DIR__ . '/../includes/app.php';
restore_exception_handler();

$ok = 0; $fail = 0;
function check(string $name, bool $cond): void
{
    global $ok, $fail;
    if ($cond) { $ok++; echo "  ✓ $name\n"; } else { $fail++; echo "  ✗ $name\n"; }
}
$code = fn(array $in) => oride_compute($in)['code'];
$lit = fn(array $acts, array $u, array $s = [], string $ev = '') => ['type' => 'LIT', 'ia' => 'si', 'acts' => $acts, 'u' => $u, 's' => $s, 'ev' => $ev];

echo "Taxonomía\n";
check('nueve tipos de obra con los códigos pedidos', array_keys(WORK_TYPES) === ['SCI', 'LIT', 'MUS', 'PHO', 'IL', 'DES', 'AUD', 'SW', 'TRA']);
check('70 actividades, como en la hoja Excel', array_sum(array_map(fn($t) => count($t['acts']), WORK_TYPES)) === 70);
check('códigos de actividad de tres letras', !array_filter(WORK_TYPES, fn($t) => array_filter(array_keys($t['acts']), fn($a) => !preg_match('/^[A-Z]{3}$/', $a))));
check('cada actividad con nombre y descripción en los dos idiomas', !array_filter(WORK_TYPES, fn($t) => array_filter($t['acts'], fn($a) => count(array_filter($a, fn($x) => trim($x) !== '')) !== 4)));
check('seis grados y cinco alcances en los dos idiomas', count(LEVELS['es']) === 6 && count(LEVELS['en']) === 6 && count(SCOPE['es']) === 5 && count(SCOPE['en']) === 5);
check('mismas claves de texto en los dos idiomas', array_keys(STR['es']) === array_keys(STR['en']));

echo "Cálculo del código\n";
check('sin tipo: vacío', oride_compute([])['status'] === 'empty');
check('tipo sin IA: pendiente', oride_compute(['type' => 'LIT'])['status'] === 'pending');
check('tipo + IA0 = código completo', $code(['type' => 'MUS', 'ia' => 'no']) === 'MUS-IA0');
check('tipo + IA1 = código completo', $code(['type' => 'SCI', 'ia' => 'si']) === 'SCI-IA1');
check('IA0 ignora actividades', $code(['type' => 'LIT', 'ia' => 'no', 'acts' => ['GEN'], 'u' => ['GEN' => '5']]) === 'LIT-IA0');
check('IA0 con evidencias', $code(['type' => 'LIT', 'ia' => 'no', 'ev' => 'si']) === 'LIT-IA0-V1' && $code(['type' => 'MUS', 'ia' => 'no', 'ev' => 'no']) === 'MUS-IA0-V0');
check('evidencias opcionales al final', $code($lit([], [], [], 'si')) === 'LIT-IA1-V1' && $code($lit([], [], [], 'no')) === 'LIT-IA1-V0');
check('grado 0 y 1: segunda cifra 0', $code($lit(['DOC', 'IDE'], ['DOC' => '1', 'IDE' => '0'])) === 'LIT-IA1-DOC10-IDE00');
check('grado 2 pide alcance', oride_compute($lit(['GEN'], ['GEN' => '2']))['status'] === 'pending');
check('grado 2 con alcance', $code($lit(['GEN'], ['GEN' => '2'], ['GEN' => '4'])) === 'LIT-IA1-GEN24');
check('alcance ignorado con grado 1', $code($lit(['GEN'], ['GEN' => '1'], ['GEN' => '3'])) === 'LIT-IA1-GEN10');
check('actividades en el orden del tipo', $code($lit(['FOR', 'DOC'], ['FOR' => '5', 'DOC' => '4'], ['FOR' => '0', 'DOC' => '4'], 'si')) === 'LIT-IA1-DOC44-FOR50-V1');
check('actividad de otro tipo se ignora', $code($lit(['COD'], ['COD' => '5'])) === 'LIT-IA1');
check('actividad sin grado: pendiente con su nombre', str_contains((string) oride_compute($lit(['EST'], []))['missing'], 'Estructuración'));
check('todas las actividades tienen dos cifras', (bool) preg_match('/^SW-IA1(-[A-Z]{3}\d\d)+-V1$/', $code(['type' => 'SW', 'ia' => 'si', 'acts' => ['REQ', 'COD', 'DOC'], 'u' => ['REQ' => '0', 'COD' => '3', 'DOC' => '1'], 's' => ['COD' => '2'], 'ev' => 'si'])));

echo "Lectura de códigos (oride.php)\n";
$x = fn(string $c) => oride_explain($c);
check('código completo', $x('LIT-IA1-DOC44-GEN24-V1')['result']['code'] === 'LIT-IA1-DOC44-GEN24-V1');
check('tipo + IA0', $x('TRA-IA0')['valid']);
check('tipo + IA1 sin más', $x('AUD-IA1')['valid']);
check('actividades en cualquier orden', $x('LIT-IA1-GEN24-DOC44')['result']['code'] === 'LIT-IA1-DOC44-GEN24');
check('admite «:», minúsculas y «ORIDE»', $x('oride lit:ia1:doc44')['result']['code'] === 'LIT-IA1-DOC44');
check('sin IA1 se entiende IA1 si hay actividades', $x('LIT:DOC44-GEN24-V')['result']['code'] === 'LIT-IA1-DOC44-GEN24-V1');
check('«V» solo equivale a V1', $x('LIT-IA1-V')['result']['code'] === 'LIT-IA1-V1');
check('rechaza tipo desconocido', !$x('XXX-IA1')['valid']);
check('rechaza solo el tipo', !$x('LIT')['valid']);
check('rechaza IA0 con actividades', !$x('LIT-IA0-DOC44')['valid'] && !$x('LIT-IA0-DOC44-V1')['valid']);
check('IA0 con evidencias se lee', $x('lit-ia0-v1')['result']['code'] === 'LIT-IA0-V1' && $x('TRA-IA0-V')['result']['code'] === 'TRA-IA0-V1');
check('rechaza actividad de otro tipo', !$x('LIT-IA1-COD44')['valid']);
check('rechaza actividad repetida', !$x('LIT-IA1-DOC44-DOC30')['valid']);
check('rechaza una sola cifra', !$x('LIT-IA1-DOC4')['valid']);
check('rechaza grado > 5', !$x('LIT-IA1-DOC64')['valid']);
check('rechaza alcance > 4', !$x('LIT-IA1-DOC45')['valid']);
check('rechaza segunda cifra distinta de 0 con grado 1', !$x('LIT-IA1-DOC13')['valid']);
check('rechaza V en medio', !$x('LIT-IA1-V1-DOC44')['valid']);

echo "Cambios de tipos\n";
check('Diseño se llama solo «Diseño»', WORK_TYPES['DES']['es'] === 'Diseño' && WORK_TYPES['DES']['en'] === 'Design');
check('tipos de dos letras en enlaces', oride_explain('SW-IA1-COD44-V1')['result']['code'] === 'SW-IA1-COD44-V1' && oride_explain('il:gen32')['result']['code'] === 'IL-IA1-GEN32');
check('los códigos antiguos SOF e ILU ya no valen', !oride_explain('SOF-IA1')['valid'] && !oride_explain('ILU-IA1')['valid']);

check('sin uso mínimo para instituciones', !isset(STR['es']['inst_min']) && !isset(STR['en']['inst_min']));

echo "Textos e idioma\n";
$r = oride_compute($lit(['DOC'], ['DOC' => '4'], ['DOC' => '4'], 'si'));
$txt = oride_text($r);
check('texto: código, tipo y actividad', str_starts_with($txt, "Código ORIDE LIT-IA1-DOC44-V1\nTipo de obra: Literatura\n") && str_contains($txt, 'Documentación: Modifiqué parcialmente los resultados. Parte de la obra afectada: del 75% al 100%.'));
check('texto: enlace que lo explica', str_contains($txt, 'oride.php?LIT-IA1-DOC44-V1&lang=es'));
check('texto de instituciones', str_starts_with(oride_text($r, t('inst_max')), 'El autor debe garantizar que el uso de la IA que hace no supera el siguiente código:'));
check('marca en trámite', str_contains(t('trademark'), 'ORIDE Code™ es una marca en trámite de registro') && str_contains(t('trademark'), 'centros de investigación públicos'));
check('por defecto, español', lang() === 'es');
check('los enlaces llevan el idioma', u('crear.php') === '/crear.php?lang=es' && u('oride.php?LIT-IA0') === '/oride.php?LIT-IA0&lang=es');

echo "\n$ok correctas, $fail fallidas\n";
exit($fail ? 1 : 0);
