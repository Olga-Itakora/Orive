/**
 * Huella de integridad con divulgación selectiva.
 *
 * Una instantánea emitida se divide en secciones. Cada sección lleva una sal
 * aleatoria (128 bits) para que las secciones no publicadas no puedan adivinarse
 * por fuerza bruta a partir de su resumen.
 *
 *   sectionDigest = SHA-256( canonical({ section, salt, data }) )
 *   contentHash   = SHA-256( canonical({ format, sections: { [name]: sectionDigest } }) )
 *
 * La página pública divulga completas las secciones autorizadas y solo el
 * resumen de las demás; cualquiera puede recalcular `contentHash` y compararlo
 * con el registrado. Quien posea la exportación JSON completa puede recalcularlo
 * todo. Ver API.md → «Procedimiento de verificación».
 *
 * Qué demuestra: que el contenido no ha cambiado desde su registro.
 * Qué NO demuestra: identidad del autor, veracidad, originalidad, titularidad
 * de derechos ni cumplimiento legal.
 */
import { canonicalize, sha256Hex, type JsonValue } from "./canonical";

export const SNAPSHOT_FORMAT = "oriva-declaration/1";
export const HASH_ALGORITHM = "SHA-256";
export const CANONICALIZATION = "RFC8785-subset(integers-only)";

export const SECTION_NAMES = [
  "meta",
  "work",
  "declarant",
  "scope",
  "classification",
  "summary",
  "process",
  "article50",
  "statement",
  "evidence",
] as const;
export type SectionName = (typeof SECTION_NAMES)[number];

export interface SaltedSection {
  salt: string;
  data: JsonValue;
}

export interface FullSnapshot {
  format: typeof SNAPSHOT_FORMAT;
  sections: Record<SectionName, SaltedSection>;
}

/** Vista divulgada: secciones completas o solo su resumen. */
export interface DisclosedSnapshot {
  format: typeof SNAPSHOT_FORMAT;
  sections: Partial<Record<SectionName, SaltedSection>>;
  digests: Record<SectionName, string>;
}

export async function sectionDigest(name: SectionName, s: SaltedSection): Promise<string> {
  return sha256Hex(canonicalize({ section: name, salt: s.salt, data: s.data }));
}

export async function rootHash(digests: Record<SectionName, string>): Promise<string> {
  return sha256Hex(canonicalize({ format: SNAPSHOT_FORMAT, sections: digests }));
}

export async function computeDigests(snapshot: FullSnapshot): Promise<Record<SectionName, string>> {
  const out = {} as Record<SectionName, string>;
  for (const name of SECTION_NAMES) out[name] = await sectionDigest(name, snapshot.sections[name]);
  return out;
}

export async function computeContentHash(snapshot: FullSnapshot): Promise<{ contentHash: string; digests: Record<SectionName, string> }> {
  const digests = await computeDigests(snapshot);
  return { contentHash: await rootHash(digests), digests };
}

export function disclose(snapshot: FullSnapshot, digests: Record<SectionName, string>, visible: SectionName[]): DisclosedSnapshot {
  const sections: Partial<Record<SectionName, SaltedSection>> = {};
  for (const name of visible) sections[name] = snapshot.sections[name];
  return { format: SNAPSHOT_FORMAT, sections, digests };
}

export interface VerificationResult {
  ok: boolean;
  computedHash: string;
  mismatchedSections: SectionName[];
}

/** Verifica una vista divulgada (o completa) frente a una huella registrada. */
export async function verifyDisclosed(view: DisclosedSnapshot | FullSnapshot, expectedHash: string): Promise<VerificationResult> {
  if (view.format !== SNAPSHOT_FORMAT) return { ok: false, computedHash: "", mismatchedSections: [] };
  const given = "digests" in view ? view.digests : undefined;
  const digests = {} as Record<SectionName, string>;
  const mismatched: SectionName[] = [];
  for (const name of SECTION_NAMES) {
    const section = view.sections[name];
    if (section) {
      digests[name] = await sectionDigest(name, section);
      if (given && given[name] !== digests[name]) mismatched.push(name);
    } else if (given?.[name]) {
      digests[name] = given[name];
    } else {
      return { ok: false, computedHash: "", mismatchedSections: [name] };
    }
  }
  const computedHash = await rootHash(digests);
  return { ok: computedHash === expectedHash && mismatched.length === 0, computedHash, mismatchedSections: mismatched };
}

export function randomSalt(): string {
  const b = new Uint8Array(16);
  globalThis.crypto.getRandomValues(b);
  return [...b].map((x) => x.toString(16).padStart(2, "0")).join("");
}
