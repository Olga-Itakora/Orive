import "server-only";
import { prisma } from "@/lib/db";
import { fingerprint } from "./crypto";

/**
 * Límite de frecuencia de ventana fija persistido en PostgreSQL, válido con
 * varias instancias de la aplicación. Devuelve true si la petición se permite.
 */
export async function rateLimit(bucket: string, subject: string, limit: number, windowSeconds: number): Promise<boolean> {
  if (process.env.ORIVA_DISABLE_RATE_LIMIT === "true") return true;
  const key = `${bucket}:${fingerprint(subject, "rl")}`;
  const now = new Date();
  const windowStart = new Date(Math.floor(now.getTime() / (windowSeconds * 1000)) * windowSeconds * 1000);
  const rows = await prisma.$queryRaw<{ count: number }[]>`
    INSERT INTO "RateLimit" ("key", "windowStart", "count") VALUES (${key}, ${windowStart}, 1)
    ON CONFLICT ("key") DO UPDATE SET
      "count" = CASE WHEN "RateLimit"."windowStart" = ${windowStart} THEN "RateLimit"."count" + 1 ELSE 1 END,
      "windowStart" = ${windowStart}
    RETURNING "count"`;
  return (rows[0]?.count ?? 1) <= limit;
}
