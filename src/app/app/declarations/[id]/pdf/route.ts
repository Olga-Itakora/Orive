import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/auth/session";
import { prisma } from "@/lib/db";

export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const user = await getCurrentUser();
  if (!user) return new NextResponse("No autorizado", { status: 401 });
  const { id } = await params;
  const type = new URL(req.url).searchParams.get("type") === "summary" ? "summary" : "full";
  const v = await prisma.declarationVersion.findFirst({
    where: { id, declaration: { ownerId: user.id }, status: { not: "DRAFT" } },
    select: { publicId: true, pdfFull: type === "full", pdfSummary: type === "summary" },
  });
  const bytes = type === "full" ? v?.pdfFull : v?.pdfSummary;
  if (!v || !bytes) return new NextResponse("No encontrado", { status: 404 });
  return new NextResponse(Buffer.from(bytes), {
    headers: {
      "Content-Type": "application/pdf",
      "Content-Disposition": `attachment; filename="${v.publicId}-${type === "full" ? "declaracion" : "resumen"}.pdf"`,
      "Cache-Control": "private, no-store",
      "X-Content-Type-Options": "nosniff",
    },
  });
}
