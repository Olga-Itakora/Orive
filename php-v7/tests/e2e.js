/* Prueba en navegador (solo desarrollo). Requiere Node y Playwright.
   Uso: php -S 127.0.0.1:8096 tests/router.php   y   ORIDE_URL=http://127.0.0.1:8096/ node tests/e2e.js */
const { chromium } = require('playwright');
const fs = require('fs');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8096/';
const out = require('os').tmpdir() + '/oride-v7-e2e';
fs.mkdirSync(out, { recursive: true });
let fails = 0;
const ok = (name, cond, extra = '') => { console.log((cond ? '  ✓ ' : '  ✗ ') + name + (extra ? ' — ' + extra : '')); if (!cond) fails++; };
const copied = async (p, sel) => { try { await p.waitForFunction(s => /Copiado|Copied/.test(document.querySelector(s).textContent), sel, { timeout: 2000 }); return true; } catch { return false; } };

(async () => {
  const port = new URL(B).port || '80';
  const browser = await chromium.launch({ args: [`--host-resolver-rules=MAP oride.test 127.0.0.1:${port}`] });
  const errors = [];
  const newPage = async (opts = {}) => {
    const p = await browser.newPage({ locale: 'es-ES', ...opts });
    p.on('pageerror', e => errors.push(e.message));
    // Los iconos de la licencia vienen de creativecommons.org, que no está accesible en el entorno de pruebas.
    p.on('console', m => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push(m.text()); });
    return p;
  };
  const page = await newPage();
  const code = async (p = page) => (await p.locator('[data-code]').innerText()).replace(/\s/g, '');
  /** Espera a que la caja muestre el código esperado (la calcula el servidor). Devuelve el que haya. */
  const waitCode = async (want, p = page) => {
    try { await p.waitForFunction(w => document.querySelector('[data-code]').innerText.replace(/\s/g, '') === w, want, { timeout: 3000 }); } catch { /* se devuelve el actual */ }
    return code(p);
  };
  const lines = async (p = page) => (await p.locator('[data-code] .code-row').allInnerTexts()).map(t => t.replace(/\s/g, ''));

  // Crea tu declaración: tríada
  await page.goto(B + 'crear.php');
  ok('al principio solo se ve el tipo de obra', await page.isVisible('text=Tipo de obra') && !(await page.isVisible('text=¿Se ha utilizado inteligencia artificial')));
  await page.check('input[name=type][value=LIT]');
  ok('tras el tipo aparece la pregunta de IA', await page.isVisible('text=¿Se ha utilizado inteligencia artificial'));
  ok('tríada parcial LIT-IA–-V–', (await waitCode('LIT-IA–-V–')) === 'LIT-IA–-V–');
  await page.check('input[name=ia][value=no]');
  ok('sin evidencias aún: pendiente', (await waitCode('LIT-IA0-V–')) === 'LIT-IA0-V–' && await page.isDisabled('[data-copy-code]'));
  await page.check('input[name=ev][value=si]');
  ok('tríada completa sin IA: LIT-IA0-V1', (await waitCode('LIT-IA0-V1')) === 'LIT-IA0-V1');
  ok('IA0: índice 0', (await page.locator('.ig-value').innerText()) === '0');
  ok('las preguntas y opciones no muestran códigos', !/\b(IA[01]|V[01]|LIT|SCI|DOC|GEN)\b/.test(await page.locator('form[data-builder]').innerText()));
  await page.check('input[name=ia][value=si]');
  ok('con IA: LIT-IA1-V1, ya copiable', (await waitCode('LIT-IA1-V1')) === 'LIT-IA1-V1' && !(await page.isDisabled('[data-copy-code]')));
  ok('con IA y sin actividades: índice no calculable', (await page.locator('.ig-value').innerText()) === 'No calculable');
  ok('se ven las actividades de literatura y no las de otros tipos', await page.isVisible('text=Revisión de formato') && !(await page.isVisible('text=Masterización')));

  // Actividades en la segunda línea
  await page.check('input[name="acts_LIT[]"][value=GEN]');
  ok('actividad sin grado: pendiente', (await waitCode('LIT-IA1-V1GEN–')) === 'LIT-IA1-V1GEN–' && (await page.locator('[data-status]').innerText()).includes('Generación de contenido'));
  await page.check('input[name=u_LIT_GEN][value="0"]');
  await waitCode('LIT-IA1-V1GEN00');
  ok('IA declarada pero actividad a 0: aviso en la caja', await page.isVisible('[data-allzero]'));
  ok('y aviso en el formulario', await page.isVisible('[data-allzero-form]'));
  ok('el aviso pide explicar el uso fuera de las actividades', (await page.locator('[data-allzero]').innerText()).includes('fuera de las actividades establecidas'));
  await page.check('input[name=u_LIT_GEN][value="2"]');
  await page.check('input[name=s_LIT_GEN][value="4"]');
  await page.check('input[name="acts_LIT[]"][value=DOC]');
  await page.check('input[name=u_LIT_DOC][value="4"]');
  await page.check('input[name=s_LIT_DOC][value="4"]');
  ok('código en dos líneas', (await waitCode('LIT-IA1-V1DOC44-GEN24')) === 'LIT-IA1-V1DOC44-GEN24' && JSON.stringify(await lines()) === JSON.stringify(['LIT-IA1-V1', 'DOC44-GEN24']));
  ok('sin aviso cuando hay uso real', !(await page.isVisible('[data-allzero]')));
  ok('aparece la pregunta de proporción (hay generación)', await page.isVisible('[data-showq]'));
  const igIs = async (want) => { try { await page.waitForFunction(w => document.querySelector('.ig-value') && document.querySelector('.ig-value').innerText === w, want, { timeout: 3000 }); } catch {} return page.locator('.ig-value').innerText(); };
  ok('índice como intervalo (alcance en tramos)', (await igIs('17,7–23,4')) === '17,7–23,4', await page.locator('.ig-value').innerText());
  await page.check('input[name=q][value="6"]');
  ok('con «La totalidad»: G = 20 y P de documentación', (await igIs('22,6–23,4')) === '22,6–23,4', await page.locator('.ig-value').innerText());
  await page.fill('#qp', '100');
  await page.waitForFunction(() => /qp=100/.test(location.search), null, { timeout: 3000 }).catch(() => {});
  ok('porcentaje exacto en la URL', page.url().includes('qp=100'));
  await page.click('.ig-details summary');
  const breakdown = await page.locator('.ig-details').innerText();
  ok('desglose con G, P, pesos, factores, alcance y versión', ['Generación efectiva (G)', 'Influencia auxiliar (P)', 'peso 0,05', 'factor 0,2', 'alcance 75–100 %', 'ORIDE-IG-1.0-experimental'].every(t => breakdown.includes(t)), breakdown.slice(0, 200));
  ok('explicación del índice', (await page.locator('.ig-note').first().innerText()).includes('No representa un porcentaje certificado de autoría de IA'));
  await page.click('[data-copy-code]');
  ok('copiar código', await copied(page, '[data-copy-code]'));
  ok('el código copiado tiene dos líneas', (await page.getAttribute('[data-copy-code]', 'data-copy-code')) === 'ORIDE LIT-IA1-V1\nDOC44-GEN24');
  const text = await page.getAttribute('[data-copy-text]', 'data-copy-text');
  ok('la declaración copiada incluye el índice', /Índice Global ORIDE: [\d,–]+ \(ORIDE-IG-1\.0-experimental\)/.test(text), text.split('\n').slice(-2).join(' / '));
  const link = await page.getAttribute('[data-copy-link]', 'data-copy-link');
  ok('el enlace lleva el código, la proporción y la versión', /oride\.php\?LIT-IA1-V1-DOC44-GEN24&ig=1\.0-experimental&qp=100&lang=es$/.test(link), link);
  await page.screenshot({ path: out + '/01-crear.png', fullPage: true });

  // La página enlazada reproduce el mismo índice
  const indexInForm = await page.locator('.ig-value').innerText();
  await page.goto(link);
  ok('oride.php reproduce el índice del formulario', (await page.locator('.ig-value').innerText()) === indexInForm, await page.locator('.ig-value').innerText());
  ok('oride.php muestra el código en dos líneas', (await page.locator('h1').innerText()).replace(/\s+/g, '|') === 'LIT-IA1-V1|DOC44-GEN24');
  await page.screenshot({ path: out + '/02-oride.png', fullPage: true });
  await page.goto(B + 'oride.php?LIT-IA1-DOC44-GEN24-V1');
  ok('oride.php entiende el formato anterior', (await page.locator('h1').innerText()).replace(/\s+/g, '|') === 'LIT-IA1-V1|DOC44-GEN24');
  await page.goto(B + 'oride.php?LIT-IA1-V0-DOC00');
  ok('oride.php avisa si todo está a 0', (await page.locator('main').innerText()).includes('no en las actividades detalladas'));

  // Sin JavaScript y navegador = servidor
  const nojs = await newPage({ javaScriptEnabled: false });
  await nojs.goto(B + 'crear.php?lang=es&type=LIT&ia=si&ev=si&acts_LIT[]=DOC&u_LIT_DOC=4&s_LIT_DOC=4&acts_LIT[]=GEN&u_LIT_GEN=2&s_LIT_GEN=4&qp=100');
  ok('sin JavaScript el servidor da el mismo código e índice', (await code(nojs)) === 'LIT-IA1-V1DOC44-GEN24' && (await nojs.locator('.ig-value').innerText()) === indexInForm);
  const types = { SCI: ['DOC', 'HIP', 'MET', 'DAT', 'ANA', 'INT', 'RED', 'REV', 'FOR'], MUS: ['IDE', 'COM', 'ARR', 'GEN', 'INT', 'MIX', 'MAS', 'RES'], PHO: ['PLA', 'CAP', 'REV', 'RET', 'MOD', 'GEN', 'REC'], IL: ['REF', 'IDE', 'COM', 'GEN', 'TRA', 'RET', 'AMP', 'VAR', 'REV', 'FIN'], AUD: ['GUI', 'STO', 'GEN', 'VOZ', 'MON', 'EFX', 'DOB', 'POS'], TRA: ['DOC', 'INT', 'GEN', 'ADA', 'REV', 'STY', 'LOC', 'VAL'] };
  let same = 0, n = 30;
  for (let i = 0; i < n; i++) {
    const t = Object.keys(types)[i % 6];
    const qs = new URLSearchParams(); qs.set('lang', 'es'); qs.set('type', t); qs.set('ia', Math.random() < 0.15 ? 'no' : 'si'); qs.set('ev', Math.random() < 0.5 ? 'si' : 'no');
    types[t].forEach(a => { if (Math.random() < 0.35) { qs.append('acts_' + t + '[]', a); const l = Math.floor(Math.random() * 6); qs.set(`u_${t}_${a}`, l); if (l >= 2) qs.set(`s_${t}_${a}`, Math.floor(Math.random() * 5)); } });
    await nojs.goto(B + 'crear.php?' + qs);
    const php = await code(nojs);
    const phpIdx = await nojs.locator('.ig-value').count() ? await nojs.locator('.ig-value').innerText() : '';
    await page.goto(B + 'crear.php?' + qs);
    await page.check('input[name=ev][value=' + qs.get('ev') + ']'); // fuerza un recálculo en el navegador
    const js = await waitCode(php);
    const jsIdx = await page.locator('.ig-value').count() ? await page.locator('.ig-value').innerText() : '';
    if (js === php && jsIdx === phpIdx) same++; else console.log('    diferencia', qs.toString(), js, php, jsIdx, phpIdx);
  }
  ok(`navegador y servidor coinciden en ${n} combinaciones (código e índice)`, same === n, same + '/' + n);

  // Descarga del cálculo
  const dl = await page.request.get(B + 'calc.php?code=LIT-IA1-V1-DOC44-GEN24&qp=100&format=json');
  const rec = await dl.json();
  ok('descarga JSON con versión, parámetros y resultado', rec.algorithm === 'ORIDE-IG-1.0-experimental' && rec.parameters.factors && rec.result.IG[0] === 22.6 === false && Math.abs(rec.result.IG[1] - 23.4) < 0.01);

  // Página del índice
  await page.goto(B + 'indice.php');
  const idx = await page.locator('main').innerText();
  ok('página «Cómo se calcula»: fórmula, factores, limitaciones y versión', ['IG = G + (100 − G) · P', 'Limitaciones', 'ORIDE-IG-1.0-experimental', '0,85', '52,5'].every(t => idx.includes(t)));
  await page.screenshot({ path: out + '/03-indice.png', fullPage: true });

  // Instituciones
  await page.goto(B + 'instituciones.php');
  await page.check('input[name=type][value=SCI]');
  await page.check('input[name=ia][value=si]');
  await page.check('input[name=ev][value=si]');
  await page.check('input[name="acts_SCI[]"][value=RED]');
  await page.check('input[name=u_SCI_RED][value="2"]');
  await page.check('input[name=s_SCI_RED][value="1"]');
  ok('instituciones: código de referencia en dos líneas', (await waitCode('SCI-IA1-V1RED21')) === 'SCI-IA1-V1RED21');
  ok('instituciones: frase de máximo y sin pregunta de proporción', (await page.locator('[data-intro]').innerText()).startsWith('El autor debe garantizar') && (await page.locator('[data-showq]').count()) === 0);

  // Pie, licencia y preguntas
  const foot = await page.locator('footer').innerText();
  ok('pie con la licencia CC BY-NC-ND 4.0', (await page.locator('footer .license').textContent()).replace(/\s+/g, ' ').includes('Oride © 2026 de Olga Revilla está bajo la licencia CC BY-NC-ND 4.0') && await page.locator('footer a[href="https://creativecommons.org/licenses/by-nc-nd/4.0/deed.es"]').count() === 1 && await page.locator('footer img.cc-icon').count() === 4);
  ok('pie sin estilos en línea (CSP)', (await page.locator('footer [style]').count()) === 0);
  await page.goto(B + 'preguntas.php');
  ok('la dirección antigua de las preguntas lleva a Documentación', page.url().includes('documentacion.php') && (await page.locator('h1').innerText()) === 'Documentación');
  const faq = await page.locator('main').textContent();
  ok('Documentación: primero cómo funcionan el código y el índice', faq.indexOf('Cómo funciona el Código ORIDE') < faq.indexOf('Cómo funciona el Índice Global ORIDE') && faq.indexOf('Cómo funciona el Índice Global ORIDE') < faq.indexOf('¿Qué es el Código ORIDE?'));
  ok('FAQ: ¿Qué es el Código ORIDE? con definición breve', faq.includes('Una declaración de originalidad'));
  ok('FAQ: ¿Quién puede usar ORIDE? (autores e instituciones)', faq.includes('¿Quién puede usar ORIDE?') && faq.includes('exigir un mínimo de originalidad'));
  ok('FAQ: pesos de las actividades', faq.includes('¿Todas las actividades tienen el mismo peso') && faq.includes('Generar contenido con IA pesa mucho más'));
  ok('FAQ: ¿El código o el índice certifican algo?', faq.includes('¿El código o el índice certifican algo?') && !faq.includes('¿El código certifica algo?'));
  ok('FAQ: licencia, sin marca registrada', faq.includes('ORIDE no es una marca registrada') && faq.includes('Reconocimiento-NoComercial-SinObraDerivada') && faq.includes('No puedes distribuir versiones modificadas') && await page.locator('main a[href*="creativecommons.org/licenses/by-nc-nd/4.0"]').count() >= 1);
  ok('ninguna mención a la marca en trámite', !(await page.locator('body').textContent()).includes('trámite de registro'));
  ok('menú con Documentación', await page.isVisible('header >> text=Documentación'));

  // Copiar sin HTTPS
  const insecure = await newPage();
  await insecure.goto(B.replace(/127\.0\.0\.1|localhost/, 'oride.test') + 'crear.php?type=LIT&ia=no&ev=no');
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
  await enp.goto(B + 'crear.php?type=TRA&ia=si&ev=no&acts_TRA[]=GEN&u_TRA_GEN=5&s_TRA_GEN=4');
  ok('inglés: código e índice', (await code(enp)) === 'TRA-IA1-V0GEN54' && (await enp.locator('.ig-head').textContent()).includes('ORIDE Global Index'));
  await enp.click('[data-lang-switch]');
  ok('cambio de idioma conserva el código', (await code(enp)) === 'TRA-IA1-V0GEN54' && (await enp.getAttribute('html', 'lang')) === 'es');
  for (const pg of ['', 'como-funciona.php', 'codigo.php', 'instituciones.php', 'documentacion.php', 'sobre.php', 'legal.php', 'marco-normativo.php', 'mejora.php', 'indice.php', 'oride.php?LIT-IA1-V1-DOC44', 'crear.php?type=LIT&ia=si&ev=si&acts_LIT[]=GEN&u_LIT_GEN=4&s_LIT_GEN=2']) {
    await enp.goto(B + pg + (pg.includes('?') ? '&' : '?') + 'lang=en');
    const t = await enp.locator('body').innerText();
    const m = t.match(/.{0,30}([¿¡]|ción\b|Código ORIDE|Crea tu|Sí\b|Obra de tipo|Índice).{0,30}/);
    if (m) ok('sin español en inglés: ' + (pg || 'inicio'), false, m[0]);
  }
  ok('versión inglesa revisada', true);

  // Móvil
  const mob = await newPage({ viewport: { width: 375, height: 800 } });
  for (const p of ['', 'crear.php?type=IL&ia=si&ev=si&acts_IL[]=GEN&u_IL_GEN=3&s_IL_GEN=2', 'codigo.php', 'indice.php', 'instituciones.php', 'oride.php?IL-IA1-V1-GEN32', 'sobre.php']) {
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
