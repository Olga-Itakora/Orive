<?php
/** Pruebas de ORIDE v4. Uso: php tests/run.php */
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
$full = fn(array $areas, array $u, array $s = [], string $ev = 'no') => ['ia' => 'si', 'areas' => $areas, 'u' => $u, 's' => $s, 'ev' => $ev];

echo "Cálculo del código\n";
check('sin responder: vacío', oride_compute([])['status'] === 'empty');
check('no se usó IA: IA0', $code(['ia' => 'no']) === 'IA0');
check('IA0 ignora el resto', $code(['ia' => 'no', 'areas' => ['C'], 'u' => ['C' => '5'], 'ev' => 'si']) === 'IA0');
check('sí sin áreas: pendiente', oride_compute(['ia' => 'si'])['missing'] === 'Señala al menos un área.');
check('área sin forma de uso: pendiente', str_contains((string) oride_compute($full(['T'], []))['missing'], 'la traducción'));
check('uso 1 y 2 sin alcance', $code($full(['I', 'D'], ['I' => '2', 'D' => '1'])) === 'IA1-D1-I2-V0');
check('uso 0 permitido', $code($full(['F'], ['F' => '0'])) === 'IA1-F0-V0');
check('uso 3–5 pide alcance', str_contains((string) oride_compute($full(['C'], ['C' => '4']))['missing'], 'parte de tu obra'));
check('uso con alcance: C43', $code($full(['C'], ['C' => '4'], ['C' => '3'])) === 'IA1-C43-V0');
check('alcance ignorado con uso 1–2', $code($full(['C'], ['C' => '2'], ['C' => '3'])) === 'IA1-C2-V0');
check('orden D, I, E, C, R, F, T', $code($full(['T', 'F', 'R', 'C', 'E', 'I', 'D'], ['T' => '5', 'F' => '5', 'R' => '1', 'C' => '3', 'E' => '2', 'I' => '1', 'D' => '0'], ['T' => '4', 'F' => '0', 'C' => '2'], 'si')) === 'IA1-D0-I1-E2-C32-R1-F50-T54-V1');
check('falta evidencia: pendiente', oride_compute(['ia' => 'si', 'areas' => ['I'], 'u' => ['I' => '1']])['missing'] === 'Indica si conservas evidencias.');
check('evidencias: V1', $code($full(['I'], ['I' => '1'], [], 'si')) === 'IA1-I1-V1');
check('áreas desconocidas se ignoran', $code($full(['X', 'I'], ['I' => '1', 'X' => '5'])) === 'IA1-I1-V0');
check('valores fuera de rango no cuentan', oride_compute($full(['I'], ['I' => '7']))['status'] === 'pending' && oride_compute($full(['C'], ['C' => '5'], ['C' => '9']))['status'] === 'pending');

echo "Lectura de códigos\n";
$x = fn(string $c) => oride_explain($c);
check('IA0 válido', $x('IA0')['valid']);
check('código completo', $x('IA1-I2-C43-T51-V1')['result']['code'] === 'IA1-I2-C43-T51-V1');
check('admite «ORIDE», minúsculas y espacios', $x('oride ia1 i2 c43 v1')['code'] === 'IA1-I2-C43-V1');
check('admite «Código ORIDE» y puntos', $x('Código ORIDE IA1·I2·V0')['valid']);
check('rechaza área desconocida', !$x('IA1-X2-V1')['valid']);
check('rechaza orden incorrecto', !$x('IA1-C2-I2-V1')['valid']);
check('rechaza área repetida', !$x('IA1-I2-I1-V1')['valid']);
check('rechaza uso > 5', !$x('IA1-I6-V1')['valid']);
check('rechaza uso 3–5 sin alcance', !$x('IA1-C4-V1')['valid']);
check('rechaza alcance con uso 1–2', !$x('IA1-C23-V1')['valid']);
check('rechaza alcance > 4', !$x('IA1-C45-V1')['valid']);
check('rechaza sin V', !$x('IA1-C43')['valid']);
check('rechaza sin áreas', !$x('IA1-V1')['valid']);
check('rechaza texto libre', !$x('hola')['valid']);

echo "Textos\n";
$txt = oride_text(oride_compute($full(['C', 'T'], ['C' => '4', 'T' => '1'], ['C' => '3'], 'si')));
check('texto: cabecera', str_starts_with($txt, "Código ORIDE IA1-C41-T1-V1") === false && str_starts_with($txt, "Código ORIDE IA1-C43-T1-V1\n"));
check('texto: forma de uso y alcance', str_contains($txt, 'Generación de contenidos: Realicé cambios menores. Parte de la obra afectada: del 50% al 75%.'));
check('texto: evidencias', str_contains($txt, 'Evidencias: conserva evidencias'));
check('alcances sin errata (75%)', scope_labels()[3] === 'Del 50% al 75%');
check('siete áreas con seis formas de uso en los dos idiomas', count(AREAS['es']) === 7 && count(AREAS['en']) === 7 && count(array_filter(AREAS['es'] + [], fn($a) => count($a[3]) === 6)) === 7 && count(array_filter(AREAS['en'], fn($a) => count($a[3]) === 6)) === 7);
check('mismas letras en los dos idiomas', array_keys(AREAS['es']) === array_keys(AREAS['en']) && array_keys(AREAS['es']) === AREA_ORDER);
check('R3 nuevo texto', AREAS['es']['R'][3][3] === 'Incorporé cambios puntuales propuestos');
check('R4 nuevo texto', AREAS['es']['R'][3][4] === 'Incorporé gran parte de los cambios propuestos');
check('mismas claves de texto en los dos idiomas', array_keys(STR['es']) === array_keys(STR['en']));
check('declaración de honor', str_contains(t('honor'), 'honor, la honestidad y la ética personal'));

echo "Idioma\n";
check('por defecto, español', lang() === 'es');
check('los enlaces llevan el idioma', u('crear.php') === '/crear.php?lang=es' && u('codigo.php?c=IA0#x') === '/codigo.php?c=IA0&lang=es#x');
check('cambiar de idioma en un enlace', u('crear.php?lang=es', 'en') === '/crear.php?lang=en');
check('los recursos no llevan idioma', u('assets/css/oride.css') === '/assets/css/oride.css');

echo "\n$ok correctas, $fail fallidas\n";
exit($fail ? 1 : 0);
