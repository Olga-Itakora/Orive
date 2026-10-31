/* Prueba en navegador (solo desarrollo). Requiere Node y Playwright.
   Uso: php -S 127.0.0.1:8091 tests/router.php   y   ORIDE_URL=http://127.0.0.1:8091/ node tests/e2e.js */
const { chromium } = require('playwright');
const fs = require('fs');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8091/';
const out = require('os').tmpdir() + '/oride-v2-e2e';
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
  ok('al principio las preguntas A, B y C están ocultas', !(await page.isVisible('text=¿Para qué utilizaste la IA?')));
  ok('el botón copiar está desactivado', await page.isDisabled('[data-copy-code]'));
  await page.screenshot({ path: out + '/01-inicio.png', fullPage: true });

  await page.check('input[name=ia][value=no]');
  ok('«No» da A0B0C0', (await code()) === 'A0B0C0');
  ok('«No» no muestra más preguntas', !(await page.isVisible('text=¿Para qué utilizaste la IA?')));
  await page.click('[data-copy-code]');
  ok('copiar código', await copied(page, '[data-copy-code]'));

  await page.check('input[name=ia][value=si]');
  ok('«Sí» muestra la pregunta A', await page.isVisible('text=¿Para qué utilizaste la IA?'));
  await page.check('input[value=A4]');
  await page.check('input[value=A2]');
  ok('código parcial A24', (await code()).startsWith('A24B–'));
  await page.check('input[name=inc][value=si]');
  await page.check('input[value=B2]');
  await page.check('input[value=B1]');
  ok('aparece C solo para los B marcados', await page.isVisible('text=¿Qué hiciste con los cambios formales?') && !(await page.isVisible('text=¿Qué hiciste con el contenido generado por IA?')));
  await page.check('input[name=cB1][value=C5]');
  ok('pendiente mientras falte un C', (await page.locator('[data-status]').innerText()).includes('cambios formales'));
  await page.check('input[name=cB2][value=C1]');
  ok('código completo A24B12C15', (await code()) === 'A24B12C15');
  ok('la URL refleja las respuestas', page.url().includes('cB2=C1'));
  ok('explicación con el nuevo nombre de B3 no aparece si no se marca', !(await page.locator('[data-explain]').innerText()).includes('contenido generado'));
  await page.screenshot({ path: out + '/02-completo.png', fullPage: true });

  // El servidor calcula lo mismo que el navegador (enlace compartido / sin JavaScript).
  const shared = page.url();
  const p2 = await browser.newPage({ javaScriptEnabled: false });
  await p2.goto(shared);
  const serverCode = (await p2.locator('[data-code]').innerText()).replace(/\s/g, '');
  ok('sin JavaScript el servidor da el mismo código', serverCode === 'A24B12C15', serverCode);
  ok('sin JavaScript se ve el botón «Ver mi código»', await p2.isVisible('text=Ver mi código'));

  // Comparación navegador/servidor en muchas combinaciones.
  const combos = [];
  const As = ['A1', 'A2', 'A3', 'A4', 'A5'];
  for (let i = 0; i < 40; i++) {
    const a = As.filter(() => Math.random() < 0.5); if (!a.length) a.push('A3');
    const inc = Math.random() < 0.3 ? 'no' : 'si';
    const b = ['B1', 'B2', 'B3'].filter(() => Math.random() < 0.5); if (!b.length) b.push('B3');
    const c = {}; b.forEach(x => c[x] = 'C' + (1 + Math.floor(Math.random() * 5)));
    combos.push({ a, inc, b, c });
  }
  let same = 0;
  for (const k of combos) {
    const qs = new URLSearchParams(); qs.set('ia', 'si'); k.a.forEach(v => qs.append('a[]', v)); qs.set('inc', k.inc);
    if (k.inc === 'si') { k.b.forEach(v => qs.append('b[]', v)); Object.entries(k.c).forEach(([x, y]) => qs.set('c' + x, y)); }
    await page.goto(B + 'crear.php?' + qs);
    const js = await code();
    await p2.goto(B + 'crear.php?' + qs);
    const php = (await p2.locator('[data-code]').innerText()).replace(/\s/g, '');
    if (js === php) same++; else console.log('    diferencia', qs.toString(), js, php);
  }
  ok('navegador y servidor coinciden en 40 combinaciones', same === combos.length, same + '/40');

  await page.goto(B + 'crear.php?ia=si&a[]=A5&inc=si&b[]=B3&cB3=C4');
  await page.click('[data-copy-text]');
  ok('copiar declaración', await copied(page, '[data-copy-text]'));

  // Sin HTTPS (dominio http): debe usar el método alternativo.
  const insecure = await browser.newPage();
  await insecure.goto(B.replace(/127\.0\.0\.1|localhost/, 'oride.test') + 'crear.php?ia=no');
  ok('la prueba sin HTTPS es un contexto no seguro', !(await insecure.evaluate(() => window.isSecureContext)));
  await insecure.click('[data-copy-code]');
  ok('copiar funciona sin HTTPS', await copied(insecure, '[data-copy-code]'));
  ok('la explicación usa «Incorporación de contenido generado por IA»', (await page.locator('[data-explain]').innerText()).includes('Incorporación de contenido generado por IA'));

  // Explicador de códigos
  await page.goto(B + 'codigo.php');
  await page.fill('#c', 'a24b13c15');
  ok('el explicador lee códigos al escribir', (await page.locator('[data-decoder-out]').innerText()).includes('Ideas o sugerencias'));
  await page.fill('#c', 'A42B1C1');
  ok('el explicador avisa de códigos mal formados', (await page.locator('[data-decoder-out] .alert').innerText()).includes('orden creciente'));
  await page.fill('#c', 'A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2');
  ok('el explicador convierte la notación antigua', (await page.locator('[data-decoder-out] .code-line').innerText()).replace(/\s/g, '') === 'A245B123C135');
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
  for (const p of ['', 'crear.php?ia=si&a[]=A2&inc=no', 'codigo.php?c=A24B13C15', 'como-funciona.php', 'preguntas.php', 'legal.php', 'mejora.php', 'marco-normativo.php']) {
    await mob.goto(B + p);
    const sw = await mob.evaluate(() => document.documentElement.scrollWidth);
    if (sw > 375) ok('móvil sin desbordar: ' + (p || 'inicio'), false, sw + 'px');
    const html = await mob.content();
    if (/ORIDE Code|Originality Declaration|Every creation|Mi espacio|Iniciar sesión/.test(html)) ok('sin textos antiguos: ' + (p || 'inicio'), false);
  }
  ok('móvil revisado', true);
  await mob.goto(B + 'crear.php?ia=si&a[]=A2&inc=no');
  ok('chip con el código en móvil', (await mob.locator('[data-chip-code]').innerText()) === 'A2B0C0');
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
