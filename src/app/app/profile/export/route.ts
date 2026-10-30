import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { audit } from "@/lib/audit";

/** Exportación de los datos personales del usuario (acceso y portabilidad, arts. 15 y 20 RGPD). */
export async function GET() {
  const me = await getCurrentUser();
  if (!me) return new NextResponse("No autorizado", { status: 401 });
  const user = await prisma.user.findUniqueOrThrow({
    where: { id: me.id },
    select: {
      email: true,
      role: true,
      locale: true,
      createdAt: true,
      profile: { select: { displayName: true, artisticName: true, organization: true, bio: true, website: true, createdAt: true, updatedAt: true } },
      works: {
        select: {
          title: true, subtitle: true, categoryKey: true, description: true, creationPeriod: true, status: true, publicationDate: true, language: true, workVersion: true, publisher: true, identifierType: true, identifier: true, url: true, isCollective: true, createdAt: true,
          components: { select: { name: true, kind: true, description: true } },
          contributors: { select: { name: true, role: true } },
          declarations: {
            select: {
              versions: {
                select: {
                  versionNumber: true, publicId: true, status: true, visibility: true, issuedAt: true, contentHash: true, snapshot: true, revokedAt: true, revocationReason: true,
                  declarantName: true, declarantRole: true, scope: true, aiUsage: true, uncertaintyNote: true, processNarrative: true, observations: true, article50Answers: true,
                  tools: { select: { name: true, identified: true, provider: true, modelVersion: true, systemType: true, purpose: true, usagePeriod: true } },
                  uses: { select: { title: true, functions: true, primaryFunction: true, objective: true, activityDescription: true, resultDescription: true, incorporation: true, phases: { select: { phase: true } }, contributions: { select: { contributionType: true, interventionAnswer: true, retainsRecognizableElements: true, interventionCode: true, interventionDescription: true } } } },
                  evidence: { select: { kind: true, description: true, fileName: true, mimeType: true, sizeBytes: true, sha256: true, createdAt: true } },
                  publications: { select: { action: true, showName: true, showWork: true, showCode: true, showSummary: true, showFull: true, showEvidence: true, createdAt: true } },
                },
              },
            },
          },
        },
      },
      acceptances: { select: { acceptedAt: true, statementHash: true, legalText: { select: { key: true, version: true } } } },
      sessions: { select: { createdAt: true, expiresAt: true, userAgent: true } },
    },
  });
  await audit("user.data_export", "User", me.id, { actorId: me.id });
  return new NextResponse(JSON.stringify({ exportedAt: new Date().toISOString(), format: "oriva-personal-data-export/1", data: user }, null, 2), {
    headers: { "Content-Type": "application/json; charset=utf-8", "Content-Disposition": `attachment; filename="oriva-mis-datos.json"`, "Cache-Control": "private, no-store" },
  });
}
