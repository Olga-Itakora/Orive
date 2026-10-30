export const STEPS = [
  { key: "work", label: "Obra" },
  { key: "declarant", label: "Declarante y alcance" },
  { key: "usage", label: "Uso de IA" },
  { key: "tools", label: "Herramientas" },
  { key: "uses", label: "Usos de IA" },
  { key: "evidence", label: "Evidencias" },
  { key: "article50", label: "Art. 50" },
  { key: "review", label: "Revisión y emisión" },
] as const;
export type StepKey = (typeof STEPS)[number]["key"];

export function applicableSteps(effectiveUsage: string | null): StepKey[] {
  const all = STEPS.map((s) => s.key);
  if (effectiveUsage === "YES") return all;
  return all.filter((k) => k !== "tools" && k !== "uses");
}

export function nextStep(current: StepKey, effectiveUsage: string | null): StepKey | null {
  const s = applicableSteps(effectiveUsage);
  return s[s.indexOf(current) + 1] ?? null;
}
export function prevStep(current: StepKey, effectiveUsage: string | null): StepKey | null {
  const s = applicableSteps(effectiveUsage);
  return s[s.indexOf(current) - 1] ?? null;
}
