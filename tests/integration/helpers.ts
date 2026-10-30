import { randomUUID } from "node:crypto";
import { prisma } from "@/lib/db";
import { activeStatement, createDeclaration, issueVersion } from "@/lib/declarations/service";

export async function makeUser(name = "Autora Prueba") {
  return prisma.user.create({
    data: { email: `${randomUUID()}@test.local`, passwordHash: "x", profile: { create: { displayName: name } } },
  });
}

export async function makeWork(ownerId: string, opts: { title?: string; category?: string; collective?: boolean; components?: { name: string; kind: "TEXT" | "IMAGE" }[] } = {}) {
  return prisma.work.create({
    data: {
      ownerId,
      title: opts.title ?? "La casa de los relojes",
      categoryKey: opts.category ?? "novel",
      isCollective: opts.collective ?? false,
      components: { create: (opts.components ?? []).map((c, i) => ({ ...c, sortOrder: i })) },
    },
    include: { components: true },
  });
}

export async function draftNoAI(userId: string, workId: string) {
  const v = await createDeclaration(userId, workId);
  return prisma.declarationVersion.update({
    where: { id: v.id },
    data: { declarantName: "Autora Prueba", declarantRole: "Autora", scope: "WHOLE_WORK", aiUsage: "NO" },
  });
}

export async function issue(userId: string, versionId: string) {
  const st = await activeStatement();
  return issueVersion(userId, versionId, {
    accepted: true,
    statementHash: st.contentHash,
    sessionFingerprint: "test-session",
    ipFingerprint: null,
    verifyUrlFor: (id) => `http://localhost:3000/verify/${id}`,
  });
}

export const ALL_FLAGS = { showName: true, showWork: true, showCode: true, showSummary: true, showFull: true, showEvidence: false, evidenceConsent: false };
