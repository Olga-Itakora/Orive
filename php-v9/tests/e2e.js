/* Prueba en navegador (solo desarrollo). Requiere Node y Playwright.
   Uso: php -S 127.0.0.1:8098 tests/router.php   y   ORIDE_URL=http://127.0.0.1:8098/ node tests/e2e.js */
const { chromium } = require('playwright');
const fs = require('fs');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8098/';
const out = require('os').tmpdir() + '/oride-v9-e2e';
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
  const ig = async (p = page) => (await p.locator('.ig-box .ig-value').innerText()).trim();
  const igIs = async (want, p = page) => { try { await p.waitForFunction(w => { const e = document.querySelector('.ig-box .ig-value'); return e && e.innerText.trim() === w; }, want, { timeout: 3000 }); } catch {} return ig(p); };
  await page.goto(B + 'crear.php');
  ok('al principio solo se ve el tipo de obra', await page.isVisible('text=Tipo de obra') && !(await page.isVisible('text=¿Se ha utilizado inteligencia artificial')));
  ok('la caja del índice se ve desde el principio, pendiente', await page.isVisible('.ig-box') && (await ig()) === 'Pendiente de los valores de las actividades');
  await page.check('input[name=type][value=LI]');
  ok('tras el tipo aparece la pregunta de IA', await page.isVisible('text=¿Se ha utilizado inteligencia artificial'));
  ok('tríada parcial LI-IA–-EV–', (await waitCode('LI-IA–-EV–')) === 'LI-IA–-EV–');
  await page.check('input[name=ia][value=no]');
  ok('sin evidencias aún: pendiente', (await waitCode('LI-IA0-EV–')) === 'LI-IA0-EV–' && await page.isDisabled('[data-copy-code]'));
  await page.check('input[name=ev][value=si]');
  ok('tríada completa sin IA: LI-IA0-EV1', (await waitCode('LI-IA0-EV1')) === 'LI-IA0-EV1');
  ok('IA0: «No se utilizó IA», sin categoría', (await igIs('No se utilizó IA')) === 'No se utilizó IA' && (await page.locator('.ig-scale').count()) === 0);
  ok('las preguntas y opciones no muestran códigos', !/\b(IA[01]|IG[A-F]|EV[01]|LI|SC|DOC|GEN)\b/.test(await page.locator('form[data-builder]').innerText()));
  await page.check('input[name=ia][value=si]');
  ok('con IA y sin actividades: LI-IA1-EV1 (sin categoría), ya copiable', (await waitCode('LI-IA1-EV1')) === 'LI-IA1-EV1' && !(await page.isDisabled('[data-copy-code]')));
  ok('con IA y sin actividades: índice pendiente', (await igIs('Pendiente de los valores de las actividades')) === 'Pendiente de los valores de las actividades');
  ok('se ven las actividades de literatura y no las de otros tipos', await page.isVisible('text=Revisión de formato') && !(await page.isVisible('text=Masterización')));

  // Actividades en la segunda línea
  await page.check('input[name="acts_LI[]"][value=GEN]');
  ok('actividad sin grado: pendiente, categoría por calcular', (await waitCode('LI-IG–-EV1GEN–')) === 'LI-IG–-EV1GEN–' && (await ig()) === 'Pendiente de los valores de las actividades');
  await page.check('input[name=u_LI_GEN][value="0"]');
  await waitCode('LI-IA1-EV1GEN00');
  ok('IA declarada pero actividad a 0: aviso en la caja', await page.isVisible('[data-allzero]'));
  ok('y aviso en el formulario', await page.isVisible('[data-allzero-form]'));
  ok('el aviso pide explicar el uso fuera de las actividades', (await page.locator('[data-allzero]').innerText()).includes('fuera de las actividades establecidas'));
  await page.check('input[name=u_LI_GEN][value="2"]');
  await page.check('input[name=s_LI_GEN][value="4"]');
  await page.check('input[name="acts_LI[]"][value=DOC]');
  await page.check('input[name=u_LI_DOC][value="4"]');
  await page.check('input[name=s_LI_DOC][value="4"]');
  ok('código en dos líneas, con la categoría en el segundo término', (await waitCode('LI-IGB-EV1DOC44-GEN24')) === 'LI-IGB-EV1DOC44-GEN24' && JSON.stringify(await lines()) === JSON.stringify(['LI-IGB-EV1', 'DOC44-GEN24']));
  ok('sin aviso cuando hay uso real', !(await page.isVisible('[data-allzero]')));
  ok('sin la pregunta de proporción', (await page.locator('[data-showq]').count()) === 0 && !(await page.locator('main').innerText()).includes('¿Qué proporción aproximada'));
  ok('categoría como valor principal: «B — Bajo»', (await igIs('B — Bajo')) === 'B — Bajo', await ig());
  ok('nota: categoría calculada a partir del punto medio', (await page.locator('.ig-box .ig-note').first().innerText()).includes('Categoría calculada a partir del punto medio del índice estimado.'));
  ok('escala A–F con la categoría señalada', (await page.locator('.ig-scale li').count()) === 6 && (await page.locator('.ig-scale li[aria-current="true"] .ig-scale-l').innerText()) === 'B' && (await page.locator('.ig-scale li[aria-current="true"]').innerText()).includes('esta declaración'));
  ok('caja «Declaración ORIDE» con la caja «Código» y, debajo, la caja «Índice global»', (await page.locator('#decl-title').textContent()) === 'Declaración ORIDE' && (await page.locator('.code-box .hl-head').textContent()) === 'Código' && (await page.locator('.ig-box .hl-head').textContent()) === 'Índice global' && await page.evaluate(() => document.querySelector('.code-box').getBoundingClientRect().bottom <= document.querySelector('.ig-box').getBoundingClientRect().top));
  ok('copiar código dentro de la caja «Código»', await page.locator('.code-box [data-copy-code]').count() === 1);
  ok('desglose del código a petición', !(await page.isVisible('[data-explain]')) && (await page.locator('.code-details summary').innerText()).startsWith('Desglose del código'));
  await page.click('.code-details summary');
  ok('al abrir el desglose del código se ve la explicación', (await page.locator('[data-explain]').innerText()).includes('Literatura'));
  ok('caja del índice en otro color', await page.evaluate(() => getComputedStyle(document.querySelector('.ig-box')).backgroundColor !== getComputedStyle(document.querySelector('.decl-box')).backgroundColor));
  ok('la descarga JSON no se ve hasta abrir el desglose', !(await page.isVisible('.ig-box a[href*="format=json"]')));
  ok('sin el texto explicativo largo en la caja', !(await page.locator('.decl-box').innerText()).includes('No representa un porcentaje certificado'));
  await page.click('.ig-details summary');
  ok('al abrir el desglose aparece la descarga JSON', await page.isVisible('.ig-box a[href*="format=json"]'));
  const breakdown = await page.locator('.ig-details').innerText();
  ok('desglose con intervalo, punto medio, categoría, G, P, pesos, factores, alcance, valor exacto y versiones', ['Intervalo estimado: 17,71–23,4 %', 'Punto medio: 20,55 %', 'Categoría: B — Bajo', 'ORIDE-IG-CAT-1.0', 'Generación efectiva (G)', 'Influencia auxiliar (P)', 'peso 0,05', 'factor 0,2', 'alcance 75–100 %', 'Valor sin redondear', 'ORIDE-IG-1.0-experimental'].every(t => breakdown.includes(t)), breakdown.slice(0, 200));
  await page.click('[data-copy-index]');
  ok('copiar el índice', await copied(page, '[data-copy-index]'));
  ok('el índice copiado', (await page.getAttribute('[data-copy-index]', 'data-copy-index')) === 'Índice Global ORIDE: B — Bajo (18–23 % · ORIDE-IG-CAT-1.0 · ORIDE-IG-1.0-experimental)');
  await page.click('[data-copy-code]');
  ok('copiar código', await copied(page, '[data-copy-code]'));
  ok('el código copiado tiene dos líneas', (await page.getAttribute('[data-copy-code]', 'data-copy-code')) === 'ORIDE LI-IGB-EV1\nDOC44-GEN24');
  const text = await page.getAttribute('[data-copy-text]', 'data-copy-text');
  ok('la declaración copiada incluye el índice', text.includes('Índice Global ORIDE: B — Bajo (18–23 % · ORIDE-IG-CAT-1.0 · ORIDE-IG-1.0-experimental)'), text.split('\n').slice(-2).join(' / '));
  const link = await page.getAttribute('[data-copy-link]', 'data-copy-link');
  ok('el enlace lleva el código y las versiones', /oride\.php\?LI-IGB-EV1-DOC44-GEN24&ig=1\.0-experimental&igc=1\.0&lang=es$/.test(link), link);
  await page.screenshot({ path: out + '/01-crear.png', fullPage: true });

  // La página enlazada reproduce el mismo índice
  const indexInForm = await ig();
  await page.goto(link);
  ok('oride.php reproduce el índice del formulario', (await ig()) === indexInForm, await ig());
  ok('oride.php muestra el código en dos líneas', (await page.locator('h1').innerText()).replace(/\s+/g, '|') === 'LI-IGB-EV1|DOC44-GEN24');
  await page.screenshot({ path: out + '/02-oride.png', fullPage: true });
  await page.goto(B + 'oride.php?LIT-IA1-DOC44-GEN24-V1');
  ok('oride.php entiende el formato de versiones anteriores (conserva IA1, categoría derivada)', (await page.locator('h1').innerText()).replace(/\s+/g, '|') === 'LI-IA1-EV1|DOC44-GEN24' && (await ig()) === 'B — Bajo' && (await page.locator('main').innerText()).includes('Categoría derivada'));
  await page.goto(B + 'oride.php?LI-IGC-EV1-DOC44-GEN24');
  ok('oride.php rechaza una letra que no coincide con las actividades', (await page.locator('main').innerText()).includes('corresponde a IGB'));
  await page.goto(B + 'oride.php?LI-IA1-EV0-DOC00');
  ok('oride.php avisa si todo está a 0', (await page.locator('main').innerText()).includes('no en las actividades detalladas'));

  // Sin JavaScript y navegador = servidor
  const nojs = await newPage({ javaScriptEnabled: false });
  await nojs.goto(B + 'crear.php?lang=es&type=LI&ia=si&ev=si&acts_LI[]=DOC&u_LI_DOC=4&s_LI_DOC=4&acts_LI[]=GEN&u_LI_GEN=2&s_LI_GEN=4');
  ok('sin JavaScript el servidor da el mismo código e índice', (await code(nojs)) === 'LI-IGB-EV1DOC44-GEN24' && (await ig(nojs)) === indexInForm);
  const types = { SC: ['DOC', 'HIP', 'MET', 'DAT', 'ANA', 'INT', 'RED', 'REV', 'FOR'], MU: ['IDE', 'COM', 'ARR', 'GEN', 'INT', 'MIX', 'MAS', 'RES'], PH: ['PLA', 'CAP', 'REV', 'RET', 'MOD', 'GEN', 'REC'], IL: ['REF', 'IDE', 'COM', 'GEN', 'TRA', 'RET', 'AMP', 'VAR', 'REV', 'FIN'], AU: ['GUI', 'STO', 'GEN', 'VOZ', 'MON', 'EFX', 'DOB', 'POS'], TR: ['DOC', 'INT', 'GEN', 'ADA', 'REV', 'STY', 'LOC', 'VAL'], ACA: ['DOC', 'IDE', 'EST', 'ANA', 'ARG', 'GEN', 'REV', 'FOR', 'REF', 'VAL'] };
  let same = 0, n = 30;
  for (let i = 0; i < n; i++) {
    const t = Object.keys(types)[i % 7];
    const qs = new URLSearchParams(); qs.set('lang', 'es'); qs.set('type', t); qs.set('ia', Math.random() < 0.15 ? 'no' : 'si'); qs.set('ev', Math.random() < 0.5 ? 'si' : 'no');
    types[t].forEach(a => { if (Math.random() < 0.35) { qs.append('acts_' + t + '[]', a); const l = Math.floor(Math.random() * 6); qs.set(`u_${t}_${a}`, l); if (l >= 2) qs.set(`s_${t}_${a}`, Math.floor(Math.random() * 5)); } });
    await nojs.goto(B + 'crear.php?' + qs);
    const php = await code(nojs);
    const phpIdx = await ig(nojs);
    await page.goto(B + 'crear.php?' + qs);
    await page.check('input[name=ev][value=' + qs.get('ev') + ']'); // fuerza un recálculo en el navegador
    const js = await waitCode(php);
    const jsIdx = await igIs(phpIdx);
    if (js === php && jsIdx === phpIdx) same++; else console.log('    diferencia', qs.toString(), js, php, jsIdx, phpIdx);
  }
  ok(`navegador y servidor coinciden en ${n} combinaciones (código e índice)`, same === n, same + '/' + n);

  // Trabajos académicos (ACA)
  await page.goto(B + 'crear.php');
  const typeLabels = await page.locator('input[name=type]').evaluateAll(xs => xs.map(x => x.value));
  ok('ACA: Trabajos académicos es la décima opción del selector', typeLabels.length === 10 && typeLabels[9] === 'ACA' && await page.isVisible('label:has(input[name=type][value=ACA]) >> text=Trabajos académicos'));
  await page.check('input[name=type][value=ACA]');
  await page.check('input[name=ia][value=si]');
  await page.check('input[name=ev][value=si]');
  const acaActs = await page.locator('input[name="acts_ACA[]"]').evaluateAll(xs => xs.filter(x => x.offsetParent !== null).map(x => x.value));
  ok('ACA: las diez actividades visibles, en orden', JSON.stringify(acaActs) === JSON.stringify(['DOC', 'IDE', 'EST', 'ANA', 'ARG', 'GEN', 'REV', 'FOR', 'REF', 'VAL']), acaActs.join(','));
  ok('ACA: descripción del tipo junto a las actividades', (await page.locator('main').innerText()).includes('Trabajos elaborados en contextos educativos o formativos'));
  ok('ACA: sin IA → ACA-IA1-EV1 hasta detallar actividades', (await waitCode('ACA-IA1-EV1')) === 'ACA-IA1-EV1');
  await page.check('input[name="acts_ACA[]"][value=DOC]');
  await page.check('input[name=u_ACA_DOC][value="2"]');
  await page.check('input[name=s_ACA_DOC][value="1"]');
  await page.check('input[name="acts_ACA[]"][value=GEN]');
  await page.check('input[name=u_ACA_GEN][value="2"]');
  await page.check('input[name=s_ACA_GEN][value="4"]');
  ok('ACA: código ACA-IGB-EV1 DOC21-GEN24 con la categoría calculada', (await waitCode('ACA-IGB-EV1DOC21-GEN24')) === 'ACA-IGB-EV1DOC21-GEN24' && (await igIs('B — Bajo')) === 'B — Bajo');
  ok('ACA: enlace con el código', /oride\.php\?ACA-IGB-EV1-DOC21-GEN24&ig=/.test(await page.getAttribute('[data-copy-link]', 'data-copy-link')));
  await page.goto(B + 'oride.php?ACA-IGB-EV1-DOC21-GEN24&lang=en');
  ok('ACA en inglés: Academic work y misma categoría', (await page.locator('main').innerText()).includes('Academic work') && (await ig()) === 'B — Low');
  await page.goto(B + 'comprobar.php?r=ACA-IGA-EV1-DOC54-ARG10-GEN21-FOR54&d=ACA-IGA-EV1-DOC21-IDE22-ARG33-REV22&mode=digits');
  ok('ACA: el verificador aplica el límite de ARG aunque la categoría cumpla', (await page.locator('#vf-verdict').innerText()).includes('Declaración No Compatible') && (await page.locator('.vf-row-fail th').innerText()).includes('ARG'));
  await page.goto(B + 'documentacion.php#academicos');
  ok('Documentación: sección de trabajos académicos con tres ejemplos y el requisito', (await page.locator('#academicos .example-card').count()) === 3 && (await page.locator('#academicos').innerText()).includes('ACA-IGA-EV1'));

  // Descarga del cálculo
  const rec = await (await page.request.get(B + 'calc.php?code=LI-IGB-EV1-DOC44-GEN24&format=json')).json();
  ok('descarga JSON con versiones, parámetros y resultado sin redondear', rec.algorithm === 'ORIDE-IG-1.0-experimental' && rec.taxonomy === 'ORIDE Code 3.1' && rec.parameters.factors && Math.abs(rec.result.IG[1] - 23.4) < 0.01 && rec.category.category === 'B' && rec.category.version === 'ORIDE-IG-CAT-1.0' && Math.abs(rec.category.midpoint - 20.555) < 0.01);

  // Documentación y subpáginas con miga de pan
  for (const [pg, title] of [['codigo.php', 'Código ORIDE'], ['indice.php', 'Cómo se calcula el Índice Global ORIDE']]) {
    await page.goto(B + pg);
    ok(pg + ': miga de pan a Documentación', (await page.locator('.breadcrumb').innerText()).includes('Documentación') && (await page.locator('header [aria-current="page"]').innerText()) === 'Documentación');
  }
  await page.click('.breadcrumb a');
  ok('la miga de pan vuelve a Documentación', page.url().includes('documentacion.php'));
  ok('Documentación: sin tarjetas bajo el menú, enlaces solo en el texto', await page.locator('.doc-cards').count() === 0 && await page.locator('#codigo a[href*="codigo.php"]').count() >= 1 && await page.locator('#indice a[href*="indice.php"]').count() >= 1);
  ok('Documentación: salto de línea antes de «Cada código tiene su propia página»', await page.locator('#codigo p:has(br)', { hasText: 'Cada código tiene su propia página' }).count() === 1);
  await page.goto(B + 'indice.php');
  const idx = await page.locator('main').innerText();
  ok('página «Cómo se calcula»: fórmula, factores, limitaciones y versión', ['IG = G + (100 − G) · P', 'Limitaciones', 'ORIDE-IG-1.0-experimental', '0,85', '52,5', 'Categorías del Índice Global ORIDE', 'ORIDE-IG-CAT-1.0', '10 < m ≤ 25'].every(t => idx.includes(t)));

  // Menú superior
  await page.goto(B);
  const nav = (await page.locator('#site-nav ul').innerText()).split('\n').map(x => x.trim()).filter(Boolean);
  ok('menú: ¿Qué es ORIDE?, ¿Cómo se usa?, Instituciones, Documentación', JSON.stringify(nav) === JSON.stringify(['¿Qué es ORIDE?', '¿Cómo se usa?', 'Instituciones', 'Documentación']), nav.join(' | '));
  await page.goto(B + 'que-es.php');
  const what = await page.locator('main').innerText();
  ok('¿Qué es ORIDE?: honor, sin verificación, dos elementos', what.includes('basada en el honor, la honestidad y la ética personal de la persona autora') && what.includes('No hay un sistema de verificación ni una certificación detrás.') && what.includes('Código ORIDE') && what.includes('Índice Global ORIDE'));
  await page.goto(B + 'como-funciona.php');
  ok('¿Cómo se usa?: empieza por el formulario guiado', (await page.locator('main .lead').innerText()).includes('rellena el formulario guiado'));

  // Instituciones: requisito con índice y verificación
  await page.goto(B + 'instituciones.php');
  await page.check('input[name=type][value=SC]');
  await page.check('input[name=ia][value=si]');
  await page.check('input[name=ev][value=si]');
  await page.check('input[name="acts_SC[]"][value=RED]');
  await page.check('input[name=u_SC_RED][value="3"]');
  await page.check('input[name=s_SC_RED][value="1"]');
  ok('instituciones: código de referencia en dos líneas', (await waitCode('SC-IGA-EV1RED31')) === 'SC-IGA-EV1RED31');
  ok('instituciones: caja «Requisito ORIDE» con el Índice global máximo', (await page.locator('#decl-title').textContent()) === 'Requisito ORIDE' && (await page.locator('.ig-box .ig-head').textContent()) === 'Índice global máximo' && (await igIs('A — Muy bajo')) === 'A — Muy bajo');
  ok('instituciones: el texto copiado incluye el índice de referencia', (await page.getAttribute('[data-copy-text]', 'data-copy-text')).includes('Índice Global ORIDE máximo: A — Muy bajo'));
  ok('la pestaña «Verificar una entrega» lleva el código del requisito', /comprobar\.php\?r=SC-IGA-EV1-RED31/.test(await page.getAttribute('[data-verify-tab]', 'href')));
  await page.click('[data-verify-tab]');
  ok('al pulsar la pestaña, el primer campo se rellena con el código', page.url().includes('comprobar.php') && (await page.inputValue('#r')) === 'SC-IGA-EV1-RED31');
  await page.goBack();
  await waitCode('SC-IGA-EV1RED31');
  ok('instituciones: botón «Verificar una entrega» con el código', /comprobar\.php\?r=SC-IGA-EV1-RED31/.test(await page.getAttribute('[data-verify]', 'href')));
  await page.click('[data-verify]');
  ok('el botón lleva a la pestaña de verificación con el primer campo relleno', page.url().includes('comprobar.php') && (await page.inputValue('#r')) === 'SC-IGA-EV1-RED31');
  ok('el texto de Instituciones también aparece en la pestaña de verificación', (await page.locator('.inst-head').innerText()).includes('Establece qué uso de IA permites en las obras que recibes') && (await page.locator('.who-list li').count()) === 7);
  await page.goto(B + 'instituciones.php');
  ok('Instituciones: texto nuevo', (await page.locator('.inst-head').innerText()).includes('Una forma clara y sencilla de incorporar criterios de uso de IA'));
  ok('pestañas sin desplazamiento (sin flechas)', await page.evaluate(() => getComputedStyle(document.querySelector('.tabs')).overflowX === 'visible'));
  ok('listado de entidades sin aspecto de botón y separado de las pestañas', await page.evaluate(() => { const li = document.querySelector('.who-list li'), cs = getComputedStyle(li); const gap = document.querySelector('.tabs').getBoundingClientRect().top - document.querySelector('.who-list').getBoundingClientRect().bottom; return cs.borderTopWidth === '0px' && cs.backgroundColor === 'rgba(0, 0, 0, 0)' && gap >= 24; }));
  await page.click('.tabs a[href*="comprobar.php"]');
  ok('verificar: dos columnas (formulario a la izquierda, resultado a la derecha)', await page.evaluate(() => { const f = document.querySelector('.vf-form').getBoundingClientRect(), o = document.querySelector('.vf-out').getBoundingClientRect(); return f.right <= o.left && o.width > f.width; }));
  ok('verificar: tres comparaciones, Índices Globales por defecto', JSON.stringify(await page.locator('.vf-form input[name=mode]').evaluateAll(xs => xs.map(x => x.value))) === '["global","weighted","digits"]' && await page.isChecked('input[name=mode][value=global]') && (await page.locator('.vf-form').innerText()).includes('Índices Globales — recomendada'));
  ok('verificar: sin límite global en %, sin «Cómo se compara»', (await page.locator('#gl').count()) === 0 && !(await page.locator('main').innerText()).includes('Cómo se compara') && !(await page.locator('main').innerText()).includes('Límite del Índice global'));
  await page.fill('#r', 'SC-IGA-EV1-RED31');
  await page.fill('#d', 'SC-IGB-EV1\nRED51');
  await page.click('main form button.btn-primary');
  ok('verificar (Índices Globales): categoría superior → Declaración No Compatible', (await page.locator('#vf-verdict').innerText()).includes('Declaración No Compatible') && (await page.locator('.cmp-result').innerText()).includes('por encima del máximo permitido (A — Muy bajo)') && (await page.locator('.vf-table').count()) === 0);
  ok('verificar: sin «Estado de los códigos» ni «Tipo de obra:» en el resumen', !(await page.locator('.cmp-result').textContent()).includes('Estado de los códigos'));
  ok('verificar: resumen plegado', !(await page.evaluate(() => document.querySelector('.vf-sum-details').open)));
  await page.click('.vf-sum-details summary');
  const summary = await page.locator('.vf-summary').innerText();
  ok('verificar: resumen con códigos, categoría, actividades y resultado', ['Requisito ORIDE', 'Declaración ORIDE', 'Modalidad', 'Códigos', 'Categoría', 'Actividades', 'Resultado'].every(t => summary.includes(t)), summary.slice(0, 120));
  await page.click('.vf-head [data-copy-text]');
  ok('verificar: copiar resultado', await copied(page, '.vf-head [data-copy-text]') && (await page.getAttribute('.vf-head [data-copy-text]', 'data-copy-text')).includes('Resultado: Declaración No Compatible'));
  ok('verificar: nota de que ORIDE no verifica la veracidad', (await page.locator('.vf-note').innerText()).includes('ORIDE no verifica de manera independiente la veracidad'));
  await page.check('input[name=mode][value=digits]');
  await page.click('main form button.btn-primary');
  ok('verificar (grado y alcance): la tabla explica por qué', page.url().includes('mode=digits') && (await page.locator('.vf-table').innerText()).includes('Se pide como máximo «Incorporé los resultados transformados sustancialmente», pero la persona autora ha señalado «Incorporé los resultados prácticamente sin cambios».'));
  await page.fill('#d', 'SC-IGA-EV1-RED21');
  await page.click('main form button.btn-primary');
  ok('verificar: compatible si está por debajo', (await page.locator('#vf-verdict').innerText()).includes('Declaración Compatible'));
  await page.fill('#d', 'SC-IGC-EV1-RED21');
  await page.click('main form button.btn-primary');
  ok('verificar: los dos códigos tienen que ser válidos (error en la columna izquierda)', (await page.locator('#d-error').innerText()).includes('IGA') && (await page.locator('.cmp-result').count()) === 0);
  await page.goto(B + 'comprobar.php?r=ACA-IGA-EV1-DOC54-ARG10-GEN21-FOR54&d=LI-IGB-EV1-DOC44-GEN24');
  ok('verificar: sin el enlace «Crear un requisito» en la columna derecha', (await page.locator('.vf-out a', { hasText: 'Crear un requisito' }).count()) === 0);
  ok('verificar: «Editar requisito» y «Editar declaración» debajo de cada código', await page.evaluate(() => { const r = document.getElementById('r').closest('.field').nextElementSibling, d = document.getElementById('d').closest('.field').nextElementSibling; return r.textContent.trim() === 'Editar requisito' && d.textContent.trim() === 'Editar declaración'; }));
  await page.click('text=Editar requisito');
  ok('«Editar requisito» abre «Crear un requisito» con las respuestas marcadas', page.url().includes('instituciones.php') && (await waitCode('ACA-IGA-EV1DOC54-ARG10-GEN21-FOR54')) === 'ACA-IGA-EV1DOC54-ARG10-GEN21-FOR54' && await page.isChecked('input[name=u_ACA_ARG][value="1"]') && (await page.locator('.tabs [aria-current="page"]').innerText()).includes('Crear un requisito'));
  await page.goBack();
  await page.fill('#d', 'MU-IGB-EV0\nGEN24');
  await page.click('text=Editar declaración');
  ok('«Editar declaración» abre «Crea tu declaración» con el código escrito (sin verificar)', page.url().includes('crear.php') && (await waitCode('MU-IGB-EV0GEN24')) === 'MU-IGB-EV0GEN24' && await page.isChecked('input[name=type][value=MU]'));
  // Caso especial: GEN24 frente a GEN30, en las tres comparaciones
  await page.goto(B + 'comprobar.php?r=LI-IGB-EV1-GEN24&d=LI-IGA-EV1-GEN30&mode=digits');
  ok('GEN24/GEN30 por grado y alcance: no compatible, aunque la categoría sea inferior', (await page.locator('#vf-verdict').innerText()).includes('Declaración No Compatible') && (await page.locator('.vf-explain').first().innerText()).includes('Una categoría baja no compensa los límites por actividad'));
  await page.check('input[name=mode][value=weighted]');
  await page.click('main form button.btn-primary');
  ok('GEN24/GEN30 con actividades ponderadas: compatible', (await page.locator('#vf-verdict').innerText()).includes('Declaración Compatible') && page.url().includes('mode=weighted'));
  await page.goto(B + 'comprobar.php?r=LI-IGB-EV1-GEN24&d=LI-IGA-EV1-GEN30');
  ok('GEN24/GEN30 con Índices Globales: compatible', (await page.locator('#vf-verdict').innerText()).includes('Declaración Compatible'));
  await page.goto(B + 'comprobar.php?r=LI-IGB-EV0-GEN24&d=LI-IGB-EV0-DOC44-GEN24&mode=digits');
  ok('actividad no mencionada en el requisito: aviso de revisión manual', (await page.locator('#vf-verdict').innerText()).includes('Declaración Compatible') && (await page.locator('.cmp-result').innerText()).includes('Revisión manual'));
  await page.goto(B + 'comprobar.php?r=IL-IGA-EV1-REF10-RET10&d=IL-IGA-EV1-REF10-RET40&mode=digits');
  ok('RET10/RET40: no compatible por el grado de RET', (await page.locator('#vf-verdict').innerText()).includes('No Compatible') && (await page.locator('.vf-row-fail th').innerText()).includes('RET'));
  ok('verificar: título «Detalles» y sin la línea del tipo de obra cuando coincide', (await page.locator('.cmp-result h3', { hasText: 'Detalles' }).count()) === 1 && !(await page.locator('.cmp-result').innerText()).includes('Tipo de obra:'));
  ok('verificar: la tabla de actividades cabe en la columna sin desplazarse', await page.evaluate(() => { const r = document.querySelector('.table-scroll'); return r.scrollWidth <= r.clientWidth; }));
  await page.screenshot({ path: out + '/03-verificar.png', fullPage: true });
  ok('menú: Instituciones activo en Verificar', (await page.locator('header [aria-current="page"]').innerText()) === 'Instituciones');

  // Pie
  const foot = await page.locator('footer').innerText();
  ok('pie: Sobre este sitio, Aviso legal y Contacto en la misma línea', JSON.stringify(await page.locator('footer .footer-links a').allInnerTexts()) === JSON.stringify(['Sobre este sitio', 'Aviso legal y privacidad', 'Contacto']) && new Set(await page.locator('footer .footer-links a').evaluateAll(as => as.map(a => Math.round(a.getBoundingClientRect().top)))).size === 1);
  ok('pie sin correo, marco normativo ni índice', !foot.includes('info@itakora.com') && !foot.includes('Marco normativo') && !foot.includes('Índice Global'));
  ok('pie con la licencia CC BY-NC-ND 4.0', (await page.locator('footer .license').textContent()).replace(/\s+/g, ' ').includes('Oride © 2026 de Olga Revilla está bajo la licencia CC BY-NC-ND 4.0') && await page.locator('footer img.cc-icon').count() === 4);
  ok('pie sin estilos en línea (CSP)', (await page.locator('footer [style]').count()) === 0);

  // Documentación: preguntas
  await page.goto(B + 'marco-normativo.php');
  ok('Marco normativo lleva a las preguntas de Documentación', page.url().includes('documentacion.php'));
  const faq = await page.locator('main').textContent();
  ok('FAQ art. 50 con la información del marco normativo', faq.includes('ultrasuplantaciones') && faq.includes('2 de agosto de 2026') && await page.locator('main a[href*="eur-lex.europa.eu"]').count() === 1);
  ok('FAQ licencia: sin «no es una marca registrada» y con © 2026 Olga Revilla', !faq.includes('marca registrada') && faq.includes('El Código ORIDE, el Índice Global ORIDE, su metodología y los contenidos de este sitio son © 2026 Olga Revilla'));
  ok('FAQ enlace: para que la gente vea el código explicado', faq.includes('Usa el botón «Copiar enlace» para compartir una página en la que cualquiera puede ver tu código explicado'));
  ok('FAQ: ¿Qué es el Código ORIDE? y pesos', faq.includes('Una declaración de originalidad') && faq.includes('Generar contenido con IA pesa mucho más'));

  // Sobre este sitio, en primera persona
  await page.goto(B + 'sobre.php');
  const about = await page.locator('main').innerText();
  ok('Sobre este sitio en primera persona', about.includes('Hola, soy Olga Revilla Muñoz') && !about.includes('La autora de este sitio'));
  ok('código de este sitio', about.includes('ORIDE SW-IGE-EV1\nREQ21-ARQ44-COD44-REF33-PRU54-DOC34'));
  await page.goto(B);
  ok('inicio: sin «Copiar índice» y botón «Qué es una declaración ORIDE»', (await page.locator('[data-copy-index]').count()) === 0 && (await page.getAttribute('a:has-text("Qué es una declaración ORIDE")', 'href')).includes('que-es.php'));

  // Copiar sin HTTPS
  const insecure = await newPage();
  await insecure.goto(B.replace(/127\.0\.0\.1|localhost/, 'oride.test') + 'crear.php?type=LI&ia=no&ev=no');
  await insecure.click('[data-copy-code]');
  ok('copiar funciona sin HTTPS', await copied(insecure, '[data-copy-code]'));

  // Formulario de mejora
  await page.goto(B + 'mejora.php');
  await page.fill('#nombre', 'Ana Prueba'); await page.fill('#email', 'ana@example.com'); await page.fill('#comentario', 'Una idea para mejorar.');
  await page.waitForTimeout(3200);
  ok('Contacto: título y texto', (await page.locator('h1').innerText()) === 'Contacto' && (await page.locator('main .lead').innerText()).includes('Prometo leer y responder a todos los mensajes'));
  await page.click('text=Enviar mensaje');
  ok('mensaje enviado', await page.isVisible('text=He recibido tu mensaje'));
  ok('ninguna cookie', (await page.context().cookies()).length === 0);

  // Inglés
  const enp = await newPage({ locale: 'en-GB' });
  await enp.goto(B + 'crear.php?type=TR&ia=si&ev=no&acts_TR[]=GEN&u_TR_GEN=5&s_TR_GEN=4');
  ok('inglés: código y categoría (misma letra)', (await code(enp)) === 'TR-IGE-EV0GEN54' && (await enp.locator('.ig-head').textContent()) === 'Global index' && (await ig(enp)) === 'E — Very high');
  await enp.click('[data-lang-switch]');
  ok('cambio de idioma conserva el código y la categoría', (await code(enp)) === 'TR-IGE-EV0GEN54' && (await ig(enp)) === 'E — Muy alto' && (await enp.getAttribute('html', 'lang')) === 'es');
  for (const pg of ['', 'que-es.php', 'como-funciona.php', 'codigo.php', 'instituciones.php', 'comprobar.php?r=LI-IGB-EV1-GEN24&d=LI-IGC-EV0-GEN34&mode=digits', 'documentacion.php', 'sobre.php', 'legal.php', 'mejora.php', 'indice.php', 'oride.php?LI-IGA-EV1-DOC44', 'crear.php?type=LI&ia=si&ev=si&acts_LI[]=GEN&u_LI_GEN=4&s_LI_GEN=2']) {
    await enp.goto(B + pg + (pg.includes('?') ? '&' : '?') + 'lang=en');
    const t = await enp.locator('body').innerText();
    const m = t.match(/.{0,30}([¿¡]|ción\b|Código ORIDE|Crea tu|Sí\b|Obra de tipo|Índice|Pendiente).{0,30}/);
    if (m) ok('sin español en inglés: ' + (pg || 'inicio'), false, m[0]);
  }
  ok('versión inglesa revisada', true);

  // Móvil
  const mob = await newPage({ viewport: { width: 375, height: 800 } });
  for (const p of ['', 'que-es.php', 'crear.php?type=IL&ia=si&ev=si&acts_IL[]=GEN&u_IL_GEN=3&s_IL_GEN=2', 'codigo.php', 'indice.php', 'instituciones.php', 'comprobar.php?r=LI-IGB-EV1-GEN24&d=LI-IGC-EV0-GEN34&mode=digits', 'oride.php?IL-IGB-EV1-GEN32', 'sobre.php', 'documentacion.php']) {
    await mob.goto(B + p);
    const sw = await mob.evaluate(() => document.documentElement.scrollWidth);
    if (sw > 375) ok('móvil sin desbordar: ' + (p || 'inicio'), false, sw + 'px');
  }
  ok('móvil revisado', true);
  await mob.goto(B + 'comprobar.php?r=LI-IGC-EV1-GEN24-DOC44-EST54&d=LI-IGB-EV1-GEN30-DOC20-IDE32-EST54&mode=digits');
  ok('móvil: la tabla de actividades se muestra como tarjetas, sin desplazamiento horizontal', await mob.evaluate(() => { const r = document.querySelector('.table-scroll'); return r.scrollWidth <= r.clientWidth && getComputedStyle(document.querySelector('.vf-table td')).display === 'block'; }));
  await mob.goto(B + 'crear.php?type=IL&ia=si&ev=si&acts_IL[]=GEN&u_IL_GEN=3&s_IL_GEN=2');
  await mob.screenshot({ path: out + '/04-movil.png', fullPage: true });

  ok('sin errores de JavaScript', errors.length === 0, errors.join(' | '));
  await browser.close();
  console.log(fails ? `\n${fails} fallos` : '\nTodo correcto');
  process.exit(fails ? 1 : 0);
})();
