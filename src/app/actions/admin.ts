"use server";
import { createHash } from "node:crypto";
import { revalidatePath } from "next/cache";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { audit } from "@/lib/audit";
import { requireAdmin } from "@/lib/auth/session";
import { fieldErrors, formObject, optionalText, requiredText, type ActionState } from "@/lib/validation/common";

// Todas las acciones comprueban el rol en el servidor y quedan auditadas.

export async function setUserRoleAction(userId: string, fd: FormData) {
  const admin = await requireAdmin();
  const role = fd.get("role") === "ADMIN" ? "ADMIN" : "AUTHOR";
  if (userId === admin.id) return; // no se puede degradar a sí mismo
  await prisma.user.update({ where: { id: userId }, data: { role } });
  await audit("admin.user_role", "User", userId, { actorId: admin.id, metadata: { role } });
  revalidatePath("/admin/users");
}

export async function toggleUserDisabledAction(userId: string) {
  const admin = await requireAdmin();
  if (userId === admin.id) return;
  const u = await prisma.user.findUniqueOrThrow({ where: { id: userId } });
  const disabledAt = u.disabledAt ? null : new Date();
  await prisma.$transaction([prisma.user.update({ where: { id: userId }, data: { disabledAt } }), ...(disabledAt ? [prisma.session.deleteMany({ where: { userId } })] : [])]);
  await audit(disabledAt ? "admin.user_disable" : "admin.user_enable", "User", userId, { actorId: admin.id });
  revalidatePath("/admin/users");
}

export async function deprecateSchemeAction(schemeId: string) {
  const admin = await requireAdmin();
  await prisma.transparencyScheme.update({ where: { id: schemeId }, data: { status: "DEPRECATED" } });
  await audit("admin.scheme_deprecate", "TransparencyScheme", schemeId, { actorId: admin.id });
  revalidatePath("/admin/taxonomy");
}

export async function activateQuestionnaireAction(id: string) {
  const admin = await requireAdmin();
  await prisma.$transaction([prisma.questionnaireVersion.updateMany({ data: { active: false } }), prisma.questionnaireVersion.update({ where: { id }, data: { active: true, publishedAt: new Date() } })]);
  await audit("admin.questionnaire_activate", "QuestionnaireVersion", id, { actorId: admin.id });
  revalidatePath("/admin/questionnaires");
}

const categorySchema = z.object({
  key: z.string().trim().regex(/^[a-z0-9_]{2,40}$/, "Clave: minúsculas, números y guiones bajos."),
  labelEs: requiredText(80),
  labelEn: requiredText(80),
  group: requiredText(80),
});

export async function createCategoryAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const admin = await requireAdmin();
  const parsed = categorySchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  if (await prisma.workCategory.findUnique({ where: { key: parsed.data.key } })) return { fieldErrors: { key: "Ya existe una modalidad con esa clave." } };
  const count = await prisma.workCategory.count();
  await prisma.workCategory.create({ data: { ...parsed.data, sortOrder: count } });
  await audit("admin.category_create", "WorkCategory", parsed.data.key, { actorId: admin.id });
  revalidatePath("/admin/categories");
  return { ok: true, message: "Modalidad creada." };
}

export async function toggleCategoryAction(key: string) {
  const admin = await requireAdmin();
  const c = await prisma.workCategory.findUniqueOrThrow({ where: { key } });
  await prisma.workCategory.update({ where: { key }, data: { active: !c.active } });
  await audit("admin.category_toggle", "WorkCategory", key, { actorId: admin.id, metadata: { active: !c.active } });
  revalidatePath("/admin/categories");
}

const legalTextSchema = z.object({
  key: z.enum(["declaration_statement", "privacy", "terms", "legal_notice"]),
  version: z.string().trim().regex(/^\d+\.\d+(\.\d+)?$/, "Versión con formato 1.1 o 1.1.0"),
  title: requiredText(200),
  content: z.string().trim().min(20).max(50000),
  pendingLegalReview: z
    .string()
    .optional()
    .transform((v) => v === "on"),
});

export async function createLegalTextAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const admin = await requireAdmin();
  const parsed = legalTextSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const d = parsed.data;
  if (await prisma.legalText.findUnique({ where: { key_version_locale: { key: d.key, version: d.version, locale: "es" } } })) return { fieldErrors: { version: "Esa versión ya existe." } };
  const t = await prisma.legalText.create({ data: { ...d, locale: "es", contentHash: createHash("sha256").update(d.content).digest("hex"), active: false } });
  await audit("admin.legal_text_create", "LegalText", t.id, { actorId: admin.id, metadata: { key: d.key, version: d.version } });
  revalidatePath("/admin/legal-texts");
  return { ok: true, message: "Versión creada como borrador. Publícala para activarla." };
}

/** Publica una versión: queda inmutable y pasa a ser la activa para su clave. */
export async function publishLegalTextAction(id: string) {
  const admin = await requireAdmin();
  const t = await prisma.legalText.findUniqueOrThrow({ where: { id } });
  await prisma.$transaction([
    prisma.legalText.updateMany({ where: { key: t.key, locale: t.locale, active: true }, data: { active: false } }),
    prisma.legalText.update({ where: { id }, data: { active: true, publishedAt: t.publishedAt ?? new Date() } }),
  ]);
  await audit("admin.legal_text_publish", "LegalText", id, { actorId: admin.id, metadata: { key: t.key, version: t.version } });
  revalidatePath("/admin/legal-texts");
}

const refSchema = z.object({
  title: requiredText(200),
  citation: requiredText(300),
  url: z.string().trim().url(),
  summary: requiredText(3000),
  verificationNote: optionalText(1000),
  active: z
    .string()
    .optional()
    .transform((v) => v === "on"),
});

export async function updateLegalReferenceAction(id: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const admin = await requireAdmin();
  const parsed = refSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  await prisma.legalReference.update({ where: { id }, data: parsed.data });
  await audit("admin.legal_reference_update", "LegalReference", id, { actorId: admin.id });
  revalidatePath("/admin/legal-references");
  return { ok: true, message: "Referencia actualizada." };
}

export async function updateIncidentAction(id: string, fd: FormData) {
  const admin = await requireAdmin();
  const status = z.enum(["OPEN", "IN_PROGRESS", "RESOLVED", "REJECTED"]).parse(fd.get("status"));
  const resolution = String(fd.get("resolution") ?? "").slice(0, 4000) || null;
  await prisma.incident.update({ where: { id }, data: { status, resolution, resolvedAt: status === "RESOLVED" || status === "REJECTED" ? new Date() : null } });
  await audit("admin.incident_update", "Incident", id, { actorId: admin.id, metadata: { status } });
  revalidatePath("/admin/incidents");
}

/**
 * Retirada administrativa de la publicación (p. ej., por una comunicación de
 * contenido ilícito). No modifica el contenido emitido ni lo revoca.
 */
export async function adminWithdrawAction(publicId: string, fd: FormData) {
  const admin = await requireAdmin();
  const reason = String(fd.get("reason") ?? "").slice(0, 1000);
  const v = await prisma.declarationVersion.findUnique({ where: { publicId } });
  if (!v || v.visibility !== "PUBLIC") return;
  await prisma.$transaction([
    prisma.declarationPublication.create({ data: { versionId: v.id, actorId: admin.id, action: "WITHDRAW" } }),
    prisma.declarationVersion.update({ where: { id: v.id }, data: { visibility: "WITHDRAWN" } }),
  ]);
  await audit("admin.declaration_withdraw", "DeclarationVersion", v.id, { actorId: admin.id, metadata: { publicId, reason } });
  revalidatePath("/admin/incidents");
}
