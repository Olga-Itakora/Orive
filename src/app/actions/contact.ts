"use server";
import { z } from "zod";
import { prisma } from "@/lib/db";
import { audit } from "@/lib/audit";
import { getCurrentUser } from "@/lib/auth/session";
import { normalizePublicId } from "@/lib/ids";
import { rateLimit } from "@/lib/security/rate-limit";
import { clientIp } from "@/lib/security/request";
import { emailSchema } from "@/lib/validation/auth";
import { fieldErrors, formObject, optionalText, type ActionState } from "@/lib/validation/common";

const schema = z.object({
  type: z.enum(["CONTACT", "CONTENT_REPORT", "TECHNICAL", "DATA_ACCESS", "DATA_RECTIFICATION", "DATA_ERASURE", "DATA_OBJECTION", "DATA_PORTABILITY", "DATA_RESTRICTION"], { error: "Selecciona el motivo." }),
  name: optionalText(120),
  email: emailSchema,
  publicId: optionalText(30),
  message: z.string().trim().min(10, "Describe tu consulta (mínimo 10 caracteres).").max(5000),
  website: z.string().max(0).optional(), // trampa anti-spam
});

export async function contactAction(_: ActionState, fd: FormData): Promise<ActionState> {
  if (!(await rateLimit("contact", await clientIp(), 5, 3600))) return { error: "Se ha alcanzado el límite de mensajes por hora desde esta conexión. Vuelve a enviarlo dentro de un rato." };
  const parsed = schema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const d = parsed.data;
  const user = await getCurrentUser();
  const inc = await prisma.incident.create({
    data: {
      type: d.type,
      name: d.name,
      email: d.email,
      publicId: d.publicId ? normalizePublicId(d.publicId) ?? d.publicId : null,
      message: d.message,
      userId: user?.id ?? null,
    },
  });
  await audit("incident.create", "Incident", inc.id, { actorId: user?.id ?? null, metadata: { type: d.type } });
  return { ok: true, message: "Mensaje recibido. Te responderemos en el correo indicado. Las solicitudes de derechos de protección de datos se atienden en el plazo legal de un mes." };
}
