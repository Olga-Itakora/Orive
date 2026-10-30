/**
 * Serialización JSON canónica (subconjunto de RFC 8785 / JCS).
 *
 * - Claves de objeto ordenadas por unidades de código UTF-16 (orden de JS).
 * - Sin espacios.
 * - Cadenas serializadas con JSON.stringify (escapes mínimos compatibles con JCS).
 * - Solo se admiten enteros seguros como números (las instantáneas ORIVA no usan decimales),
 *   lo que evita las ambigüedades de representación de coma flotante.
 * - `undefined` no está permitido: usar null explícitamente.
 *
 * Este módulo es isomórfico (navegador y servidor) y no tiene dependencias.
 */
export type JsonValue = null | boolean | number | string | JsonValue[] | { [k: string]: JsonValue };

export function canonicalize(value: unknown): string {
  if (value === null) return "null";
  switch (typeof value) {
    case "boolean":
      return value ? "true" : "false";
    case "number":
      if (!Number.isSafeInteger(value)) throw new Error(`Canonical JSON: solo se admiten enteros seguros (recibido ${value}).`);
      return String(value);
    case "string":
      return JSON.stringify(value);
    case "object": {
      if (Array.isArray(value)) return `[${value.map(canonicalize).join(",")}]`;
      const obj = value as Record<string, unknown>;
      const keys = Object.keys(obj).sort();
      const parts: string[] = [];
      for (const k of keys) {
        const v = obj[k];
        if (v === undefined) throw new Error(`Canonical JSON: valor undefined en la clave "${k}".`);
        parts.push(`${JSON.stringify(k)}:${canonicalize(v)}`);
      }
      return `{${parts.join(",")}}`;
    }
    default:
      throw new Error(`Canonical JSON: tipo no admitido (${typeof value}).`);
  }
}

export async function sha256Hex(input: string | Uint8Array): Promise<string> {
  const bytes = typeof input === "string" ? new TextEncoder().encode(input) : input;
  const digest = await globalThis.crypto.subtle.digest("SHA-256", bytes as BufferSource);
  return [...new Uint8Array(digest)].map((b) => b.toString(16).padStart(2, "0")).join("");
}
