/* Auditoría WCAG 2.2 AA con axe-core (solo desarrollo). Requiere Node, Playwright y axe-core:
   npm install axe-core   y   ORIDE_URL=http://127.0.0.1:8097/ AXE_DIR=node_modules/axe-core node tests/a11y-axe.js */

const { chromium } = require('playwright');
const fs = require('fs');
const axeSrc = fs.readFileSync((process.env.AXE_DIR || __dirname + '/node_modules/axe-core') + '/axe.min.js', 'utf8');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8097/';
const pages = [
  '', 'que-es.php', 'como-funciona.php', 'crear.php',
  'crear.php?type=LI&ia=si&ev=si&acts_LI[]=GEN&u_LI_GEN=4&s_LI_GEN=3&acts_LI[]=DOC&u_LI_DOC=0',
  'instituciones.php', 'instituciones.php?type=SC&ia=si&ev=si&acts_SC[]=RED&u_SC_RED=3&s_SC_RED=1',
  'comprobar.php', 'comprobar.php?r=SC-IA1-EV1-RED31&d=SC-IA1-EV0-RED51', 'comprobar.php?r=x&d=y',
  'documentacion.php', 'codigo.php', 'indice.php', 'oride.php?LI-IA1-EV1-DOC44-GEN24', 'oride.php?zzz',
  'sobre.php', 'legal.php', 'mejora.php',
];
(async () => {
  const browser = await chromium.launch();
  let total = 0;
  for (const lang of ['es', 'en']) {
    for (const vp of [{ width: 1280, height: 900 }, { width: 375, height: 800 }]) {
      const page = await browser.newPage({ viewport: vp, locale: lang === 'en' ? 'en-GB' : 'es-ES', bypassCSP: true });
      for (const p of pages) {
        await page.goto(B + p + (p.includes('?') ? '&' : '?') + 'lang=' + lang);
        // Abre los desgloses y preguntas para revisar también su contenido.
        await page.evaluate(() => document.querySelectorAll('details').forEach(d => { d.open = true; }));
        await page.addScriptTag({ content: axeSrc });
        const res = await page.evaluate(async () => (await axe.run(document, {
          runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] },
        })).violations.map(v => ({ id: v.id, impact: v.impact, help: v.help, n: v.nodes.length, target: v.nodes.slice(0, 3).map(n => n.target.join(' ')).join(' | ') })));
        for (const v of res) { total++; console.log(`[${lang} ${vp.width}] ${p || 'inicio'} — ${v.id} (${v.impact}, ${v.n}): ${v.help} → ${v.target}`); }
      }
      await page.close();
    }
  }
  console.log(total ? `\n${total} incidencias` : '\nSin incidencias');
  await browser.close();
})();
