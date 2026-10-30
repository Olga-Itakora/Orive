import { expect, test, type Page } from "@playwright/test";

async function register(page: Page, name: string) {
  const email = `${name.toLowerCase()}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}@example.test`;
  await page.goto("/register");
  await page.getByLabel("Nombre profesional").fill(name);
  await page.getByLabel("Correo electrónico").fill(email);
  await page.getByLabel("Contraseña").fill("contraseña-segura-123");
  await page.getByRole("checkbox").check();
  await page.getByRole("button", { name: "Crear cuenta" }).click();
  await expect(page).toHaveURL(/\/app/);
  return email;
}

test("cabeceras de seguridad y CSP", async ({ request }) => {
  const res = await request.get("/");
  const h = res.headers();
  expect(h["content-security-policy"]).toContain("frame-ancestors 'none'");
  expect(h["content-security-policy"]).toMatch(/script-src 'self' 'nonce-/);
  expect(h["x-content-type-options"]).toBe("nosniff");
  expect(h["x-frame-options"]).toBe("DENY");
  expect(h["x-powered-by"]).toBeUndefined();
});

test("zona privada exige sesión y la administración exige rol", async ({ page }) => {
  await page.goto("/app");
  await expect(page).toHaveURL(/\/login/);
  await register(page, "Autor");
  await page.goto("/admin");
  await expect(page).toHaveURL(/\/app$/);
});

test("un autor no puede acceder a borradores ni documentos de otro (IDOR)", async ({ browser }) => {
  const a = await (await browser.newContext()).newPage();
  await register(a, "Propietaria");
  await a.goto("/app/works/new");
  await a.locator("#title").fill("Obra privada");
  await a.getByLabel("Tipo de obra").selectOption("poetry");
  await a.getByRole("button", { name: "Guardar obra" }).click();
  await expect(a.getByRole("heading", { name: "Obra privada" })).toBeVisible();
  const workUrl = new URL(a.url()).pathname;
  await a.getByRole("button", { name: "Nueva declaración" }).click();
  await expect(a).toHaveURL(/edit\/work/);
  const draftId = a.url().match(/declarations\/([^/]+)\/edit/)![1];

  const b = await (await browser.newContext()).newPage();
  await register(b, "Intrusa");
  for (const path of [workUrl, `/app/declarations/${draftId}`, `/app/declarations/${draftId}/edit/declarant`, `/app/declarations/${draftId}/edit/uses/new`]) {
    const r = await b.goto(path);
    expect(r?.status(), path).toBe(404);
  }
  for (const path of [`/app/declarations/${draftId}/pdf`, `/app/declarations/${draftId}/json`]) {
    const r = await b.request.get(path);
    expect(r.status(), path).toBe(404);
  }
});

test("la API pública no revela declaraciones privadas ni inexistentes", async ({ request }) => {
  const r = await request.get("/api/v1/declarations/ORV-0000-0000-0000");
  expect(r.status()).toBe(404);
  const code = await request.get("/api/v1/codes/A5B3C4D2");
  expect((await code.json()).segments).toHaveLength(4);
  const bad = await request.get("/api/v1/codes/A5B0C3D2");
  expect(bad.status()).toBe(400);
});

test("el administrador accede al panel y a la auditoría", async ({ page }) => {
  // Credenciales del administrador del seed (definidas en .env, nunca en el código).
  test.skip(!process.env.SEED_ADMIN_EMAIL || !process.env.SEED_ADMIN_PASSWORD, "Define SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD");
  await page.goto("/login");
  await page.getByLabel("Correo electrónico").fill(process.env.SEED_ADMIN_EMAIL!);
  await page.getByLabel("Contraseña").fill(process.env.SEED_ADMIN_PASSWORD!);
  await page.getByRole("button", { name: "Iniciar sesión" }).click();
  await expect(page).toHaveURL(/\/app/);
  await page.goto("/admin");
  await expect(page.getByRole("heading", { name: "Resumen" })).toBeVisible();
  await page.goto("/admin/taxonomy");
  await expect(page.getByRole("heading", { name: "ORIVA Code v1.0" })).toBeVisible();
  await page.goto("/admin/audit");
  await expect(page.getByText("declaration.issue").first()).toBeVisible();
});

test("formularios: validación al salir del campo, el error se borra al corregir y no se pierden datos", async ({ page }) => {
  await page.goto("/register");
  const email = page.getByLabel("Correo electrónico");
  // Campo intacto: escribir no muestra errores.
  await email.pressSequentially("nombre");
  await expect(page.locator("#email-error")).toBeHidden();
  // Al salir del campo, se valida.
  await email.blur();
  await expect(page.locator("#email-error")).toBeVisible();
  await expect(email).toHaveAttribute("aria-invalid", "true");
  // Al corregir, el error desaparece de inmediato.
  await email.pressSequentially("@example.test");
  await expect(page.locator("#email-error")).toBeHidden();
  // Error del servidor: los datos escritos se conservan.
  await page.getByLabel("Nombre profesional").fill("Persona Prueba");
  await page.getByLabel("Contraseña").fill("corta-pero");
  await page.getByRole("button", { name: "Crear cuenta" }).click();
  await expect(page.locator("#acceptTerms-error")).toBeVisible();
  await expect(page.getByLabel("Nombre profesional")).toHaveValue("Persona Prueba");
  await expect(email).toHaveValue("nombre@example.test");
});

test("las acciones destructivas piden confirmación con el nombre de la entidad", async ({ page }) => {
  await register(page, "Confirma");
  await page.goto("/app/works/new");
  await page.locator("#title").fill("Obra para borrar");
  await page.getByLabel("Tipo de obra").selectOption("poetry");
  await page.getByRole("button", { name: "Guardar obra" }).click();
  await page.getByLabel("Nombre del componente").fill("Poemas");
  await page.getByRole("button", { name: "Añadir componente" }).click();
  const trash = page.getByRole("button", { name: "Eliminar componente «Poemas»" });
  await expect(trash).toBeVisible();
  // Tooltip visible al enfocar.
  await trash.focus();
  await expect(page.getByRole("tooltip", { name: "Eliminar componente «Poemas»" })).toBeVisible();
  await trash.click();
  const dialog = page.getByRole("dialog", { name: "¿Eliminar el componente «Poemas»?" });
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole("button", { name: "Cancelar" })).toBeFocused();
  await page.keyboard.press("Escape");
  await expect(dialog).toBeHidden();
  await expect(trash).toBeFocused();
  await expect(page.getByText("Poemas · Texto")).toBeVisible();
  await trash.click();
  await dialog.getByRole("button", { name: "Eliminar componente" }).click();
  await expect(page.getByText("Poemas · Texto")).toHaveCount(0);
});
