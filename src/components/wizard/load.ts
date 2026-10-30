import "server-only";
import { notFound, redirect } from "next/navigation";
import { requireUser } from "@/lib/auth/session";
import { DomainError, getOwnedVersion } from "@/lib/declarations/service";
import { effectiveAiUsage } from "@/lib/declarations/load";

/** Carga un borrador del usuario actual; si ya está emitido, redirige a su ficha. */
export async function loadDraftForPage(id: string) {
  const user = await requireUser();
  let v;
  try {
    v = await getOwnedVersion(user.id, id);
  } catch (e) {
    if (e instanceof DomainError) notFound();
    throw e;
  }
  if (v.status !== "DRAFT") redirect(`/app/declarations/${id}`);
  return { user, v, eff: effectiveAiUsage(v) };
}
