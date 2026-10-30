/* Prueba de extremo a extremo (solo desarrollo). Requiere Node y Playwright.
   Uso: ORIDE_URL=http://127.0.0.1:8090/ ORIDE_ADMIN_EMAIL=… ORIDE_ADMIN_PASS=… node tests/e2e.js */
const { chromium } = require('playwright');
const fs = require('fs');
const B = process.env.ORIDE_URL || 'http://127.0.0.1:8090/';
const out = require('os').tmpdir() + '/oride-e2e';
fs.mkdirSync(out, { recursive: true });
(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ acceptDownloads: true });
  const page = await ctx.newPage();
  const errors = [];
  page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
  page.on('pageerror', e => errors.push(e.message));
  page.on('response', r => { if (r.status() >= 500) errors.push(r.status() + ' ' + r.url()); });
  const step = async (name) => { await page.screenshot({ path: `${out}/${name}.png`, fullPage: true }); console.log('✓', name, page.url().replace(B, '')); };
  const flash = async () => (await page.locator('.alert').allInnerTexts()).join(' | ');

  const email = `e2e${Date.now()}@example.com`;
  await page.goto(B + 'registro.php');
  await page.fill('#f-display_name', 'Ana Prueba');
  await page.fill('#f-email', email);
  await page.fill('#f-password', 'Contraseña-segura-1');
  await page.check('#f-accept');
  await page.click('form button.btn-primary');
  await step('01-registro');

  await page.goto(B + 'autor/obra.php');
  await page.fill('#f-title', 'El faro de las horas');
  await page.selectOption('#f-category_key', 'novel');
  await page.click('button[name=then][value=declare]');
  await step('02-paso-obra');
  await page.click('form button.btn-primary');
  await step('03-declarante');
  await page.fill('#f-declarant_name', 'Ana Prueba');
  await page.fill('#f-declarant_role', 'Autora');
  await page.check('#c-scope-WHOLE_WORK');
  await page.click('button[name=intent][value=next]');
  await page.check('#c-ai_usage-YES');
  await page.click('button[name=intent][value=next]');
  await step('04-herramientas');
  await page.fill('#new-name', 'Claude');
  await page.fill('#new-provider', 'Anthropic');
  await page.click('section.card form button:has-text("Añadir herramienta")');
  await page.click('a:has-text("Continuar con los usos")');
  await page.click('a:has-text("Añadir el primer uso")');
  await step('05-uso-vacio');
  await page.fill('#f-title', 'Revisión crítica del manuscrito');
  const toolOpt = await page.locator('#f-tool_id option').nth(1).getAttribute('value');
  await page.selectOption('#f-tool_id', toolOpt);
  await page.check('input[name="phases[]"] >> nth=0');
  await page.check('#c-functions-A3');
  await page.check('#c-functions-A4');
  await page.check('#c-primary_function-NONE');
  await page.fill('#f-activity', 'Pedí una lectura crítica del capítulo 3 y una corrección de estilo.');
  await page.check('#c-incorporation-INCORPORATED');
  await page.check('#c-contrib_types-B2');
  await page.check('#c-answer_B2-MINOR_EDITS');
  await page.fill('#f-description_B2', 'Acepté algunas correcciones y ajusté otras.');
  await page.click('button[name=intent][value=stay]');
  await step('06-uso-clasificado');
  console.log('  flash:', await flash());
  await page.click('button[name=intent][value=list]');
  await step('07-usos');
  await page.click('.actions a:has-text("Continuar")');
  await page.click('.actions a:has-text("Continuar")');
  await page.click('button[name=intent][value=next]');
  await step('08-revision');
  await page.check('#f-accept');
  await page.click('button:has-text("Emitir declaración")');
  await step('09-emitida');
  console.log('  flash:', await flash());
  const publicId = (await page.locator('.mono').allInnerTexts()).find(t => /^ORD-/.test(t));
  console.log('  publicId', publicId);
  const vid = new URL(page.url()).searchParams.get('id');

  // PDF
  const pdf = await ctx.request.get(B + 'autor/descargar.php?id=' + vid + '&tipo=pdf');
  const pdfBuf = await pdf.body();
  console.log('  pdf', pdf.status(), pdf.headers()['content-type'], pdfBuf.length, pdfBuf.slice(0, 5).toString());
  fs.writeFileSync(out + '/decl.pdf', pdfBuf);
  const json = await ctx.request.get(B + 'autor/descargar.php?id=' + vid + '&tipo=json');
  fs.writeFileSync(out + '/decl.json', await json.body());

  // Publicar
  await page.goto(B + 'autor/declaracion.php?id=' + vid + '&tab=publicacion');
  await page.check('input[name=show_name]');
  await page.click('button:has-text("Publicar declaración")');
  await step('10-publicada');
  console.log('  flash:', await flash());

  // Vista pública
  const pub = await browser.newPage();
  pub.on('pageerror', e => errors.push('pub ' + e.message));
  pub.on('console', m => { if (m.type() === 'error') errors.push('pub ' + m.text()); });
  await pub.goto(B + 'verificar.php?id=' + publicId);
  await pub.screenshot({ path: out + '/11-publica.png', fullPage: true });
  await pub.goto(B + 'verificar.php?id=' + publicId + '&tab=integridad');
  await pub.click('[data-verify-run]');
  await pub.waitForSelector('[data-verify-result].alert');
  console.log('  navegador:', await pub.locator('[data-verify-result]').innerText());
  await pub.setInputFiles('#f-file', out + '/decl.pdf');
  await pub.click('button:has-text("Comprobar documento")');
  console.log('  comprobar pdf:', (await pub.locator('#comprobar .alert').allInnerTexts()).join(' | '));
  await pub.screenshot({ path: out + '/12-integridad.png', fullPage: true });
  fs.writeFileSync(out + '/bad.pdf', Buffer.concat([pdfBuf, Buffer.from(' ')]));
  await pub.setInputFiles('#f-file', out + '/bad.pdf');
  await pub.click('button:has-text("Comprobar documento")');
  console.log('  comprobar alterado:', (await pub.locator('#comprobar .alert').allInnerTexts()).join(' | '));
  await pub.setInputFiles('#f-file', out + '/decl.json');
  await pub.click('button:has-text("Comprobar documento")');
  console.log('  comprobar json:', (await pub.locator('#comprobar .alert').allInnerTexts()).join(' | '));
  for (const p of ['qr.php?id=', 'distintivo.php?id=', 'api/declaracion.php?id=', 'documento.php?tipo=resumen&id=', 'documento.php?tipo=pdf&id=']) {
    const r = await ctx.request.get(B + p + publicId); console.log('  ', r.status(), r.headers()['content-type'], p);
  }
  await pub.goto(B + 'declaraciones.php');
  await pub.screenshot({ path: out + '/13-listado.png', fullPage: true });

  // Nueva versión
  await page.goto(B + 'autor/declaracion.php?id=' + vid + '&tab=versiones');
  await page.click('button:has-text("Crear nueva versión")');
  await step('14-nueva-version');
  await page.goto(B + page.url().replace(B, '').replace(/p=[a-z0-9]+/, 'p=revision'));
  await page.check('#f-accept');
  await page.click('button:has-text("Emitir declaración")');
  await step('15-v2-emitida');
  const old = await ctx.request.get(B + 'api/declaracion.php?id=' + publicId);
  console.log('  v1 tras v2:', old.status(), (await old.json()).status);
  await pub.goto(B + 'verificar.php?id=' + publicId);
  await pub.screenshot({ path: out + '/16-v1-sustituida.png', fullPage: true });

  // Revocar v2
  const vid2 = new URL(page.url()).searchParams.get('id');
  await page.goto(B + 'autor/declaracion.php?id=' + vid2 + '&tab=versiones');
  await page.click('button:has-text("Revocar esta declaración")');
  await page.fill('dialog[open] textarea[name=reason]', 'Error en los datos de la obra');
  await page.click('dialog[open] button.btn-danger');
  await step('17-revocada');
  console.log('  flash:', await flash());

  // Perfil y admin (como admin)
  await page.goto(B + 'autor/perfil.php'); await step('18-perfil');
  const adm = await browser.newContext(); const ap = await adm.newPage();
  ap.on('pageerror', e => errors.push('adm ' + e.message));
  ap.on('response', r => { if (r.status() >= 500) errors.push('adm ' + r.status() + ' ' + r.url()); });
  await ap.goto(B + 'entrar.php');
  await ap.fill('#f-email', process.env.ORIDE_ADMIN_EMAIL || 'admin@oridecode.com'); await ap.fill('#f-password', process.env.ORIDE_ADMIN_PASS || '');
  await ap.click('form button.btn-primary');
  for (const p of ['index', 'usuarios', 'taxonomia', 'textos', 'incidencias', 'auditoria']) {
    await ap.goto(B + 'admin/' + p + '.php'); await ap.screenshot({ path: `${out}/20-admin-${p}.png`, fullPage: true });
    console.log('  admin', p, (await ap.locator('h1').first().innerText()));
  }
  // Móvil
  const mob = await browser.newPage({ viewport: { width: 375, height: 800 } });
  for (const p of ['', 'verificar.php?id=' + publicId]) {
    await mob.goto(B + p);
    const sw = await mob.evaluate(() => document.documentElement.scrollWidth);
    console.log('  móvil', p || 'inicio', 'scrollWidth', sw);
  }
  await mob.screenshot({ path: out + '/30-movil-verificar.png', fullPage: true });
  console.log('ERRORES:', errors.length ? errors : 'ninguno');
  await browser.close();
})().catch(e => { console.error('FALLO', e.message); process.exit(1); });
