<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

page_start(t('nav_legal'), tr('Responsable, datos personales y cookies.', 'Controller, personal data and cookies.'));
?>
<div class="container narrow-text page legal">
  <h1><?= h(t('nav_legal')) ?></h1>
  <?php if (en()): ?>
    <h2>Controller</h2>
    <p>Olga Revilla is responsible for this website. For any communication: <a href="mailto:info@itakora.com">info@itakora.com</a>.</p>
    <h2>Personal data</h2>
    <p>The only data collected is what you type into the Contact form: full name, email address and message. It is used only to read your message and, if needed, reply to you. It is sent by email to info@itakora.com and is not stored on this website.</p>
    <p>You can request access to, rectification or erasure of your data by writing to <a href="mailto:info@itakora.com">info@itakora.com</a>. You can also lodge a complaint with the Spanish Data Protection Agency (aepd.es).</p>
    <p>Your answers in “Create your declaration” are not personal data and are not stored: the code is calculated in your browser.</p>
    <h2>Cookies</h2>
    <p>This site does not use cookies or store any information in your browser.</p>
    <p>The Creative Commons licence icons in the footer are loaded from creativecommons.org, so your browser connects to that site to display them.</p>
    <h2>Licence</h2>
    <p>The ORIDE Code, the ORIDE Global Index, their methodology and the contents of this site are © 2026 Olga Revilla and are published under the <a href="https://creativecommons.org/licenses/by-nc-nd/4.0/" rel="license">CC BY-NC-ND 4.0</a> licence.</p>
    <h2>Hosting</h2>
    <p>The site is hosted by IONOS, which acts as technical provider.</p>
    <h2>Responsibility</h2>
    <p><?= h(t('honor')) ?> This site does not check what is declared.</p>
  <?php else: ?>
    <h2>Responsable</h2>
    <p>Olga Revilla es la responsable de este sitio web. Para cualquier comunicación: <a href="mailto:info@itakora.com">info@itakora.com</a>.</p>
    <h2>Datos personales</h2>
    <p>Solo se recogen los datos que escribes en el formulario de Contacto: nombre y apellidos, correo electrónico y mensaje. Se usan únicamente para leer tu mensaje y, si hace falta, responderte. Se envían por correo a info@itakora.com y no se guardan en este sitio web.</p>
    <p>Puedes pedir el acceso, la rectificación o la supresión de tus datos escribiendo a <a href="mailto:info@itakora.com">info@itakora.com</a>. También puedes reclamar ante la Agencia Española de Protección de Datos (aepd.es).</p>
    <p>Las respuestas del formulario «Crea tu declaración» no son datos personales y no se guardan: el código se calcula en tu navegador.</p>
    <h2>Cookies</h2>
    <p>Este sitio no usa cookies ni guarda información en tu navegador.</p>
    <p>Los iconos de la licencia Creative Commons del pie de página se cargan desde creativecommons.org, por lo que tu navegador se conecta a ese sitio para mostrarlos.</p>
    <h2>Licencia</h2>
    <p>El Código ORIDE, el Índice Global ORIDE, su metodología y los contenidos de este sitio son © 2026 Olga Revilla y se publican con la licencia <a href="https://creativecommons.org/licenses/by-nc-nd/4.0/deed.es" rel="license">CC BY-NC-ND 4.0</a>.</p>
    <h2>Alojamiento</h2>
    <p>El sitio está alojado en IONOS, que actúa como proveedor técnico.</p>
    <h2>Responsabilidad</h2>
    <p><?= h(t('honor')) ?> Este sitio no comprueba lo declarado.</p>
  <?php endif; ?>
</div>
<?php page_end();
