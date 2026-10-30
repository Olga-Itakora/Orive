import { describe, expect, it } from "vitest";
import { canonicalize, sha256Hex } from "@/lib/integrity/canonical";
import { computeContentHash, disclose, SECTION_NAMES, SNAPSHOT_FORMAT, verifyDisclosed, type FullSnapshot } from "@/lib/integrity/disclosure";
import { generatePublicId, normalizePublicId, PUBLIC_ID_REGEX } from "@/lib/ids";

function snap(): FullSnapshot {
  const sections = {} as FullSnapshot["sections"];
  for (const [i, n] of SECTION_NAMES.entries()) sections[n] = { salt: `salt${i}`.padEnd(32, "0"), data: { n, value: i, nested: { b: 1, a: [true, null, "ñ"] } } };
  return { format: SNAPSHOT_FORMAT, sections };
}

describe("JSON canónico", () => {
  it("ordena claves y elimina espacios", () => {
    expect(canonicalize({ b: 1, a: { d: [1, "x"], c: null } })).toBe('{"a":{"c":null,"d":[1,"x"]},"b":1}');
  });
  it("es independiente del orden de inserción", () => {
    expect(canonicalize({ x: 1, y: 2 })).toBe(canonicalize({ y: 2, x: 1 }));
  });
  it("rechaza decimales y undefined", () => {
    expect(() => canonicalize({ a: 1.5 })).toThrow();
    expect(() => canonicalize({ a: undefined })).toThrow();
  });
  it("SHA-256 conocido", async () => {
    expect(await sha256Hex("abc")).toBe("ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad");
  });
});

describe("Divulgación selectiva", () => {
  it("la huella se verifica con la instantánea completa y con vistas parciales", async () => {
    const s = snap();
    const { contentHash, digests } = await computeContentHash(s);
    expect((await verifyDisclosed(s, contentHash)).ok).toBe(true);
    const view = disclose(s, digests, ["meta", "classification"]);
    expect(view.sections.declarant).toBeUndefined();
    expect((await verifyDisclosed(view, contentHash)).ok).toBe(true);
  });
  it("detecta cualquier alteración de una sección divulgada", async () => {
    const s = snap();
    const { contentHash, digests } = await computeContentHash(s);
    const view = disclose(s, digests, ["meta", "work"]);
    (view.sections.work!.data as Record<string, unknown>).value = 999;
    const r = await verifyDisclosed(view, contentHash);
    expect(r.ok).toBe(false);
    expect(r.mismatchedSections).toContain("work");
  });
  it("detecta resúmenes manipulados de secciones ocultas", async () => {
    const s = snap();
    const { contentHash, digests } = await computeContentHash(s);
    const view = disclose(s, { ...digests, declarant: "0".repeat(64) }, ["meta"]);
    expect((await verifyDisclosed(view, contentHash)).ok).toBe(false);
  });
  it("la sal impide reproducir el resumen sin conocerla", async () => {
    const a = snap();
    const b = snap();
    b.sections.declarant.salt = "otra".padEnd(32, "1");
    expect((await computeContentHash(a)).digests.declarant).not.toBe((await computeContentHash(b)).digests.declarant);
  });
});

describe("Identificadores públicos", () => {
  it("formato ORV-XXXX-XXXX-XXXX y unicidad práctica", () => {
    const ids = new Set(Array.from({ length: 2000 }, generatePublicId));
    expect(ids.size).toBe(2000);
    for (const id of ids) expect(id).toMatch(PUBLIC_ID_REGEX);
  });
  it("normaliza entradas de usuario", () => {
    expect(normalizePublicId("orv-7f29-c4a8-km3z")).toBe("ORV-7F29-C4A8-KM3Z");
    expect(normalizePublicId("ORVO0IL7F29C4A8K")).toBeNull();
    expect(normalizePublicId("7F29C4A8KM3Z")).toBe("ORV-7F29-C4A8-KM3Z");
    expect(normalizePublicId("ORV-OOOO-IIII-LLLL")).toBe("ORV-0000-1111-1111");
    expect(normalizePublicId("../../etc")).toBeNull();
  });
});
