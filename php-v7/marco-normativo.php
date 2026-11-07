<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_law'), tr('El Código ORIDE y el artículo 50 del Reglamento europeo de inteligencia artificial.', 'The ORIDE Code and Article 50 of the EU Artificial Intelligence Act.'));
?>
<div class="container narrow-text page">
  <h1><?= h(t('nav_law')) ?></h1>
  <?php if (en()): ?>
    <p class="lead">The ORIDE Code is voluntary. It does not replace any legal obligation.</p>
    <h2>Article 50 of the AI Act</h2>
    <p class="muted">Regulation (EU) 2024/1689 requires disclosure, in some cases, that content has been generated or manipulated with AI:</p>
    <ul class="muted">
      <li>Images, audio or video that look real and could make people believe they are authentic (deep fakes).</li>
      <li>AI-generated text published to inform the public on matters of public interest, unless it has undergone human review and someone holds editorial responsibility.</li>
    </ul>
    <p class="muted">For clearly artistic, creative, satirical or fictional works, it is enough to disclose it in a way that does not spoil the work. These obligations apply from 2 August 2026.</p>
    <p><a href="https://eur-lex.europa.eu/eli/reg/2024/1689/oj" rel="noopener">Regulation (EU) 2024/1689 on EUR-Lex</a></p>
    <div class="card callout"><p class="small">General information, not legal advice. If in doubt about your case, consult a professional.</p></div>
  <?php else: ?>
    <p class="lead">El Código ORIDE es voluntario. No sustituye ninguna obligación legal.</p>
    <h2>Artículo 50 del Reglamento de IA</h2>
    <p class="muted">El Reglamento (UE) 2024/1689 exige avisar, en algunos casos, de que un contenido se ha generado o manipulado con IA:</p>
    <ul class="muted">
      <li>Imágenes, audio o vídeo que parecen reales y pueden hacer creer que son auténticos (ultrasuplantaciones).</li>
      <li>Texto generado con IA que se publica para informar sobre asuntos de interés público, salvo que tenga revisión humana y alguien asuma la responsabilidad editorial.</li>
    </ul>
    <p class="muted">En obras claramente artísticas, creativas, satíricas o de ficción basta con indicarlo de forma que no estropee la obra. Estas obligaciones se aplican desde el 2 de agosto de 2026.</p>
    <p><a href="https://eur-lex.europa.eu/eli/reg/2024/1689/oj" rel="noopener">Reglamento (UE) 2024/1689 en EUR-Lex</a></p>
    <div class="card callout"><p class="small">Información orientativa, no asesoramiento jurídico. Si tienes dudas sobre tu caso, consulta a un profesional.</p></div>
  <?php endif; ?>
</div>
<?php page_end();
