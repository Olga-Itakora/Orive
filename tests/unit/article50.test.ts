import { describe, expect, it } from "vitest";
import { article50AnswersSchema, assessArticle50 } from "@/lib/legal/article50";

const a = (o: Record<string, unknown>) => article50AnswersSchema.parse(o);
const ids = (o: Record<string, unknown>) => assessArticle50(a(o)).findings.map((f) => f.id);

describe("Orientación del artículo 50", () => {
  it("sin respuestas no produce orientación", () => {
    expect(assessArticle50(a({})).answered).toBe(false);
  });
  it("ultrasuplantación en contexto profesional → posible obligación", () => {
    const r = assessArticle50(a({ role: "OWN_AUTHORITY", context: "PROFESSIONAL", syntheticMedia: "YES", realisticResemblance: "YES", evidentlyCreative: "NO" }));
    expect(r.findings[0]).toMatchObject({ id: "DEEPFAKE", level: "POSSIBLE_OBLIGATION" });
    expect(r.findings[0].references).toContain("AIA-50-4-1");
  });
  it("obra manifiestamente creativa → obligación limitada", () => {
    expect(ids({ role: "OWN_AUTHORITY", context: "PROFESSIONAL", syntheticMedia: "YES", realisticResemblance: "YES", evidentlyCreative: "YES" })).toContain("DEEPFAKE_CREATIVE");
  });
  it("una imagen sintética no realista no es una ultrasuplantación", () => {
    const f = ids({ role: "OWN_AUTHORITY", context: "PROFESSIONAL", syntheticMedia: "YES", realisticResemblance: "NO" });
    expect(f).toContain("SYNTHETIC_NOT_DEEPFAKE");
    expect(f).toContain("NOT_IDENTIFIED");
    expect(f).not.toContain("DEEPFAKE");
  });
  it("uso personal no profesional no activa obligaciones de responsable del despliegue", () => {
    const f = ids({ role: "OWN_AUTHORITY", context: "PERSONAL_NON_PROFESSIONAL", syntheticMedia: "YES", realisticResemblance: "YES", evidentlyCreative: "NO" });
    expect(f).toContain("PERSONAL_USE");
    expect(f).not.toContain("DEEPFAKE");
  });
  it("texto de interés público con revisión y responsabilidad editorial → posible excepción", () => {
    const r = assessArticle50(a({ role: "FOR_ORGANIZATION", context: "PROFESSIONAL", publicInterestText: "YES", humanReview: "YES", editorialResponsibility: "YES" }));
    expect(r.findings.map((f) => f.id)).toEqual(expect.arrayContaining(["ORG_AUTHORITY", "PUBLIC_TEXT_EXCEPTION", "NOT_IDENTIFIED"]));
  });
  it("texto de interés público sin control editorial → posible obligación", () => {
    expect(ids({ role: "OWN_AUTHORITY", context: "PROFESSIONAL", publicInterestText: "YES", humanReview: "NO", editorialResponsibility: "NO" })).toContain("PUBLIC_TEXT");
  });
  it("nunca afirma cumplimiento: el resultado negativo lo advierte expresamente", () => {
    const r = assessArticle50(a({ role: "OWN_AUTHORITY", context: "PROFESSIONAL", syntheticMedia: "NO", publicInterestText: "NO" }));
    expect(r.findings.at(-1)!.text).toMatch(/no equivale a una certificación de cumplimiento legal/);
    expect(r.disclaimer).toMatch(/no constituye asesoramiento jurídico/);
  });
});
