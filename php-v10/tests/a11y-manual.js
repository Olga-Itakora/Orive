/* Comprobaciones de WCAG 2.2 AA que axe no cubre (solo desarrollo): 1.4.10, 1.4.12, 2.5.8, 2.1.1, 2.4.7.
   Uso: ORIDE_URL=http://127.0.0.1:8099/ node tests/a11y-manual.js */

const { chromium } = require('playwright');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8099/';
const pages = ['', 'que-es.php', 'como-funciona.php', 'crear.php?type=LI&ia=si&ev=si&u_LI_GEN=4&s_LI_GEN=3',
  'instituciones.php?type=SC&ia=si&ev=si', 'comprobar.php?r=SC-IGA-EV1-RED31&d=SC-IGB-EV0-RED51&mode=digits', 'comprobar.php?r=LI-IGB-EV1-GEN24-DOC44&d=LI-IGB-EV1-GEN30-EST54&mode=digits', 'documentacion.php', 'codigo.php',
  'indice.php', 'leer.php', 'oride.php?LI-IGB-EV1-DOC44-GEN24', 'sobre.php', 'legal.php', 'mejora.php'];
let fails = 0;
const bad = (m) => { fails++; console.log('  ✗ ' + m); };
(async () => {
  const b = await chromium.launch();
  // 1.4.10 Reflow (320 px) y 1.4.12 Text spacing
  const p = await b.newPage({ viewport: { width: 320, height: 700 }, locale: 'es-ES' });
  for (const pg of pages) {
    await p.goto(B + pg);
    if ((await p.evaluate(() => document.documentElement.scrollWidth)) > 320) bad('1.4.10 desbordamiento a 320 px: ' + (pg || 'inicio'));
    await p.addStyleTag({ content: '* { line-height: 1.5 !important; letter-spacing: 0.12em !important; word-spacing: 0.16em !important; } p { margin-bottom: 2em !important; }' }).catch(() => {});
    if ((await p.evaluate(() => document.documentElement.scrollWidth)) > 320) bad('1.4.12 espaciado de texto desborda: ' + (pg || 'inicio'));
  }
  // 2.5.8 Target size (mínimo 24×24, salvo enlaces dentro de texto)
  const d = await b.newPage({ viewport: { width: 1280, height: 900 }, locale: 'es-ES' });
  for (const pg of pages) {
    await d.goto(B + pg);
    await d.evaluate(() => document.querySelectorAll('details').forEach(x => { x.open = true; }));
    const small = await d.evaluate(() => [...document.querySelectorAll('a, button, input, select, textarea, summary, [tabindex]')]
      .filter(e => { const r = e.getBoundingClientRect(); if (!r.width || !r.height) return false;
        const inline = e.tagName === 'A' && getComputedStyle(e).display === 'inline' && /\S/.test((e.parentElement.innerText || '').replace(e.innerText, ''));
        // La etiqueta completa es el objetivo (.choice) o la etiqueta que hace de botón (.opt, .pill en v10)
        const lab = e.matches('.opt input, .pill input') ? e.nextElementSibling.getBoundingClientRect() : null;
        const choice = e.matches('.choice input') || (lab && lab.width >= 24 && lab.height >= 24);
        return !inline && !choice && (r.width < 24 || r.height < 24); })
      .map(e => e.tagName + '.' + e.className + ' «' + (e.innerText || e.value || '').slice(0, 20) + '» ' + Math.round(e.getBoundingClientRect().width) + '×' + Math.round(e.getBoundingClientRect().height)));
    small.forEach(s => bad('2.5.8 objetivo pequeño en ' + (pg || 'inicio') + ': ' + s));
  }
  // 2.1.1 / 2.4.7 Teclado y foco visible: recorre la página con Tab y comprueba que siempre hay contorno de foco
  for (const pg of ['crear.php?type=LI&ia=si&ev=si&u_LI_GEN=4&s_LI_GEN=3', 'comprobar.php', 'documentacion.php']) {
    await d.goto(B + pg);
    let noOutline = 0, steps = 0;
    for (let i = 0; i < 60; i++) {
      await d.keyboard.press('Tab');
      const info = await d.evaluate(() => { const e = document.activeElement; if (!e || e === document.body) return null; const s = getComputedStyle(e);
        const own = s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) > 0;
        const lab = e.closest('.opt, .pill') ? e.nextElementSibling : null; // v10: radio oculto, el foco se ve en la etiqueta
        const viaLabel = (e.closest('.choice') && getComputedStyle(e.closest('.choice')).outlineStyle !== 'none') || (lab && getComputedStyle(lab).outlineStyle !== 'none' && parseFloat(getComputedStyle(lab).outlineWidth) > 0);
        return { ok: own || viaLabel || s.boxShadow !== 'none', tag: e.tagName + ' ' + (e.innerText || e.name || '').slice(0, 20) }; });
      if (!info) continue;
      steps++;
      if (!info.ok) { noOutline++; if (noOutline < 4) bad('2.4.7 sin foco visible en ' + pg + ': ' + info.tag); }
    }
    if (!steps) bad('2.1.1 no se puede navegar con teclado: ' + pg);
  }
  // Teclado: crear una declaración solo con el teclado
  await d.goto(B + 'crear.php');
  await d.focus('input[name=type][value=LI]'); await d.keyboard.press('Space');
  await d.waitForTimeout(200);
  await d.focus('input[name=ia][value=no]'); await d.keyboard.press('Space');
  await d.focus('input[name=ev][value=si]'); await d.keyboard.press('Space');
  await d.waitForFunction(() => document.querySelector('[data-code]').innerText.replace(/\s/g, '') === 'LI-IA0-EV1', null, { timeout: 3000 }).catch(() => {});
  if ((await d.locator('[data-code]').innerText()).replace(/\s/g, '') !== 'LI-IA0-EV1') bad('2.1.1 el formulario no se puede completar con teclado');
  // Actividades con teclado: desplegable del grado y pastillas del alcance (flechas)
  await d.focus('input[name=ia][value=si]'); await d.keyboard.press('Space');
  await d.focus('#lv-LI-GEN'); await d.selectOption('#lv-LI-GEN', '3');
  await d.focus('#s_LI_GEN-0'); await d.keyboard.press('Space'); await d.keyboard.press('ArrowRight'); await d.keyboard.press('ArrowRight');
  await d.waitForFunction(() => document.querySelector('[data-code]').innerText.replace(/\s/g, '') === 'LI-IGB-EV1GEN32', null, { timeout: 3000 }).catch(() => {});
  if ((await d.locator('[data-code]').innerText()).replace(/\s/g, '') !== 'LI-IGB-EV1GEN32') bad('2.1.1 las actividades no se pueden completar con teclado: ' + (await d.locator('[data-code]').innerText()));
  const pillFocus = await d.evaluate(() => { const l = document.activeElement.nextElementSibling; return l && getComputedStyle(l).outlineStyle !== 'none'; });
  if (!pillFocus) bad('2.4.7 la pastilla con el foco no se ve');
  console.log(fails ? `\n${fails} problemas` : '\nSin problemas');
  await b.close();
})();
