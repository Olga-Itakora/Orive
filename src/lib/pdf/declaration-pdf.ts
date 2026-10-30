/**
 * Generación de los PDF de una ORIVA Declaration a partir de la instantánea emitida.
 * El PDF refleja exactamente el contenido registrado (y su huella).
 *
 * Accesibilidad: PDF etiquetado (PDF/UA parcial): estructura H1/H2/P, idioma,
 * título visible en el visor, texto alternativo del QR y paginación marcada
 * como artefacto. Fuentes Inter (OFL) incrustadas.
 */
import path from "node:path";
import PDFDocument from "pdfkit";
import QRCode from "qrcode";
import { DIMENSIONS, labelOf, type DimensionKey } from "@/lib/oriva-code";
import type { FullSnapshot } from "@/lib/integrity/disclosure";
import { EVIDENCE_KIND_LABEL, INCORPORATION_LABEL, INTERVENTION_ANSWER_LABEL, PROFILE_STATUS_LABEL, TRI_LABEL, WORK_STATUS_LABEL } from "@/lib/labels";
import { FINDING_LEVEL_LABEL, type FindingLevel } from "@/lib/legal/article50";

const FONT_DIR = path.join(process.cwd(), "assets", "fonts");
const C = {
  petrol: "#0E3B3C",
  accent: "#2BA39A",
  ink: "#1C2526",
  muted: "#5B6B6C",
  line: "#D9E2E1",
  paper: "#FAF8F4",
};

/* eslint-disable @typescript-eslint/no-explicit-any */
type Data = any;

interface Ctx {
  doc: PDFKit.PDFDocument;
  root: PDFKit.PDFStructureElement;
}

function newDoc(title: string, subject: string, author: string) {
  const doc = new PDFDocument({
    size: "A4",
    margins: { top: 56, bottom: 64, left: 56, right: 56 },
    bufferPages: true,
    tagged: true,
    lang: "es-ES",
    displayTitle: true,
    pdfVersion: "1.7",
    info: { Title: title, Subject: subject, Author: author, Creator: "ORIVA (orivacode.com)", Keywords: "ORIVA, ORIVA Code, transparencia, inteligencia artificial" },
    autoFirstPage: true,
  });
  doc.registerFont("Inter", path.join(FONT_DIR, "Inter-Regular.otf"));
  doc.registerFont("Inter-SemiBold", path.join(FONT_DIR, "Inter-SemiBold.otf"));
  doc.registerFont("Inter-Bold", path.join(FONT_DIR, "Inter-Bold.otf"));
  doc.registerFont("Inter-Italic", path.join(FONT_DIR, "Inter-Italic.otf"));
  doc.registerFont("Mono", path.join(FONT_DIR, "JetBrainsMono-Medium.ttf"));
  doc.font("Inter");
  const root = doc.struct("Document");
  doc.addStructure(root);
  return { doc, root };
}

function toBuffer(doc: PDFKit.PDFDocument): Promise<Buffer> {
  return new Promise((resolve, reject) => {
    const chunks: Buffer[] = [];
    doc.on("data", (c: Buffer) => chunks.push(c));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);
    doc.end();
  });
}

function artifact(doc: PDFKit.PDFDocument, draw: () => void) {
  doc.markContent("Artifact", { type: "Layout" } as any);
  draw();
  doc.endMarkedContent();
}

function width(doc: PDFKit.PDFDocument) {
  return doc.page.width - doc.page.margins.left - doc.page.margins.right;
}

function ensureSpace(doc: PDFKit.PDFDocument, h: number) {
  if (doc.y + h > doc.page.height - doc.page.margins.bottom) doc.addPage();
}

function h1(ctx: Ctx, text: string, w?: number) {
  const { doc } = ctx;
  ctx.root.add(doc.struct("H1", {}, () => {
    doc.font("Inter-Bold").fontSize(17).fillColor(C.petrol).text(text, { width: w ?? width(doc) });
  }));
  doc.moveDown(0.3);
}

function h2(ctx: Ctx, text: string) {
  const { doc } = ctx;
  ensureSpace(doc, 60);
  doc.moveDown(0.6);
  ctx.root.add(doc.struct("H2", {}, () => {
    doc.font("Inter-SemiBold").fontSize(11.5).fillColor(C.petrol).text(text.toUpperCase(), { width: width(doc), characterSpacing: 0.6 });
  }));
  const y = doc.y + 2;
  artifact(doc, () => {
    doc.moveTo(doc.page.margins.left, y).lineTo(doc.page.margins.left + width(doc), y).lineWidth(0.6).strokeColor(C.line).stroke();
  });
  doc.moveDown(0.5);
}

function p(ctx: Ctx, text: string, opts: { color?: string; size?: number; font?: string } = {}) {
  const { doc } = ctx;
  ctx.root.add(doc.struct("P", {}, () => {
    doc.font(opts.font ?? "Inter").fontSize(opts.size ?? 9.5).fillColor(opts.color ?? C.ink).text(text, { width: width(doc), lineGap: 1.5 });
  }));
  doc.moveDown(0.25);
}

function kv(ctx: Ctx, label: string, value: string | null | undefined) {
  if (value === null || value === undefined || value === "") return;
  const { doc } = ctx;
  ensureSpace(doc, 24);
  ctx.root.add(doc.struct("P", {}, () => {
    doc.font("Inter-SemiBold").fontSize(9).fillColor(C.muted).text(`${label}: `, { continued: true });
    doc.font("Inter").fillColor(C.ink).text(String(value), { width: width(doc), lineGap: 1.2 });
  }));
  doc.moveDown(0.15);
}

function codeBox(ctx: Ctx, mainText: string, caption: string) {
  const { doc } = ctx;
  ensureSpace(doc, 70);
  const x = doc.page.margins.left;
  const y = doc.y;
  const w = width(doc);
  doc.font("Mono").fontSize(mainText.length > 30 ? 12 : 20);
  const textH = doc.heightOfString(mainText, { width: w - 32 });
  const h = textH + 40;
  artifact(doc, () => {
    doc.roundedRect(x, y, w, h, 6).fillColor(C.petrol).fill();
  });
  ctx.root.add(doc.struct("P", {}, () => {
    doc.fillColor("#FFFFFF").font("Mono").fontSize(mainText.length > 30 ? 12 : 20).text(mainText, x + 16, y + 12, { width: w - 32 });
    doc.font("Inter").fontSize(8.5).fillColor("#BFE3DF").text(caption, x + 16, doc.y + 4, { width: w - 32 });
  }));
  doc.x = x;
  doc.y = y + h + 10;
}

function codeLegend(ctx: Ctx, codes: { A: string[]; B: string[]; C: string[]; D: string }) {
  const entries: [DimensionKey, string[]][] = [["A", codes.A], ["B", codes.B], ["C", codes.C], ["D", [codes.D]]];
  for (const [k, vals] of entries) {
    const dim = DIMENSIONS[k];
    kv(ctx, `${k} · ${dim.name.es}`, vals.map((c) => `${c} — ${labelOf(c)}`).join("; "));
  }
}

function footer(doc: PDFKit.PDFDocument, publicId: string, label: string) {
  const range = doc.bufferedPageRange();
  for (let i = range.start; i < range.start + range.count; i++) {
    doc.switchToPage(i);
    const bottom = doc.page.margins.bottom;
    doc.page.margins.bottom = 0;
    doc.markContent("Artifact", { type: "Pagination" } as any);
    doc.font("Inter").fontSize(7.5).fillColor(C.muted).text(
      `${label} · ${publicId} · Página ${i + 1} de ${range.count}`,
      doc.page.margins.left,
      doc.page.height - 36,
      { width: width(doc), align: "center", lineBreak: false },
    );
    doc.endMarkedContent();
    doc.page.margins.bottom = bottom;
  }
}

async function header(ctx: Ctx, meta: Data, title: string, qrAlt: string) {
  const { doc } = ctx;
  const x = doc.page.margins.left;
  const qr = await QRCode.toBuffer(meta.verifyUrl, { errorCorrectionLevel: "M", margin: 1, width: 240, color: { dark: C.petrol, light: "#FFFFFF" } });
  artifact(doc, () => {
    doc.rect(0, 0, doc.page.width, 6).fillColor(C.accent).fill();
  });
  ctx.root.add(doc.struct("P", {}, () => {
    doc.font("Inter-Bold").fontSize(22).fillColor(C.petrol).text("ORIVA", x, 40, { characterSpacing: 3 });
    doc.font("Inter").fontSize(8).fillColor(C.muted).text("Creative AI Transparency · Toda creación tiene un proceso. Hazlo visible.");
  }));
  ctx.root.add(doc.struct("Figure", { alt: qrAlt } as any, () => {
    doc.image(qr, doc.page.width - doc.page.margins.right - 78, 34, { width: 78 });
  }));
  doc.x = x;
  doc.y = 92;
  h1(ctx, title, width(doc) - 100);
  ctx.root.add(doc.struct("P", {}, () => {
    doc.font("Mono").fontSize(10).fillColor(C.ink).text(meta.publicId, { continued: true });
    doc.font("Inter").fontSize(9).fillColor(C.muted).text(`   ·   Versión ${meta.versionNumber}   ·   Emitida el ${formatUtc(meta.issuedAt)}`);
  }));
  doc.moveDown(0.6);
}

export function formatUtc(isoStr: string): string {
  const d = new Date(isoStr);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${pad(d.getUTCDate())}/${pad(d.getUTCMonth() + 1)}/${d.getUTCFullYear()} ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())} UTC`;
}

function profileMain(cls: Data): { text: string; caption: string } {
  if (cls.code) return { text: `ORIVA ${cls.code}`, caption: `ORIVA Code v${cls.schemeVersion} · Código compacto de la declaración` };
  if (cls.compositeNotation)
    return { text: cls.compositeNotation, caption: `ORIVA Code v${cls.schemeVersion} · Perfil compuesto: síntesis de los valores presentes (no son parejas B–C). Ver desglose por usos.` };
  return { text: PROFILE_STATUS_LABEL[cls.status] ?? cls.status, caption: `ORIVA Code v${cls.schemeVersion} · No se emite un código sin información suficiente.` };
}

const NOT_MEANING =
  "El código ORIVA es una clasificación descriptiva del proceso declarado. No es un porcentaje de autoría, una puntuación de originalidad ni una certificación de cumplimiento legal. Los números identifican categorías y no son magnitudes comparables.";

const VERIFY_NOTE =
  "La huella criptográfica permite comprobar que el contenido registrado no ha sido alterado. No acredita la identidad del declarante, la veracidad de lo declarado, la originalidad, la titularidad de derechos ni el cumplimiento legal.";

export async function generateFullPdf(snapshot: FullSnapshot, contentHash: string): Promise<Buffer> {
  const d = Object.fromEntries(Object.entries(snapshot.sections).map(([k, v]) => [k, v.data])) as Record<string, Data>;
  const { meta, work, declarant, scope, classification: cls, process: proc, article50, statement, evidence, summary } = d;
  const { doc, root } = newDoc(`ORIVA Declaration ${meta.publicId} — ${work.title}`, "Declaración responsable de transparencia creativa", declarant.name);
  const ctx: Ctx = { doc, root };

  await header(ctx, meta, "ORIVA Declaration — Declaración responsable de transparencia creativa", `Código QR que enlaza con la página de verificación ${meta.verifyUrl}`);

  h2(ctx, "Obra");
  kv(ctx, "Título", work.title + (work.subtitle ? `. ${work.subtitle}` : ""));
  kv(ctx, "Tipo de obra", work.category.label);
  kv(ctx, "Estado", WORK_STATUS_LABEL[work.status]);
  kv(ctx, "Periodo de creación", work.creationPeriod);
  kv(ctx, "Fecha de publicación", work.publicationDate);
  kv(ctx, "Idioma", work.language);
  kv(ctx, "Versión de la obra", work.workVersion);
  kv(ctx, "Editorial o institución", work.publisher);
  kv(ctx, work.identifierType || "Identificador", work.identifier);
  kv(ctx, "URL", work.url);
  kv(ctx, "Descripción", work.description);
  if (work.components.length) kv(ctx, "Componentes", work.components.map((c: Data) => `${c.ref} ${c.name} (${c.kindLabel})`).join("; "));
  if (work.isCollective) kv(ctx, "Obra colectiva", `Sí. Roles de otros participantes: ${work.contributorRoles.map((r: Data) => r.role).join(", ") || "no indicados"}`);

  h2(ctx, "Declarante y alcance");
  kv(ctx, "Declarante", declarant.name + (declarant.artisticName ? ` («${declarant.artisticName}»)` : ""));
  kv(ctx, "Institución u organización", declarant.organization);
  kv(ctx, "Rol en la obra", declarant.role);
  kv(ctx, "Alcance", scope.scopeLabel);
  kv(ctx, "Ámbito de responsabilidad", scope.responsibilityNote);
  p(ctx, scope.coverageStatement, { font: "Inter-Italic", color: C.muted });
  kv(ctx, "Utilización de IA", scope.effectiveAiUsageLabel);
  kv(ctx, "Incertidumbre declarada", scope.uncertaintyNote);

  h2(ctx, "Código o perfil ORIVA");
  const main = profileMain(cls);
  codeBox(ctx, main.text, main.caption);
  kv(ctx, "Estado de la clasificación", PROFILE_STATUS_LABEL[cls.status] ?? cls.status);
  if (cls.composite) codeLegend(ctx, cls.composite);
  p(ctx, NOT_MEANING, { color: C.muted, size: 8.5 });

  h2(ctx, "Resumen");
  p(ctx, summary.text);

  if (proc.tools.length) {
    h2(ctx, "Herramientas declaradas");
    for (const t of proc.tools) {
      kv(ctx, t.ref, [t.identified ? t.name : `Herramienta no identificada${t.name ? ` (${t.name})` : ""}`, t.provider, t.modelVersion, t.systemType, t.usagePeriod].filter(Boolean).join(" · "));
      if (t.purpose) p(ctx, `Finalidad: ${t.purpose}`, { color: C.muted, size: 8.5 });
    }
  }

  if (proc.uses.length) {
    h2(ctx, "Usos de IA declarados");
    for (const u of proc.uses) {
      const c = cls.uses.find((x: Data) => x.ref === u.ref);
      ensureSpace(doc, 120);
      root.add(doc.struct("H3", {}, () => {
        doc.font("Inter-SemiBold").fontSize(10.5).fillColor(C.ink).text(`${u.ref} · ${u.title}`, { width: width(doc) });
      }));
      root.add(doc.struct("P", {}, () => {
        doc.font("Mono").fontSize(10).fillColor(C.petrol).text(c?.code ? `ORIVA ${c.code}` : c?.notation ?? "—");
      }));
      doc.moveDown(0.2);
      kv(ctx, "Componente", u.componentRef ? `${u.componentRef} ${work.components.find((k: Data) => k.ref === u.componentRef)?.name ?? ""}` : "Obra en general");
      kv(ctx, "Herramienta", u.toolRef ? `${u.toolRef} ${proc.tools.find((t: Data) => t.ref === u.toolRef)?.name ?? ""}` : "No indicada");
      kv(ctx, "Funciones (A)", u.functions.map((f: string) => `${f} ${labelOf(f)}`).join("; ") + (u.primaryFunction && u.primaryFunction !== "NONE" && u.functions.length > 1 ? ` · Principal: ${u.primaryFunction}` : ""));
      kv(ctx, "Fases", u.phaseLabels.join(", "));
      kv(ctx, "Objetivo", u.objective);
      kv(ctx, "Actividad", u.activityDescription);
      kv(ctx, "Resultado obtenido", u.resultDescription);
      kv(ctx, "Incorporación a la obra", u.incorporation ? INCORPORATION_LABEL[u.incorporation] : null);
      for (const k of u.contributions) {
        kv(
          ctx,
          `Aportación ${k.type} (${labelOf(k.type)})`,
          `${k.interventionCode ? `${k.interventionCode} ${labelOf(k.interventionCode)}` : "Intervención no precisada"} — ${INTERVENTION_ANSWER_LABEL[k.interventionAnswer] ?? "sin respuesta"}${k.retainsRecognizableElements ? ` (elementos reconocibles: ${TRI_LABEL[k.retainsRecognizableElements]})` : ""}`,
        );
        if (k.interventionDescription) p(ctx, `Intervención humana: ${k.interventionDescription}`, { color: C.muted, size: 8.5 });
      }
      kv(ctx, "Documentación (D)", c ? `${c.documentation} ${labelOf(c.documentation)}` : null);
      if (u.evidenceRefs.length) kv(ctx, "Evidencias vinculadas", u.evidenceRefs.join(", "));
      doc.moveDown(0.4);
    }
  }

  if (proc.processNarrative || proc.observations) {
    h2(ctx, "Descripción del proceso y observaciones");
    if (proc.processNarrative) p(ctx, proc.processNarrative);
    if (proc.observations) kv(ctx, "Observaciones", proc.observations);
  }

  h2(ctx, "Documentación complementaria declarada");
  if (evidence.items.length === 0) p(ctx, "No se han registrado evidencias complementarias.");
  else {
    p(ctx, `Se han registrado ${evidence.items.length} evidencia(s). Su contenido es privado y no se reproduce en este documento. ${evidence.note}`, { color: C.muted, size: 8.5 });
    for (const e of evidence.items) kv(ctx, e.ref, `${EVIDENCE_KIND_LABEL[e.kind] ?? e.kind}${e.useRef ? ` · ${e.useRef}` : " · declaración completa"}${e.sha256 ? ` · SHA-256 ${e.sha256.slice(0, 16)}…` : ""}`);
  }

  if (article50.answered) {
    h2(ctx, "Orientación sobre el artículo 50 del Reglamento (UE) 2024/1689");
    for (const f of article50.findings) {
      kv(ctx, FINDING_LEVEL_LABEL[f.level as FindingLevel] ?? f.level, `${f.title}. ${f.text}`);
    }
    p(ctx, article50.disclaimer, { color: C.muted, size: 8 });
    p(ctx, "Esta orientación es independiente del código ORIVA, que describe prácticas creativas y no incorpora ninguna valoración de cumplimiento.", { color: C.muted, size: 8 });
  }

  h2(ctx, "Declaración responsable");
  p(ctx, statement.text, { font: "Inter-Italic" });
  kv(ctx, "Texto aceptado", `${statement.legalTextKey} v${statement.legalTextVersion} (${statement.locale}) · SHA-256 ${statement.textSha256.slice(0, 16)}…`);
  kv(ctx, "Registro de aceptación", `${formatUtc(statement.acceptedAt)} · ${statement.acceptanceMethod}`);

  h2(ctx, "Integridad y verificación");
  kv(ctx, "Identificador", meta.publicId);
  kv(ctx, "Esquema", `ORIVA Code v${meta.schemeVersion} · Cuestionario v${meta.questionnaireVersion}`);
  if (meta.supersedesPublicId) kv(ctx, "Sustituye a", meta.supersedesPublicId);
  ensureSpace(doc, 40);
  root.add(doc.struct("P", {}, () => {
    doc.font("Inter-SemiBold").fontSize(9).fillColor(C.muted).text("Huella SHA-256 del contenido registrado:");
    doc.font("Mono").fontSize(8.5).fillColor(C.ink).text(contentHash, { width: width(doc) });
  }));
  doc.moveDown(0.3);
  kv(ctx, "Verificación", meta.verifyUrl);
  p(ctx, VERIFY_NOTE, { color: C.muted, size: 8.5 });
  p(ctx, "La consulta pública solo muestra los datos que el declarante haya decidido publicar. Este documento no es una declaración jurada, no tiene fe pública y no constituye una firma electrónica cualificada.", { color: C.muted, size: 8 });

  root.end();
  footer(doc, meta.publicId, "ORIVA Declaration");
  return toBuffer(doc);
}

export async function generateSummaryPdf(snapshot: FullSnapshot, contentHash: string): Promise<Buffer> {
  const d = Object.fromEntries(Object.entries(snapshot.sections).map(([k, v]) => [k, v.data])) as Record<string, Data>;
  const { meta, work, declarant, scope, classification: cls, summary } = d;
  const { doc, root } = newDoc(`Resumen ORIVA ${meta.publicId} — ${work.title}`, "Resumen ORIVA", declarant.name);
  const ctx: Ctx = { doc, root };
  await header(ctx, meta, "Resumen ORIVA", `Código QR que enlaza con la página de verificación ${meta.verifyUrl}`);

  kv(ctx, "Obra", `${work.title}${work.subtitle ? `. ${work.subtitle}` : ""} (${work.category.label})`);
  kv(ctx, "Declarante", declarant.name + (declarant.artisticName ? ` («${declarant.artisticName}»)` : "") + (declarant.role ? ` — ${declarant.role}` : ""));
  kv(ctx, "Alcance", scope.scopeLabel);
  doc.moveDown(0.3);
  const main = profileMain(cls);
  codeBox(ctx, main.text, main.caption);
  if (cls.composite) codeLegend(ctx, cls.composite);
  if (!cls.code && cls.uses.length > 0) {
    kv(ctx, "Códigos por uso", cls.uses.map((u: Data) => `${u.ref}: ${u.code ?? u.notation}`).join("  ·  "));
  }
  doc.moveDown(0.2);
  p(ctx, summary.text, { size: 9 });
  p(ctx, NOT_MEANING, { color: C.muted, size: 8 });
  kv(ctx, "Fecha de emisión", formatUtc(meta.issuedAt));
  kv(ctx, "Identificador", meta.publicId);
  root.add(doc.struct("P", {}, () => {
    doc.font("Inter-SemiBold").fontSize(8).fillColor(C.muted).text("Huella SHA-256: ", { continued: true });
    doc.font("Mono").fontSize(7.5).fillColor(C.ink).text(contentHash);
  }));
  kv(ctx, "Verificación", meta.verifyUrl);
  p(ctx, VERIFY_NOTE, { color: C.muted, size: 7.5 });

  root.end();
  footer(doc, meta.publicId, "Resumen ORIVA");
  return toBuffer(doc);
}
