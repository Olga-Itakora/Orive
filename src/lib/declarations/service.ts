/**
 * Servicios de dominio de las declaraciones. No dependen de Next.js: reciben el
 * usuario autenticado como parámetro y comprueban la propiedad de cada recurso
 * en el servidor (protección frente a IDOR).
 */
import { Prisma } from "@prisma/client";
import { prisma } from "@/lib/db";
import { generatePublicId } from "@/lib/ids";
import { computeContentHash, CANONICALIZATION, HASH_ALGORITHM, SNAPSHOT_FORMAT, type SectionName } from "@/lib/integrity/disclosure";
import { sha256Hex } from "@/lib/integrity/canonical";
import { generateCompositeProfile, validateTransparencyProfile, type DeclarationProfile } from "@/lib/oriva-code";
import { generateFullPdf, generateSummaryPdf } from "@/lib/pdf/declaration-pdf";
import { completenessIssues, toDeclarationInput, versionFullInclude, type VersionFull } from "./load";
import { buildSnapshot } from "./snapshot";

export class DomainError extends Error {
  constructor(
    public code: "NOT_FOUND" | "FORBIDDEN" | "INVALID" | "CONFLICT",
    message: string,
    public details?: unknown,
  ) {
    super(message);
  }
}

type Tx = Prisma.TransactionClient;

// ---------------------------------------------------------------------------
// Acceso
// ---------------------------------------------------------------------------

export async function getOwnedWork(userId: string, workId: string) {
  const work = await prisma.work.findFirst({
    where: { id: workId, ownerId: userId },
    include: { category: true, components: { orderBy: [{ sortOrder: "asc" }, { createdAt: "asc" }] }, contributors: true },
  });
  if (!work) throw new DomainError("NOT_FOUND", "Obra no encontrada.");
  return work;
}

export async function getOwnedVersion(userId: string, versionId: string, client: Tx | typeof prisma = prisma): Promise<VersionFull> {
  const v = await client.declarationVersion.findFirst({
    where: { id: versionId, declaration: { ownerId: userId } },
    include: versionFullInclude,
  });
  if (!v) throw new DomainError("NOT_FOUND", "Declaración no encontrada.");
  return v;
}

export async function getOwnedDraft(userId: string, versionId: string, client: Tx | typeof prisma = prisma): Promise<VersionFull> {
  const v = await getOwnedVersion(userId, versionId, client);
  if (v.status !== "DRAFT") throw new DomainError("CONFLICT", "Esta versión ya ha sido emitida y no puede modificarse. Crea una nueva versión.");
  return v;
}

// ---------------------------------------------------------------------------
// Esquema y cuestionario activos
// ---------------------------------------------------------------------------

export async function activeQuestionnaire(client: Tx | typeof prisma = prisma) {
  const q = await client.questionnaireVersion.findFirst({ where: { active: true }, orderBy: { publishedAt: "desc" }, include: { scheme: true } });
  if (!q) throw new DomainError("CONFLICT", "No hay ninguna versión activa del cuestionario. Ejecuta el seed.");
  return q;
}

export async function activeStatement(client: Tx | typeof prisma = prisma) {
  const t = await client.legalText.findFirst({ where: { key: "declaration_statement", locale: "es", active: true } });
  if (!t) throw new DomainError("CONFLICT", "No hay texto de declaración responsable activo.");
  return t;
}

// ---------------------------------------------------------------------------
// Creación y versionado
// ---------------------------------------------------------------------------

export async function createDeclaration(userId: string, workId: string) {
  await getOwnedWork(userId, workId);
  const [q, profile] = await Promise.all([activeQuestionnaire(), prisma.creatorProfile.findUnique({ where: { userId } })]);
  return prisma.$transaction(async (tx) => {
    const decl = await tx.declaration.create({ data: { workId, ownerId: userId } });
    return tx.declarationVersion.create({
      data: {
        declarationId: decl.id,
        versionNumber: 1,
        questionnaireVersionId: q.id,
        schemeId: q.schemeId,
        declarantName: profile?.displayName ?? null,
        declarantArtisticName: profile?.artisticName ?? null,
        declarantOrganization: profile?.organization ?? null,
      },
    });
  });
}

/**
 * Crea una nueva versión (borrador) a partir de la última versión emitida.
 * La versión anterior permanece intacta; se enlaza mediante `supersedesId` y
 * pasa a «Sustituida» solo cuando la nueva versión se emite.
 */
export async function createNewVersion(userId: string, declarationId: string) {
  const decl = await prisma.declaration.findFirst({
    where: { id: declarationId, ownerId: userId },
    include: { versions: { orderBy: { versionNumber: "desc" } } },
  });
  if (!decl) throw new DomainError("NOT_FOUND", "Declaración no encontrada.");
  const latest = decl.versions[0];
  if (latest.status === "DRAFT") return latest; // ya existe un borrador en curso
  const base = decl.versions.find((v) => v.status === "ISSUED");
  if (!base) throw new DomainError("CONFLICT", "No hay una versión emitida vigente a partir de la cual crear una nueva versión.");
  const src = await getOwnedVersion(userId, base.id);
  const q = await activeQuestionnaire();

  return prisma.$transaction(async (tx) => {
    const v = await tx.declarationVersion.create({
      data: {
        declarationId,
        versionNumber: latest.versionNumber + 1,
        questionnaireVersionId: q.id,
        schemeId: q.schemeId,
        supersedesId: src.id,
        declarantName: src.declarantName,
        declarantArtisticName: src.declarantArtisticName,
        declarantOrganization: src.declarantOrganization,
        declarantRole: src.declarantRole,
        scope: src.scope,
        scopeComponentIds: src.scopeComponentIds,
        responsibilityNote: src.responsibilityNote,
        aiUsage: src.aiUsage,
        ownContributionAiUsage: src.ownContributionAiUsage,
        uncertaintyNote: src.uncertaintyNote,
        processNarrative: src.processNarrative,
        observations: src.observations,
        article50Answers: (src.article50Answers ?? Prisma.JsonNull) as Prisma.InputJsonValue,
      },
    });
    const toolMap = new Map<string, string>();
    for (const t of src.tools) {
      const nt = await tx.aITool.create({
        data: { versionId: v.id, identified: t.identified, name: t.name, provider: t.provider, modelVersion: t.modelVersion, systemType: t.systemType, purpose: t.purpose, usagePeriod: t.usagePeriod, sortOrder: t.sortOrder },
      });
      toolMap.set(t.id, nt.id);
    }
    const useMap = new Map<string, string>();
    // Primero usos raíz, después subusos.
    const ordered = [...src.uses.filter((u) => !u.parentUseId), ...src.uses.filter((u) => u.parentUseId)];
    for (const u of ordered) {
      const nu = await tx.aIUse.create({
        data: {
          versionId: v.id,
          componentId: u.componentId,
          toolId: u.toolId ? toolMap.get(u.toolId) ?? null : null,
          parentUseId: u.parentUseId ? useMap.get(u.parentUseId) ?? null : null,
          title: u.title,
          functions: u.functions,
          primaryFunction: u.primaryFunction,
          objective: u.objective,
          activityDescription: u.activityDescription,
          resultDescription: u.resultDescription,
          incorporation: u.incorporation,
          sortOrder: u.sortOrder,
          phases: { create: u.phases.map((p) => ({ phase: p.phase, note: p.note })) },
          contributions: {
            create: u.contributions.map((c) => ({
              contributionType: c.contributionType,
              interventionAnswer: c.interventionAnswer,
              retainsRecognizableElements: c.retainsRecognizableElements,
              interventionDescription: c.interventionDescription,
            })),
          },
        },
      });
      useMap.set(u.id, nu.id);
    }
    // Las evidencias se copian como metadatos y comparten el fichero almacenado (inmutable).
    for (const e of src.evidence) {
      await tx.evidence.create({
        data: {
          versionId: v.id,
          useId: e.useId ? useMap.get(e.useId) ?? null : null,
          kind: e.kind,
          description: e.description,
          fileName: e.fileName,
          mimeType: e.mimeType,
          sizeBytes: e.sizeBytes,
          sha256: e.sha256,
          storageKey: e.storageKey,
        },
      });
    }
    return v;
  });
}

export async function deleteDraft(userId: string, versionId: string) {
  const v = await getOwnedDraft(userId, versionId);
  await prisma.$transaction(async (tx) => {
    await tx.declarationVersion.delete({ where: { id: v.id } });
    const remaining = await tx.declarationVersion.count({ where: { declarationId: v.declarationId } });
    if (remaining === 0) await tx.declaration.delete({ where: { id: v.declarationId } });
  });
  return v;
}

// ---------------------------------------------------------------------------
// Clasificación de un borrador (vista previa)
// ---------------------------------------------------------------------------

export function classifyVersion(v: VersionFull): DeclarationProfile {
  return generateCompositeProfile(toDeclarationInput(v));
}

export function issuanceReadiness(v: VersionFull) {
  const profile = classifyVersion(v);
  const validation = validateTransparencyProfile(profile);
  const completeness = completenessIssues(v);
  return { profile, validation, completeness, canIssue: validation.canIssue && completeness.length === 0 };
}

// ---------------------------------------------------------------------------
// Emisión
// ---------------------------------------------------------------------------

export interface IssueOptions {
  accepted: boolean;
  /** Hash del texto que el usuario vio en pantalla (garantiza que acepta exactamente ese texto). */
  statementHash: string;
  sessionFingerprint: string;
  ipFingerprint: string | null;
  verifyUrlFor: (publicId: string) => string;
}

export async function issueVersion(userId: string, versionId: string, opts: IssueOptions) {
  if (opts.accepted !== true) throw new DomainError("INVALID", "Debes aceptar expresamente la declaración responsable.");

  for (let attempt = 0; attempt < 3; attempt++) {
    try {
      return await prisma.$transaction(
        async (tx) => {
          // Bloqueo de la fila para impedir emisiones concurrentes del mismo borrador.
          const locked = await tx.$queryRaw<{ status: string }[]>`SELECT "status"::text AS status FROM "DeclarationVersion" WHERE "id" = ${versionId} FOR UPDATE`;
          if (!locked[0]) throw new DomainError("NOT_FOUND", "Declaración no encontrada.");
          const v = await getOwnedDraft(userId, versionId, tx);

          const statement = await activeStatement(tx);
          if (statement.contentHash !== opts.statementHash) {
            throw new DomainError("CONFLICT", "El texto de la declaración responsable ha cambiado. Revísalo de nuevo antes de aceptar.");
          }

          const { profile, canIssue, validation, completeness } = issuanceReadiness(v);
          if (!canIssue) throw new DomainError("INVALID", "La declaración no está completa.", { errors: validation.errors, completeness });

          const publicId = generatePublicId();
          const issuedAt = new Date();
          const verifyUrl = opts.verifyUrlFor(publicId);
          const { snapshot, refs } = buildSnapshot(v, profile, {
            publicId,
            issuedAt,
            verifyUrl,
            statement: { key: statement.key, version: statement.version, locale: statement.locale, text: statement.content, textHash: statement.contentHash },
          });
          const { contentHash, digests } = await computeContentHash(snapshot);
          const [pdfFull, pdfSummary] = await Promise.all([generateFullPdf(snapshot, contentHash), generateSummaryPdf(snapshot, contentHash)]);
          const [pdfFullHash, pdfSummaryHash] = await Promise.all([sha256Hex(new Uint8Array(pdfFull)), sha256Hex(new Uint8Array(pdfSummary))]);

          // Perfiles calculados y C derivado: se escriben antes del cambio de estado.
          await tx.transparencyProfile.deleteMany({ where: { versionId } });
          const input = toDeclarationInput(v);
          for (const u of profile.uses) {
            await tx.transparencyProfile.create({
              data: {
                versionId,
                scope: "USE",
                useId: u.ref,
                componentId: u.componentRef,
                status: u.status,
                code: u.code,
                notation: u.notation,
                functions: u.functions,
                pairs: u.pairs.map((p) => ({ contributionRef: p.contributionRef, b: p.b, c: p.c })),
                documentation: u.documentation,
                rulesApplied: u.rulesApplied,
                inputs: JSON.parse(JSON.stringify(input.uses.find((x) => x.ref === u.ref))),
                schemeVersion: profile.schemeVersion,
                computedAt: issuedAt,
              },
            });
            for (const p of u.pairs) {
              if (p.contributionRef) await tx.creativeContribution.update({ where: { id: p.contributionRef }, data: { interventionCode: p.c } });
            }
          }
          for (const c of profile.components) {
            await tx.transparencyProfile.create({
              data: {
                versionId,
                scope: "COMPONENT",
                componentId: c.componentRef,
                status: c.status,
                code: c.code,
                notation: c.compositeNotation,
                functions: c.composite?.A ?? [],
                pairs: [],
                documentation: c.composite?.D ?? "D0",
                rulesApplied: [],
                inputs: { useRefs: c.useRefs },
                schemeVersion: profile.schemeVersion,
                computedAt: issuedAt,
              },
            });
          }
          await tx.transparencyProfile.create({
            data: {
              versionId,
              scope: "WORK",
              status: profile.status,
              code: profile.code,
              notation: profile.compositeNotation,
              functions: profile.composite?.A ?? [],
              pairs: profile.uses.flatMap((u) => u.pairs.map((p) => ({ use: refs.use.get(u.ref), b: p.b, c: p.c }))),
              documentation: profile.documentation,
              rulesApplied: profile.rulesApplied,
              inputs: JSON.parse(JSON.stringify({ aiUsage: input.aiUsage, declarationEvidenceCount: input.declarationEvidenceCount, processNarrativeLength: input.processNarrative.trim().length })),
              schemeVersion: profile.schemeVersion,
              computedAt: issuedAt,
            },
          });

          await tx.declarationVersion.update({
            where: { id: versionId },
            data: {
              status: "ISSUED",
              publicId,
              issuedAt,
              snapshot: snapshot as unknown as Prisma.InputJsonValue,
              contentHash,
              pdfFull: new Uint8Array(pdfFull),
              pdfFullHash,
              pdfSummary: new Uint8Array(pdfSummary),
              pdfSummaryHash,
            },
          });

          await tx.declarationAcceptance.create({
            data: {
              versionId,
              userId,
              legalTextId: statement.id,
              statementHash: statement.contentHash,
              acceptedAt: issuedAt,
              sessionFingerprint: opts.sessionFingerprint,
              ipFingerprint: opts.ipFingerprint,
            },
          });

          await tx.verificationRecord.create({
            data: {
              versionId,
              algorithm: HASH_ALGORITHM,
              canonicalization: CANONICALIZATION,
              snapshotFormat: SNAPSHOT_FORMAT,
              contentHash,
              sectionDigests: digests as Record<SectionName, string>,
              pdfFullHash,
              pdfSummaryHash,
            },
          });

          if (v.supersedesId) {
            await tx.declarationVersion.updateMany({ where: { id: v.supersedesId, status: "ISSUED" }, data: { status: "SUPERSEDED" } });
          }

          await tx.auditLog.create({
            data: { actorId: userId, action: "declaration.issue", entityType: "DeclarationVersion", entityId: versionId, metadata: { publicId, contentHash, supersedes: v.supersedes?.publicId ?? null }, ipFingerprint: opts.ipFingerprint },
          });
          return { publicId, contentHash, versionId };
        },
        { isolationLevel: "Serializable", timeout: 30_000, maxWait: 10_000 },
      );
    } catch (e) {
      // Colisión (improbable) del identificador público: se reintenta.
      if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002" && String(e.meta?.target).includes("publicId")) continue;
      throw e;
    }
  }
  throw new DomainError("CONFLICT", "No se pudo generar un identificador único. Inténtalo de nuevo.");
}

// ---------------------------------------------------------------------------
// Publicación y revocación
// ---------------------------------------------------------------------------

export interface PublicationFlags {
  showName: boolean;
  showWork: boolean;
  showCode: boolean;
  showSummary: boolean;
  showFull: boolean;
  showEvidence: boolean;
  evidenceConsent: boolean;
}

/** Normaliza las opciones: la declaración completa implica obra, código y resumen. */
export function normalizeFlags(f: PublicationFlags): Omit<PublicationFlags, "evidenceConsent"> {
  const showFull = f.showFull;
  return {
    showName: f.showName,
    showWork: f.showWork || showFull,
    showCode: f.showCode || showFull,
    showSummary: f.showSummary || showFull,
    showFull,
    showEvidence: f.showEvidence && f.evidenceConsent,
  };
}

export async function publishVersion(userId: string, versionId: string, flags: PublicationFlags, ipFp: string | null) {
  const v = await getOwnedVersion(userId, versionId);
  if (v.status === "DRAFT") throw new DomainError("CONFLICT", "Solo se pueden publicar declaraciones emitidas.");
  if (flags.showEvidence && !flags.evidenceConsent) throw new DomainError("INVALID", "Publicar evidencias requiere tu consentimiento específico.");
  const n = normalizeFlags(flags);
  if (!n.showCode && !n.showSummary && !n.showWork) throw new DomainError("INVALID", "Selecciona al menos qué información quieres publicar.");
  await prisma.$transaction(async (tx) => {
    await tx.declarationPublication.create({
      data: { versionId, actorId: userId, action: v.visibility === "PUBLIC" ? "UPDATE" : "PUBLISH", ...n, evidenceConsentAt: n.showEvidence ? new Date() : null },
    });
    await tx.declarationVersion.update({ where: { id: versionId }, data: { visibility: "PUBLIC" } });
    await tx.auditLog.create({ data: { actorId: userId, action: "declaration.publish", entityType: "DeclarationVersion", entityId: versionId, metadata: n, ipFingerprint: ipFp } });
  });
}

export async function withdrawPublication(userId: string, versionId: string, ipFp: string | null) {
  const v = await getOwnedVersion(userId, versionId);
  if (v.visibility !== "PUBLIC") throw new DomainError("CONFLICT", "La declaración no está publicada.");
  await prisma.$transaction(async (tx) => {
    await tx.declarationPublication.create({ data: { versionId, actorId: userId, action: "WITHDRAW" } });
    await tx.declarationVersion.update({ where: { id: versionId }, data: { visibility: "WITHDRAWN" } });
    await tx.auditLog.create({ data: { actorId: userId, action: "declaration.withdraw", entityType: "DeclarationVersion", entityId: versionId, ipFingerprint: ipFp } });
  });
}

/** Revocación: la declaración deja de estar vigente, pero se conserva y sigue siendo verificable. */
export async function revokeVersion(userId: string, versionId: string, reason: string, ipFp: string | null) {
  const v = await getOwnedVersion(userId, versionId);
  if (v.status !== "ISSUED" && v.status !== "SUPERSEDED") throw new DomainError("CONFLICT", "Solo se pueden revocar declaraciones emitidas.");
  const r = reason.trim();
  if (r.length < 5) throw new DomainError("INVALID", "Indica brevemente el motivo de la revocación.");
  await prisma.$transaction(async (tx) => {
    await tx.declarationVersion.update({ where: { id: versionId }, data: { status: "REVOKED", revokedAt: new Date(), revocationReason: r.slice(0, 1000) } });
    await tx.auditLog.create({ data: { actorId: userId, action: "declaration.revoke", entityType: "DeclarationVersion", entityId: versionId, ipFingerprint: ipFp } });
  });
}

export async function currentPublication(versionId: string) {
  return prisma.declarationPublication.findFirst({ where: { versionId }, orderBy: { createdAt: "desc" } });
}
