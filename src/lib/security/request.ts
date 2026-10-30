import "server-only";
import { headers } from "next/headers";
import { fingerprint } from "./crypto";

/** IP del cliente (detrás de un proxy de confianza que fije X-Forwarded-For). */
export async function clientIp(): Promise<string> {
  const h = await headers();
  return h.get("x-forwarded-for")?.split(",")[0]?.trim() || h.get("x-real-ip") || "unknown";
}

export async function ipFingerprint(): Promise<string> {
  return fingerprint(await clientIp(), "ip");
}

/**
 * Comprobación de origen para manejadores de ruta que modifican estado
 * (las Server Actions de Next.js ya comparan Origin y Host).
 */
export function sameOrigin(req: Request): boolean {
  const origin = req.headers.get("origin");
  if (!origin) return false;
  const host = req.headers.get("x-forwarded-host") ?? req.headers.get("host");
  try {
    return new URL(origin).host === host;
  } catch {
    return false;
  }
}
