import { notFound } from "next/navigation";
import { loadDraftForPage } from "@/components/wizard/load";
import { UseEditor } from "@/components/wizard/use-editor";
import { WizardShell } from "@/components/wizard/wizard-shell";
import type { UseInput } from "@/lib/validation/use";

export const metadata = { title: "Uso de IA" };

export default async function UseEditPage({ params }: { params: Promise<{ id: string; useId: string }> }) {
  const { id, useId } = await params;
  const { v, eff } = await loadDraftForPage(id);
  if (eff !== "YES") notFound();
  const isNew = useId === "new";
  const u = isNew ? null : v.uses.find((x) => x.id === useId);
  if (!isNew && !u) notFound();
  const initial: UseInput = u
    ? {
        title: u.title,
        componentId: u.componentId,
        toolId: u.toolId,
        parentUseId: u.parentUseId,
        phases: u.phases.map((p) => p.phase) as UseInput["phases"],
        functions: u.functions as UseInput["functions"],
        primaryFunction: (u.primaryFunction as UseInput["primaryFunction"]) ?? null,
        objective: u.objective ?? "",
        activityDescription: u.activityDescription ?? "",
        resultDescription: u.resultDescription ?? "",
        incorporation: u.incorporation,
        contributions: u.contributions.map((c) => ({
          type: c.contributionType as "B1" | "B2" | "B3",
          interventionAnswer: (c.interventionAnswer as UseInput["contributions"][number]["interventionAnswer"]) ?? null,
          retainsRecognizableElements: (c.retainsRecognizableElements as "YES" | "NO" | "UNSURE" | null) ?? null,
          interventionDescription: c.interventionDescription ?? "",
        })),
      }
    : {
        title: "",
        componentId: v.declaration.work.components.length === 1 ? v.declaration.work.components[0].id : null,
        toolId: v.tools.length === 1 ? v.tools[0].id : null,
        parentUseId: null,
        phases: [],
        functions: [],
        primaryFunction: null,
        objective: "",
        activityDescription: "",
        resultDescription: "",
        incorporation: null,
        contributions: [],
      };
  return (
    <WizardShell versionId={id} current="uses" effectiveUsage={eff} workTitle={v.declaration.work.title} versionNumber={v.versionNumber} title={isNew ? "Nuevo uso de IA" : `Uso: ${u!.title}`}>
      <UseEditor
        versionId={id}
        useId={isNew ? null : useId}
        initial={initial}
        components={v.declaration.work.components.map((c) => ({ id: c.id, name: c.name }))}
        tools={v.tools.map((t) => ({ id: t.id, name: t.name, identified: t.identified }))}
        otherUses={v.uses.filter((x) => x.id !== useId && !x.parentUseId).map((x) => ({ id: x.id, title: x.title }))}
        phaseHints={(v.declaration.work.category.phaseHints ?? {}) as Record<string, string>}
        evidenceCount={u?.evidence.length ?? 0}
        declarationEvidenceCount={v.evidence.filter((e) => !e.useId).length}
      />
    </WizardShell>
  );
}
