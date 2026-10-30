import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";
import { readEvidenceFile } from "@/lib/storage/evidence";

/** Descarga privada de una evidencia: solo su propietario. */
export async function GET(_: Request, { params }: { params: Promise<{ id: string; evidenceId: string }> }) {
  const user = await getCurrentUser();
  if (!user) return new NextResponse("No autorizado", { status: 401 });
  const { id, evidenceId } = await params;
  const e = await prisma.evidence.findFirst({ where: { id: evidenceId, versionId: id, version: { declaration: { ownerId: user.id } } } });
  if (!e?.storageKey) return new NextResponse("No encontrado", { status: 404 });
  const buf = await readEvidenceFile(e.storageKey);
  if (!buf) return new NextResponse("No encontrado", { status: 404 });
  return new NextResponse(new Uint8Array(buf), {
    headers: {
      "Content-Type": e.mimeType === "text/plain" ? "text/plain; charset=utf-8" : e.mimeType ?? "application/octet-stream",
      "Content-Disposition": `attachment; filename="${encodeURIComponent(e.fileName ?? "evidencia")}"`,
      "Cache-Control": "private, no-store",
      "X-Content-Type-Options": "nosniff",
      "Content-Security-Policy": "default-src 'none'; sandbox",
    },
  });
}
