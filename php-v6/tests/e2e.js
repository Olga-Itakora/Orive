/* Prueba en navegador (solo desarrollo). Requiere Node y Playwright.
   Uso: php -S 127.0.0.1:8095 tests/router.php   y   ORIDE_URL=http://127.0.0.1:8095/ node tests/e2e.js */
const { chromium } = require('playwright');
const fs = require('fs');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8095/';
const out = require('os').tmpdir() + '/oride-v6-e2e';
fs.mkdirSync(out, { recursive: true });
let fails = 0;
const ok = (name, cond, extra = '') => { console.log((cond ? '  ✓ ' : '  ✗ ') + name + (extra ? ' — ' + extra : '')); if (!cond) fails++; };
const copied = async (p, sel) => { try { await p.waitForFunction(s => /Copiado|Copied/.test(document.querySelector(s).textContent), sel, { timeout: 2000 }); return true; } catch { return false; } };

(async () => {
  const port = new URL(B).port || '80';
  const browser = await chromium.launch({ args: [`--host-resolver-rules=MAP oride.test 127.0.0.1:${port}`] });
  const errors = [];
  const newPage = async (opts = {}) => { const p = await browser.newPage({ locale: 'es-ES', ...opts }); p.on('pageerror', e => errors.push(e.message)); p.on('console', m => { if (m.type() === 'error') errors.push(m.text()); }); return p; };
  const page = await newPage();
  const code = async (p = page) => (await p.locator('[data-code]').innerText()).replace(/\s/g, '');

  // Crea tu declaración
  await page.goto(B + 'crear.php');
  ok('al principio solo se ve el tipo de obra', await page.isVisible('text=Tipo de obra') && !(await page.isVisible('text=¿Se ha utilizado inteligencia artificial')));
  await page.check('input[name=type][value=LIT]');
  ok('tras el tipo aparece la pregunta de IA', await page.isVisible('text=¿Se ha utilizado inteligencia artificial'));
  ok('código parcial LIT-IA–', (await code()) === 'LIT-IA–');
  await page.check('input[name=ia][value=no]');
  ok('LIT + No = LIT-IA0', (await code()) === 'LIT-IA0');
  ok('IA0 no muestra actividades', !(await page.isVisible('text=Actividades (opcional)')));
  ok('IA0 pregunta por las evidencias', await page.isVisible('text=¿Conservas evidencias que permitan documentar que no has usado IA?'));
  await page.check('input[name=ev][value=si]');
  ok('IA0 con evidencias: LIT-IA0-V1', (await code()) === 'LIT-IA0-V1');
  ok('las preguntas y opciones no muestran códigos', !/\b(IA[01]|V[01]|LIT|SCI|DOC|GEN)\b/.test(await page.locator('form[data-builder]').innerText()));
  await page.check('input[name=ia][value=si]');
  ok('las evidencias se conservan al pasar a Sí: LIT-IA1-V1', (await code()) === 'LIT-IA1-V1');
  await page.check('input[name=ev][value=no]');
  await page.check('input[name=ia][value=no]');
  await page.check('input[name=ia][value=si]');
  ok('LIT + Sí, ya copiable', (await code()) === 'LIT-IA1-V0' && !(await page.isDisabled('[data-copy-code]')));
  ok('se ven las actividades de literatura y no las de otros tipos', await page.isVisible('text=Revisión de formato') && !(await page.isVisible('text=Masterización')));
  await page.check('input[name=ev][value=si]');
  ok('evidencias al final: LIT-IA1-V1', (await code()) === 'LIT-IA1-V1');
  ok('las preguntas de actividades no muestran códigos', !/\b(IA[01]|V[01]|LIT|DOC|GEN|EST|REV|FOR|IDE)\b/.test(await page.locator('form[data-builder]').innerText()));
  ok('aviso de evidencias', await page.isVisible('.create-form .alert >> text=medio para contactar'));
  await page.check('input[name="acts_LIT[]"][value=GEN]');
  ok('actividad sin grado: pendiente', (await page.locator('[data-status]').innerText()).includes('Generación de contenido') && await page.isDisabled('[data-copy-code]'));
  await page.check('input[name=u_LIT_GEN][value="1"]');
  ok('grado 1 sin alcance y con 0 final: GEN10', (await code()) === 'LIT-IA1-GEN10-V1' && (await page.locator('legend:visible', { hasText: '¿Qué parte de la obra está afectada?' }).count()) === 0);
  await page.check('input[name=u_LIT_GEN][value="2"]');
  ok('grado 2 pide el alcance', await page.locator('legend:visible', { hasText: '¿Qué parte de la obra está afectada?' }).count() === 1);
  await page.check('input[name=s_LIT_GEN][value="4"]');
  await page.check('input[name="acts_LIT[]"][value=DOC]');
  await page.check('input[name=u_LIT_DOC][value="4"]');
  await page.check('input[name=s_LIT_DOC][value="4"]');
  ok('código completo LIT-IA1-DOC44-GEN24-V1', (await code()) === 'LIT-IA1-DOC44-GEN24-V1', await code());
  await page.click('[data-copy-code]');
  ok('copiar código', await copied(page, '[data-copy-code]'));
  await page.click('[data-copy-link]');
  ok('copiar enlace', await copied(page, '[data-copy-link]'));
  const link = await page.getAttribute('[data-copy-link]', 'data-copy-link');
  ok('el enlace apunta a oride.php', /oride\.php\?LIT-IA1-DOC44-GEN24-V1&lang=es$/.test(link), link);
  await page.screenshot({ path: out + '/01-crear.png', fullPage: true });

  // Cambio de tipo: las actividades de otro tipo no se arrastran
  await page.check('input[name=type][value=SW]');
  ok('al cambiar de tipo, el código se adapta', (await code()) === 'SW-IA1-V1', await code());
  await page.check('input[name=type][value=LIT]');

  // Sin JavaScript y navegador = servidor
  const shared = page.url();
  const nojs = await newPage({ javaScriptEnabled: false });
  await nojs.goto(shared);
  ok('sin JavaScript el servidor da el mismo código', (await code(nojs)) === 'LIT-IA1-DOC44-GEN24-V1', await code(nojs));
  const types = { SCI: ['DOC', 'HIP', 'MET', 'DAT', 'ANA', 'INT', 'RED', 'REV', 'FOR'], MUS: ['IDE', 'COM', 'ARR', 'GEN', 'INT', 'MIX', 'MAS', 'RES'], PHO: ['PLA', 'CAP', 'REV', 'RET', 'MOD', 'GEN', 'REC'], IL: ['REF', 'IDE', 'COM', 'GEN', 'TRA', 'RET', 'AMP', 'VAR', 'REV', 'FIN'], AUD: ['GUI', 'STO', 'GEN', 'VOZ', 'MON', 'EFX', 'DOB', 'POS'], TRA: ['DOC', 'INT', 'GEN', 'ADA', 'REV', 'STY', 'LOC', 'VAL'] };
  let same = 0, n = 50;
  for (let i = 0; i < n; i++) {
    const t = Object.keys(types)[i % 6];
    const qs = new URLSearchParams(); qs.set('lang', 'es'); qs.set('type', t); qs.set('ia', Math.random() < 0.15 ? 'no' : 'si');
    if (Math.random() < 0.7) qs.set('ev', Math.random() < 0.5 ? 'si' : 'no');
    types[t].forEach(a => { if (Math.random() < 0.35) { qs.append('acts_' + t + '[]', a); const l = Math.floor(Math.random() * 6); qs.set(`u_${t}_${a}`, l); if (l >= 2 && Math.random() < 0.9) qs.set(`s_${t}_${a}`, Math.floor(Math.random() * 5)); } });
    await page.goto(B + 'crear.php?' + qs);
    await nojs.goto(B + 'crear.php?' + qs);
    const js = await code(), php = await code(nojs);
    if (js === php) same++; else console.log('    diferencia', qs.toString(), js, php);
  }
  ok(`navegador y servidor coinciden en ${n} combinaciones`, same === n, same + '/' + n);

  // Página de cada código
  await page.goto(B + 'oride.php?LIT-IA1-DOC44-GEN24-V1');
  const txt = await page.locator('main').innerText();
  ok('oride.php explica el código', txt.includes('Obra de tipo «Literatura» en la que se utilizó inteligencia artificial') && txt.includes('«Documentación»: se modificaron parcialmente los resultados (parte de la obra afectada: del 75% al 100%).') && txt.includes('conserva evidencias'), txt.split('\n').slice(0, 8).join(' / '));
  await page.screenshot({ path: out + '/02-oride.png', fullPage: true });
  await page.goto(B + 'oride.php?LIT-IA0-V1');
  ok('oride.php explica IA0 con evidencias', (await page.locator('main').innerText()).includes('conserva evidencias de su proceso de creación'));
  await page.goto(B + 'oride.php?lit:gen24-doc44-v');
  ok('oride.php acepta otro orden, «:» y la forma corta', (await page.locator('h1').innerText()) === 'LIT-IA1-DOC44-GEN24-V1');
  await page.goto(B + 'oride.php?LIT-IA1-DOC45');
  ok('oride.php explica los errores', (await page.locator('main .alert').innerText()).includes('alcance va de 0 a 4'));
  await page.goto(B + 'codigo.php');
  await page.fill('#c', 'sci-ia1-ana53-v0');
  await page.click('text=Leer código');
  ok('el lector de codigo.php lleva a oride.php', page.url().includes('oride.php') && (await page.locator('h1').innerText()) === 'SCI-IA1-ANA53-V0');

  // Instituciones
  await page.goto(B + 'instituciones.php');
  await page.check('input[name=type][value=SCI]');
  await page.check('input[name=ia][value=si]');
  await page.check('input[name="acts_SCI[]"][value=RED]');
  await page.check('input[name=u_SCI_RED][value="2"]');
  await page.check('input[name=s_SCI_RED][value="1"]');
  ok('instituciones: código de referencia', (await code()) === 'SCI-IA1-RED21');
  ok('instituciones: frase de máximo', (await page.locator('[data-intro]').innerText()).startsWith('El autor debe garantizar que el uso de la IA que hace no supera el siguiente código'));
  ok('instituciones: sin uso mínimo', (await page.locator('input[name=limit]').count()) === 0 && !(await page.locator('main').innerText()).includes('mínimo'));
  ok('instituciones: el texto copiado incluye la frase', (await page.getAttribute('[data-copy-text]', 'data-copy-text')).startsWith('El autor debe garantizar'));
  await page.screenshot({ path: out + '/03-instituciones.png', fullPage: true });

  // Pie, Sobre este sitio y preguntas
  await page.goto(B + 'sobre.php');
  const about = await page.locator('main').innerText();
  ok('Sobre: nacimiento del código', about.includes('creación literaria, de software y de investigación científica') && about.includes('comunidad de creadores'));
  const foot = await page.locator('footer').innerText();
  ok('pie con copyright y marca', /© 20\d\d Olga Revilla Muñoz/.test(foot) && foot.includes('ORIDE Code™ es una marca en trámite de registro') && foot.includes('centros de investigación públicos'));
  await page.goto(B + 'preguntas.php');
  ok('FAQ: no equivale a cumplir el art. 50', (await page.locator('main').textContent()).includes('no equivale a cumplir el artículo 50'));
  ok('FAQ: sin «¿Cómo se lee…?»', !(await page.locator('main').textContent()).includes('Cómo se lee LIT'));
  ok('menú con Instituciones', await page.isVisible('header >> text=Instituciones'));

  // Copiar sin HTTPS
  const insecure = await newPage();
  await insecure.goto(B.replace(/127\.0\.0\.1|localhost/, 'oride.test') + 'crear.php?type=LIT&ia=no');
  await insecure.click('[data-copy-code]');
  ok('copiar funciona sin HTTPS', await copied(insecure, '[data-copy-code]'));

  // Formulario de mejora
  await page.goto(B + 'mejora.php');
  await page.fill('#nombre', 'Ana Prueba'); await page.fill('#email', 'ana@example.com'); await page.fill('#comentario', 'Una idea para mejorar.');
  await page.waitForTimeout(3200);
  await page.click('text=Enviar comentario');
  ok('mensaje enviado', await page.isVisible('text=Hemos recibido tu comentario'));
  ok('ninguna cookie', (await page.context().cookies()).length === 0);

  // Inglés
  const enp = await newPage({ locale: 'en-GB' });
  await enp.goto(B + 'crear.php');
  await enp.check('input[name=type][value=TRA]');
  await enp.check('input[name=ia][value=si]');
  await enp.check('input[name="acts_TRA[]"][value=GEN]');
  ok('inglés: mensajes en inglés', (await enp.locator('[data-status]').innerText()).startsWith('Say how you used AI for “Translation generation”'));
  await enp.check('input[name=u_TRA_GEN][value="5"]');
  await enp.check('input[name=s_TRA_GEN][value="4"]');
  ok('inglés: mismo código', (await code(enp)) === 'TRA-IA1-GEN54');
  await enp.click('[data-lang-switch]');
  ok('cambio de idioma conserva el código', (await code(enp)) === 'TRA-IA1-GEN54' && (await enp.getAttribute('html', 'lang')) === 'es');
  for (const pg of ['', 'como-funciona.php', 'codigo.php', 'instituciones.php', 'preguntas.php', 'sobre.php', 'legal.php', 'marco-normativo.php', 'mejora.php', 'oride.php?LIT-IA1-DOC44-V1', 'crear.php?type=LIT&ia=si&ev=si']) {
    await enp.goto(B + pg + (pg.includes('?') ? '&' : '?') + 'lang=en');
    const t = await enp.locator('body').innerText();
    const m = t.match(/.{0,30}([¿¡]|ción\b|Código ORIDE|Crea tu|Sí\b|Obra de tipo).{0,30}/);
    if (m) ok('sin español en inglés: ' + (pg || 'inicio'), false, m[0]);
  }
  ok('versión inglesa revisada', true);

  // Móvil
  const mob = await newPage({ viewport: { width: 375, height: 800 } });
  for (const p of ['', 'crear.php?type=IL&ia=si&ev=si&acts_IL[]=GEN&u_IL_GEN=3&s_IL_GEN=2', 'codigo.php', 'instituciones.php', 'oride.php?IL-IA1-GEN32-V1', 'sobre.php']) {
    await mob.goto(B + p);
    const sw = await mob.evaluate(() => document.documentElement.scrollWidth);
    if (sw > 375) ok('móvil sin desbordar: ' + (p || 'inicio'), false, sw + 'px');
  }
  ok('móvil revisado', true);
  await mob.goto(B + 'crear.php?type=IL&ia=si&ev=si&acts_IL[]=GEN&u_IL_GEN=3&s_IL_GEN=2');
  await mob.screenshot({ path: out + '/04-movil.png', fullPage: true });

  ok('sin errores de JavaScript', errors.length === 0, errors.join(' | '));
  await browser.close();
  console.log(fails ? `\n${fails} fallos` : '\nTodo correcto');
  process.exit(fails ? 1 : 0);
})();
