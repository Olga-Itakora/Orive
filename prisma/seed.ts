/**
 * Seed idempotente: taxonomía ORIVA Code v1.0, cuestionario v1.0, modalidades
 * de obra, textos legales, referencias normativas y administrador inicial.
 * Con SEED_DEMO=true crea además un autor de demostración con una declaración emitida.
 */
import { createHash } from "node:crypto";
import { PrismaClient } from "@prisma/client";
import { hash } from "@node-rs/argon2";
import { DIMENSIONS, SCHEME_VERSION, CREATIVE_PHASES } from "../src/lib/oriva-code/taxonomy";
import { RULES_V1 } from "../src/lib/oriva-code/rules";
import { CATEGORIES } from "../src/content/categories";
import { LEGAL_REFERENCES } from "../src/lib/legal/references";
import { DECLARATION_STATEMENT_ES, LEGAL_NOTICE_ES, PRIVACY_ES, TERMS_ES } from "../src/content/legal-texts";

const prisma = new PrismaClient();
const sha = (s: string) => createHash("sha256").update(s).digest("hex");

export const QUESTIONNAIRE_V1 = {
  version: "1.0",
  steps: [
    { key: "work", title: "Obra", questions: ["title", "subtitle", "category", "description", "creationPeriod", "status", "publicationDate", "language", "workVersion", "publisher", "identifier", "url", "components"] },
    { key: "declarant", title: "Declarante y alcance", questions: ["declarantName", "declarantArtisticName", "email(private, from account)", "declarantOrganization", "declarantRole", "scope", "scopeComponentIds", "responsibilityNote"] },
    { key: "usage", title: "Utilización de IA", questions: ["aiUsage", "ownContributionAiUsage", "uncertaintyNote", "processNarrative"] },
    { key: "tools", title: "Herramientas", questions: ["tool.name", "tool.identified", "tool.provider", "tool.modelVersion", "tool.systemType", "tool.purpose", "tool.usagePeriod"] },
    { key: "uses", title: "Usos de IA", questions: ["use.title", "use.component", "use.tool", "use.phases", "use.functions", "use.primaryFunction", "use.objective", "use.activityDescription", "use.resultDescription", "use.incorporation", "contribution.type", "contribution.interventionAnswer", "contribution.retainsRecognizableElements", "contribution.interventionDescription"] },
    { key: "evidence", title: "Evidencias (opcional)", questions: ["evidence.kind", "evidence.description", "evidence.use", "evidence.file"] },
    { key: "article50", title: "Orientación art. 50", questions: ["role", "organizationName", "context", "syntheticMedia", "realisticResemblance", "evidentlyCreative", "publicInterestText", "humanReview", "editorialResponsibility"] },
    { key: "review", title: "Revisión y emisión", questions: ["observations", "accept"] },
  ],
  phases: CREATIVE_PHASES,
};

async function main() {
  // --- Taxonomía ---
  let scheme = await prisma.transparencyScheme.findUnique({ where: { slug_version: { slug: "oriva-code", version: SCHEME_VERSION } } });
  if (!scheme) {
    scheme = await prisma.transparencyScheme.create({
      data: {
        slug: "oriva-code",
        version: SCHEME_VERSION,
        name: `ORIVA Code v${SCHEME_VERSION}`,
        status: "DRAFT",
        spec: JSON.parse(JSON.stringify({ dimensions: DIMENSIONS, rules: RULES_V1, format: "A#B#C#D#" })),
      },
    });
    for (const dim of Object.values(DIMENSIONS)) {
      await prisma.transparencyDimension.create({
        data: {
          schemeId: scheme.id,
          key: dim.key,
          nameEs: dim.name.es,
          nameEn: dim.name.en,
          question: dim.question.es,
          notMeaning: dim.notMeaning.es,
          values: {
            create: dim.values.map((v) => ({
              code: v.code,
              labelEs: v.label.es,
              labelEn: v.label.en,
              descriptionEs: v.description.es,
              descriptionEn: v.description.en,
              exampleEs: v.example?.es ?? null,
            })),
          },
        },
      });
    }
    for (const r of RULES_V1) {
      await prisma.transparencyRule.create({ data: { schemeId: scheme.id, ruleKey: r.key, kind: r.kind, description: r.description } });
    }
    scheme = await prisma.transparencyScheme.update({ where: { id: scheme.id }, data: { status: "PUBLISHED", publishedAt: new Date() } });
    console.log(`✔ Esquema ${scheme.name} publicado`);
  }

  // --- Cuestionario ---
  const q = await prisma.questionnaireVersion.findUnique({ where: { version: "1.0" } });
  if (!q) {
    await prisma.questionnaireVersion.create({
      data: { version: "1.0", schemeId: scheme.id, definition: JSON.parse(JSON.stringify(QUESTIONNAIRE_V1)), active: true, publishedAt: new Date() },
    });
    console.log("✔ Cuestionario v1.0 activo");
  }

  // --- Modalidades ---
  for (const [i, c] of CATEGORIES.entries()) {
    await prisma.workCategory.upsert({
      where: { key: c.key },
      create: { key: c.key, labelEs: c.labelEs, labelEn: c.labelEn, group: c.group, phaseHints: c.phaseHints, sortOrder: i },
      update: {},
    });
  }
  console.log(`✔ ${CATEGORIES.length} modalidades de obra`);

  // --- Textos legales ---
  const texts = [
    { key: "declaration_statement", title: "Declaración responsable de transparencia creativa", content: DECLARATION_STATEMENT_ES },
    { key: "privacy", title: "Política de privacidad", content: PRIVACY_ES },
    { key: "terms", title: "Condiciones de uso", content: TERMS_ES },
    { key: "legal_notice", title: "Aviso legal", content: LEGAL_NOTICE_ES },
  ];
  for (const t of texts) {
    const exists = await prisma.legalText.findUnique({ where: { key_version_locale: { key: t.key, version: "1.0", locale: "es" } } });
    if (!exists) {
      await prisma.legalText.create({
        data: { key: t.key, version: "1.0", locale: "es", title: t.title, content: t.content, contentHash: sha(t.content), active: true, pendingLegalReview: true, publishedAt: new Date() },
      });
    }
  }
  console.log("✔ Textos legales v1.0 (pendientes de revisión jurídica)");

  // --- Referencias normativas ---
  for (const r of LEGAL_REFERENCES) {
    await prisma.legalReference.upsert({
      where: { key: r.key },
      create: { key: r.key, title: r.title, citation: r.citation, url: r.url, summary: r.summary, verificationNote: r.verificationNote, applicableFrom: r.applicableFrom ? new Date(r.applicableFrom) : null },
      update: {},
    });
  }
  console.log(`✔ ${LEGAL_REFERENCES.length} referencias normativas`);

  // --- Administrador ---
  const adminEmail = process.env.SEED_ADMIN_EMAIL?.toLowerCase();
  const adminPassword = process.env.SEED_ADMIN_PASSWORD;
  if (adminEmail && adminPassword) {
    const existing = await prisma.user.findUnique({ where: { email: adminEmail } });
    if (!existing) {
      await prisma.user.create({
        data: {
          email: adminEmail,
          passwordHash: await hash(adminPassword, { memoryCost: 19456, timeCost: 2, parallelism: 1 }),
          role: "ADMIN",
          profile: { create: { displayName: "Administración ORIVA" } },
        },
      });
      console.log(`✔ Administrador ${adminEmail} creado (cambia la contraseña tras el primer acceso)`);
    }
  }
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
