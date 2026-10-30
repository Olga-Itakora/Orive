<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/views.php';

page_start('Inicio', ['description' => 'ORIDE te permite documentar cómo has utilizado la inteligencia artificial en tu proceso creativo, generar un código de transparencia y emitir una declaración responsable verificable.']);
?>
<section class="hero">
  <div class="container grid-main-aside">
    <div>
      <p class="eyebrow">ORIDE · Originality Declaration</p>
      <h1>Every creation has a process.<br><span class="hero-accent">Make it visible.</span></h1>
      <p class="muted"><em>Toda creación tiene un proceso. Hazlo visible.</em></p>
      <p class="lead">ORIDE te permite documentar cómo has utilizado la inteligencia artificial en tu proceso creativo, generar un código de transparencia y emitir una declaración responsable que puedes compartir con lectores, editoriales e instituciones.</p>
      <div class="actions">
        <a class="btn btn-primary btn-lg" href="<?= u('autor/obra.php') ?>">Crear declaración</a>
        <a class="btn btn-secondary btn-lg" href="<?= u('verificar.php') ?>">Consultar código ORIDE</a>
        <a class="btn btn-ghost btn-lg" href="<?= u('codigo.php') ?>">Descubrir ORIDE Code</a>
      </div>
    </div>
    <div class="code-card">
      <div class="inner">
        <p class="label">Ejemplo de perfil de una obra híbrida</p>
        <ul class="example-uses">
          <li><span class="mono">A4B2C1</span><span>Revisión lingüística aceptada</span></li>
          <li><span class="mono">A2B1C5</span><span>Idea desarrollada por la autora</span></li>
          <li><span class="mono">A5B3C3</span><span>Ilustración generada y transformada</span></li>
        </ul>
        <p class="big small-big">A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2</p>
        <p>Perfil compuesto: se conservan los tres usos y sus relaciones.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="how">
  <div class="container">
    <h2 id="how" class="h-section">Cómo funciona</h2>
    <ol class="steps-home">
      <li class="card"><span class="num">1</span><h3>Describe tu proceso creativo</h3><p class="muted small">Un cuestionario guiado, sin tecnicismos, para registrar cada uso concreto de IA: para qué, en qué fase, qué se incorporó a la obra y qué hiciste tú con ello.</p></li>
      <li class="card"><span class="num">2</span><h3>Obtén tu código ORIDE</h3><p class="muted small">Reglas deterministas clasifican cada uso en cuatro dimensiones. Sin puntuaciones, sin porcentajes y sin modelos generativos decidiendo por ti.</p></li>
      <li class="card"><span class="num">3</span><h3>Emite y comparte tu declaración</h3><p class="muted small">Acepta la declaración responsable, descarga el PDF con su huella criptográfica y decide qué publicar. Cualquiera puede verificar su integridad.</p></li>
    </ol>
  </div>
</section>

<section class="band" aria-labelledby="understand">
  <div class="container grid-2">
    <div>
      <h2 id="understand" class="h-section">Entiende tu código</h2>
      <p class="muted">Cada letra es una dimensión; cada número, una categoría. Selecciona un segmento para ver su significado. Los números no son puntuaciones: A5 no es «más IA» que A3, y C5 no significa «más autoría» que C4.</p>
      <dl class="dim-legend">
        <div><dt>A</dt><dd>Función de la IA</dd></div>
        <div><dt>B</dt><dd>Aportación incorporada</dd></div>
        <div><dt>C</dt><dd>Intervención humana</dd></div>
        <div><dt>D</dt><dd>Documentación</dd></div>
      </dl>
    </div>
    <div class="stack">
      <?php require __DIR__ . '/includes/partials/explorer.php'; explorer('A5B3C4D2'); ?>
      <form class="card" method="get" action="<?= u('verificar.php') ?>">
        <label for="lookup">Consultar un código o una declaración</label>
        <p class="hint" id="lookup-hint">Introduce un identificador (ORD-XXXX-XXXX-XXXX) o un código como A5B3C4D2.</p>
        <div class="search-row"><input id="lookup" name="q" class="mono" required maxlength="60" placeholder="ORD-7F29-C4A8-K2M3" aria-describedby="lookup-hint"><button class="btn btn-primary">Consultar</button></div>
      </form>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="who">
  <div class="container">
    <h2 id="who" class="h-section">Para quién es</h2>
    <ul class="audience">
      <li>Escritores</li><li>Artistas</li><li>Investigadores</li><li>Editoriales</li><li>Instituciones</li><li>Concursos</li><li>Creadores audiovisuales</li>
    </ul>
    <p class="muted small prose">Literatura, publicaciones académicas, ilustración, fotografía, música, cine, artes escénicas, videojuegos, diseño, arquitectura y obras multimedia. Las obras híbridas pueden describir cada componente por separado.</p>
  </div>
</section>

<section class="band" aria-labelledby="why">
  <div class="container">
    <h2 id="why" class="h-section">Por qué ORIDE</h2>
    <div class="grid-3 why">
      <div><h3>La IA interviene de formas muy distintas</h3><p class="muted small">Corregir erratas, sugerir una trama o generar una ilustración no son lo mismo. Tampoco la cantidad de material generado equivale a su importancia creativa.</p></div>
      <div><h3>Un sí o un no no basta</h3><p class="muted small">«¿Has usado IA?» no describe un proceso. ORIDE distingue la función de la IA, lo que se incorporó y lo que hizo la persona.</p></div>
      <div><h3>Una clasificación multidimensional</h3><p class="muted small">Cuatro dimensiones (A, B, C, D) con valores categóricos. Una obra híbrida conserva el perfil de cada uso y de cada componente.</p></div>
      <div><h3>El autor conserva el control</h3><p class="muted small">Todo es privado por defecto. Tú decides si publicas tu nombre, tu obra, el código, el resumen o la declaración completa.</p></div>
      <div><h3>El lector puede entenderlo</h3><p class="muted small">Cada código enlaza con su significado y con la declaración verificable. Un lenguaje común para autores, editoriales y lectores.</p></div>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="legal">
  <div class="container grid-2">
    <div>
      <h2 id="legal" class="h-section">Marco normativo</h2>
      <p class="muted">El Reglamento (UE) 2024/1689 de Inteligencia Artificial establece obligaciones específicas de transparencia en su artículo 50, aplicable desde el 2 de agosto de 2026: por ejemplo, para determinadas ultrasuplantaciones o textos que informan sobre asuntos de interés público. No obliga a todos los autores a declarar su uso de IA.</p>
      <p class="muted">ORIDE distingue entre esas obligaciones, los requisitos de editoriales, concursos o instituciones y las buenas prácticas voluntarias de transparencia. Incluye una orientación sobre el artículo 50, separada del código ORIDE.</p>
      <p><a class="link-44" href="<?= u('marco-normativo.php') ?>">Leer el marco normativo</a></p>
    </div>
    <aside class="notice">
      <p><strong>ORIDE no es una certificación oficial</strong></p>
      <p>ORIDE no es un servicio de la Unión Europea ni certifica el cumplimiento normativo. Las declaraciones son manifestaciones responsables de sus autores. La plataforma acredita técnicamente que una declaración se registró con un contenido determinado y que no ha sido alterada; no acredita la identidad del declarante, la veracidad de lo declarado, la originalidad ni la titularidad de derechos.</p>
    </aside>
  </div>
</section>
<?php page_end();
