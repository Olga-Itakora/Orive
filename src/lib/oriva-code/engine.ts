/**
 * ORIVA Code v1.0 — motor de clasificación determinista.
 *
 * Principios de implementación (ver ORIVA_CODE_SPEC.md):
 *  - La clasificación se deriva exclusivamente de respuestas estructuradas.
 *  - Ningún valor se elige "por ser el número más alto": los números son categorías.
 *  - Cada uso conserva sus propias parejas B–C; nunca se emparejan valores de usos distintos.
 *  - Sin información suficiente no se emite código: se informa "pendiente" o "insuficiente".
 *  - No se usa ningún modelo generativo.
 */
import {
  SCHEME_VERSION,
  type ContributionCode,
  type DocumentationCode,
  type FunctionCode,
  type IncorporatedContributionCode,
  type InterventionCode,
  type UseFunctionCode,
} from "./taxonomy";

// ---------------------------------------------------------------------------
// Entradas (respuestas originales del cuestionario)
// ---------------------------------------------------------------------------

/** Respuesta a "¿Qué hiciste con esa aportación?" */
export type InterventionAnswer =
  | "ACCEPTED" // selección o aceptación sin cambios significativos
  | "MINOR_EDITS" // ajustes menores
  | "TRANSFORMED" // transformación: requiere precisar si quedan elementos reconocibles
  | "OWN_EXPRESSION" // tomé la idea/referencia y realicé la expresión yo
  | "UNSURE"; // no puedo precisarlo

export type TriState = "YES" | "NO" | "UNSURE";

export interface ContributionInput {
  ref: string;
  type: IncorporatedContributionCode;
  interventionAnswer: InterventionAnswer | null;
  /** Solo para TRANSFORMED: ¿se reconocen elementos del resultado original? */
  retainsRecognizableElements: TriState | null;
  interventionDescription: string;
}

export type IncorporationAnswer = "NONE" | "INCORPORATED" | "UNKNOWN";

export interface AIUseInput {
  ref: string;
  componentRef: string | null;
  /** true si la herramienta está identificada por su nombre; false si se registró como desconocida; null si no se indicó. */
  toolIdentified: boolean | null;
  phases: string[];
  functions: UseFunctionCode[];
  /** Función principal, "NONE" si no existe una función principal, null si no se ha respondido. */
  primaryFunction: UseFunctionCode | "NONE" | null;
  objective: string;
  activityDescription: string;
  resultDescription: string;
  incorporation: IncorporationAnswer | null;
  contributions: ContributionInput[];
  /** Evidencias vinculadas a este uso. */
  evidenceCount: number;
}

export type AIUsageAnswer = "NO" | "YES" | "UNCERTAIN";

export interface DeclarationInput {
  aiUsage: AIUsageAnswer | null;
  /** Descripción libre del proceso creativo (relevante para D en declaraciones sin IA o inciertas). */
  processNarrative: string;
  uses: AIUseInput[];
  /** Evidencias registradas a nivel de declaración (aplican a todos los usos). */
  declarationEvidenceCount: number;
}

// ---------------------------------------------------------------------------
// Salidas
// ---------------------------------------------------------------------------

export type ClassificationStatus =
  | "COMPLETE" // clasificación completa
  | "INSUFFICIENT" // el declarante ha indicado expresamente que no puede precisar algo
  | "PENDING"; // faltan respuestas esenciales

export interface Issue {
  rule: string;
  field: string;
  severity: "error" | "warning";
  message: string;
}

export interface BCPair {
  contributionRef: string | null;
  b: ContributionCode;
  /** null cuando la intervención no se ha podido precisar. */
  c: InterventionCode | null;
}

export interface UseProfile {
  ref: string;
  componentRef: string | null;
  status: ClassificationStatus;
  functions: UseFunctionCode[];
  /** Función que aparece en el código compacto: principal o única. null si es multifunción. */
  compactFunction: UseFunctionCode | null;
  multiFunction: boolean;
  pairs: BCPair[];
  documentation: DocumentationCode;
  /** Código compacto A#B#C#D#, solo si es inequívoco. */
  code: string | null;
  /** Notación completa que preserva las parejas B–C, p. ej. "A2+A4 · B1C5 + B2C1 · D2". */
  notation: string;
  rulesApplied: string[];
  issues: Issue[];
}

export interface CompositeProfile {
  A: FunctionCode[];
  B: ContributionCode[];
  C: InterventionCode[];
  D: DocumentationCode;
}

export type DeclarationStatus =
  | "COMPLETE"
  | "PARTIAL" // algún uso con información insuficiente declarada
  | "UNDETERMINED" // el declarante no puede determinar si se usó IA
  | "PENDING"; // faltan respuestas esenciales

export interface GroupProfile {
  status: DeclarationStatus;
  code: string | null;
  composite: CompositeProfile | null;
  compositeNotation: string | null;
  insufficientUseCount: number;
}

export interface ComponentProfile extends GroupProfile {
  componentRef: string | null;
  useRefs: string[];
}

export interface DeclarationProfile extends GroupProfile {
  schemeVersion: string;
  aiUsage: AIUsageAnswer | null;
  documentation: DocumentationCode;
  uses: UseProfile[];
  components: ComponentProfile[];
  rulesApplied: string[];
  issues: Issue[];
}

// ---------------------------------------------------------------------------
// Umbrales objetivos para la dimensión D (documentados en la especificación)
// ---------------------------------------------------------------------------

export const D_THRESHOLDS = {
  /** Longitud mínima de la descripción de la actividad para D1. */
  activityMinChars: 10,
  /** Longitud mínima del resultado obtenido para D2. */
  resultMinChars: 10,
  /** Longitud mínima de la descripción de la intervención humana para D2. */
  interventionMinChars: 10,
  /** Longitud mínima del relato del proceso en declaraciones sin IA/inciertas para D2. */
  narrativeMinChars: 100,
} as const;

const FUNCTION_ORDER: FunctionCode[] = ["A0", "A1", "A2", "A3", "A4", "A5"];
const B_ORDER: ContributionCode[] = ["B0", "B1", "B2", "B3"];
const C_ORDER: InterventionCode[] = ["C0", "C1", "C2", "C3", "C4", "C5"];
const D_ORDER: DocumentationCode[] = ["D0", "D1", "D2", "D3"];

/** Ordena por identificador (orden de presentación), no por "importancia". */
function sortCodes<T extends string>(codes: Iterable<T>, order: T[]): T[] {
  return [...new Set(codes)].sort((a, b) => order.indexOf(a) - order.indexOf(b));
}

function len(s: string | null | undefined): number {
  return (s ?? "").trim().length;
}

// ---------------------------------------------------------------------------
// Dimensión C a partir de la respuesta original
// ---------------------------------------------------------------------------

export function deriveIntervention(
  c: Pick<ContributionInput, "interventionAnswer" | "retainsRecognizableElements">,
): { code: InterventionCode | null; state: "OK" | "MISSING" | "UNSURE"; rule: string } {
  switch (c.interventionAnswer) {
    case "ACCEPTED":
      return { code: "C1", state: "OK", rule: "R-C-01" };
    case "MINOR_EDITS":
      return { code: "C2", state: "OK", rule: "R-C-02" };
    case "TRANSFORMED":
      if (c.retainsRecognizableElements === "YES") return { code: "C3", state: "OK", rule: "R-C-03" };
      if (c.retainsRecognizableElements === "NO") return { code: "C4", state: "OK", rule: "R-C-04" };
      if (c.retainsRecognizableElements === "UNSURE") return { code: null, state: "UNSURE", rule: "R-C-07" };
      return { code: null, state: "MISSING", rule: "R-C-07" };
    case "OWN_EXPRESSION":
      return { code: "C5", state: "OK", rule: "R-C-05" };
    case "UNSURE":
      return { code: null, state: "UNSURE", rule: "R-C-06" };
    default:
      return { code: null, state: "MISSING", rule: "R-C-08" };
  }
}

// ---------------------------------------------------------------------------
// Dimensión D por uso
// ---------------------------------------------------------------------------

export function deriveUseDocumentation(use: AIUseInput, declarationEvidenceCount: number): {
  code: DocumentationCode;
  rules: string[];
} {
  const rules: string[] = [];
  const d1 =
    use.toolIdentified === true &&
    use.functions.length > 0 &&
    len(use.activityDescription) >= D_THRESHOLDS.activityMinChars;
  if (!d1) return { code: "D0", rules: ["R-D-00"] };
  rules.push("R-D-01");

  const contributionsDescribed =
    use.incorporation !== "INCORPORATED" ||
    (use.contributions.length > 0 &&
      use.contributions.every((c) => len(c.interventionDescription) >= D_THRESHOLDS.interventionMinChars));
  const d2 =
    use.phases.length > 0 &&
    len(use.resultDescription) >= D_THRESHOLDS.resultMinChars &&
    use.incorporation !== null &&
    contributionsDescribed;
  if (!d2) return { code: "D1", rules };
  rules.push("R-D-02");

  if (use.evidenceCount + declarationEvidenceCount > 0) {
    rules.push("R-D-03");
    return { code: "D3", rules };
  }
  return { code: "D2", rules };
}

// ---------------------------------------------------------------------------
// classifyAIUse
// ---------------------------------------------------------------------------

export function classifyAIUse(use: AIUseInput, declarationEvidenceCount = 0): UseProfile {
  const issues: Issue[] = [];
  const rules: string[] = [];
  let missing = false;
  let unsure = false;

  // --- A ---
  const functions = sortCodes(use.functions, FUNCTION_ORDER) as UseFunctionCode[];
  if ((functions as string[]).includes("A0")) {
    issues.push({ rule: "R-A-03", field: "functions", severity: "error", message: "A0 solo puede usarse en declaraciones sin utilización de IA." });
    missing = true;
  }
  if (functions.length === 0) {
    issues.push({ rule: "R-A-01", field: "functions", severity: "error", message: "Indica al menos una función de la IA en este uso." });
    missing = true;
  } else {
    rules.push("R-A-01");
  }

  let compactFunction: UseFunctionCode | null = null;
  if (functions.length === 1) {
    compactFunction = functions[0];
  } else if (functions.length > 1) {
    if (use.primaryFunction && use.primaryFunction !== "NONE") {
      if (functions.includes(use.primaryFunction)) {
        compactFunction = use.primaryFunction;
        rules.push("R-A-02");
      } else {
        issues.push({ rule: "R-A-02", field: "primaryFunction", severity: "error", message: "La función principal debe estar entre las funciones seleccionadas." });
        missing = true;
      }
    } else {
      // Sin función principal: perfil multifunción; nunca se elige el número mayor.
      rules.push("R-A-04");
    }
  }

  // --- B / C ---
  const pairs: BCPair[] = [];
  switch (use.incorporation) {
    case "NONE":
      pairs.push({ contributionRef: null, b: "B0", c: "C0" });
      rules.push("R-B-01", "R-BC-01");
      if (use.contributions.length > 0) {
        issues.push({ rule: "R-B-03", field: "contributions", severity: "error", message: "La respuesta «No se incorporó nada» (B0) es incompatible con otras aportaciones. Elige «Sí, algo se incorporó» o quita las aportaciones." });
        missing = true;
      }
      break;
    case "INCORPORATED": {
      rules.push("R-B-02");
      if (use.contributions.length === 0) {
        issues.push({ rule: "R-B-02", field: "contributions", severity: "error", message: "Indica qué tipo de aportación de la IA se incorporó." });
        missing = true;
      }
      const seen = new Set<string>();
      for (const c of use.contributions) {
        if (seen.has(c.type)) {
          issues.push({ rule: "R-B-04", field: "contributions", severity: "error", message: `El tipo ${c.type} aparece repetido en el mismo uso. Si los resultados son heterogéneos, divide el uso en subusos.` });
          missing = true;
        }
        seen.add(c.type);
        const d = deriveIntervention(c);
        rules.push(d.rule);
        if (d.state === "MISSING") {
          issues.push({ rule: d.rule, field: `contribution.${c.ref}.intervention`, severity: "error", message: `Indica qué hiciste con la aportación ${c.type}.` });
          missing = true;
        } else if (d.state === "UNSURE") {
          unsure = true;
        }
        pairs.push({ contributionRef: c.ref, b: c.type, c: d.code });
        issues.push(...compatibilityWarnings(functions, c.type, d.code, c.ref));
      }
      break;
    }
    case "UNKNOWN":
      rules.push("R-B-05");
      unsure = true;
      break;
    default:
      issues.push({ rule: "R-B-06", field: "incorporation", severity: "error", message: "Indica si algo procedente de este uso se incorporó a la obra." });
      missing = true;
  }

  // --- D ---
  const doc = deriveUseDocumentation(use, declarationEvidenceCount);
  rules.push(...doc.rules);

  const status: ClassificationStatus = missing ? "PENDING" : unsure ? "INSUFFICIENT" : "COMPLETE";

  let code: string | null = null;
  if (status === "COMPLETE" && compactFunction && pairs.length === 1 && pairs[0].c) {
    code = `${compactFunction}${pairs[0].b}${pairs[0].c}${doc.code}`;
    rules.push("R-CODE-01");
  }

  const notation = useNotation(functions, compactFunction, pairs, doc.code, status);

  return {
    ref: use.ref,
    componentRef: use.componentRef,
    status,
    functions,
    compactFunction,
    multiFunction: functions.length > 1,
    pairs,
    documentation: doc.code,
    code,
    notation,
    rulesApplied: [...new Set(rules)],
    issues,
  };
}

function useNotation(
  functions: UseFunctionCode[],
  compact: UseFunctionCode | null,
  pairs: BCPair[],
  d: DocumentationCode,
  status: ClassificationStatus,
): string {
  const a = functions.length === 0 ? "A?" : functions.join("+");
  const bc = pairs.length === 0 ? "B?C?" : pairs.map((p) => `${p.b}${p.c ?? "C?"}`).join(" + ");
  const primary = compact && functions.length > 1 ? ` (principal ${compact})` : "";
  const suffix = status === "COMPLETE" ? "" : status === "INSUFFICIENT" ? " · información insuficiente" : " · clasificación pendiente";
  return `${a}${primary} · ${bc} · ${d}${suffix}`;
}

/**
 * Avisos de compatibilidad. Nunca prohíben combinaciones no previstas:
 * invitan a revisar las definiciones (spec §11).
 */
function compatibilityWarnings(
  functions: UseFunctionCode[],
  b: IncorporatedContributionCode,
  c: InterventionCode | null,
  ref: string,
): Issue[] {
  const out: Issue[] = [];
  if (b === "B3" && c === "C5") {
    out.push({
      rule: "W-BC-01",
      field: `contribution.${ref}`,
      severity: "warning",
      message: "Si solo tomaste la idea y realizaste tú toda la expresión final, la aportación quizá sea conceptual (B1) y no de contenido expresivo (B3). Revisa las definiciones.",
    });
  }
  if (b === "B2" && (c === "C4" || c === "C5")) {
    out.push({
      rule: "W-BC-02",
      field: `contribution.${ref}`,
      severity: "warning",
      message: "Combinación poco habitual: una aportación formal (B2) suele aceptarse o editarse (C1/C2). Revisa si la descripción es correcta.",
    });
  }
  if (b === "B3" && !functions.includes("A5") && !functions.includes("A4")) {
    out.push({
      rule: "W-AB-01",
      field: `contribution.${ref}`,
      severity: "warning",
      message: "Hay contenido expresivo incorporado (B3) sin la función de generación (A5) ni de transformación (A4). Revisa las funciones del uso.",
    });
  }
  return out;
}

// ---------------------------------------------------------------------------
// Perfiles de grupo (obra completa o componente)
// ---------------------------------------------------------------------------

function groupProfile(uses: UseProfile[], d: DocumentationCode): GroupProfile {
  if (uses.length === 0) {
    return { status: "PENDING", code: null, composite: null, compositeNotation: null, insufficientUseCount: 0 };
  }
  const pending = uses.some((u) => u.status === "PENDING");
  const insufficient = uses.filter((u) => u.status === "INSUFFICIENT").length;

  const A = sortCodes(uses.flatMap((u) => u.functions), FUNCTION_ORDER);
  const B = sortCodes(uses.flatMap((u) => u.pairs.map((p) => p.b)), B_ORDER);
  const C = sortCodes(
    uses.flatMap((u) => u.pairs.map((p) => p.c).filter((c): c is InterventionCode => c !== null)),
    C_ORDER,
  );
  const composite: CompositeProfile = { A, B, C, D: d };
  const fmt = (xs: string[]) => (xs.length ? xs.join("+") : "—");
  let compositeNotation = `${fmt(A)} | ${fmt(B)} | ${fmt(C)} | ${d}`;
  if (insufficient > 0) compositeNotation += ` (+${insufficient} uso${insufficient > 1 ? "s" : ""} con información insuficiente)`;

  if (pending) {
    return { status: "PENDING", code: null, composite, compositeNotation, insufficientUseCount: insufficient };
  }

  // Código compacto global: solo si todos los usos comparten exactamente la misma
  // función compacta y la misma (única) pareja B–C. Nunca se eligen "los números más altos".
  let code: string | null = null;
  if (insufficient === 0) {
    const signatures = new Set(
      uses.map((u) => (u.compactFunction && u.pairs.length === 1 && u.pairs[0].c ? `${u.compactFunction}${u.pairs[0].b}${u.pairs[0].c}` : null)),
    );
    const allSingleFunction = uses.every((u) => u.functions.length === 1);
    if (signatures.size === 1 && !signatures.has(null) && allSingleFunction) {
      code = `${[...signatures][0]}${d}`;
    }
  }
  return {
    status: insufficient > 0 ? "PARTIAL" : "COMPLETE",
    code,
    composite,
    compositeNotation,
    insufficientUseCount: insufficient,
  };
}

function minDoc(codes: DocumentationCode[]): DocumentationCode {
  if (codes.length === 0) return "D0";
  return codes.reduce((m, c) => (D_ORDER.indexOf(c) < D_ORDER.indexOf(m) ? c : m));
}

/** D de una declaración sin uso de IA o con uso indeterminado (regla R-D-NOAI). */
function narrativeDocumentation(input: DeclarationInput): DocumentationCode {
  if (len(input.processNarrative) < D_THRESHOLDS.narrativeMinChars) return "D0";
  return input.declarationEvidenceCount > 0 ? "D3" : "D2";
}

// ---------------------------------------------------------------------------
// generateCompositeProfile — clasificación de la declaración completa
// ---------------------------------------------------------------------------

export function generateCompositeProfile(input: DeclarationInput): DeclarationProfile {
  const base = { schemeVersion: SCHEME_VERSION, aiUsage: input.aiUsage };

  if (input.aiUsage === null) {
    return {
      ...base,
      status: "PENDING",
      code: null,
      composite: null,
      compositeNotation: null,
      insufficientUseCount: 0,
      documentation: "D0",
      uses: [],
      components: [],
      rulesApplied: [],
      issues: [{ rule: "R-G-00", field: "aiUsage", severity: "error", message: "Indica si se utilizaron sistemas de IA." }],
    };
  }

  if (input.aiUsage === "NO") {
    const d = narrativeDocumentation(input);
    const issues: Issue[] = [];
    if (input.uses.length > 0) {
      issues.push({ rule: "R-G-02", field: "uses", severity: "error", message: "La declaración indica que no se utilizó IA, pero contiene usos registrados. Elimina los usos o cambia la respuesta." });
    }
    const composite: CompositeProfile = { A: ["A0"], B: ["B0"], C: ["C0"], D: d };
    return {
      ...base,
      status: issues.length ? "PENDING" : "COMPLETE",
      code: issues.length ? null : `A0B0C0${d}`,
      composite,
      compositeNotation: `A0 | B0 | C0 | ${d}`,
      insufficientUseCount: 0,
      documentation: d,
      uses: [],
      components: [],
      rulesApplied: ["R-G-01", "R-D-NOAI"],
      issues,
    };
  }

  if (input.aiUsage === "UNCERTAIN") {
    const d = narrativeDocumentation(input);
    const uses = input.uses.map((u) => classifyAIUse(u, input.declarationEvidenceCount));
    return {
      ...base,
      status: "UNDETERMINED",
      code: null,
      composite: null,
      compositeNotation: null,
      insufficientUseCount: 0,
      documentation: d,
      uses,
      components: [],
      rulesApplied: ["R-G-03", "R-D-NOAI"],
      issues: uses.flatMap((u) => u.issues.filter((i) => i.severity === "error")),
    };
  }

  // YES
  const uses = input.uses.map((u) => classifyAIUse(u, input.declarationEvidenceCount));
  const issues: Issue[] = [];
  if (uses.length === 0) {
    issues.push({ rule: "R-G-04", field: "uses", severity: "error", message: "Falta registrar al menos un uso de IA para describir cómo se utilizó." });
  }
  const d = minDoc(uses.map((u) => u.documentation));
  const group = groupProfile(uses, d);

  const componentRefs = [...new Set(uses.map((u) => u.componentRef))];
  const components: ComponentProfile[] = componentRefs.map((ref) => {
    const cu = uses.filter((u) => u.componentRef === ref);
    return { componentRef: ref, useRefs: cu.map((u) => u.ref), ...groupProfile(cu, minDoc(cu.map((u) => u.documentation))) };
  });

  return {
    ...base,
    ...group,
    status: issues.length ? "PENDING" : group.status,
    code: issues.length ? null : group.code,
    documentation: d,
    uses,
    components,
    rulesApplied: ["R-G-05", "R-D-MIN"],
    issues: [...issues, ...uses.flatMap((u) => u.issues)],
  };
}

// ---------------------------------------------------------------------------
// validateTransparencyProfile — ¿puede emitirse la declaración?
// ---------------------------------------------------------------------------

export interface ProfileValidation {
  canIssue: boolean;
  errors: Issue[];
  warnings: Issue[];
}

export function validateTransparencyProfile(profile: DeclarationProfile): ProfileValidation {
  const errors = profile.issues.filter((i) => i.severity === "error");
  const warnings = profile.issues.filter((i) => i.severity === "warning");
  // Los casos de información insuficiente o uso indeterminado SON emitibles:
  // documentan una incertidumbre real sin inventar respuestas.
  const canIssue = errors.length === 0 && profile.status !== "PENDING";
  return { canIssue, errors, warnings };
}

// ---------------------------------------------------------------------------
// generateORIVACode / validateORIVACode
// ---------------------------------------------------------------------------

/** Devuelve el código compacto de la declaración si es inequívoco; null en otro caso. */
export function generateORIVACode(input: DeclarationInput): string | null {
  return generateCompositeProfile(input).code;
}

export const CODE_REGEX = /^A([0-5])B([0-3])C([0-5])D([0-3])$/;

export interface CodeValidation {
  valid: boolean;
  errors: string[];
  parts?: { A: FunctionCode; B: ContributionCode; C: InterventionCode; D: DocumentationCode };
}

/** Valida sintaxis y compatibilidad básica de un código compacto ORIVA v1.0. */
export function validateORIVACode(raw: string): CodeValidation {
  const code = raw.trim().replace(/^ORIVA\s+/i, "").toUpperCase();
  const m = CODE_REGEX.exec(code);
  if (!m) return { valid: false, errors: ["Formato no válido. Se espera A#B#C#D#, por ejemplo A5B3C4D2."] };
  const parts = {
    A: `A${m[1]}` as FunctionCode,
    B: `B${m[2]}` as ContributionCode,
    C: `C${m[3]}` as InterventionCode,
    D: `D${m[4]}` as DocumentationCode,
  };
  const errors: string[] = [];
  if (parts.A === "A0" && (parts.B !== "B0" || parts.C !== "C0")) errors.push("A0 (sin IA) exige B0 y C0.");
  if (parts.B === "B0" && parts.C !== "C0") errors.push("B0 (nada incorporado) exige C0.");
  if (parts.B !== "B0" && parts.C === "C0") errors.push("C0 solo es aplicable cuando no se incorporó ninguna aportación (B0).");
  if (parts.A === "A0" && parts.D === "D1") errors.push("D1 (herramientas y usos identificados) no es aplicable a una declaración sin IA en v1.0.");
  return { valid: errors.length === 0, errors, parts };
}
