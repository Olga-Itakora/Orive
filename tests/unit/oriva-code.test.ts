import { describe, expect, it } from "vitest";
import {
  classifyAIUse,
  explainORIVACode,
  generateCompositeProfile,
  generateORIVACode,
  validateORIVACode,
  validateTransparencyProfile,
} from "@/lib/oriva-code";
import { contrib, decl, use } from "./fixtures";

describe("Caso 1 — novela sin IA", () => {
  it("genera A0B0C0D0 y es emitible", () => {
    const p = generateCompositeProfile(decl({ aiUsage: "NO" }));
    expect(p.code).toBe("A0B0C0D0");
    expect(p.status).toBe("COMPLETE");
    expect(validateTransparencyProfile(p).canIssue).toBe(true);
  });
  it("D2 si se describe el proceso; D3 con evidencias", () => {
    const narrative = "Escribí la novela a mano durante dos años, con cuadernos de notas y tres borradores revisados por mi editora.".repeat(2);
    expect(generateORIVACode(decl({ aiUsage: "NO", processNarrative: narrative }))).toBe("A0B0C0D2");
    expect(generateORIVACode(decl({ aiUsage: "NO", processNarrative: narrative, declarationEvidenceCount: 1 }))).toBe("A0B0C0D3");
  });
  it("evidencias sin relato no elevan D (criterio acumulativo)", () => {
    expect(generateORIVACode(decl({ aiUsage: "NO", declarationEvidenceCount: 2 }))).toBe("A0B0C0D0");
  });
  it("rechaza usos registrados si se declaró que no hubo IA", () => {
    const p = generateCompositeProfile(decl({ aiUsage: "NO", uses: [use()] }));
    expect(p.code).toBeNull();
    expect(validateTransparencyProfile(p).canIssue).toBe(false);
  });
});

describe("Caso 2 — ensayo revisado, sugerencias descartadas", () => {
  it("A3 B0 C0", () => {
    const u = classifyAIUse(use({ functions: ["A3"], incorporation: "NONE" }));
    expect(u.status).toBe("COMPLETE");
    expect(u.pairs).toEqual([{ contributionRef: null, b: "B0", c: "C0" }]);
    expect(u.code).toBe("A3B0C0D2");
  });
});

describe("Caso 3 — corrección lingüística aceptada", () => {
  it("A4 B2 C1", () => {
    const u = classifyAIUse(
      use({ functions: ["A4"], phases: ["CORRECTION"], incorporation: "INCORPORATED", contributions: [contrib({ type: "B2", interventionAnswer: "ACCEPTED" })] }),
    );
    expect(u.code).toBe("A4B2C1D2");
  });
});

describe("Caso 4 — desarrollo conceptual", () => {
  it("A2 B1 C5", () => {
    const u = classifyAIUse(
      use({ functions: ["A2"], phases: ["IDEATION"], incorporation: "INCORPORATED", contributions: [contrib({ type: "B1", interventionAnswer: "OWN_EXPRESSION" })] }),
    );
    expect(u.code).toBe("A2B1C5D2");
    expect(u.issues.filter((i) => i.severity === "warning")).toHaveLength(0);
  });
});

describe("Caso 5 — ilustración generada sin cambios", () => {
  it("A5 B3 C1", () => {
    const u = classifyAIUse(use({ functions: ["A5"], incorporation: "INCORPORATED", contributions: [contrib({ type: "B3", interventionAnswer: "ACCEPTED" })] }));
    expect(u.code).toBe("A5B3C1D2");
  });
});

describe("Caso 6 — ilustración transformada: C3 vs C4", () => {
  const base = { functions: ["A5" as const], incorporation: "INCORPORATED" as const };
  it("C3 si se reconocen elementos del original", () => {
    const u = classifyAIUse(use({ ...base, contributions: [contrib({ type: "B3", interventionAnswer: "TRANSFORMED", retainsRecognizableElements: "YES" })] }));
    expect(u.code).toBe("A5B3C3D2");
  });
  it("C4 si la expresión es considerablemente diferente", () => {
    const u = classifyAIUse(use({ ...base, contributions: [contrib({ type: "B3", interventionAnswer: "TRANSFORMED", retainsRecognizableElements: "NO" })] }));
    expect(u.code).toBe("A5B3C4D2");
  });
  it("sin precisar: pendiente (no se elige arbitrariamente)", () => {
    const u = classifyAIUse(use({ ...base, contributions: [contrib({ type: "B3", interventionAnswer: "TRANSFORMED", retainsRecognizableElements: null })] }));
    expect(u.status).toBe("PENDING");
    expect(u.code).toBeNull();
  });
  it("si el declarante no puede precisarlo: información insuficiente", () => {
    const u = classifyAIUse(use({ ...base, contributions: [contrib({ type: "B3", interventionAnswer: "TRANSFORMED", retainsRecognizableElements: "UNSURE" })] }));
    expect(u.status).toBe("INSUFFICIENT");
    expect(u.code).toBeNull();
    expect(u.pairs[0]).toMatchObject({ b: "B3", c: null });
  });
});

describe("Caso 7 — obra híbrida", () => {
  const uses = [
    use({ ref: "rev", componentRef: "text", functions: ["A4"], incorporation: "INCORPORATED", contributions: [contrib({ type: "B2", interventionAnswer: "ACCEPTED" })] }),
    use({ ref: "idea", componentRef: "text", functions: ["A2"], incorporation: "INCORPORATED", contributions: [contrib({ type: "B1", interventionAnswer: "OWN_EXPRESSION" })] }),
    use({ ref: "ill", componentRef: "img", functions: ["A5"], incorporation: "INCORPORATED", contributions: [contrib({ type: "B3", interventionAnswer: "TRANSFORMED", retainsRecognizableElements: "YES" })] }),
  ];
  const p = generateCompositeProfile(decl({ uses }));
  it("conserva los tres usos y sus códigos", () => {
    expect(p.uses.map((u) => u.code)).toEqual(["A4B2C1D2", "A2B1C5D2", "A5B3C3D2"]);
  });
  it("no fabrica un código global: muestra perfil compuesto", () => {
    expect(p.code).toBeNull();
    expect(p.compositeNotation).toBe("A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2");
    expect(p.status).toBe("COMPLETE");
  });
  it("conserva las parejas B–C originales por uso", () => {
    expect(p.uses.find((u) => u.ref === "idea")!.pairs).toEqual([{ contributionRef: "c1", b: "B1", c: "C5" }]);
  });
  it("agrupa por componente", () => {
    const text = p.components.find((c) => c.componentRef === "text")!;
    expect(text.useRefs).toEqual(["rev", "idea"]);
    expect(text.compositeNotation).toBe("A2+A4 | B1+B2 | C1+C5 | D2");
    expect(p.components.find((c) => c.componentRef === "img")!.code).toBe("A5B3C3D2");
  });
  it("código global compacto solo si todos los usos coinciden", () => {
    const same = generateCompositeProfile(decl({ uses: [uses[0], { ...uses[0], ref: "rev2" }] }));
    expect(same.code).toBe("A4B2C1D2");
  });
});

describe("Caso 9 — información insuficiente", () => {
  it("herramienta desconocida: se clasifica pero D0", () => {
    const u = classifyAIUse(use({ toolIdentified: false }));
    expect(u.documentation).toBe("D0");
    expect(u.code).toBe("A3B0C0D0");
  });
  it("incorporación desconocida: insuficiente, sin código", () => {
    const p = generateCompositeProfile(decl({ uses: [use({ incorporation: "UNKNOWN" })] }));
    expect(p.status).toBe("PARTIAL");
    expect(p.code).toBeNull();
    expect(validateTransparencyProfile(p).canIssue).toBe(true);
  });
  it("no sabe si se usó IA: indeterminado, sin código, emitible", () => {
    const p = generateCompositeProfile(decl({ aiUsage: "UNCERTAIN" }));
    expect(p.status).toBe("UNDETERMINED");
    expect(p.code).toBeNull();
    expect(validateTransparencyProfile(p).canIssue).toBe(true);
  });
  it("respuestas esenciales vacías: pendiente, no emitible", () => {
    const p = generateCompositeProfile(decl({ uses: [use({ incorporation: null })] }));
    expect(p.status).toBe("PENDING");
    expect(validateTransparencyProfile(p).canIssue).toBe(false);
  });
  it("sí, pero sin usos: pendiente", () => {
    expect(generateCompositeProfile(decl({ uses: [] })).status).toBe("PENDING");
  });
});

describe("Dimensión A — multifunción", () => {
  it("sin función principal no elige el número mayor", () => {
    const u = classifyAIUse(use({ functions: ["A3", "A2"], primaryFunction: "NONE" }));
    expect(u.compactFunction).toBeNull();
    expect(u.code).toBeNull();
    expect(u.notation.startsWith("A2+A3 ·")).toBe(true);
    expect(u.status).toBe("COMPLETE");
  });
  it("con función principal usa esa función", () => {
    const u = classifyAIUse(use({ functions: ["A2", "A3"], primaryFunction: "A2" }));
    expect(u.code).toBe("A2B0C0D2");
  });
  it("función principal ajena a la selección es un error", () => {
    expect(classifyAIUse(use({ functions: ["A2", "A3"], primaryFunction: "A5" })).status).toBe("PENDING");
  });
});

describe("Dimensiones B y C", () => {
  it("varias aportaciones en un uso conservan su propia C", () => {
    const u = classifyAIUse(
      use({
        functions: ["A4"],
        incorporation: "INCORPORATED",
        contributions: [contrib({ ref: "x", type: "B1", interventionAnswer: "OWN_EXPRESSION" }), contrib({ ref: "y", type: "B2", interventionAnswer: "ACCEPTED" })],
      }),
    );
    expect(u.pairs.map((p) => `${p.b}${p.c}`)).toEqual(["B1C5", "B2C1"]);
    expect(u.code).toBeNull();
    expect(u.notation).toContain("B1C5 + B2C1");
  });
  it("B3+C5 genera aviso, no bloqueo", () => {
    const u = classifyAIUse(use({ functions: ["A5"], incorporation: "INCORPORATED", contributions: [contrib({ type: "B3", interventionAnswer: "OWN_EXPRESSION" })] }));
    expect(u.status).toBe("COMPLETE");
    expect(u.issues.some((i) => i.rule === "W-BC-01" && i.severity === "warning")).toBe(true);
  });
  it("tipo de aportación repetido exige dividir en subusos", () => {
    const u = classifyAIUse(use({ incorporation: "INCORPORATED", contributions: [contrib({ ref: "a" }), contrib({ ref: "b" })] }));
    expect(u.status).toBe("PENDING");
  });
  it("NONE con aportaciones es incoherente (B0 incompatible)", () => {
    expect(classifyAIUse(use({ incorporation: "NONE", contributions: [contrib()] })).status).toBe("PENDING");
  });
});

describe("Dimensión D — criterios objetivos", () => {
  it("D1 sin fases/resultado", () => {
    expect(classifyAIUse(use({ phases: [] })).documentation).toBe("D1");
  });
  it("D2 exige descripción de la intervención en cada aportación", () => {
    const u = classifyAIUse(use({ functions: ["A4"], incorporation: "INCORPORATED", contributions: [contrib({ interventionDescription: "" })] }));
    expect(u.documentation).toBe("D1");
  });
  it("D3 con evidencia del uso o de la declaración", () => {
    expect(classifyAIUse(use({ evidenceCount: 1 })).documentation).toBe("D3");
    expect(classifyAIUse(use(), 1).documentation).toBe("D3");
  });
  it("D global es el mínimo de los usos (conservador)", () => {
    const p = generateCompositeProfile(decl({ uses: [use({ ref: "a", evidenceCount: 1 }), use({ ref: "b", phases: [] })] }));
    expect(p.documentation).toBe("D1");
  });
});

describe("validateORIVACode / explainORIVACode", () => {
  it.each([
    ["A5B3C4D2", true],
    ["ORIVA A0B0C0D0", true],
    ["a4b2c1d2", true],
    ["A6B3C4D2", false],
    ["A5B0C3D2", false],
    ["A5B3C0D2", false],
    ["A0B3C1D0", false],
    ["A5B3C4", false],
  ])("%s → %s", (code, ok) => {
    expect(validateORIVACode(code).valid).toBe(ok);
  });
  it("explica cada segmento", () => {
    const e = explainORIVACode("A5B3C4D2");
    expect(e.segments.map((s) => s.label)).toEqual([
      "Generación de contenido creativo",
      "Aportación de contenido expresivo",
      "Reelaboración creativa",
      "Proceso creativo descrito en detalle",
    ]);
    expect(e.disclaimer).toMatch(/no es un porcentaje/i);
  });
  it("explica en inglés", () => {
    expect(explainORIVACode("B1", "en").segments[0].label).toBe("Conceptual contribution");
  });
});
