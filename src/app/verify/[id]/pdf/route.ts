import { prisma } from "@/lib/db";
import { getPublicDeclaration } from "@/lib/declarations/public";

/**
 * Descarga pública de los PDF. El PDF contiene el nombre del declarante y
 * la descripción del proceso, así que solo se ofrece si se publicó lo necesario:
 * resumen → nombre + obra + código + resumen; completo → nombre + declaración completa.
 */
export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const d = await getPublicDeclaration(id);
  if (!d) return new Response("No encontrado", { status: 404 });
  const type = new URL(req.url).searchParams.get("type") === "full" ? "full" : "summary";
  const p = d.publication;
  const allowed = type === "full" ? p.showName && p.showFull : p.showName && (p.showFull || (p.showWork && p.showCode && p.showSummary));
  if (!allowed) return new Response("Documento no publicado", { status: 404 });
  const v = await prisma.declarationVersion.findUnique({ where: { publicId: d.publicId }, select: { pdfFull: type === "full", pdfSummary: type === "summary" } });
  const bytes = type === "full" ? v?.pdfFull : v?.pdfSummary;
  if (!bytes) return new Response("No encontrado", { status: 404 });
  return new Response(new Uint8Array(bytes), {
    headers: { "Content-Type": "application/pdf", "Content-Disposition": `inline; filename="${d.publicId}-${type === "full" ? "declaracion" : "resumen"}.pdf"`, "X-Content-Type-Options": "nosniff" },
  });
}
