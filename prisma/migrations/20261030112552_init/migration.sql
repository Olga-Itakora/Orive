-- CreateEnum
CREATE TYPE "Role" AS ENUM ('AUTHOR', 'ADMIN');

-- CreateEnum
CREATE TYPE "WorkStatus" AS ENUM ('IN_PROGRESS', 'FINISHED', 'PUBLISHED');

-- CreateEnum
CREATE TYPE "ComponentKind" AS ENUM ('TEXT', 'IMAGE', 'AUDIO', 'VIDEO', 'CODE', 'INTERACTIVE', 'OTHER');

-- CreateEnum
CREATE TYPE "DocumentStatus" AS ENUM ('DRAFT', 'ISSUED', 'SUPERSEDED', 'REVOKED');

-- CreateEnum
CREATE TYPE "Visibility" AS ENUM ('PRIVATE', 'PUBLIC', 'WITHDRAWN');

-- CreateEnum
CREATE TYPE "DeclarationScope" AS ENUM ('WHOLE_WORK', 'COMPONENTS', 'OWN_CONTRIBUTION');

-- CreateEnum
CREATE TYPE "AIUsageAnswer" AS ENUM ('NO', 'YES', 'UNCERTAIN', 'OWN_CONTRIBUTION_ONLY');

-- CreateEnum
CREATE TYPE "IncorporationAnswer" AS ENUM ('NONE', 'INCORPORATED', 'UNKNOWN');

-- CreateEnum
CREATE TYPE "EvidenceKind" AS ENUM ('VERSION_HISTORY', 'CONVERSATION_LOG', 'WORK_MATERIAL', 'SCREENSHOT', 'OTHER');

-- CreateEnum
CREATE TYPE "SchemeStatus" AS ENUM ('DRAFT', 'PUBLISHED', 'DEPRECATED');

-- CreateEnum
CREATE TYPE "RuleKind" AS ENUM ('DERIVATION', 'VALIDATION', 'COMPATIBILITY_WARNING', 'DOCUMENTATION', 'AGGREGATION');

-- CreateEnum
CREATE TYPE "ProfileScope" AS ENUM ('USE', 'COMPONENT', 'WORK');

-- CreateEnum
CREATE TYPE "IncidentType" AS ENUM ('CONTACT', 'CONTENT_REPORT', 'TECHNICAL', 'DATA_ACCESS', 'DATA_RECTIFICATION', 'DATA_ERASURE', 'DATA_OBJECTION', 'DATA_PORTABILITY', 'DATA_RESTRICTION');

-- CreateEnum
CREATE TYPE "IncidentStatus" AS ENUM ('OPEN', 'IN_PROGRESS', 'RESOLVED', 'REJECTED');

-- CreateTable
CREATE TABLE "User" (
    "id" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "passwordHash" TEXT NOT NULL,
    "role" "Role" NOT NULL DEFAULT 'AUTHOR',
    "locale" TEXT NOT NULL DEFAULT 'es',
    "disabledAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "User_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Session" (
    "id" TEXT NOT NULL,
    "userId" TEXT NOT NULL,
    "expiresAt" TIMESTAMP(3) NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "userAgent" TEXT,

    CONSTRAINT "Session_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "PasswordResetToken" (
    "id" TEXT NOT NULL,
    "userId" TEXT NOT NULL,
    "expiresAt" TIMESTAMP(3) NOT NULL,
    "usedAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "PasswordResetToken_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "CreatorProfile" (
    "id" TEXT NOT NULL,
    "userId" TEXT NOT NULL,
    "displayName" TEXT NOT NULL,
    "artisticName" TEXT,
    "organization" TEXT,
    "bio" TEXT,
    "website" TEXT,
    "orcid" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "CreatorProfile_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "RateLimit" (
    "key" TEXT NOT NULL,
    "windowStart" TIMESTAMP(3) NOT NULL,
    "count" INTEGER NOT NULL,

    CONSTRAINT "RateLimit_pkey" PRIMARY KEY ("key")
);

-- CreateTable
CREATE TABLE "WorkCategory" (
    "key" TEXT NOT NULL,
    "labelEs" TEXT NOT NULL,
    "labelEn" TEXT NOT NULL,
    "group" TEXT NOT NULL,
    "phaseHints" JSONB NOT NULL DEFAULT '{}',
    "sortOrder" INTEGER NOT NULL DEFAULT 0,
    "active" BOOLEAN NOT NULL DEFAULT true,

    CONSTRAINT "WorkCategory_pkey" PRIMARY KEY ("key")
);

-- CreateTable
CREATE TABLE "Work" (
    "id" TEXT NOT NULL,
    "ownerId" TEXT NOT NULL,
    "title" TEXT NOT NULL,
    "subtitle" TEXT,
    "categoryKey" TEXT NOT NULL,
    "description" TEXT,
    "creationPeriod" TEXT,
    "status" "WorkStatus" NOT NULL DEFAULT 'IN_PROGRESS',
    "publicationDate" DATE,
    "language" TEXT,
    "workVersion" TEXT,
    "publisher" TEXT,
    "identifierType" TEXT,
    "identifier" TEXT,
    "url" TEXT,
    "isCollective" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "Work_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "WorkComponent" (
    "id" TEXT NOT NULL,
    "workId" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "kind" "ComponentKind" NOT NULL,
    "description" TEXT,
    "sortOrder" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "WorkComponent_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "WorkContributor" (
    "id" TEXT NOT NULL,
    "workId" TEXT NOT NULL,
    "userId" TEXT,
    "name" TEXT NOT NULL,
    "role" TEXT NOT NULL,
    "componentIds" TEXT[] DEFAULT ARRAY[]::TEXT[],
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "WorkContributor_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Declaration" (
    "id" TEXT NOT NULL,
    "workId" TEXT NOT NULL,
    "ownerId" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "Declaration_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "DeclarationVersion" (
    "id" TEXT NOT NULL,
    "declarationId" TEXT NOT NULL,
    "versionNumber" INTEGER NOT NULL,
    "publicId" TEXT,
    "status" "DocumentStatus" NOT NULL DEFAULT 'DRAFT',
    "visibility" "Visibility" NOT NULL DEFAULT 'PRIVATE',
    "declarantName" TEXT,
    "declarantArtisticName" TEXT,
    "declarantOrganization" TEXT,
    "declarantRole" TEXT,
    "scope" "DeclarationScope",
    "scopeComponentIds" TEXT[] DEFAULT ARRAY[]::TEXT[],
    "responsibilityNote" TEXT,
    "aiUsage" "AIUsageAnswer",
    "ownContributionAiUsage" "AIUsageAnswer",
    "uncertaintyNote" TEXT,
    "processNarrative" TEXT,
    "observations" TEXT,
    "article50Answers" JSONB,
    "lastStep" TEXT,
    "questionnaireVersionId" TEXT NOT NULL,
    "schemeId" TEXT NOT NULL,
    "issuedAt" TIMESTAMP(3),
    "snapshot" JSONB,
    "contentHash" TEXT,
    "pdfFull" BYTEA,
    "pdfFullHash" TEXT,
    "pdfSummary" BYTEA,
    "pdfSummaryHash" TEXT,
    "supersedesId" TEXT,
    "revokedAt" TIMESTAMP(3),
    "revocationReason" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "DeclarationVersion_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "AITool" (
    "id" TEXT NOT NULL,
    "versionId" TEXT NOT NULL,
    "identified" BOOLEAN NOT NULL DEFAULT true,
    "name" TEXT NOT NULL,
    "provider" TEXT,
    "modelVersion" TEXT,
    "systemType" TEXT,
    "purpose" TEXT,
    "usagePeriod" TEXT,
    "sortOrder" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "AITool_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "AIUse" (
    "id" TEXT NOT NULL,
    "versionId" TEXT NOT NULL,
    "componentId" TEXT,
    "toolId" TEXT,
    "parentUseId" TEXT,
    "title" TEXT NOT NULL,
    "functions" TEXT[] DEFAULT ARRAY[]::TEXT[],
    "primaryFunction" TEXT,
    "objective" TEXT,
    "activityDescription" TEXT,
    "resultDescription" TEXT,
    "incorporation" "IncorporationAnswer",
    "sortOrder" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "AIUse_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "AIUsagePhase" (
    "id" TEXT NOT NULL,
    "useId" TEXT NOT NULL,
    "phase" TEXT NOT NULL,
    "note" TEXT,

    CONSTRAINT "AIUsagePhase_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "CreativeContribution" (
    "id" TEXT NOT NULL,
    "useId" TEXT NOT NULL,
    "contributionType" TEXT NOT NULL,
    "interventionAnswer" TEXT,
    "retainsRecognizableElements" TEXT,
    "interventionCode" TEXT,
    "interventionDescription" TEXT,

    CONSTRAINT "CreativeContribution_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Evidence" (
    "id" TEXT NOT NULL,
    "versionId" TEXT NOT NULL,
    "useId" TEXT,
    "kind" "EvidenceKind" NOT NULL,
    "description" TEXT NOT NULL,
    "fileName" TEXT,
    "mimeType" TEXT,
    "sizeBytes" INTEGER,
    "sha256" TEXT,
    "storageKey" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "Evidence_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "TransparencyScheme" (
    "id" TEXT NOT NULL,
    "slug" TEXT NOT NULL,
    "version" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "status" "SchemeStatus" NOT NULL DEFAULT 'DRAFT',
    "publishedAt" TIMESTAMP(3),
    "spec" JSONB NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "TransparencyScheme_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "TransparencyDimension" (
    "id" TEXT NOT NULL,
    "schemeId" TEXT NOT NULL,
    "key" TEXT NOT NULL,
    "nameEs" TEXT NOT NULL,
    "nameEn" TEXT NOT NULL,
    "question" TEXT NOT NULL,
    "notMeaning" TEXT NOT NULL,

    CONSTRAINT "TransparencyDimension_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "TransparencyValue" (
    "id" TEXT NOT NULL,
    "dimensionId" TEXT NOT NULL,
    "code" TEXT NOT NULL,
    "labelEs" TEXT NOT NULL,
    "labelEn" TEXT NOT NULL,
    "descriptionEs" TEXT NOT NULL,
    "descriptionEn" TEXT NOT NULL,
    "exampleEs" TEXT,

    CONSTRAINT "TransparencyValue_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "TransparencyRule" (
    "id" TEXT NOT NULL,
    "schemeId" TEXT NOT NULL,
    "ruleKey" TEXT NOT NULL,
    "kind" "RuleKind" NOT NULL,
    "description" TEXT NOT NULL,

    CONSTRAINT "TransparencyRule_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "TransparencyProfile" (
    "id" TEXT NOT NULL,
    "versionId" TEXT NOT NULL,
    "scope" "ProfileScope" NOT NULL,
    "useId" TEXT,
    "componentId" TEXT,
    "status" TEXT NOT NULL,
    "code" TEXT,
    "notation" TEXT,
    "functions" TEXT[] DEFAULT ARRAY[]::TEXT[],
    "pairs" JSONB NOT NULL DEFAULT '[]',
    "documentation" TEXT NOT NULL,
    "rulesApplied" TEXT[] DEFAULT ARRAY[]::TEXT[],
    "inputs" JSONB NOT NULL,
    "schemeVersion" TEXT NOT NULL,
    "computedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "TransparencyProfile_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "QuestionnaireVersion" (
    "id" TEXT NOT NULL,
    "version" TEXT NOT NULL,
    "schemeId" TEXT NOT NULL,
    "definition" JSONB NOT NULL,
    "active" BOOLEAN NOT NULL DEFAULT false,
    "publishedAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "QuestionnaireVersion_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "DeclarationAcceptance" (
    "id" TEXT NOT NULL,
    "versionId" TEXT NOT NULL,
    "userId" TEXT NOT NULL,
    "legalTextId" TEXT NOT NULL,
    "statementHash" TEXT NOT NULL,
    "acceptedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "sessionFingerprint" TEXT NOT NULL,
    "ipFingerprint" TEXT,

    CONSTRAINT "DeclarationAcceptance_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "DeclarationPublication" (
    "id" TEXT NOT NULL,
    "versionId" TEXT NOT NULL,
    "actorId" TEXT NOT NULL,
    "action" TEXT NOT NULL,
    "showName" BOOLEAN NOT NULL DEFAULT false,
    "showWork" BOOLEAN NOT NULL DEFAULT false,
    "showCode" BOOLEAN NOT NULL DEFAULT false,
    "showSummary" BOOLEAN NOT NULL DEFAULT false,
    "showFull" BOOLEAN NOT NULL DEFAULT false,
    "showEvidence" BOOLEAN NOT NULL DEFAULT false,
    "evidenceConsentAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "DeclarationPublication_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "VerificationRecord" (
    "id" TEXT NOT NULL,
    "versionId" TEXT NOT NULL,
    "algorithm" TEXT NOT NULL,
    "canonicalization" TEXT NOT NULL,
    "snapshotFormat" TEXT NOT NULL,
    "contentHash" TEXT NOT NULL,
    "sectionDigests" JSONB NOT NULL,
    "pdfFullHash" TEXT NOT NULL,
    "pdfSummaryHash" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "VerificationRecord_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "LegalText" (
    "id" TEXT NOT NULL,
    "key" TEXT NOT NULL,
    "version" TEXT NOT NULL,
    "locale" TEXT NOT NULL DEFAULT 'es',
    "title" TEXT NOT NULL,
    "content" TEXT NOT NULL,
    "contentHash" TEXT NOT NULL,
    "active" BOOLEAN NOT NULL DEFAULT false,
    "pendingLegalReview" BOOLEAN NOT NULL DEFAULT true,
    "publishedAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "LegalText_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "LegalReference" (
    "id" TEXT NOT NULL,
    "key" TEXT NOT NULL,
    "title" TEXT NOT NULL,
    "citation" TEXT NOT NULL,
    "url" TEXT NOT NULL,
    "applicableFrom" DATE,
    "summary" TEXT NOT NULL,
    "verificationNote" TEXT,
    "active" BOOLEAN NOT NULL DEFAULT true,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "LegalReference_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "Incident" (
    "id" TEXT NOT NULL,
    "type" "IncidentType" NOT NULL,
    "status" "IncidentStatus" NOT NULL DEFAULT 'OPEN',
    "name" TEXT,
    "email" TEXT NOT NULL,
    "publicId" TEXT,
    "userId" TEXT,
    "message" TEXT NOT NULL,
    "resolution" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,
    "resolvedAt" TIMESTAMP(3),

    CONSTRAINT "Incident_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "AuditLog" (
    "id" TEXT NOT NULL,
    "actorId" TEXT,
    "action" TEXT NOT NULL,
    "entityType" TEXT NOT NULL,
    "entityId" TEXT,
    "metadata" JSONB NOT NULL DEFAULT '{}',
    "ipFingerprint" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "AuditLog_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "User_email_key" ON "User"("email");

-- CreateIndex
CREATE INDEX "Session_userId_idx" ON "Session"("userId");

-- CreateIndex
CREATE INDEX "PasswordResetToken_userId_idx" ON "PasswordResetToken"("userId");

-- CreateIndex
CREATE UNIQUE INDEX "CreatorProfile_userId_key" ON "CreatorProfile"("userId");

-- CreateIndex
CREATE INDEX "Work_ownerId_idx" ON "Work"("ownerId");

-- CreateIndex
CREATE INDEX "WorkComponent_workId_idx" ON "WorkComponent"("workId");

-- CreateIndex
CREATE INDEX "WorkContributor_workId_idx" ON "WorkContributor"("workId");

-- CreateIndex
CREATE INDEX "Declaration_ownerId_idx" ON "Declaration"("ownerId");

-- CreateIndex
CREATE INDEX "Declaration_workId_idx" ON "Declaration"("workId");

-- CreateIndex
CREATE UNIQUE INDEX "DeclarationVersion_publicId_key" ON "DeclarationVersion"("publicId");

-- CreateIndex
CREATE UNIQUE INDEX "DeclarationVersion_supersedesId_key" ON "DeclarationVersion"("supersedesId");

-- CreateIndex
CREATE INDEX "DeclarationVersion_status_visibility_idx" ON "DeclarationVersion"("status", "visibility");

-- CreateIndex
CREATE UNIQUE INDEX "DeclarationVersion_declarationId_versionNumber_key" ON "DeclarationVersion"("declarationId", "versionNumber");

-- CreateIndex
CREATE INDEX "AITool_versionId_idx" ON "AITool"("versionId");

-- CreateIndex
CREATE INDEX "AIUse_versionId_idx" ON "AIUse"("versionId");

-- CreateIndex
CREATE UNIQUE INDEX "AIUsagePhase_useId_phase_key" ON "AIUsagePhase"("useId", "phase");

-- CreateIndex
CREATE UNIQUE INDEX "CreativeContribution_useId_contributionType_key" ON "CreativeContribution"("useId", "contributionType");

-- CreateIndex
CREATE INDEX "Evidence_versionId_idx" ON "Evidence"("versionId");

-- CreateIndex
CREATE UNIQUE INDEX "TransparencyScheme_slug_version_key" ON "TransparencyScheme"("slug", "version");

-- CreateIndex
CREATE UNIQUE INDEX "TransparencyDimension_schemeId_key_key" ON "TransparencyDimension"("schemeId", "key");

-- CreateIndex
CREATE UNIQUE INDEX "TransparencyValue_dimensionId_code_key" ON "TransparencyValue"("dimensionId", "code");

-- CreateIndex
CREATE UNIQUE INDEX "TransparencyRule_schemeId_ruleKey_key" ON "TransparencyRule"("schemeId", "ruleKey");

-- CreateIndex
CREATE INDEX "TransparencyProfile_versionId_idx" ON "TransparencyProfile"("versionId");

-- CreateIndex
CREATE UNIQUE INDEX "QuestionnaireVersion_version_key" ON "QuestionnaireVersion"("version");

-- CreateIndex
CREATE UNIQUE INDEX "DeclarationAcceptance_versionId_key" ON "DeclarationAcceptance"("versionId");

-- CreateIndex
CREATE INDEX "DeclarationPublication_versionId_createdAt_idx" ON "DeclarationPublication"("versionId", "createdAt");

-- CreateIndex
CREATE UNIQUE INDEX "VerificationRecord_versionId_key" ON "VerificationRecord"("versionId");

-- CreateIndex
CREATE UNIQUE INDEX "LegalText_key_version_locale_key" ON "LegalText"("key", "version", "locale");

-- CreateIndex
CREATE UNIQUE INDEX "LegalReference_key_key" ON "LegalReference"("key");

-- CreateIndex
CREATE INDEX "Incident_status_createdAt_idx" ON "Incident"("status", "createdAt");

-- CreateIndex
CREATE INDEX "AuditLog_entityType_entityId_idx" ON "AuditLog"("entityType", "entityId");

-- CreateIndex
CREATE INDEX "AuditLog_createdAt_idx" ON "AuditLog"("createdAt");

-- AddForeignKey
ALTER TABLE "Session" ADD CONSTRAINT "Session_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "PasswordResetToken" ADD CONSTRAINT "PasswordResetToken_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "CreatorProfile" ADD CONSTRAINT "CreatorProfile_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Work" ADD CONSTRAINT "Work_ownerId_fkey" FOREIGN KEY ("ownerId") REFERENCES "User"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Work" ADD CONSTRAINT "Work_categoryKey_fkey" FOREIGN KEY ("categoryKey") REFERENCES "WorkCategory"("key") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "WorkComponent" ADD CONSTRAINT "WorkComponent_workId_fkey" FOREIGN KEY ("workId") REFERENCES "Work"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "WorkContributor" ADD CONSTRAINT "WorkContributor_workId_fkey" FOREIGN KEY ("workId") REFERENCES "Work"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "WorkContributor" ADD CONSTRAINT "WorkContributor_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Declaration" ADD CONSTRAINT "Declaration_workId_fkey" FOREIGN KEY ("workId") REFERENCES "Work"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Declaration" ADD CONSTRAINT "Declaration_ownerId_fkey" FOREIGN KEY ("ownerId") REFERENCES "User"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationVersion" ADD CONSTRAINT "DeclarationVersion_declarationId_fkey" FOREIGN KEY ("declarationId") REFERENCES "Declaration"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationVersion" ADD CONSTRAINT "DeclarationVersion_questionnaireVersionId_fkey" FOREIGN KEY ("questionnaireVersionId") REFERENCES "QuestionnaireVersion"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationVersion" ADD CONSTRAINT "DeclarationVersion_schemeId_fkey" FOREIGN KEY ("schemeId") REFERENCES "TransparencyScheme"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationVersion" ADD CONSTRAINT "DeclarationVersion_supersedesId_fkey" FOREIGN KEY ("supersedesId") REFERENCES "DeclarationVersion"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AITool" ADD CONSTRAINT "AITool_versionId_fkey" FOREIGN KEY ("versionId") REFERENCES "DeclarationVersion"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AIUse" ADD CONSTRAINT "AIUse_versionId_fkey" FOREIGN KEY ("versionId") REFERENCES "DeclarationVersion"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AIUse" ADD CONSTRAINT "AIUse_componentId_fkey" FOREIGN KEY ("componentId") REFERENCES "WorkComponent"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AIUse" ADD CONSTRAINT "AIUse_toolId_fkey" FOREIGN KEY ("toolId") REFERENCES "AITool"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AIUse" ADD CONSTRAINT "AIUse_parentUseId_fkey" FOREIGN KEY ("parentUseId") REFERENCES "AIUse"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AIUsagePhase" ADD CONSTRAINT "AIUsagePhase_useId_fkey" FOREIGN KEY ("useId") REFERENCES "AIUse"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "CreativeContribution" ADD CONSTRAINT "CreativeContribution_useId_fkey" FOREIGN KEY ("useId") REFERENCES "AIUse"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Evidence" ADD CONSTRAINT "Evidence_versionId_fkey" FOREIGN KEY ("versionId") REFERENCES "DeclarationVersion"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "Evidence" ADD CONSTRAINT "Evidence_useId_fkey" FOREIGN KEY ("useId") REFERENCES "AIUse"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "TransparencyDimension" ADD CONSTRAINT "TransparencyDimension_schemeId_fkey" FOREIGN KEY ("schemeId") REFERENCES "TransparencyScheme"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "TransparencyValue" ADD CONSTRAINT "TransparencyValue_dimensionId_fkey" FOREIGN KEY ("dimensionId") REFERENCES "TransparencyDimension"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "TransparencyRule" ADD CONSTRAINT "TransparencyRule_schemeId_fkey" FOREIGN KEY ("schemeId") REFERENCES "TransparencyScheme"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "TransparencyProfile" ADD CONSTRAINT "TransparencyProfile_versionId_fkey" FOREIGN KEY ("versionId") REFERENCES "DeclarationVersion"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "TransparencyProfile" ADD CONSTRAINT "TransparencyProfile_useId_fkey" FOREIGN KEY ("useId") REFERENCES "AIUse"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "QuestionnaireVersion" ADD CONSTRAINT "QuestionnaireVersion_schemeId_fkey" FOREIGN KEY ("schemeId") REFERENCES "TransparencyScheme"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationAcceptance" ADD CONSTRAINT "DeclarationAcceptance_versionId_fkey" FOREIGN KEY ("versionId") REFERENCES "DeclarationVersion"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationAcceptance" ADD CONSTRAINT "DeclarationAcceptance_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationAcceptance" ADD CONSTRAINT "DeclarationAcceptance_legalTextId_fkey" FOREIGN KEY ("legalTextId") REFERENCES "LegalText"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationPublication" ADD CONSTRAINT "DeclarationPublication_versionId_fkey" FOREIGN KEY ("versionId") REFERENCES "DeclarationVersion"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "DeclarationPublication" ADD CONSTRAINT "DeclarationPublication_actorId_fkey" FOREIGN KEY ("actorId") REFERENCES "User"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "VerificationRecord" ADD CONSTRAINT "VerificationRecord_versionId_fkey" FOREIGN KEY ("versionId") REFERENCES "DeclarationVersion"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "AuditLog" ADD CONSTRAINT "AuditLog_actorId_fkey" FOREIGN KEY ("actorId") REFERENCES "User"("id") ON DELETE SET NULL ON UPDATE CASCADE;
