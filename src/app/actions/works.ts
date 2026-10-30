"use server";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/db";
import { audit } from "@/lib/audit";
import { requireUser } from "@/lib/auth/session";
import { getOwnedWork } from "@/lib/declarations/service";
import { componentSchema, contributorSchema, workSchema } from "@/lib/validation/work";
import { fieldErrors, formObject, type ActionState } from "@/lib/validation/common";

function workData(d: ReturnType<typeof workSchema.parse>) {
  return { ...d, publicationDate: d.publicationDate ? new Date(`${d.publicationDate}T00:00:00Z`) : null };
}

export async function createWorkAction(_: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const parsed = workSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const cat = await prisma.workCategory.findFirst({ where: { key: parsed.data.categoryKey, active: true } });
  if (!cat) return { fieldErrors: { categoryKey: "Tipo de obra no válido." } };
  const work = await prisma.work.create({ data: { ...workData(parsed.data), ownerId: me.id } });
  await audit("work.create", "Work", work.id, { actorId: me.id });
  redirect(fd.get("then") === "declare" ? `/app/works/${work.id}?declare=1` : `/app/works/${work.id}`);
}

export async function updateWorkAction(workId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  await getOwnedWork(me.id, workId);
  const parsed = workSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  await prisma.work.update({ where: { id: workId }, data: workData(parsed.data) });
  revalidatePath(`/app/works/${workId}`);
  const next = fd.get("next");
  if (typeof next === "string" && next.startsWith("/app/")) redirect(next);
  return { ok: true, message: "Obra guardada." };
}

export async function deleteWorkAction(workId: string) {
  const me = await requireUser();
  await getOwnedWork(me.id, workId);
  const count = await prisma.declaration.count({ where: { workId } });
  if (count > 0) redirect(`/app/works/${workId}?error=has-declarations`);
  await prisma.work.delete({ where: { id: workId } });
  await audit("work.delete", "Work", workId, { actorId: me.id });
  redirect("/app/works");
}

export async function addComponentAction(workId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const work = await getOwnedWork(me.id, workId);
  const parsed = componentSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  if (work.components.length >= 50) return { error: "Máximo 50 componentes." };
  await prisma.workComponent.create({ data: { ...parsed.data, workId, sortOrder: work.components.length } });
  revalidatePath(`/app/works/${workId}`);
  return { ok: true };
}

export async function deleteComponentAction(workId: string, componentId: string) {
  const me = await requireUser();
  const work = await getOwnedWork(me.id, workId);
  if (!work.components.some((c) => c.id === componentId)) return;
  const used = await prisma.aIUse.count({ where: { componentId } });
  if (used > 0) redirect(`/app/works/${workId}?error=component-in-use`);
  await prisma.workComponent.delete({ where: { id: componentId } });
  revalidatePath(`/app/works/${workId}`);
}

export async function addContributorAction(workId: string, _: ActionState, fd: FormData): Promise<ActionState> {
  const me = await requireUser();
  const work = await getOwnedWork(me.id, workId);
  const parsed = contributorSchema.safeParse(formObject(fd));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const componentIds = fd.getAll("componentIds").map(String).filter((id) => work.components.some((c) => c.id === id));
  await prisma.workContributor.create({ data: { ...parsed.data, workId, componentIds } });
  if (!work.isCollective) await prisma.work.update({ where: { id: workId }, data: { isCollective: true } });
  revalidatePath(`/app/works/${workId}`);
  return { ok: true };
}

export async function deleteContributorAction(workId: string, contributorId: string) {
  const me = await requireUser();
  const work = await getOwnedWork(me.id, workId);
  if (!work.contributors.some((c) => c.id === contributorId)) return;
  await prisma.workContributor.delete({ where: { id: contributorId } });
  revalidatePath(`/app/works/${workId}`);
}
