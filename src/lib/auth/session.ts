import "server-only";
import { cache } from "react";
import { cookies, headers } from "next/headers";
import { redirect } from "next/navigation";
import type { Role } from "@prisma/client";
import { prisma } from "@/lib/db";
import { env } from "@/lib/env";
import { randomToken, sha256 } from "@/lib/security/crypto";

export const SESSION_COOKIE = "oriva_session";
const SESSION_DAYS = 30;
const REFRESH_THRESHOLD_DAYS = 15;

export interface SessionUser {
  id: string;
  email: string;
  role: Role;
  displayName: string | null;
  sessionId: string;
}

export async function createSession(userId: string): Promise<void> {
  const token = randomToken(32);
  const expiresAt = new Date(Date.now() + SESSION_DAYS * 86400_000);
  const ua = (await headers()).get("user-agent")?.slice(0, 200) ?? null;
  await prisma.session.create({ data: { id: sha256(token), userId, expiresAt, userAgent: ua } });
  (await cookies()).set(SESSION_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: env().COOKIE_SECURE === "true",
    path: "/",
    expires: expiresAt,
  });
}

export async function destroySession(): Promise<void> {
  const jar = await cookies();
  const token = jar.get(SESSION_COOKIE)?.value;
  if (token) await prisma.session.deleteMany({ where: { id: sha256(token) } });
  jar.delete(SESSION_COOKIE);
}

/** Usuario autenticado de la petición actual (memorizado por petición). */
export const getCurrentUser = cache(async (): Promise<SessionUser | null> => {
  const token = (await cookies()).get(SESSION_COOKIE)?.value;
  if (!token || token.length > 100) return null;
  const id = sha256(token);
  const session = await prisma.session.findUnique({
    where: { id },
    include: { user: { include: { profile: true } } },
  });
  if (!session) return null;
  if (session.expiresAt < new Date() || session.user.disabledAt) {
    await prisma.session.deleteMany({ where: { id } });
    return null;
  }
  if (session.expiresAt.getTime() - Date.now() < REFRESH_THRESHOLD_DAYS * 86400_000) {
    await prisma.session.update({ where: { id }, data: { expiresAt: new Date(Date.now() + SESSION_DAYS * 86400_000) } });
  }
  return {
    id: session.user.id,
    email: session.user.email,
    role: session.user.role,
    displayName: session.user.profile?.displayName ?? null,
    sessionId: id,
  };
});

export async function requireUser(): Promise<SessionUser> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  return user;
}

export async function requireAdmin(): Promise<SessionUser> {
  const user = await requireUser();
  if (user.role !== "ADMIN") redirect("/app");
  return user;
}

export async function revokeAllSessions(userId: string, exceptSessionId?: string): Promise<void> {
  await prisma.session.deleteMany({ where: { userId, ...(exceptSessionId ? { NOT: { id: exceptSessionId } } : {}) } });
}
