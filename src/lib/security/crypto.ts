import "server-only";
import { createHash, createHmac, randomBytes } from "node:crypto";
import { env } from "@/lib/env";

export function randomToken(bytes = 32): string {
  return randomBytes(bytes).toString("base64url");
}

export function sha256(input: string | Buffer): string {
  return createHash("sha256").update(input).digest("hex");
}

/** Huella con clave: permite correlacionar sin almacenar el dato (IP, sesión). */
export function fingerprint(input: string, purpose: string): string {
  return createHmac("sha256", env().APP_SECRET).update(`${purpose}:${input}`).digest("hex").slice(0, 32);
}
