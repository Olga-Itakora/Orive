/**
 * Consulta pública de declaraciones: solo contenido expresamente publicado.
 * Una declaración privada, retirada o inexistente produce la misma respuesta.
 */
import type { DeclarationPublication } from "@prisma/client";
import { prisma } from "@/lib/db";
import { normalizePublicId } from "@/lib/ids";
import { disclose, verifyDisclosed, type DisclosedSnapshot, type FullSnapshot, type SectionName } from "@/lib/integrity/disclosure";
import { sha256Hex } from "@/lib/integrity/canonical";

export function visibleSections(p: Pick<DeclarationPublication, "showName" | "showWork" | "showCode" | "showSummary" | "showFull" | "showEvidence">): SectionName[] {
  const out: SectionName[] = ["meta"];
  if (p.showWork || p.showFull) out.push("work");
  if (p.showName) out.push("declarant");
  if (p.showCode || p.showFull) out.push("scope", "classification");
  if (p.showSummary || p.showFull) out.push("summary");
  if (p.showFull) out.push("process", "article50", "statement");
  if (p.showEvidence) out.push("evidence");
  return out;
}

export interface PublicDeclaration {
  publicId: string;
  status: "ISSUED" | "SUPERSEDED" | "REVOKED";
  issuedAt: Date;
  revokedAt: Date | null;
  revocationReason: string | null;
  contentHash: string;
  pdfFullHash: string;
  pdfSummaryHash: string;
  view: DisclosedSnapshot;
  visible: SectionName[];
  publication: DeclarationPublication;
  supersededBy: { publicId: string; isPublic: boolean } | null;
  supersedes: { publicId: string; isPublic: boolean } | null;
  otherPublicVersions: { publicId: string; versionNumber: number; status: string }[];
}

export async function getPublicDeclaration(rawId: string): Promise<PublicDeclaration | null> {
  const publicId = normalizePublicId(rawId);
  if (!publicId) return null;
  const v = await prisma.declarationVersion.findUnique({
    where: { publicId },
    include: {
      verification: true,
      supersededBy: { select: { publicId: true, visibility: true, status: true } },
      supersedes: { select: { publicId: true, visibility: true } },
      declaration: { select: { versions: { where: { visibility: "PUBLIC", status: { not: "DRAFT" } }, select: { publicId: true, versionNumber: true, status: true }, orderBy: { versionNumber: "asc" } } } },
    },
  });
  if (!v || v.visibility !== "PUBLIC" || v.status === "DRAFT" || !v.snapshot || !v.verification) return null;
  const publication = await prisma.declarationPublication.findFirst({ where: { versionId: v.id }, orderBy: { createdAt: "desc" } });
  if (!publication || publication.action === "WITHDRAW") return null;
  const visible = visibleSections(publication);
  const snapshot = v.snapshot as unknown as FullSnapshot;
  const view = disclose(snapshot, v.verification.sectionDigests as Record<SectionName, string>, visible);
  const succ = v.supersededBy && v.supersededBy.status !== "DRAFT" ? v.supersededBy : null;
  return {
    publicId,
    status: v.status as PublicDeclaration["status"],
    issuedAt: v.issuedAt!,
    revokedAt: v.revokedAt,
    revocationReason: v.revocationReason,
    contentHash: v.contentHash!,
    pdfFullHash: v.verification.pdfFullHash,
    pdfSummaryHash: v.verification.pdfSummaryHash,
    view,
    visible,
    publication,
    supersededBy: succ?.publicId ? { publicId: succ.publicId, isPublic: succ.visibility === "PUBLIC" } : null,
    supersedes: v.supersedes?.publicId ? { publicId: v.supersedes.publicId, isPublic: v.supersedes.visibility === "PUBLIC" } : null,
    otherPublicVersions: v.declaration.versions.filter((x) => x.publicId && x.publicId !== publicId).map((x) => ({ publicId: x.publicId!, versionNumber: x.versionNumber, status: x.status })),
  };
}

export type FileCheckResult =
  | { kind: "pdf-full" | "pdf-summary"; match: true; status: string }
  | { kind: "json"; match: boolean; status: string; computedHash: string }
  | { kind: "none"; match: false };

/**
 * Comprueba un fichero aportado por quien verifica (PDF o JSON exportado)
 * contra el registro. Funciona también para declaraciones privadas: solo
 * confirma la coincidencia de una huella que el verificador ya posee, sin
 * revelar contenido. Respuesta idéntica para «no existe» y «no coincide».
 */
export async function checkFileAgainstRegistry(rawId: string, bytes: Uint8Array): Promise<FileCheckResult> {
  const publicId = normalizePublicId(rawId);
  if (!publicId) return { kind: "none", match: false };
  const v = await prisma.declarationVersion.findUnique({ where: { publicId }, include: { verification: true } });
  if (!v || !v.verification || v.status === "DRAFT") return { kind: "none", match: false };
  const hash = await sha256Hex(bytes);
  if (hash === v.verification.pdfFullHash) return { kind: "pdf-full", match: true, status: v.status };
  if (hash === v.verification.pdfSummaryHash) return { kind: "pdf-summary", match: true, status: v.status };
  try {
    const text = new TextDecoder().decode(bytes);
    const parsed = JSON.parse(text);
    const snapshot = parsed?.snapshot ?? parsed;
    const res = await verifyDisclosed(snapshot, v.verification.contentHash);
    if (res.ok) return { kind: "json", match: true, status: v.status, computedHash: res.computedHash };
  } catch {
    /* no es JSON */
  }
  return { kind: "none", match: false };
}

export async function listPublicDeclarations(opts: { q?: string; code?: string; take?: number; skip?: number }) {
  const rows = await prisma.declarationVersion.findMany({
    where: { visibility: "PUBLIC", status: { in: ["ISSUED", "SUPERSEDED", "REVOKED"] } },
    orderBy: { issuedAt: "desc" },
    take: 200,
    select: { id: true, publicId: true, status: true, issuedAt: true, snapshot: true },
  });
  const pubs = await prisma.declarationPublication.findMany({
    where: { versionId: { in: rows.map((r) => r.id) } },
    orderBy: { createdAt: "desc" },
  });
  const latest = new Map<string, DeclarationPublication>();
  for (const p of pubs) if (!latest.has(p.versionId)) latest.set(p.versionId, p);

  const items = rows
    .map((r) => {
      const pub = latest.get(r.id);
      if (!pub || pub.action === "WITHDRAW") return null;
      const vis = visibleSections(pub);
      const snap = r.snapshot as unknown as FullSnapshot;
      const get = (n: SectionName) => (vis.includes(n) ? (snap.sections[n].data as Record<string, unknown>) : null);
      const work = get("work");
      const decl = get("declarant");
      const cls = get("classification");
      return {
        publicId: r.publicId!,
        status: r.status,
        issuedAt: r.issuedAt!,
        title: (work?.title as string) ?? null,
        category: ((work?.category as { label?: string })?.label as string) ?? null,
        declarant: (decl?.name as string) ?? null,
        artisticName: (decl?.artisticName as string) ?? null,
        code: (cls?.code as string) ?? null,
        compositeNotation: (cls?.compositeNotation as string) ?? null,
        classificationStatus: (cls?.status as string) ?? null,
      };
    })
    .filter((x): x is NonNullable<typeof x> => x !== null);

  const q = opts.q?.trim().toLowerCase();
  const code = (opts.code ?? opts.q)?.trim().toUpperCase().replace(/^ORIVA\s+/, "");
  const filtered = items.filter((i) => {
    if (!q && !opts.code) return true;
    const textHit = q && [i.title, i.declarant, i.artisticName, i.publicId].some((x) => x?.toLowerCase().includes(q));
    const codeHit = code && (i.code === code || i.compositeNotation?.includes(code));
    return Boolean(textHit || codeHit);
  });
  const skip = opts.skip ?? 0;
  return { total: filtered.length, items: filtered.slice(skip, skip + (opts.take ?? 20)) };
}
