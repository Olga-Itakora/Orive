import "server-only";
import { hash, verify } from "@node-rs/argon2";

// Argon2id con parámetros recomendados por OWASP (m=19 MiB, t=2, p=1).
const OPTS = { memoryCost: 19456, timeCost: 2, parallelism: 1, outputLen: 32 } as const;

export function hashPassword(password: string): Promise<string> {
  return hash(password, OPTS);
}

export async function verifyPassword(hashed: string, password: string): Promise<boolean> {
  try {
    return await verify(hashed, password);
  } catch {
    return false;
  }
}

/** Hash ficticio para igualar tiempos cuando el usuario no existe. */
let dummy: Promise<string> | null = null;
export async function burnPasswordTime(password: string): Promise<void> {
  dummy ??= hashPassword("oriva-dummy-password");
  await verifyPassword(await dummy, password);
}
