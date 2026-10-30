"use server";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/db";
import { audit } from "@/lib/audit";
import { burnPasswordTime, hashPassword, verifyPassword } from "@/lib/auth/password";
import { createSession, destroySession, getCurrentUser, revokeAllSessions } from "@/lib/auth/session";
import { appUrl } from "@/lib/env";
import { randomToken, sha256 } from "@/lib/security/crypto";
import { rateLimit } from "@/lib/security/rate-limit";
import { clientIp, ipFingerprint } from "@/lib/security/request";
import { changePasswordSchema, forgotSchema, loginSchema, registerSchema, resetSchema } from "@/lib/validation/auth";
import { fieldErrors, formObject, type ActionState } from "@/lib/validation/common";

const TOO_MANY = "Demasiados intentos. Espera unos minutos y vuelve a intentarlo.";

function safeNext(v: FormDataEntryValue | null): string {
  const s = typeof v === "string" ? v : "";
  return s.startsWith("/app") || s.startsWith("/admin") ? s : "/app";
}

export async function registerAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const ip = await clientIp();
  if (!(await rateLimit("register", ip, 10, 3600))) return { error: TOO_MANY };
  const parsed = registerSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const { email, password, displayName } = parsed.data;
  const exists = await prisma.user.findUnique({ where: { email } });
  if (exists) {
    // Mensaje genérico para no confirmar qué correos están registrados.
    return { fieldErrors: { email: "No se ha podido crear la cuenta con este correo. Si ya tienes cuenta, inicia sesión o recupera el acceso." } };
  }
  const user = await prisma.user.create({
    data: { email, passwordHash: await hashPassword(password), profile: { create: { displayName } } },
  });
  await audit("user.register", "User", user.id, { actorId: user.id, ipFingerprint: await ipFingerprint() });
  await createSession(user.id);
  redirect("/app?welcome=1");
}

export async function loginAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const ip = await clientIp();
  const parsed = loginSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const { email, password } = parsed.data;
  if (!(await rateLimit("login-ip", ip, 30, 900)) || !(await rateLimit("login-email", email, 10, 900))) return { error: TOO_MANY };
  const user = await prisma.user.findUnique({ where: { email } });
  if (!user || user.disabledAt) {
    await burnPasswordTime(password);
    return { error: "El correo y la contraseña no coinciden con ninguna cuenta activa. Revísalos o recupera el acceso con «He olvidado mi contraseña»." };
  }
  if (!(await verifyPassword(user.passwordHash, password))) {
    await audit("user.login_failed", "User", user.id, { ipFingerprint: await ipFingerprint() });
    return { error: "El correo y la contraseña no coinciden con ninguna cuenta activa. Revísalos o recupera el acceso con «He olvidado mi contraseña»." };
  }
  await createSession(user.id);
  await audit("user.login", "User", user.id, { actorId: user.id, ipFingerprint: await ipFingerprint() });
  redirect(safeNext(fd.get("next")));
}

export async function logoutAction() {
  await destroySession();
  redirect("/");
}

export async function forgotPasswordAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const ip = await clientIp();
  if (!(await rateLimit("forgot", ip, 5, 3600))) return { error: TOO_MANY };
  const parsed = forgotSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const user = await prisma.user.findUnique({ where: { email: parsed.data.email } });
  if (user && !user.disabledAt) {
    const token = randomToken(32);
    await prisma.passwordResetToken.create({ data: { id: sha256(token), userId: user.id, expiresAt: new Date(Date.now() + 3600_000) } });
    await audit("user.password_reset_requested", "User", user.id, { ipFingerprint: await ipFingerprint() });
    // El envío de correo no está implementado en el MVP (ver DEPLOYMENT.md).
    // En desarrollo el enlace se escribe en el registro del servidor.
    if (process.env.NODE_ENV !== "production" || process.env.ORIVA_LOG_RESET_LINKS === "true") {
      console.info(`[ORIVA] Enlace de recuperación para ${user.email}: ${appUrl(`/reset-password/${token}`)}`);
    }
  }
  return { ok: true, message: "Si existe una cuenta con ese correo, recibirás un enlace para restablecer la contraseña. El enlace caduca en una hora." };
}

export async function resetPasswordAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const ip = await clientIp();
  if (!(await rateLimit("reset", ip, 10, 3600))) return { error: TOO_MANY };
  const parsed = resetSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const record = await prisma.passwordResetToken.findUnique({ where: { id: sha256(parsed.data.token) } });
  if (!record || record.usedAt || record.expiresAt < new Date()) return { error: "El enlace no es válido o ha caducado. Solicita uno nuevo." };
  await prisma.$transaction([
    prisma.user.update({ where: { id: record.userId }, data: { passwordHash: await hashPassword(parsed.data.password) } }),
    prisma.passwordResetToken.update({ where: { id: record.id }, data: { usedAt: new Date() } }),
    prisma.session.deleteMany({ where: { userId: record.userId } }),
  ]);
  await audit("user.password_reset", "User", record.userId, { actorId: record.userId, ipFingerprint: await ipFingerprint() });
  redirect("/login?reset=1");
}

export async function changePasswordAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const me = await getCurrentUser();
  if (!me) redirect("/login");
  if (!(await rateLimit("change-password", me.id, 10, 3600))) return { error: TOO_MANY };
  const parsed = changePasswordSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const user = await prisma.user.findUniqueOrThrow({ where: { id: me.id } });
  if (!(await verifyPassword(user.passwordHash, parsed.data.current))) return { fieldErrors: { current: "La contraseña actual no es correcta." } };
  await prisma.user.update({ where: { id: me.id }, data: { passwordHash: await hashPassword(parsed.data.password) } });
  await revokeAllSessions(me.id, me.sessionId);
  await audit("user.password_change", "User", me.id, { actorId: me.id, ipFingerprint: await ipFingerprint() });
  return { ok: true, message: "Contraseña actualizada. Se han cerrado las demás sesiones." };
}
