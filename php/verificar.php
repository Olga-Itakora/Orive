<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/declarations.php';
require_once __DIR__ . '/includes/views.php';

$id = get('id') ?: post('id');
$q = get('q');
if ($q !== '') {
    if ($n = oride_normalize_public_id($q)) redirect('verificar.php?id=' . $n);
    $code = strtoupper(preg_replace('/^ORIDE\s+/i', '', $q));
    if (preg_match('/^A[0-5]B[0-3]C[0-5]D[0-3]$/', $code)) redirect('codigo.php?c=' . $code);
}

// Comprobación de un documento recibido (funciona sin JavaScript; no se almacena el fichero).
$check = null;
if (is_post()) {
    csrf_check();
    if (!rate_limit('check', client_ip(), 30, 600)) {
        $check = ['error' => 'Demasiadas comprobaciones seguidas. Espera unos minutos.'];
    } elseif (($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $_FILES['file']['size'] > 15 * 1024 * 1024) {
        $check = ['error' => 'Selecciona un fichero PDF o JSON de ORIDE (máximo 15 MB).'];
    } else {
        $check = check_file($id, (string) file_get_contents($_FILES['file']['tmp_name']));
    }
}

function file_check_form(?string $id): string
{
    return '<form method="post" enctype="multipart/form-data" action="' . u('verificar.php' . ($id ? '?id=' . rawurlencode($id) : '')) . '#comprobar">' . csrf_field()
        . ($id ? '<input type="hidden" name="id" value="' . h($id) . '">' : field('id', 'Identificador de la declaración', ['required' => true, 'placeholder' => 'ORD-7F29-C4A8-K2M3', 'max' => 20]))
        . field('file', 'Documento recibido (PDF o JSON de ORIDE)', ['type' => 'file', 'required' => true, 'attrs' => ' accept=".pdf,.json,application/pdf,application/json"', 'hint' => 'El fichero se envía solo para calcular su huella; no se almacena.'])
        . '<button class="btn btn-secondary">Comprobar documento</button></form>';
}

function check_result(?array $c): string
{
    if (!$c) return '';
    if (isset($c['error'])) return alert($c['error'], 'danger');
    if (!$c['match']) return alert('El documento no coincide con ninguna declaración registrada con ese identificador. Puede haber sido modificado, o el identificador no es correcto.', 'danger');
    $kind = ['pdf-full' => 'El fichero es idéntico al PDF completo registrado para esta declaración.', 'pdf-summary' => 'El fichero es idéntico al Resumen ORIDE registrado para esta declaración.', 'json' => 'El JSON exportado reproduce exactamente la huella registrada.'][$c['kind']];
    $status = ['ISSUED' => 'vigente', 'SUPERSEDED' => 'sustituida por una versión posterior', 'REVOKED' => 'revocada'][$c['status']] ?? $c['status'];
    return alert("$kind Estado de la declaración: $status.", 'ok');
}

if ($id === '') {
    page_start('Verificar una declaración', ['description' => 'Consulta una declaración ORIDE pública o comprueba la integridad de un documento.']);
    echo '<div class="container narrow stack"><h1>Verificar</h1><p class="muted">Consulta una declaración pública mediante su identificador, o comprueba si un documento ORIDE que has recibido coincide exactamente con el registrado.</p>';
    if ($q !== '') echo alert('No reconocemos «' . $q . '». Un identificador tiene la forma ORD-XXXX-XXXX-XXXX y un código, la forma A5B3C4D2.', 'warn');
    echo '<form class="card" method="get" action="' . u('verificar.php') . '">' . field('q', 'Consultar un código o una declaración', ['required' => true, 'max' => 60, 'placeholder' => 'ORD-7F29-C4A8-K2M3', 'hint' => 'Identificador (ORD-XXXX-XXXX-XXXX) o código como A5B3C4D2.']) . '<button class="btn btn-primary">Consultar</button></form>';
    echo '<section class="card" id="comprobar"><h2>Comprobar un documento</h2><p class="muted small">Funciona también con declaraciones no publicadas: solo confirma si el documento que ya tienes coincide con el registro, sin revelar nada más.</p>' . check_result($check) . file_check_form(null) . '</section>';
    echo '<section class="prose small muted"><h2>Qué se verifica y qué no</h2><p>ORIDE comprueba que una declaración fue registrada con un contenido determinado en una fecha determinada y que ese contenido no ha sido alterado (huella SHA-256 sobre una representación canónica). No verifica la identidad del declarante, la veracidad de lo declarado, la originalidad de la obra, la titularidad de derechos ni el cumplimiento de obligaciones legales.</p></section></div>';
    page_end();
    exit;
}

$d = public_declaration($id);
$norm = oride_normalize_public_id($id);
if (!$d) {
    http_response_code(404);
    page_start('Declaración no disponible');
    echo '<div class="container narrow stack"><h1>Declaración no disponible</h1><p class="muted">No hay ninguna declaración pública con el identificador <span class="mono">' . h($norm ?? $id) . '</span>. Puede que no exista, que sea privada o que su autor haya retirado la publicación.</p>';
    if ($norm) echo '<section class="card" id="comprobar"><h2>¿Tienes el documento?</h2><p class="muted small">Puedes comprobar si un PDF o JSON que has recibido coincide con el registro de esta declaración, aunque no sea pública.</p>' . check_result($check) . file_check_form($norm) . '</section>';
    echo '</div>';
    page_end();
    exit;
}

$sections = [];
foreach ($d['view']->sections as $n => $sec) $sections[$n] = $sec->data;
$p = $d['publication'];
$hasProcess = (bool) array_intersect(['process', 'article50', 'statement', 'evidence'], $d['visible']);
$tabs = ['declaracion' => 'Declaración'] + ($hasProcess ? ['proceso' => 'Proceso declarado'] : []) + ['integridad' => 'Integridad y descargas'];
$tab = isset($tabs[get('tab')]) ? get('tab') : ($check ? 'integridad' : 'declaracion');
$base = 'verificar.php?id=' . $d['public_id'];
$title = $sections['work']->title ?? 'Declaración ORIDE';
$statusText = ['ISSUED' => ['Vigente', 'ok'], 'REVOKED' => ['Revocada', 'danger'], 'SUPERSEDED' => ['Sustituida', 'neutral']][$d['status']];

page_start($title . ' — ORIDE Declaration', ['description' => 'Declaración ORIDE ' . $d['public_id'] . (isset($sections['classification']->code) ? ' · ' . $sections['classification']->code : ''), 'scripts' => ['assets/js/verificar.js']]);
?>
<div class="container">
  <p class="eyebrow">ORIDE Declaration</p>
  <h1><?= h($title) ?></h1>
  <p class="badges"><?= badge($statusText[0], $statusText[1]) ?><span class="mono"><?= h($d['public_id']) ?></span><span class="muted small">· emitida el <?= fecha($d['issued_at'], true) ?></span></p>
  <?php
  if ($d['status'] === 'SUPERSEDED') echo alert('Existe una versión posterior de esta declaración' . ($d['superseded_by']['is_public'] ?? false ? ': <a class="mono" href="' . u('verificar.php?id=' . $d['superseded_by']['public_id']) . '">' . h($d['superseded_by']['public_id']) . '</a>' : ' (no publicada)') . '. Esta versión se conserva sin cambios.', 'info', 'Esta versión ha sido sustituida', true);
  if ($d['status'] === 'REVOKED') echo alert(trim(($d['revoked_at'] ? 'Revocada el ' . fecha($d['revoked_at'], true) . '. ' : '') . ($d['revocation_reason'] ? 'Motivo: ' . $d['revocation_reason'] . '. ' : '') . 'El contenido se conserva para su verificación, pero el declarante indica que ya no es válida.'), 'danger', 'Declaración revocada por su autor');
  if ($d['supersedes'] && $d['supersedes']['is_public']) echo '<p class="small muted">Sustituye a la versión <a class="mono" href="' . u('verificar.php?id=' . $d['supersedes']['public_id']) . '">' . h($d['supersedes']['public_id']) . '</a>.</p>';
  ?>
  <nav class="tabs" aria-label="Secciones de la declaración"><ul>
    <?php foreach ($tabs as $k => $label): ?><li><a href="<?= u($base . ($k === 'declaracion' ? '' : '&tab=' . $k)) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= h($label) ?></a></li><?php endforeach; ?>
  </ul></nav>

  <?php if ($tab === 'declaracion'): ?>
    <div class="prose-wide"><?= render_snapshot($sections, ['meta', 'work', 'declarant', 'scope', 'classification', 'summary']) ?></div>
  <?php elseif ($tab === 'proceso'): ?>
    <div class="prose-wide"><?= render_snapshot($sections, ['process', 'article50', 'statement', 'evidence'], false) ?></div>
  <?php else: ?>
    <div class="grid-2">
      <section class="card">
        <h2>Integridad</h2>
        <p class="muted small">Huella SHA-256 registrada:</p>
        <p class="mono small break"><?= h($d['content_hash']) ?></p>
        <div data-verify="<?= h(u('api/declaracion.php?id=' . $d['public_id'])) ?>" data-hash="<?= h($d['content_hash']) ?>">
          <button type="button" class="btn btn-secondary" data-verify-run>Recalcular la huella en mi navegador</button>
          <div data-verify-result role="status" aria-live="polite"></div>
        </div>
        <p class="meta">Las secciones no publicadas por el autor se representan solo por su resumen criptográfico, de modo que la huella puede comprobarse sin revelarlas.</p>
        <table class="small"><tbody><?php foreach (ORIDE_SECTIONS as $s): ?><tr><td><?= h(['meta' => 'Datos del registro', 'work' => 'Obra', 'declarant' => 'Declarante', 'scope' => 'Alcance', 'classification' => 'Clasificación ORIDE', 'summary' => 'Resumen', 'process' => 'Proceso declarado', 'article50' => 'Orientación art. 50', 'statement' => 'Declaración responsable', 'evidence' => 'Evidencias'][$s]) ?></td><td><?= in_array($s, $d['visible'], true) ? 'publicada' : '<span class="muted">no publicada</span>' ?></td></tr><?php endforeach; ?></tbody></table>
      </section>
      <div class="stack">
        <section class="card" id="comprobar"><h2>Comprobar un documento</h2><p class="muted small">¿Has recibido el PDF o el JSON de esta declaración? Comprueba que es idéntico al registrado.</p><?= check_result($check) . file_check_form($d['public_id']) ?></section>
        <section class="card stack"><h2>Descargas</h2>
          <a class="btn btn-secondary" href="<?= u('api/declaracion.php?id=' . $d['public_id']) ?>">Descargar datos publicados (JSON)</a>
          <?php if ($p['show_name'] && ($p['show_full'] || ($p['show_work'] && $p['show_code'] && $p['show_summary']))): ?><a class="btn btn-secondary" href="<?= u('documento.php?id=' . $d['public_id'] . '&tipo=resumen') ?>">Descargar resumen (PDF)</a><?php endif; ?>
          <?php if ($p['show_name'] && $p['show_full']): ?><a class="btn btn-secondary" href="<?= u('documento.php?id=' . $d['public_id'] . '&tipo=pdf') ?>">Descargar declaración completa (PDF)</a><?php endif; ?>
          <img class="qr" src="<?= u('qr.php?id=' . $d['public_id']) ?>" alt="Código QR de <?= h(abs_url('verificar.php?id=' . $d['public_id'])) ?>">
        </section>
      </div>
    </div>
  <?php endif; ?>
  <p class="meta spaced prose">Esta es una declaración responsable de su autor. ORIDE acredita que fue registrada con este contenido y que no ha sido alterada; no acredita la identidad del declarante, la veracidad de lo declarado, la originalidad, la titularidad de derechos ni el cumplimiento legal. <a href="<?= u('codigo.php') ?>">Cómo leer el código ORIDE</a></p>
</div>
<?php page_end();
