<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_start('Cómo funciona');
$steps = [
    ['Identifica la obra', 'Título, modalidad, estado, idioma, editorial e identificadores (ISBN, DOI…). Si es una obra híbrida, como un libro ilustrado o un videojuego, divídela en componentes: texto, ilustraciones, audio, código…'],
    ['Indica quién declara y qué cubre la declaración', 'Tu nombre profesional, tu rol y el alcance: la obra completa, componentes concretos o solo tu contribución. En una obra colectiva solo declaras lo que conoces; tu declaración no se atribuye a los demás participantes.'],
    ['Responde si se utilizó IA', 'No, sí, no puedo determinarlo con certeza, o solo puedo declarar sobre mi contribución. Si no se utilizó IA, puedes emitir una declaración negativa. Si hay incertidumbre, se registra tal cual, sin inventar datos.'],
    ['Registra las herramientas y cada uso concreto', 'ORIDE registra usos individuales: la parte de la obra, la herramienta, las fases, para qué se utilizó la IA, qué se incorporó a la obra y qué hiciste tú con ello.'],
    ['Obtén tu clasificación ORIDE', 'Unas reglas deterministas calculan el código de cada uso y el perfil de la obra. No puedes editar el código directamente: si no te parece correcto, revisa las respuestas.'],
    ['Consulta la orientación sobre el artículo 50', 'Unas preguntas opcionales te ayudan a reconocer si podría existir una obligación específica de transparencia. Es una orientación, separada del código, y no una certificación.'],
    ['Revisa, acepta y emite', 'Aceptas expresamente la declaración responsable y la emites. Se registra la fecha y hora del servidor, la versión del texto aceptado, la versión de la taxonomía y una huella SHA-256. Recibes un identificador, un PDF completo, un resumen y una exportación JSON.'],
    ['Decide qué publicar', 'Emitir y publicar son acciones distintas. Puedes publicar solo el código, la obra, el resumen, tu nombre o la declaración completa, y retirar la publicación cuando quieras.'],
    ['Actualiza con nuevas versiones', 'Si necesitas corregir algo, creas una nueva versión. La anterior se conserva intacta, enlazada y verificable, marcada como sustituida.'],
];
?>
<div class="container prose">
  <p class="eyebrow">Cómo funciona</p>
  <h1>De tu proceso creativo a una declaración verificable</h1>
  <p class="lead muted">Un recorrido guiado, sin tecnicismos. Puedes guardar un borrador, volver atrás y corregir tus respuestas en cualquier momento antes de emitir.</p>
  <ol>
    <?php foreach ($steps as [$t, $d]): ?><li><strong><?= h($t) ?>.</strong> <?= h($d) ?></li><?php endforeach; ?>
  </ol>
  <p><a class="btn btn-primary" href="<?= u('registro.php') ?>">Crear cuenta</a></p>
</div>
<?php page_end();
