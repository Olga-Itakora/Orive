/**
 * Verificación independiente de una ORIVA Declaration exportada (JSON).
 *
 *   npx tsx scripts/verify-declaration.ts <fichero.json> [huella-esperada]
 *
 * Recalcula los resúmenes de cada sección y la huella raíz sin consultar
 * ninguna base de datos. Si se indica una huella esperada (p. ej. la impresa en
 * el PDF o la mostrada en /verify/{id}), comprueba que coincida.
 */
import { readFile } from "node:fs/promises";
import { verifyDisclosed } from "../src/lib/integrity/disclosure";

async function main() {
  const [file, expected] = process.argv.slice(2);
  if (!file) {
    console.error("Uso: npx tsx scripts/verify-declaration.ts <fichero.json> [huella-esperada]");
    process.exit(2);
  }
  const doc = JSON.parse(await readFile(file, "utf8"));
  const view = doc.snapshot ?? doc.view ?? doc;
  const target = expected ?? doc.verification?.contentHash ?? doc.contentHash;
  const r = await verifyDisclosed(view, target ?? "");
  console.log(`Huella recalculada: ${r.computedHash}`);
  console.log(`Huella esperada:    ${target ?? "(no indicada)"}`);
  if (r.mismatchedSections.length) console.log(`Secciones con resumen discrepante: ${r.mismatchedSections.join(", ")}`);
  console.log(r.ok ? "✔ Coincide: el contenido no ha sido alterado." : "✘ No coincide.");
  console.log("Nota: la integridad no acredita identidad, veracidad, originalidad, titularidad de derechos ni cumplimiento legal.");
  process.exit(r.ok ? 0 : 1);
}
main();
