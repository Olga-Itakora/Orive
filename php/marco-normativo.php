<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$refs = q('SELECT * FROM legal_references WHERE active = 1 ORDER BY rkey')->fetchAll();
page_start('Marco normativo');
?>
<div class="container prose">
  <p class="eyebrow">Marco normativo</p>
  <h1>ORIDE y el Reglamento europeo de inteligencia artificial</h1>
  <p class="lead muted">El Reglamento (UE) 2024/1689 establece obligaciones específicas de transparencia. ORIDE ayuda a documentar procesos creativos, pero no certifica el cumplimiento normativo.</p>
  <?= alert('Esta página resume el marco aplicable a fecha de 30 de octubre de 2026 y no constituye asesoramiento jurídico. Las referencias deben contrastarse con el Diario Oficial de la UE y la versión consolidada vigente en EUR-Lex.', 'warn', 'Contenido informativo pendiente de revisión jurídica') ?>
  <h2>No todos los autores están obligados a declarar su uso de IA</h2>
  <ul>
    <li><strong>Obligaciones específicas de transparencia</strong> previstas en el artículo 50 para proveedores y responsables del despliegue de determinados sistemas.</li>
    <li><strong>Requisitos de editoriales, concursos, revistas o instituciones</strong>, que pueden exigir declarar el uso de IA aunque la ley no lo haga.</li>
    <li><strong>Obligaciones contractuales o sectoriales</strong> que puedan resultar aplicables.</li>
    <li><strong>Buenas prácticas voluntarias</strong> de transparencia, que es el ámbito principal de ORIDE.</li>
  </ul>
  <h2>Proveedores y responsables del despliegue</h2>
  <p>El <em>proveedor</em> desarrolla un sistema de IA y lo comercializa o lo pone en servicio con su nombre o marca. El <em>responsable del despliegue</em> utiliza un sistema bajo su propia autoridad, salvo en una actividad personal de carácter no profesional. No toda persona que usa una herramienta es responsable del despliegue: cuando se trabaja por cuenta de una editorial o una empresa, esa organización puede serlo.</p>
  <h2>El artículo 50</h2>
  <p>Aplicable desde el 2 de agosto de 2026. Entre otras cuestiones:</p>
  <ul>
    <li>Los proveedores de sistemas que generan audio, imagen, vídeo o texto sintéticos deben marcar los resultados en un formato legible por máquina (art. 50.2), salvo funciones de asistencia para la edición estándar o cuando no se alteran sustancialmente los datos de entrada.</li>
    <li>Los responsables del despliegue que generan o manipulan imagen, audio o vídeo que constituya una <em>ultrasuplantación</em> deben revelarlo (art. 50.4). Una ultrasuplantación se asemeja a personas, objetos, lugares, entidades o sucesos reales y puede parecer falsamente auténtica: <strong>no toda imagen generada con IA lo es</strong>.</li>
    <li>En obras manifiestamente artísticas, creativas, satíricas, de ficción o análogas, la obligación se limita a revelar su existencia sin dificultar la exhibición o el disfrute de la obra.</li>
    <li>El texto generado o manipulado publicado para informar al público sobre asuntos de interés público debe revelarse, salvo revisión humana o control editorial con una persona que asuma la responsabilidad editorial.</li>
  </ul>
  <h2>Cambios recientes</h2>
  <p>Según fuentes secundarias consultadas, el Reglamento (UE) 2026/1744 («Digital Omnibus on AI») aplazó parte de las obligaciones de alto riesgo y estableció un periodo transitorio hasta el 2 de diciembre de 2026 para el marcado del art. 50.2 de sistemas ya comercializados, sin aplazar el resto del artículo 50. La Comisión publicó en 2026 un Código de prácticas sobre contenido generado por IA y directrices sobre el artículo 50. <strong>Estos extremos deben verificarse en las fuentes oficiales.</strong></p>
  <h2>Autoría y derechos de autor</h2>
  <p>Que una obra se haya elaborado con asistencia de IA no significa por sí mismo que deje de tener autoría humana ni protección por derechos de autor. ORIDE no se pronuncia sobre la autoría ni sobre la titularidad de derechos.</p>
  <h2>Referencias</h2>
  <ul><?php foreach ($refs as $r): ?><li><a href="<?= h($r['url']) ?>" rel="noopener noreferrer" target="_blank"><?= h($r['citation']) ?></a> — <?= h($r['title']) ?>.<?php if ($r['verification_note']): ?> <span class="muted small"><?= h($r['verification_note']) ?></span><?php endif; ?></li><?php endforeach; ?></ul>
</div>
<?php page_end();
