import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { exportDocument } from "@/lib/declarations/export";
import type { FullSnapshot } from "@/lib/integrity/disclosure";

export async function GET(_: Request, { params }: { params: Promise<{ id: string }> }) {
  const user = await getCurrentUser();
  if (!user) return new NextResponse("No autorizado", { status: 401 });
  const { id } = await params;
  const v = await prisma.declarationVersion.findFirst({ where: { id, declaration: { ownerId: user.id }, status: { not: "DRAFT" } }, include: { verification: true } });
  if (!v || !v.verification) return new NextResponse("No encontrado", { status: 404 });
  const doc = exportDocument({ snapshot: v.snapshot as unknown as FullSnapshot, contentHash: v.contentHash!, sectionDigests: v.verification.sectionDigests, pdfFullHash: v.verification.pdfFullHash, pdfSummaryHash: v.verification.pdfSummaryHash, status: v.status });
  return new NextResponse(JSON.stringify(doc, null, 2), {
    headers: { "Content-Type": "application/json; charset=utf-8", "Content-Disposition": `attachment; filename="${v.publicId}.json"`, "Cache-Control": "private, no-store" },
  });
}
