import type { AIUseInput, ContributionInput, DeclarationInput } from "@/lib/oriva-code";

export function use(overrides: Partial<AIUseInput> = {}): AIUseInput {
  return {
    ref: "u1",
    componentRef: null,
    toolIdentified: true,
    phases: ["EVALUATION"],
    functions: ["A3"],
    primaryFunction: null,
    objective: "Obtener una lectura crítica",
    activityDescription: "Se pidió una revisión crítica del manuscrito completo.",
    resultDescription: "Una lista de observaciones sobre el ritmo y la estructura.",
    incorporation: "NONE",
    contributions: [],
    evidenceCount: 0,
    ...overrides,
  };
}

export function contrib(overrides: Partial<ContributionInput> = {}): ContributionInput {
  return {
    ref: "c1",
    type: "B2",
    interventionAnswer: "ACCEPTED",
    retainsRecognizableElements: null,
    interventionDescription: "Se revisaron y aceptaron las correcciones una a una.",
    ...overrides,
  };
}

export function decl(overrides: Partial<DeclarationInput> = {}): DeclarationInput {
  return { aiUsage: "YES", processNarrative: "", uses: [], declarationEvidenceCount: 0, ...overrides };
}
