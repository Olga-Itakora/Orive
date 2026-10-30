import { PrismaClient } from "@prisma/client";

const globalForPrisma = globalThis as unknown as { prisma?: PrismaClient };

export const prisma =
  globalForPrisma.prisma ??
  new PrismaClient({ log: process.env.NODE_ENV === "development" ? ["warn", "error"] : process.env.NODE_ENV === "test" ? [] : ["error"] });

if (process.env.NODE_ENV !== "production") globalForPrisma.prisma = prisma;

/** ¿El error procede de un disparador de inmutabilidad? */
export function isImmutabilityError(e: unknown): boolean {
  return e instanceof Error && e.message.includes("ORIVA_IMMUTABLE");
}
