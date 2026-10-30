import { afterAll, describe, expect, it } from "vitest";
import { prisma } from "@/lib/db";
import {
  activeStatement,
  createDeclaration,
  createNewVersion,
  DomainError,
  getOwnedVersion,
  issuanceReadiness,
  issueVersion,
  publishVersion,
  revokeVersion,
  withdrawPublication,
} from "@/lib/declarations/service";
import { checkFileAgainstRegistry, getPublicDeclaration, listPublicDeclarations } from "@/lib/declarations/public";
import { verifyDisclosed, type FullSnapshot } from "@/lib/integrity/disclosure";
import { sha256Hex } from "@/lib/integrity/canonical";
import { PUBLIC_ID_REGEX } from "@/lib/ids";
import { ALL_FLAGS, draftNoAI, issue, makeUser, makeWork } from "./helpers";

afterAll(async () => {
  await prisma.$disconnect();
});

describe("Caso 1 — novela sin IA: emisión completa", () => {
  it("emite con A0B0C0D0, PDF, identificador y huella verificable", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id);
    const d = await draftNoAI(u.id, w.id);
    const res = await issue(u.id, d.id);
    expect(res.publicId).toMatch(PUBLIC_ID_REGEX);

    const v = await getOwnedVersion(u.id, d.id);
    expect(v.status).toBe("ISSUED");
    expect(v.visibility).toBe("PRIVATE");
    const snap = v.snapshot as unknown as FullSnapshot;
    expect((snap.sections.classification.data as { code: string }).code).toBe("A0B0C0D0");
    expect(Buffer.from(v.pdfFull!).subarray(0, 5).toString()).toBe("%PDF-");
    expect(Buffer.from(v.pdfSummary!).subarray(0, 5).toString()).toBe("%PDF-");
    expect(await sha256Hex(new Uint8Array(v.pdfFull!))).toBe(v.pdfFullHash);
    expect((await verifyDisclosed(snap, v.contentHash!)).ok).toBe(true);

    // El correo nunca forma parte de la instantánea.
    expect(JSON.stringify(snap)).not.toContain(u.email);
    // Aceptación registrada con la versión exacta del texto.
    const acc = await prisma.declarationAcceptance.findUnique({ where: { versionId: d.id }, include: { legalText: true } });
    expect(acc?.legalText.key).toBe("declaration_statement");
    expect(acc?.statementHash).toBe(acc?.legalText.contentHash);
  });

  it("exige aceptación expresa y el texto exacto mostrado", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id);
    const d = await draftNoAI(u.id, w.id);
    const st = await activeStatement();
    const base = { statementHash: st.contentHash, sessionFingerprint: "s", ipFingerprint: null, verifyUrlFor: (id: string) => id };
    await expect(issueVersion(u.id, d.id, { ...base, accepted: false })).rejects.toBeInstanceOf(DomainError);
    await expect(issueVersion(u.id, d.id, { ...base, accepted: true, statementHash: "otro" })).rejects.toThrow(/ha cambiado/);
    expect((await prisma.declarationVersion.findUnique({ where: { id: d.id } }))!.status).toBe("DRAFT");
  });

  it("no emite borradores incompletos", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id);
    const d = await createDeclaration(u.id, w.id);
    await expect(issue(u.id, d.id)).rejects.toThrow(/no está completa/);
  });
});

describe("Inmutabilidad (base de datos)", () => {
  it("rechaza modificar o borrar una versión emitida y sus usos", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id);
    const d = await draftNoAI(u.id, w.id);
    await issue(u.id, d.id);
    await expect(prisma.declarationVersion.update({ where: { id: d.id }, data: { declarantName: "Otra persona" } })).rejects.toThrow(/ORIVA_IMMUTABLE/);
    await expect(prisma.declarationVersion.update({ where: { id: d.id }, data: { contentHash: "0".repeat(64) } })).rejects.toThrow(/ORIVA_IMMUTABLE/);
    await expect(prisma.declarationVersion.delete({ where: { id: d.id } })).rejects.toThrow(/ORIVA_IMMUTABLE/);
    await expect(prisma.aITool.create({ data: { versionId: d.id, name: "X" } })).rejects.toThrow(/ORIVA_IMMUTABLE/);
    await expect(prisma.declarationVersion.update({ where: { id: d.id }, data: { status: "DRAFT" } })).rejects.toThrow(/ORIVA_IMMUTABLE/);
    // Visibilidad sí puede cambiar.
    await prisma.declarationVersion.update({ where: { id: d.id }, data: { visibility: "PUBLIC" } });
    await expect(prisma.verificationRecord.updateMany({ where: { versionId: d.id }, data: { contentHash: "x" } })).rejects.toThrow(/ORIVA_IMMUTABLE/);
  });
});

describe("Autorización por propietario (IDOR)", () => {
  it("otro usuario no puede leer, emitir ni versionar", async () => {
    const owner = await makeUser();
    const intruder = await makeUser("Intrusa");
    const w = await makeWork(owner.id);
    const d = await draftNoAI(owner.id, w.id);
    await expect(getOwnedVersion(intruder.id, d.id)).rejects.toMatchObject({ code: "NOT_FOUND" });
    await expect(issue(intruder.id, d.id)).rejects.toMatchObject({ code: "NOT_FOUND" });
    await expect(createDeclaration(intruder.id, w.id)).rejects.toMatchObject({ code: "NOT_FOUND" });
    await issue(owner.id, d.id);
    await expect(createNewVersion(intruder.id, d.declarationId)).rejects.toMatchObject({ code: "NOT_FOUND" });
    await expect(publishVersion(intruder.id, d.id, ALL_FLAGS, null)).rejects.toMatchObject({ code: "NOT_FOUND" });
  });
});

describe("Caso 7 — obra híbrida y Caso 8 — obra colectiva", () => {
  it("conserva usos, parejas B–C y perfil compuesto; declara el alcance limitado", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id, { collective: true, components: [{ name: "Texto", kind: "TEXT" }, { name: "Ilustraciones", kind: "IMAGE" }] });
    await prisma.workContributor.create({ data: { workId: w.id, name: "Ilustrador Tercero", role: "Ilustrador", componentIds: [w.components[1].id] } });
    const text = w.components.find((c) => c.name === "Texto")!;
    const d = await createDeclaration(u.id, w.id);
    const tool = await prisma.aITool.create({ data: { versionId: d.id, name: "Asistente X", provider: "Proveedor" } });
    const mk = (title: string, fn: string, b: string, answer: string, extra: Record<string, string> = {}) =>
      prisma.aIUse.create({
        data: {
          versionId: d.id,
          componentId: text.id,
          toolId: tool.id,
          title,
          functions: [fn],
          activityDescription: "Descripción suficiente de la actividad realizada.",
          resultDescription: "Resultado descrito con detalle suficiente.",
          incorporation: "INCORPORATED",
          phases: { create: [{ phase: "EDITING" }] },
          contributions: { create: [{ contributionType: b, interventionAnswer: answer, interventionDescription: "Intervención humana descrita con detalle.", ...extra }] },
        },
      });
    await mk("Revisión lingüística", "A4", "B2", "ACCEPTED");
    await mk("Idea de trama", "A2", "B1", "OWN_EXPRESSION");
    await prisma.declarationVersion.update({
      where: { id: d.id },
      data: { declarantName: "Autora", declarantRole: "Autora del texto", scope: "OWN_CONTRIBUTION", scopeComponentIds: [text.id], aiUsage: "OWN_CONTRIBUTION_ONLY", ownContributionAiUsage: "YES" },
    });
    const ready = issuanceReadiness(await getOwnedVersion(u.id, d.id));
    expect(ready.canIssue).toBe(true);
    expect(ready.profile.compositeNotation).toBe("A2+A4 | B1+B2 | C1+C5 | D2");

    await issue(u.id, d.id);
    const v = await getOwnedVersion(u.id, d.id);
    const snap = v.snapshot as unknown as FullSnapshot;
    const cls = snap.sections.classification.data as { code: string | null; uses: { code: string }[] };
    expect(cls.code).toBeNull();
    expect(cls.uses.map((x) => x.code)).toEqual(["A4B2C1D2", "A2B1C5D2"]);
    const scope = snap.sections.scope.data as { coverageStatement: string };
    expect(scope.coverageStatement).toMatch(/No abarca necesariamente otras partes de la obra ni las aportaciones de los demás participantes/);
    // Nombres de terceros no se incluyen.
    expect(JSON.stringify(snap)).not.toContain("Ilustrador Tercero");
    // C derivado almacenado y perfiles persistidos.
    const contribs = await prisma.creativeContribution.findMany({ where: { use: { versionId: d.id } } });
    expect(contribs.map((c) => c.interventionCode).sort()).toEqual(["C1", "C5"]);
    const profiles = await prisma.transparencyProfile.findMany({ where: { versionId: d.id } });
    expect(profiles.filter((p) => p.scope === "USE")).toHaveLength(2);
    expect(profiles.find((p) => p.scope === "WORK")?.notation).toBe("A2+A4 | B1+B2 | C1+C5 | D2");
  });
});

describe("Caso 10 — integridad y versiones; publicación y verificación pública", () => {
  it("la versión anterior permanece inalterada y la relación histórica se conserva", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id);
    const d1 = await draftNoAI(u.id, w.id);
    const r1 = await issue(u.id, d1.id);
    const before = await prisma.declarationVersion.findUnique({ where: { id: d1.id } });

    // Publicación selectiva: sin nombre.
    await publishVersion(u.id, d1.id, { ...ALL_FLAGS, showName: false, showFull: false }, null);
    let pub = await getPublicDeclaration(r1.publicId);
    expect(pub).not.toBeNull();
    expect(pub!.visible).not.toContain("declarant");
    expect(pub!.view.sections.declarant).toBeUndefined();
    // La huella sigue siendo verificable con las secciones ocultas.
    expect((await verifyDisclosed(pub!.view, pub!.contentHash)).ok).toBe(true);
    // Manipular una sección divulgada rompe la verificación.
    const tampered = structuredClone(pub!.view);
    (tampered.sections.work!.data as { title: string }).title = "Otro título";
    expect((await verifyDisclosed(tampered, pub!.contentHash)).ok).toBe(false);

    // Nueva versión.
    const d2 = await createNewVersion(u.id, d1.declarationId);
    expect(d2.versionNumber).toBe(2);
    expect(d2.supersedesId).toBe(d1.id);
    await prisma.declarationVersion.update({ where: { id: d2.id }, data: { processNarrative: "Descripción ampliada del proceso creativo: borradores manuscritos, revisiones con la editora y lecturas en voz alta, sin herramientas de IA." } });
    const r2 = await issue(u.id, d2.id);
    expect(r2.publicId).not.toBe(r1.publicId);
    expect(r2.contentHash).not.toBe(r1.contentHash);

    const after = await prisma.declarationVersion.findUnique({ where: { id: d1.id } });
    expect(after!.status).toBe("SUPERSEDED");
    expect(after!.contentHash).toBe(before!.contentHash);
    expect(Buffer.compare(Buffer.from(after!.pdfFull!), Buffer.from(before!.pdfFull!))).toBe(0);
    expect(JSON.stringify(after!.snapshot)).toBe(JSON.stringify(before!.snapshot));
    const snap2 = (await prisma.declarationVersion.findUnique({ where: { id: d2.id } }))!.snapshot as unknown as FullSnapshot;
    expect((snap2.sections.meta.data as { supersedesPublicId: string }).supersedesPublicId).toBe(r1.publicId);
    expect((snap2.sections.classification.data as { code: string }).code).toBe("A0B0C0D2");

    // La página pública de la v1 refleja el estado de sustitución.
    pub = await getPublicDeclaration(r1.publicId);
    expect(pub!.status).toBe("SUPERSEDED");
    expect(pub!.supersededBy?.publicId).toBe(r2.publicId);
    expect(pub!.supersededBy?.isPublic).toBe(false);

    // Retirada de publicación ≠ revocación.
    await withdrawPublication(u.id, d1.id, null);
    expect(await getPublicDeclaration(r1.publicId)).toBeNull();
    expect((await prisma.declarationVersion.findUnique({ where: { id: d1.id } }))!.status).toBe("SUPERSEDED");

    // Revocación conserva el contenido y su verificabilidad.
    await revokeVersion(u.id, d2.id, "Error en los datos de la obra", null);
    const rev = await prisma.declarationVersion.findUnique({ where: { id: d2.id } });
    expect(rev!.status).toBe("REVOKED");
    expect(rev!.contentHash).toBe(r2.contentHash);
  });

  it("verificación por fichero: PDF y JSON coinciden; no revela si no existe", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id);
    const d = await draftNoAI(u.id, w.id);
    const r = await issue(u.id, d.id);
    const v = await prisma.declarationVersion.findUnique({ where: { id: d.id } });
    // Funciona aunque la declaración sea privada (el verificador ya posee el fichero).
    expect(await checkFileAgainstRegistry(r.publicId, new Uint8Array(v!.pdfFull!))).toMatchObject({ kind: "pdf-full", match: true });
    expect(await checkFileAgainstRegistry(r.publicId, new Uint8Array(v!.pdfSummary!))).toMatchObject({ kind: "pdf-summary", match: true });
    const json = new TextEncoder().encode(JSON.stringify({ snapshot: v!.snapshot }));
    expect(await checkFileAgainstRegistry(r.publicId, json)).toMatchObject({ kind: "json", match: true });
    expect(await checkFileAgainstRegistry(r.publicId, new TextEncoder().encode("%PDF-falso"))).toEqual({ kind: "none", match: false });
    expect(await checkFileAgainstRegistry("ORV-0000-0000-0000", new Uint8Array(v!.pdfFull!))).toEqual({ kind: "none", match: false });
    // Una declaración privada no aparece en consultas públicas.
    expect(await getPublicDeclaration(r.publicId)).toBeNull();
    const list = await listPublicDeclarations({ q: r.publicId });
    expect(list.items).toHaveLength(0);
  });

  it("publicar evidencias exige consentimiento específico", async () => {
    const u = await makeUser();
    const w = await makeWork(u.id);
    const d = await draftNoAI(u.id, w.id);
    await issue(u.id, d.id);
    await expect(publishVersion(u.id, d.id, { ...ALL_FLAGS, showEvidence: true, evidenceConsent: false }, null)).rejects.toMatchObject({ code: "INVALID" });
  });
});
