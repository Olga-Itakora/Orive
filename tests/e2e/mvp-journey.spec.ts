import { expect, test, type Page } from "@playwright/test";
import { readFile } from "node:fs/promises";

/**
 * Recorrido de aceptación del MVP (sección 34): registro → obra → usos de IA →
 * perfiles ORIVA → emisión → PDF → identificador → publicación → consulta
 * pública → integridad → nueva versión → versión anterior preservada.
 */

const email = `e2e-${Date.now()}@example.test`;
const password = "una-frase-larga-de-prueba";

async function saveAndContinue(page: Page) {
  await page.getByRole("button", { name: "Guardar y continuar" }).click();
}

test("recorrido completo del MVP", async ({ page, browser }) => {
  // 1–2. Entrar y comprender
  await page.goto("/");
  await expect(page.getByRole("heading", { level: 1 })).toContainText("Every creation has a process");
  await expect(page.getByText("ORIVA no es una certificación oficial")).toBeVisible();

  // 3. Registro
  await page.getByRole("link", { name: "Crear cuenta" }).first().click();
  await page.getByLabel("Nombre profesional").fill("Lucía Prueba");
  await page.getByLabel("Correo electrónico").fill(email);
  await page.getByLabel("Contraseña").fill(password);
  await page.getByRole("checkbox").check();
  await page.getByRole("button", { name: "Crear cuenta" }).click();
  await expect(page.getByRole("heading", { name: /Hola, Lucía Prueba/ })).toBeVisible();

  // 4–5. Crear obra e identificar su modalidad
  await page.getByRole("link", { name: "Nueva declaración" }).first().click();
  await page.locator("#title").fill("El jardín de las horas");
  await page.getByLabel("Tipo de obra").selectOption("novel");
  await page.getByRole("button", { name: "Guardar y empezar la declaración" }).click();
  await expect(page.getByRole("heading", { name: "El jardín de las horas" })).toBeVisible();
  // Componentes de la obra híbrida
  await page.getByLabel("Nombre del componente").fill("Texto");
  await page.getByRole("button", { name: "Añadir componente" }).click();
  await expect(page.getByText("Texto · Texto")).toBeVisible();
  await page.getByLabel("Nombre del componente").fill("Ilustraciones");
  await page.locator("#comp-kind").selectOption("IMAGE");
  await page.getByRole("button", { name: "Añadir componente" }).click();
  await expect(page.getByText("Ilustraciones · Imagen / ilustración")).toBeVisible();

  await page.getByRole("button", { name: "Nueva declaración" }).click();
  await expect(page.getByRole("heading", { name: "Identificación de la obra" })).toBeVisible();
  await page.getByRole("button", { name: "Guardar y continuar" }).click();

  // Declarante y alcance
  await expect(page.getByRole("heading", { name: "Declarante y alcance" })).toBeVisible();
  await page.getByLabel("Tu rol en la obra").fill("Autora");
  await page.getByText("La obra completa", { exact: true }).click();
  await saveAndContinue(page);

  // 6. Uso general de IA
  await expect(page.getByRole("heading", { name: "Utilización general de IA" })).toBeVisible();
  await page.getByText("Sí", { exact: true }).click();
  await saveAndContinue(page);

  // Herramientas
  await expect(page.getByRole("heading", { name: "Herramientas de IA" })).toBeVisible();
  await page.getByLabel("Nombre de la herramienta").fill("Asistente de escritura X");
  await page.getByRole("button", { name: "Añadir herramienta" }).click();
  await expect(page.getByText("Asistente de escritura X").first()).toBeVisible();
  await page.getByLabel("Nombre de la herramienta").fill("Generador de imágenes Y");
  await page.getByRole("button", { name: "Añadir herramienta" }).click();
  await expect(page.getByText("Generador de imágenes Y").first()).toBeVisible();
  await page.getByRole("link", { name: "Continuar con los usos" }).click();

  // Uso 1: corrección lingüística aceptada → A4B2C1
  await page.getByRole("link", { name: "Añadir uso" }).click();
  await page.getByLabel("Título breve del uso").fill("Corrección ortográfica");
  await page.getByLabel("Parte de la obra").selectOption({ label: "Texto" });
  await page.getByLabel("Herramienta utilizada").selectOption({ label: "Asistente de escritura X" });
  await page.getByRole("checkbox", { name: /^Corrección/ }).check();
  await page.getByRole("checkbox", { name: /Corregir, editar, traducir/ }).check();
  await page.getByLabel("Qué hiciste con la IA").fill("Pedí al asistente que señalara erratas en cada capítulo.");
  await page.getByLabel("Qué resultado obtuviste").fill("Una lista de erratas y correcciones propuestas.");
  await page.getByText("Sí, algo se incorporó").click();
  await page.getByRole("checkbox", { name: /Cambios formales/ }).check();
  await page.getByText("Lo seleccioné o lo acepté sin cambios significativos").click();
  await page.getByLabel("Describe tu intervención").fill("Revisé y acepté una a una las correcciones.");
  // 8–10. Vista previa de la clasificación
  await expect(page.getByLabel("Vista previa de la clasificación")).toContainText("A4B2C1D2");
  await page.getByRole("button", { name: "Guardar uso" }).click();
  await expect(page.getByText("Uso guardado.")).toBeVisible();

  // Uso 2: ilustración transformada sin elementos reconocibles → A5B3C4
  await page.getByRole("link", { name: "Añadir uso" }).click();
  await page.getByLabel("Título breve del uso").fill("Ilustración de cubierta");
  await page.getByLabel("Parte de la obra").selectOption({ label: "Ilustraciones" });
  await page.getByLabel("Herramienta utilizada").selectOption({ label: "Generador de imágenes Y" });
  await page.getByRole("checkbox", { name: /^Generación de contenido/ }).check();
  await page.getByRole("checkbox", { name: /Generar contenido/ }).check();
  await page.getByLabel("Qué hiciste con la IA").fill("Generé varias imágenes a partir de una descripción de la escena.");
  await page.getByLabel("Qué resultado obtuviste").fill("Cuatro propuestas de composición.");
  await page.getByText("Sí, algo se incorporó").click();
  await page.getByRole("checkbox", { name: /Contenido expresivo generado/ }).check();
  await page.getByText("Lo transformé", { exact: true }).click();
  await page.getByText("No, el resultado es una expresión considerablemente diferente").click();
  await page.getByLabel("Describe tu intervención").fill("Pinté a mano una nueva composición a partir de una de las propuestas.");
  await expect(page.getByLabel("Vista previa de la clasificación")).toContainText("A5B3C4D2");
  await page.getByRole("button", { name: "Guardar uso" }).click();
  await expect(page.getByText("A2+A4+A5").or(page.getByText("A4+A5 | B2+B3 | C1+C4 | D2"))).toBeVisible();

  // Evidencias (opcional) y art. 50
  await page.getByRole("link", { name: "Continuar", exact: true }).click();
  await expect(page.getByRole("heading", { name: /Evidencias complementarias/ })).toBeVisible();
  await page.getByRole("link", { name: "Continuar", exact: true }).click();
  await expect(page.getByRole("heading", { name: "Orientación sobre el artículo 50" })).toBeVisible();
  await page.getByText("Los utilicé por mi cuenta, bajo mi propia autoridad").click();
  await page.getByText("Profesional", { exact: true }).click();
  await page.locator('input[name="syntheticMedia"][value="YES"]').check();
  await page.locator('input[name="realisticResemblance"][value="NO"]').check();
  await page.locator('input[name="evidentlyCreative"][value="YES"]').check();
  await page.locator('input[name="publicInterestText"][value="NO"]').check();
  await saveAndContinue(page);

  // 11–12. Revisión y aceptación
  await expect(page.getByRole("heading", { name: "Revisión y emisión" })).toBeVisible();
  await expect(page.getByText("A4+A5 | B2+B3 | C1+C4 | D2").first()).toBeVisible();
  await expect(page.getByText("Contenido sintético que no parece una ultrasuplantación").first()).toBeVisible();
  const issue = page.getByRole("button", { name: "Emitir declaración" });
  await issue.click(); // sin aceptar → error
  await expect(page.getByText(/debes aceptar expresamente/)).toBeVisible();
  await page.getByLabel(/He leído y acepto expresamente/).check();
  await issue.click();

  // 13–14. PDF e identificador
  await expect(page.getByText("Declaración emitida")).toBeVisible();
  const publicId = (await page.locator("text=/ORV-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}/").first().textContent())!.match(/ORV-[0-9A-Z-]{14}/)![0];
  const dl = page.waitForEvent("download");
  await page.getByRole("link", { name: "PDF completo" }).click();
  const pdfPath = await (await dl).path();
  const pdf = await readFile(pdfPath!);
  expect(pdf.subarray(0, 5).toString()).toBe("%PDF-");

  // Privada por defecto
  const anon = await browser.newContext();
  const anonPage = await anon.newPage();
  await anonPage.goto(`/verify/${publicId}`);
  await expect(anonPage.getByRole("heading", { name: "Declaración no disponible" })).toBeVisible();

  // 15–16. Publicar (pestaña «Publicación y distintivo»)
  await page.getByRole("link", { name: "Publicación y distintivo" }).click();
  await page.getByLabel("Mi nombre profesional").check();
  await page.getByRole("button", { name: "Publicar declaración" }).click();
  await expect(page.getByText("Publicación actualizada.")).toBeVisible();

  // 17–18. Consulta pública e integridad
  await anonPage.goto(`/verify/${publicId}`);
  await expect(anonPage.getByRole("heading", { name: "El jardín de las horas" })).toBeVisible();
  await expect(anonPage.getByText("Lucía Prueba").first()).toBeVisible();
  await expect(anonPage.getByText(email)).toHaveCount(0);
  await anonPage.getByRole("link", { name: "Integridad y descargas" }).click();
  await anonPage.getByRole("button", { name: "Recalcular la huella en mi navegador" }).click();
  await expect(anonPage.getByText("La huella recalculada coincide con la registrada.")).toBeVisible();
  await anonPage.locator("#fc-file").setInputFiles(pdfPath!);
  await anonPage.getByRole("button", { name: "Comprobar documento" }).click();
  await expect(anonPage.getByText(/idéntico al PDF completo registrado/)).toBeVisible();

  // 19. Nueva versión (pestaña «Integridad y versiones»)
  await page.getByRole("link", { name: "Integridad y versiones" }).click();
  await page.getByRole("button", { name: "Crear nueva versión" }).click();
  await expect(page.getByRole("heading", { name: "Declarante y alcance" })).toBeVisible();
  await page.getByLabel("Ámbito de responsabilidad").fill("Asumo las decisiones creativas y editoriales finales del texto y las ilustraciones.");
  await saveAndContinue(page);
  await page.goto(page.url().replace(/edit\/.*$/, "edit/review"));
  await page.getByLabel(/He leído y acepto expresamente/).check();
  await page.getByRole("button", { name: "Emitir declaración" }).click();
  await expect(page.getByText("Declaración emitida")).toBeVisible();
  const newId = (await page.locator("text=/ORV-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}/").first().textContent())!.match(/ORV-[0-9A-Z-]{14}/)![0];
  expect(newId).not.toBe(publicId);

  // 20. La versión anterior permanece preservada y verificable
  await anonPage.goto(`/verify/${publicId}`);
  await expect(anonPage.getByText("Esta versión ha sido sustituida")).toBeVisible();
  await anonPage.getByRole("link", { name: "Integridad y descargas" }).click();
  await anonPage.getByRole("button", { name: "Recalcular la huella en mi navegador" }).click();
  await expect(anonPage.getByText("La huella recalculada coincide con la registrada.")).toBeVisible();
  await anonPage.locator("#fc-file").setInputFiles(pdfPath!);
  await anonPage.getByRole("button", { name: "Comprobar documento" }).click();
  await expect(anonPage.getByText(/sustituida por una versión posterior/)).toBeVisible();
  await anon.close();
});
