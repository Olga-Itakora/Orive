import { DIMENSIONS, getValue, SCHEME_VERSION, type DimensionKey } from "./taxonomy";
import { validateORIVACode } from "./engine";

export type Locale = "es" | "en";

export interface ExplainedSegment {
  dimension: DimensionKey;
  dimensionName: string;
  code: string;
  label: string;
  description: string;
}

export interface Explanation {
  valid: boolean;
  schemeVersion: string;
  code: string;
  segments: ExplainedSegment[];
  errors: string[];
  disclaimer: string;
}

const DISCLAIMER: Record<Locale, string> = {
  es: "El código ORIVA es una clasificación descriptiva del proceso declarado. No es un porcentaje de autoría, una puntuación de originalidad ni una certificación de cumplimiento legal. Los números son categorías, no magnitudes.",
  en: "The ORIVA code is a descriptive classification of the declared process. It is not a share of authorship, an originality score or a certification of legal compliance. Numbers are categories, not magnitudes.",
};

/** Explica un código compacto (A#B#C#D#) o un valor suelto (p. ej. "B3"). */
export function explainORIVACode(raw: string, locale: Locale = "es"): Explanation {
  const code = raw.trim().replace(/^ORIVA\s+/i, "").toUpperCase();
  const single = /^[ABCD]\d$/.test(code) ? [code] : null;
  const v = single ? { valid: !!getValue(code), errors: getValue(code) ? [] : ["Valor desconocido."] } : validateORIVACode(code);
  const tokens = single ?? code.match(/[ABCD]\d/g) ?? [];
  const segments: ExplainedSegment[] = [];
  for (const t of tokens) {
    const value = getValue(t);
    if (!value) continue;
    const dim = DIMENSIONS[t.charAt(0) as DimensionKey];
    segments.push({
      dimension: dim.key,
      dimensionName: dim.name[locale],
      code: t,
      label: value.label[locale],
      description: value.description[locale],
    });
  }
  return { valid: v.valid, schemeVersion: SCHEME_VERSION, code, segments, errors: v.errors, disclaimer: DISCLAIMER[locale] };
}

export function labelOf(code: string, locale: Locale = "es"): string {
  return getValue(code)?.label[locale] ?? code;
}
