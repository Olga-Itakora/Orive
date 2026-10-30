<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_start('Qué es ORIDE');
?>
<div class="container prose">
  <p class="eyebrow">Qué es ORIDE</p>
  <h1>Un lenguaje común para explicar cómo se ha creado una obra</h1>
  <p class="lead muted">ORIDE (Originality Declaration) es una plataforma de transparencia creativa. Permite a autoras y autores describir de forma estructurada qué uso han hecho de la inteligencia artificial y emitir una declaración responsable verificable.</p>
  <p><strong>ORIDE no pretende determinar quién merece ser considerado autor</strong>, sino ofrecer una forma transparente, estructurada y comprensible de explicar cómo se ha creado una obra.</p>
  <h2>Cinco principios</h2>
  <ul>
    <li><strong>Transparencia.</strong> Describir qué sistemas de IA se utilizaron, para qué, en qué fases y con qué resultados.</li>
    <li><strong>Neutralidad.</strong> El uso de IA no es automáticamente positivo ni negativo. ORIDE no penaliza su uso ni presupone que una obra sin IA sea mejor.</li>
    <li><strong>Responsabilidad.</strong> Cada declaración identifica quién declara, qué parte de la obra cubre y quién asume las decisiones finales.</li>
    <li><strong>Trazabilidad.</strong> Las declaraciones emitidas, sus versiones, las reglas aplicadas y sus huellas criptográficas se conservan sin alteraciones.</li>
    <li><strong>Proporcionalidad.</strong> No se piden datos innecesarios: ni prompts completos, ni conversaciones privadas, ni documentación confidencial.</li>
  </ul>
  <h2>Qué es y qué no es</h2>
  <p>ORIDE <strong>es</strong> un sistema de clasificación descriptiva (ORIDE Code), un cuestionario guiado y un registro técnico que acredita que una declaración concreta se emitió con un contenido determinado y no ha sido alterada.</p>
  <p>ORIDE <strong>no es</strong> una certificación de originalidad, de autoría ni de cumplimiento legal; no es un servicio de las instituciones europeas; no detecta automáticamente el uso de IA; y no puntúa obras ni calcula porcentajes de participación humana.</p>
  <h2>Identidad</h2>
  <ul>
    <li>Plataforma: ORIDE (Originality Declaration).</li>
    <li>Sistema de clasificación: ORIDE Code (versión actual v<?= ORIDE_SCHEME_VERSION ?>).</li>
    <li>Documentos: ORIDE Declaration, con identificadores ORD-XXXX-XXXX-XXXX.</li>
    <li>Lema: «Every creation has a process. Make it visible.» — «Toda creación tiene un proceso. Hazlo visible.»</li>
  </ul>
  <p><a href="<?= u('codigo.php') ?>">Consulta la especificación de ORIDE Code</a>.</p>
</div>
<?php page_end();
