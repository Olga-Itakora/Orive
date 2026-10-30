import { randomBytes } from "node:crypto";

/** Alfabeto Crockford base32 (sin I, L, O, U) para evitar confusiones al teclear. */
const ALPHABET = "0123456789ABCDEFGHJKMNPQRSTVWXYZ";

/**
 * Identificador documental público: ORV-XXXX-XXXX-XXXX (60 bits aleatorios).
 * No es secuencial ni derivable de identificadores internos. La unicidad se
 * garantiza con una restricción UNIQUE y reintento ante colisión.
 */
export function generatePublicId(): string {
  const bytes = randomBytes(8);
  let n = bytes.readBigUInt64BE(0) >> 4n; // 60 bits
  let out = "";
  for (let i = 0; i < 12; i++) {
    out = ALPHABET[Number(n & 31n)] + out;
    n >>= 5n;
  }
  return `ORV-${out.slice(0, 4)}-${out.slice(4, 8)}-${out.slice(8, 12)}`;
}

export const PUBLIC_ID_REGEX = /^ORV-[0-9A-HJKMNP-TV-Z]{4}-[0-9A-HJKMNP-TV-Z]{4}-[0-9A-HJKMNP-TV-Z]{4}$/;

/** Normaliza entradas de usuario (minúsculas, O→0, I/L→1, sin guiones). */
export function normalizePublicId(input: string): string | null {
  const raw = input.trim().toUpperCase().replace(/^ORV-?/, "").replace(/[\s-]/g, "").replace(/O/g, "0").replace(/[IL]/g, "1");
  if (!/^[0-9A-HJKMNP-TV-Z]{12}$/.test(raw)) return null;
  return `ORV-${raw.slice(0, 4)}-${raw.slice(4, 8)}-${raw.slice(8, 12)}`;
}
