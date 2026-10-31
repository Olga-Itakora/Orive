<?php
/** Pruebas de ORIDE v2. Uso: php tests/run.php */
declare(strict_types=1);
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require __DIR__ . '/../includes/app.php';
restore_exception_handler();

$ok = 0; $fail = 0;
function t(string $name, bool $cond): void
{
    global $ok, $fail;
    if ($cond) { $ok++; echo "  ✓ $name\n"; } else { $fail++; echo "  ✗ $name\n"; }
}
$c = fn(array $in) => oride_compute($in)['code'];

echo "Cálculo del código\n";
t('sin responder: vacío', oride_compute([])['status'] === 'empty');
t('no se usó IA: A0B0C0', $c(['ia' => 'no']) === 'A0B0C0');
t('no se usó IA ignora el resto', $c(['ia' => 'no', 'a' => ['A5'], 'inc' => 'si']) === 'A0B0C0');
t('sí sin A: pendiente', oride_compute(['ia' => 'si'])['status'] === 'pending');
t('nada incorporado: B0C0', $c(['ia' => 'si', 'a' => ['A3'], 'inc' => 'no']) === 'A3B0C0');
t('combina A en orden: A1+A3+A5 → A135', $c(['ia' => 'si', 'a' => ['A5', 'A1', 'A3'], 'inc' => 'no']) === 'A135B0C0');
t('valores A desconocidos se ignoran', $c(['ia' => 'si', 'a' => ['A9', 'A2'], 'inc' => 'no']) === 'A2B0C0');
t('incorporado sin B: pendiente', oride_compute(['ia' => 'si', 'a' => ['A2'], 'inc' => 'si'])['status'] === 'pending');
t('B sin C: pendiente con mensaje', str_contains((string) oride_compute(['ia' => 'si', 'a' => ['A2'], 'inc' => 'si', 'b' => ['B2']])['missing'], 'cambios formales'));
t('B123 y C sin repetir', $c(['ia' => 'si', 'a' => ['A2', 'A4', 'A5'], 'inc' => 'si', 'b' => ['B1', 'B2', 'B3'], 'c' => ['B1' => 'C5', 'B2' => 'C1', 'B3' => 'C1']]) === 'A245B123C15');
t('C de un B no marcado se ignora', $c(['ia' => 'si', 'a' => ['A5'], 'inc' => 'si', 'b' => ['B3'], 'c' => ['B3' => 'C3', 'B1' => 'C5']]) === 'A5B3C3');
t('conserva las parejas B–C', oride_compute(['ia' => 'si', 'a' => ['A4'], 'inc' => 'si', 'b' => ['B2', 'B3'], 'c' => ['B2' => 'C1', 'B3' => 'C4']])['pairs'] === [['B2', 'C1'], ['B3', 'C4']]);

echo "Lectura de códigos\n";
t('válido simple', oride_explain('A5B3C4')['valid']);
t('válido combinado', oride_explain('A24B13C15')['valid']);
t('admite prefijo y minúsculas', oride_explain('oride a24b13c15')['code'] === 'A24B13C15');
t('admite «Código ORIDE»', oride_explain('Código ORIDE A5B3C4')['valid']);
t('convierte notación antigua y quita D', oride_explain('A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2')['code'] === 'A245B123C135');
t('quita la D del código compacto antiguo', oride_explain('A5B3C4D2')['code'] === 'A5B3C4');
t('A0B0C0 válido', oride_explain('A0B0C0')['valid']);
t('rechaza A0 con B', !oride_explain('A0B3C1')['valid']);
t('rechaza B0 con C distinto de C0', !oride_explain('A2B0C1')['valid']);
t('rechaza orden decreciente', !oride_explain('A42B1C1')['valid']);
t('rechaza repetidos', !oride_explain('A22B1C1')['valid']);
t('rechaza 0 combinado', !oride_explain('A02B1C1')['valid']);
t('rechaza más C que B', !oride_explain('A2B1C15')['valid']);
t('rechaza valores fuera de rango', !oride_explain('A6B1C1')['valid'] && !oride_explain('A1B4C1')['valid']);
t('rechaza texto libre', !oride_explain('hola')['valid']);

echo "Textos\n";
t('B3 con el nuevo nombre', label('B3') === 'Incorporación de contenido generado por IA');
t('texto para copiar', str_starts_with(oride_text(oride_compute(['ia' => 'si', 'a' => ['A4'], 'inc' => 'si', 'b' => ['B2'], 'c' => ['B2' => 'C2']])), "Código ORIDE A4B2C2\n"));
t('texto sin IA', str_contains(oride_text(oride_compute(['ia' => 'no'])), 'No se utilizó'));

echo "\n$ok correctas, $fail fallidas\n";
exit($fail ? 1 : 0);
