import "server-only";
import type { Prisma, PrismaClient } from "@prisma/client";
import { prisma } from "@/lib/db";

type Tx = Prisma.TransactionClient | PrismaClient;

export async function audit(
  action: string,
  entityType: string,
  entityId: string | null,
  opts: { actorId?: string | null; metadata?: Prisma.InputJsonValue; ipFingerprint?: string | null; tx?: Tx } = {},
): Promise<void> {
  const client = opts.tx ?? prisma;
  await client.auditLog.create({
    data: {
      action,
      entityType,
      entityId,
      actorId: opts.actorId ?? null,
      metadata: opts.metadata ?? {},
      ipFingerprint: opts.ipFingerprint ?? null,
    },
  });
}
