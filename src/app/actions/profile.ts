"use server";
import { revalidatePath } from "next/cache";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { audit } from "@/lib/audit";
import { requireUser, revokeAllSessions } from "@/lib/auth/session";
import { fieldErrors, formObject, optionalText, optionalUrl, type ActionState } from "@/lib/validation/common";

const profileSchema = z.object({
  displayName: z.string().trim().min(2, "Indica tu nombre profesional.").max(120),
  artisticName: optionalText(120),
  organization: optionalText(200),
  bio: optionalText(2000),
  website: optionalUrl,
});

export async function updateProfileAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const parsed = profileSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  await prisma.creatorProfile.upsert({ where: { userId: me.id }, create: { userId: me.id, ...parsed.data }, update: parsed.data });
  revalidatePath("/app/profile");
  return { ok: true, message: "Perfil actualizado. Los cambios no afectan a las declaraciones ya emitidas." };
}

export async function signOutOthersAction() {
  const me = await requireUser();
  await revokeAllSessions(me.id, me.sessionId);
  await audit("user.sessions_revoked", "User", me.id, { actorId: me.id });
  revalidatePath("/app/profile");
}

export async function requestErasureAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  if (fd.get("confirm") !== "on") return { fieldErrors: { confirm: "Confirma la solicitud." } };
  const existing = await prisma.incident.findFirst({ where: { userId: me.id, type: "DATA_ERASURE", status: { in: ["OPEN", "IN_PROGRESS"] } } });
  if (existing) return { ok: true, message: "Ya tienes una solicitud de supresión en curso." };
  await prisma.incident.create({
    data: { type: "DATA_ERASURE", email: me.email, userId: me.id, message: String(fd.get("details") ?? "").slice(0, 2000) || "Solicitud de supresión de la cuenta desde el perfil." },
  });
  await audit("user.erasure_requested", "User", me.id, { actorId: me.id });
  return { ok: true, message: "Solicitud registrada. Te responderemos en el plazo legal de un mes. Las declaraciones emitidas pueden conservarse de forma limitada si existe una base legítima; te informaremos de ello." };
}
