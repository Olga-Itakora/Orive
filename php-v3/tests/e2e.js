/* Prueba en navegador (solo desarrollo). Requiere Node y Playwright.
   Uso: php -S 127.0.0.1:8092 tests/router.php   y   ORIDE_URL=http://127.0.0.1:8092/ node tests/e2e.js */
const { chromium } = require('playwright');
const fs = require('fs');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8092/';
const out = require('os').tmpdir() + '/oride-v3-e2e';
fs.mkdirSync(out, { recursive: true });
let fails = 0;
const ok = (name, cond, extra = '') => { console.log((cond ? '  ✓ ' : '  ✗ ') + name + (extra ? ' — ' + extra : '')); if (!cond) fails++; };

(async () => {
  const browser = await browserLaunch();
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
  const code = async () => (await page.locator('[data-code]').innerText()).replace(/\s/g, '');

  await page.goto(B + 'crear.php');
  ok('al principio no hay respuesta marcada', !(await page.isChecked('input[name=ia][value=si]')) && !(await page.isChecked('input[name=ia][value=no]')));
  ok('al principio el resto está oculto', !(await page.isVisible('text=Señala las áreas')));
  ok('el botón copiar está desactivado', await page.isDisabled('[data-copy-code]'));
  await page.screenshot({ path: out + '/01-inicio.png', fullPage: true });

  await page.check('input[name=ia][value=no]');
  ok('«No» da IA0', (await code()) === 'IA0');
  ok('«No» termina el formulario', !(await page.isVisible('text=Señala las áreas')));
  await page.click('[data-copy-code]');
  ok('copiar código', await copied(page, '[data-copy-code]'));

  await page.check('input[name=ia][value=si]');
  ok('«Sí» muestra las áreas', await page.isVisible('text=Señala las áreas'));
  ok('las preguntas de cada área están ocultas hasta marcarla', !(await page.isVisible('text=¿Cómo utilizaste los resultados de la IA para la traducción?')));
  await page.check('input[name="areas[]"][value=T]');
  await page.check('input[name="areas[]"][value=I]');
  ok('aparece la pregunta de la traducción', await page.isVisible('text=¿Cómo utilizaste los resultados de la IA para la traducción?'));
  await page.check('input[name=uI][value="2"]');
  ok('con uso 2 no se pregunta el alcance', !(await page.isVisible('text=¿Qué parte de tu obra está afectada por el uso de la IA para la generación de ideas?')));
  await page.check('input[name=uT][value="5"]');
  ok('con uso 5 se pregunta el alcance', await page.isVisible('text=¿Qué parte de tu obra está afectada por el uso de la IA para la traducción?'));
  ok('código parcial', (await code()) === 'IA1-I2-T5–-V–', await code());
  await page.check('input[name=sT][value="1"]');
  ok('falta la evidencia', (await page.locator('[data-status]').innerText()).includes('evidencias'));
  await page.check('input[name=ev][value=si]');
  ok('aviso de evidencias visible', await page.isVisible('.create-form .alert >> text=medio para contactar'));
  ok('código completo IA1-I2-T51-V1', (await code()) === 'IA1-I2-T51-V1', await code());
  ok('la explicación incluye el alcance', (await page.locator('[data-explain]').innerText()).includes('del 5% al 25%'));
  ok('la URL refleja las respuestas', page.url().includes('sT=1'));
  await page.screenshot({ path: out + '/02-completo.png', fullPage: true });
  await page.check('input[name=uT][value="1"]');
  ok('al bajar a uso 1 desaparece el alcance', (await code()) === 'IA1-I2-T1-V1', await code());
  await page.check('input[name=uT][value="5"]');

  const shared = page.url();
  const p2 = await browser.newPage({ javaScriptEnabled: false });
  await p2.goto(shared);
  const serverCode = (await p2.locator('[data-code]').innerText()).replace(/\s/g, '');
  ok('sin JavaScript el servidor da el mismo código', serverCode === 'IA1-I2-T51-V1', serverCode);
  ok('sin JavaScript se ve el botón «Ver mi código»', await p2.isVisible('text=Ver mi código'));

  // Navegador y servidor en muchas combinaciones.
  const L = ['D', 'I', 'E', 'C', 'R', 'F', 'T'];
  let same = 0, n = 60;
  for (let i = 0; i < n; i++) {
    const qs = new URLSearchParams(); qs.set('ia', 'si');
    L.forEach(l => { if (Math.random() < 0.4) { qs.append('areas[]', l); const u = Math.floor(Math.random() * 6); qs.set('u' + l, u); if (u >= 3 && Math.random() < 0.9) qs.set('s' + l, Math.floor(Math.random() * 5)); } });
    if (Math.random() < 0.9) qs.set('ev', Math.random() < 0.5 ? 'si' : 'no');
    await page.goto(B + 'crear.php?' + qs);
    const js = await code();
    await p2.goto(B + 'crear.php?' + qs);
    const php = (await p2.locator('[data-code]').innerText()).replace(/\s/g, '');
    if (js === php) same++; else console.log('    diferencia', qs.toString(), js, php);
  }
  ok('navegador y servidor coinciden en ' + n + ' combinaciones', same === n, same + '/' + n);

  await page.goto(B + 'crear.php?ia=si&areas[]=C&uC=4&sC=3&ev=no');
  await page.click('[data-copy-text]');
  ok('copiar declaración', await copied(page, '[data-copy-text]'));

  const insecure = await browser.newPage();
  await insecure.goto(B.replace(/127\.0\.0\.1|localhost/, 'oride.test') + 'crear.php?ia=no');
  ok('la prueba sin HTTPS es un contexto no seguro', !(await insecure.evaluate(() => window.isSecureContext)));
  await insecure.click('[data-copy-code]');
  ok('copiar funciona sin HTTPS', await copied(insecure, '[data-copy-code]'));

  // Lector de códigos
  await page.goto(B + 'codigo.php');
  await page.fill('#c', 'ia1 i2 c43 v1');
  ok('el lector entiende códigos al escribir', (await page.locator('[data-decoder-out]').innerText()).includes('Realicé cambios menores'));
  await page.fill('#c', 'IA1-C4-V1');
  ok('el lector avisa si falta el alcance', (await page.locator('[data-decoder-out] .alert').innerText()).includes('falta el alcance'));
  await page.fill('#c', 'IA0');
  ok('el lector entiende IA0', (await page.locator('[data-decoder-out]').innerText()).includes('No se utilizó'));
  await page.goto(B + 'codigo.php?c=IA1-D1-E43-R2-V0');
  await page.screenshot({ path: out + '/03-codigo.png', fullPage: true });

  // Formulario de mejora
  await page.goto(B + 'mejora.php');
  await page.click('text=Enviar comentario');
  ok('validación sin enviar', await page.isVisible('#nombre-error'));
  await page.fill('#nombre', 'Ana Prueba');
  await page.fill('#email', 'ana@example.com');
  await page.fill('#comentario', 'Me gustaría poder elegir el idioma de la declaración.');
  await page.waitForTimeout(3200);
  await page.click('text=Enviar comentario');
  ok('mensaje enviado', await page.isVisible('text=Hemos recibido tu comentario'));
  await page.screenshot({ path: out + '/04-mejora.png', fullPage: true });

  // Cookies y almacenamiento
  const cookies = await page.context().cookies();
  ok('ninguna cookie', cookies.length === 0);
  const storage = await page.evaluate(() => localStorage.length + sessionStorage.length);
  ok('nada en el almacenamiento del navegador', storage === 0);

  // Móvil y textos
  const mob = await browser.newPage({ viewport: { width: 375, height: 800 } });
  for (const p of ['', 'crear.php?ia=si&areas[]=C&areas[]=T&uC=4&sC=3&uT=1&ev=si', 'codigo.php?c=IA1-I2-C43-V1', 'como-funciona.php', 'preguntas.php', 'legal.php', 'mejora.php', 'marco-normativo.php']) {
    await mob.goto(B + p);
    const sw = await mob.evaluate(() => document.documentElement.scrollWidth);
    if (sw > 375) ok('móvil sin desbordar: ' + (p || 'inicio'), false, sw + 'px');
    const html = await mob.content();
    if (/ORIDE Code|Originality Declaration|Every creation|Mi espacio|Iniciar sesión|750%/.test(html)) ok('sin textos antiguos: ' + (p || 'inicio'), false);
  }
  ok('móvil revisado', true);
  await mob.goto(B + 'crear.php?ia=si&areas[]=I&uI=1&ev=no');
  ok('chip con el código en móvil', (await mob.locator('[data-chip-code]').innerText()) === 'IA1-I1-V0');
  await mob.screenshot({ path: out + '/05-movil.png', fullPage: true });

  ok('sin errores de JavaScript', errors.length === 0, errors.join(' | '));
  await browser.close();
  console.log(fails ? `\n${fails} fallos` : '\nTodo correcto');
  process.exit(fails ? 1 : 0);
})();
function browserLaunch() {
  const port = new URL(B).port || '80';
  return chromium.launch({ args: [`--host-resolver-rules=MAP oride.test 127.0.0.1:${port}`] });
}
async function copied(p, sel) {
  try { await p.waitForFunction(s => document.querySelector(s).textContent === 'Copiado', sel, { timeout: 2000 }); return true; } catch { return false; }
}
