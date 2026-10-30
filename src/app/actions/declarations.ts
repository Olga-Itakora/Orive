"use server";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { requireUser } from "@/lib/auth/session";
import { appUrl } from "@/lib/env";
import { article50AnswersSchema } from "@/lib/legal/article50";
import { fingerprint } from "@/lib/security/crypto";
import { rateLimit } from "@/lib/security/rate-limit";
import { ipFingerprint } from "@/lib/security/request";
import { storeEvidenceFile } from "@/lib/storage/evidence";
import {
  createDeclaration,
  createNewVersion,
  deleteDraft,
  DomainError,
  getOwnedDraft,
  issueVersion,
  publishVersion,
  revokeVersion,
  withdrawPublication,
} from "@/lib/declarations/service";
import { fieldErrors, formObject, optionalText, requiredText, type ActionState } from "@/lib/validation/common";
import { useInputSchema, type UseInput } from "@/lib/validation/use";

const editPath = (id: string, step: string) => `/app/declarations/${id}/edit/${step}`;

function domainMessage(e: unknown): string {
  if (e instanceof DomainError) return e.message;
  throw e;
}

async function touchStep(versionId: string, step: string) {
  await prisma.declarationVersion.update({ where: { id: versionId }, data: { lastStep: step } });
}

// ---------------------------------------------------------------------------
// Inicio y versionado
// ---------------------------------------------------------------------------

export async function startDeclarationAction(workId: string) {
  const me = await requireUser();
  const v = await createDeclaration(me.id, workId);
  redirect(editPath(v.id, "work"));
}

export async function newVersionAction(declarationId: string) {
  const me = await requireUser();
  const v = await createNewVersion(me.id, declarationId);
  redirect(editPath(v.id, "declarant"));
}

export async function deleteDraftAction(versionId: string) {
  const me = await requireUser();
  const v = await deleteDraft(me.id, versionId);
  redirect(`/app/works/${v.declaration.workId}`);
}

// ---------------------------------------------------------------------------
// Paso 2 · Declarante y alcance
// ---------------------------------------------------------------------------

const declarantSchema = z.object({
  declarantName: requiredText(160, "Indica tu nombre."),
  declarantArtisticName: optionalText(160),
  declarantOrganization: optionalText(200),
  declarantRole: requiredText(120, "Indica tu rol en la obra."),
  scope: z.enum(["WHOLE_WORK", "COMPONENTS", "OWN_CONTRIBUTION"], { error: "Indica qué cubre tu declaración." }),
  responsibilityNote: optionalText(2000),
});

export async function saveDeclarantAction(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const v = await getOwnedDraft(me.id, versionId);
  const parsed = declarantSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const valid = new Set(v.declaration.work.components.map((c) => c.id));
  const scopeComponentIds = fd.getAll("scopeComponentIds").map(String).filter((id) => valid.has(id));
  if (parsed.data.scope === "COMPONENTS" && scopeComponentIds.length === 0) return { fieldErrors: { scopeComponentIds: "Selecciona al menos un componente." } };
  await prisma.declarationVersion.update({ where: { id: versionId }, data: { ...parsed.data, scopeComponentIds, lastStep: "usage" } });
  redirect(fd.get("intent") === "stay" ? editPath(versionId, "declarant") + "?saved=1" : editPath(versionId, "usage"));
}

// ---------------------------------------------------------------------------
// Paso 3 · Utilización general de IA
// ---------------------------------------------------------------------------

const usageSchema = z.object({
  aiUsage: z.enum(["NO", "YES", "UNCERTAIN", "OWN_CONTRIBUTION_ONLY"], { error: "Selecciona una opción." }),
  ownContributionAiUsage: z.enum(["NO", "YES", "UNCERTAIN"]).optional(),
  uncertaintyNote: optionalText(2000),
  processNarrative: optionalText(6000),
});

export async function saveUsageAction(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const v = await getOwnedDraft(me.id, versionId);
  const parsed = usageSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const d = parsed.data;
  if (d.aiUsage === "OWN_CONTRIBUTION_ONLY" && !d.ownContributionAiUsage) return { fieldErrors: { ownContributionAiUsage: "Indica si se utilizó IA en tu contribución." } };
  const effective = d.aiUsage === "OWN_CONTRIBUTION_ONLY" ? d.ownContributionAiUsage : d.aiUsage;
  if (effective === "NO" && v.uses.length > 0) {
    return { error: `Este borrador tiene ${v.uses.length} uso(s) de IA registrados, incompatibles con «No se utilizó IA». Elimínalos en el paso «Usos de IA» o elige otra respuesta.` };
  }
  await prisma.declarationVersion.update({
    where: { id: versionId },
    data: {
      aiUsage: d.aiUsage,
      ownContributionAiUsage: d.aiUsage === "OWN_CONTRIBUTION_ONLY" ? d.ownContributionAiUsage : null,
      // Si solo puede declarar sobre su contribución, el alcance lo refleja.
      ...(d.aiUsage === "OWN_CONTRIBUTION_ONLY" && v.scope !== "OWN_CONTRIBUTION" ? { scope: "OWN_CONTRIBUTION" as const } : {}),
      uncertaintyNote: d.uncertaintyNote,
      processNarrative: d.processNarrative,
    },
  });
  const next = effective === "YES" ? "tools" : "evidence";
  await touchStep(versionId, next);
  redirect(fd.get("intent") === "stay" ? editPath(versionId, "usage") + "?saved=1" : editPath(versionId, next));
}

// ---------------------------------------------------------------------------
// Paso 4 · Herramientas
// ---------------------------------------------------------------------------

const toolSchema = z.object({
  identified: z.enum(["yes", "no"]),
  name: z.string().trim().max(160).default(""),
  provider: optionalText(160),
  modelVersion: optionalText(160),
  systemType: optionalText(120),
  purpose: optionalText(1000),
  usagePeriod: optionalText(120),
});

export async function saveToolAction(versionId: string, toolId: string | null, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const v = await getOwnedDraft(me.id, versionId);
  const parsed = toolSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const d = parsed.data;
  const identified = d.identified === "yes";
  if (identified && !d.name) return { fieldErrors: { name: "Indica el nombre de la herramienta, o márcala como desconocida." } };
  const data = { identified, name: d.name || "Herramienta no identificada", provider: d.provider, modelVersion: d.modelVersion, systemType: d.systemType, purpose: d.purpose, usagePeriod: d.usagePeriod };
  if (toolId) {
    if (!v.tools.some((t) => t.id === toolId)) return { error: "Herramienta no encontrada." };
    await prisma.aITool.update({ where: { id: toolId }, data });
  } else {
    if (v.tools.length >= 50) return { error: "Máximo 50 herramientas." };
    await prisma.aITool.create({ data: { ...data, versionId, sortOrder: v.tools.length } });
  }
  revalidatePath(editPath(versionId, "tools"));
  return { ok: true, message: "Herramienta guardada." };
}

export async function deleteToolAction(versionId: string, toolId: string) {
  const me = await requireUser();
  const v = await getOwnedDraft(me.id, versionId);
  if (!v.tools.some((t) => t.id === toolId)) return;
  await prisma.aITool.delete({ where: { id: toolId } });
  revalidatePath(editPath(versionId, "tools"));
}

// ---------------------------------------------------------------------------
// Paso 5 · Usos de IA
// ---------------------------------------------------------------------------



export async function saveUseAction(versionId: string, useId: string | null, input: UseInput): Promise<ActionState & { useId?: string }> {
  const me = await requireUser();
  const v = await getOwnedDraft(me.id, versionId);
  const parsed = useInputSchema.safeParse(input);
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const d = parsed.data;
  if (d.componentId && !v.declaration.work.components.some((c) => c.id === d.componentId)) return { error: "Componente no válido." };
  if (d.toolId && !v.tools.some((t) => t.id === d.toolId)) return { error: "Herramienta no válida." };
  if (d.parentUseId && (!v.uses.some((u) => u.id === d.parentUseId) || d.parentUseId === useId)) return { error: "Uso principal no válido." };
  if (useId && !v.uses.some((u) => u.id === useId)) return { error: "Uso no encontrado." };
  if (!useId && v.uses.length >= 100) return { error: "Máximo 100 usos por declaración." };

  // Las aportaciones solo tienen sentido si se incorporó algo; se descartan en otro caso.
  const contributions = d.incorporation === "INCORPORATED" ? d.contributions : [];
  const types = new Set<string>();
  for (const c of contributions) {
    if (types.has(c.type)) return { error: "Cada tipo de aportación solo puede aparecer una vez en un uso. Si los resultados son distintos, crea un subuso." };
    types.add(c.type);
  }
  const base = {
    title: d.title,
    componentId: d.componentId,
    toolId: d.toolId,
    parentUseId: d.parentUseId,
    functions: d.functions,
    primaryFunction: d.functions.length > 1 ? d.primaryFunction : null,
    objective: d.objective || null,
    activityDescription: d.activityDescription || null,
    resultDescription: d.resultDescription || null,
    incorporation: d.incorporation,
  };
  const savedId = await prisma.$transaction(async (tx) => {
    let id = useId;
    if (id) {
      await tx.aIUse.update({ where: { id }, data: base });
      await tx.aIUsagePhase.deleteMany({ where: { useId: id } });
      await tx.creativeContribution.deleteMany({ where: { useId: id } });
    } else {
      id = (await tx.aIUse.create({ data: { ...base, versionId, sortOrder: v.uses.length } })).id;
    }
    if (d.phases.length) await tx.aIUsagePhase.createMany({ data: [...new Set(d.phases)].map((phase) => ({ useId: id!, phase })) });
    for (const c of contributions) {
      await tx.creativeContribution.create({
        data: {
          useId: id!,
          contributionType: c.type,
          interventionAnswer: c.interventionAnswer,
          retainsRecognizableElements: c.interventionAnswer === "TRANSFORMED" ? c.retainsRecognizableElements : null,
          interventionDescription: c.interventionDescription || null,
        },
      });
    }
    await tx.declarationVersion.update({ where: { id: versionId }, data: { lastStep: "uses" } });
    return id!;
  });
  revalidatePath(editPath(versionId, "uses"));
  return { ok: true, useId: savedId, message: "Uso guardado." };
}

export async function deleteUseAction(versionId: string, useId: string) {
  const me = await requireUser();
  const v = await getOwnedDraft(me.id, versionId);
  if (!v.uses.some((u) => u.id === useId)) return;
  await prisma.aIUse.delete({ where: { id: useId } });
  revalidatePath(editPath(versionId, "uses"));
}

// ---------------------------------------------------------------------------
// Paso 6 · Evidencias
// ---------------------------------------------------------------------------

const evidenceSchema = z.object({
  kind: z.enum(["VERSION_HISTORY", "CONVERSATION_LOG", "WORK_MATERIAL", "SCREENSHOT", "OTHER"], { error: "Selecciona el tipo de evidencia." }),
  description: requiredText(1000, "Describe brevemente la evidencia."),
  useId: z.string().optional(),
});

export async function addEvidenceAction(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  if (!(await rateLimit("evidence", me.id, 30, 3600))) return { error: "Se ha alcanzado el límite de 30 evidencias por hora. Vuelve a intentarlo más tarde; lo ya registrado está guardado." };
  const v = await getOwnedDraft(me.id, versionId);
  if (v.evidence.length >= 30) return { error: "Máximo 30 evidencias por declaración." };
  const parsed = evidenceSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const useId = parsed.data.useId && v.uses.some((u) => u.id === parsed.data.useId) ? parsed.data.useId : null;
  const file = fd.get("file");
  let fileData = {};
  if (file instanceof File && file.size > 0) {
    const stored = await storeEvidenceFile(file);
    if ("error" in stored) return { fieldErrors: { file: stored.error } };
    fileData = stored;
  }
  await prisma.evidence.create({ data: { versionId, useId, kind: parsed.data.kind, description: parsed.data.description, ...fileData } });
  revalidatePath(editPath(versionId, "evidence"));
  return { ok: true, message: "Evidencia registrada." };
}

export async function deleteEvidenceAction(versionId: string, evidenceId: string) {
  const me = await requireUser();
  const v = await getOwnedDraft(me.id, versionId);
  if (!v.evidence.some((e) => e.id === evidenceId)) return;
  // El fichero no se borra físicamente: puede estar referenciado por versiones emitidas.
  await prisma.evidence.delete({ where: { id: evidenceId } });
  revalidatePath(editPath(versionId, "evidence"));
}

// ---------------------------------------------------------------------------
// Paso 7 · Orientación del artículo 50
// ---------------------------------------------------------------------------

export async function saveArticle50Action(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  await getOwnedDraft(me.id, versionId);
  const raw = formObject(fd);
  const nullify = (k: string) => (raw[k] ? raw[k] : null);
  const parsed = article50AnswersSchema.safeParse({
    role: nullify("role"),
    organizationName: raw.organizationName ?? "",
    context: nullify("context"),
    syntheticMedia: nullify("syntheticMedia"),
    realisticResemblance: nullify("realisticResemblance"),
    evidentlyCreative: nullify("evidentlyCreative"),
    publicInterestText: nullify("publicInterestText"),
    humanReview: nullify("humanReview"),
    editorialResponsibility: nullify("editorialResponsibility"),
  });
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  await prisma.declarationVersion.update({ where: { id: versionId }, data: { article50Answers: parsed.data, lastStep: "review" } });
  redirect(fd.get("intent") === "stay" ? editPath(versionId, "article50") + "?saved=1" : editPath(versionId, "review"));
}

// ---------------------------------------------------------------------------
// Paso 8 · Revisión y emisión
// ---------------------------------------------------------------------------

export async function saveObservationsAction(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  await getOwnedDraft(me.id, versionId);
  const obs = optionalText(4000).safeParse(fd.get("observations") ?? "");
  if (!obs.success) return { fieldErrors: fieldErrors(obs.error) };
  await prisma.declarationVersion.update({ where: { id: versionId }, data: { observations: obs.data } });
  revalidatePath(editPath(versionId, "review"));
  return { ok: true, message: "Observaciones guardadas." };
}

export async function issueAction(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  if (!(await rateLimit("issue", me.id, 20, 3600))) return { error: "Demasiadas emisiones en poco tiempo. Inténtalo más tarde." };
  const accepted = fd.get("accept") === "on";
  if (!accepted) return { fieldErrors: { accept: "Para emitir la declaración debes aceptar expresamente el texto de la declaración responsable." } };
  try {
    await issueVersion(me.id, versionId, {
      accepted,
      statementHash: String(fd.get("statementHash") ?? ""),
      sessionFingerprint: fingerprint(me.sessionId, "session"),
      ipFingerprint: await ipFingerprint(),
      verifyUrlFor: (id) => appUrl(`/verify/${id}`),
    });
  } catch (e) {
    return { error: domainMessage(e) };
  }
  redirect(`/app/declarations/${versionId}?issued=1`);
}

// ---------------------------------------------------------------------------
// Publicación, retirada y revocación
// ---------------------------------------------------------------------------

export async function publishAction(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const on = (k: string) => fd.get(k) === "on";
  try {
    await publishVersion(
      me.id,
      versionId,
      { showName: on("showName"), showWork: on("showWork"), showCode: on("showCode"), showSummary: on("showSummary"), showFull: on("showFull"), showEvidence: on("showEvidence"), evidenceConsent: on("evidenceConsent") },
      await ipFingerprint(),
    );
  } catch (e) {
    return { error: domainMessage(e) };
  }
  revalidatePath(`/app/declarations/${versionId}`);
  return { ok: true, message: "Publicación actualizada." };
}

export async function withdrawAction(versionId: string) {
  const me = await requireUser();
  await withdrawPublication(me.id, versionId, await ipFingerprint());
  revalidatePath(`/app/declarations/${versionId}`);
}

export async function revokeAction(versionId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  if (fd.get("confirm") !== "on") return { fieldErrors: { confirm: "Confirma que entiendes que la revocación no se puede deshacer." } };
  try {
    await revokeVersion(me.id, versionId, String(fd.get("reason") ?? ""), await ipFingerprint());
  } catch (e) {
    return { error: domainMessage(e) };
  }
  revalidatePath(`/app/declarations/${versionId}`);
  return { ok: true, message: "Declaración revocada." };
}
