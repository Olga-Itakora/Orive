import { NextResponse } from "next/server";
import { getPublicDeclaration } from "@/lib/declarations/public";
import { CANONICALIZATION, HASH_ALGORITHM } from "@/lib/integrity/disclosure";
import { rateLimit } from "@/lib/security/rate-limit";
import { clientIp } from "@/lib/security/request";

/** API pública de verificación: solo contenido expresamente publicado. */
export async function GET(_: Request, { params }: { params: Promise<{ id: string }> }) {
  if (!(await rateLimit("api-verify", await clientIp(), 120, 60))) return NextResponse.json({ error: "rate_limited" }, { status: 429 });
  const { id } = await params;
  const d = await getPublicDeclaration(id);
  if (!d) return NextResponse.json({ error: "not_found", message: "No hay ninguna declaración pública con este identificador." }, { status: 404 });
  return NextResponse.json(
    {
      apiVersion: "1",
      publicId: d.publicId,
      status: d.status,
      issuedAt: d.issuedAt.toISOString(),
      revokedAt: d.revokedAt?.toISOString() ?? null,
      supersededBy: d.supersededBy?.isPublic ? d.supersededBy.publicId : d.supersededBy ? "(no pública)" : null,
      supersedes: d.supersedes?.isPublic ? d.supersedes.publicId : d.supersedes ? "(no pública)" : null,
      verification: { algorithm: HASH_ALGORITHM, canonicalization: CANONICALIZATION, contentHash: d.contentHash, pdfFullSha256: d.pdfFullHash, pdfSummarySha256: d.pdfSummaryHash },
      disclosedSections: d.visible,
      view: d.view,
      notice: "La verificación de integridad no acredita identidad, veracidad, originalidad, titularidad de derechos ni cumplimiento legal.",
    },
    { headers: { "Access-Control-Allow-Origin": "*", "Cache-Control": "public, max-age=60" } },
  );
}
