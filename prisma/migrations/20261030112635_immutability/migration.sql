-- ORIVA — garantías de inmutabilidad a nivel de base de datos.
-- Una versión emitida (status <> 'DRAFT') no puede modificar su contenido.
-- Solo cambian: estado documental (transiciones permitidas), visibilidad y datos de revocación.

-- Coherencia de las versiones emitidas
ALTER TABLE "DeclarationVersion"
  ADD CONSTRAINT "issued_requires_integrity_data"
  CHECK (
    "status" = 'DRAFT'
    OR ("publicId" IS NOT NULL AND "contentHash" IS NOT NULL AND "snapshot" IS NOT NULL
        AND "issuedAt" IS NOT NULL AND "pdfFull" IS NOT NULL AND "pdfSummary" IS NOT NULL)
  );

ALTER TABLE "DeclarationVersion"
  ADD CONSTRAINT "draft_is_private"
  CHECK ("status" <> 'DRAFT' OR "visibility" = 'PRIVATE');

CREATE OR REPLACE FUNCTION oriva_guard_declaration_version() RETURNS trigger AS $$
DECLARE
  mutable_cols text[] := ARRAY['status','visibility','revokedAt','revocationReason','updatedAt'];
BEGIN
  IF TG_OP = 'DELETE' THEN
    IF OLD."status" <> 'DRAFT' THEN
      RAISE EXCEPTION 'ORIVA_IMMUTABLE: no se puede eliminar una versión emitida (%).', OLD."publicId";
    END IF;
    RETURN OLD;
  END IF;

  IF OLD."status" <> 'DRAFT' THEN
    IF (to_jsonb(NEW) - mutable_cols) IS DISTINCT FROM (to_jsonb(OLD) - mutable_cols) THEN
      RAISE EXCEPTION 'ORIVA_IMMUTABLE: el contenido de la versión emitida % no puede modificarse.', OLD."publicId";
    END IF;
    IF NEW."status" IS DISTINCT FROM OLD."status" AND NOT (
      (OLD."status" = 'ISSUED' AND NEW."status" IN ('SUPERSEDED','REVOKED'))
      OR (OLD."status" = 'SUPERSEDED' AND NEW."status" = 'REVOKED')
    ) THEN
      RAISE EXCEPTION 'ORIVA_IMMUTABLE: transición de estado no permitida % -> %.', OLD."status", NEW."status";
    END IF;
  ELSIF NEW."status" NOT IN ('DRAFT','ISSUED') THEN
    RAISE EXCEPTION 'ORIVA_IMMUTABLE: un borrador solo puede pasar a emitido.';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER oriva_guard_declaration_version
  BEFORE UPDATE OR DELETE ON "DeclarationVersion"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_declaration_version();

-- Tablas hijas cuyo contenido forma parte de la versión
CREATE OR REPLACE FUNCTION oriva_version_status(vid text) RETURNS text AS $$
  SELECT "status"::text FROM "DeclarationVersion" WHERE "id" = vid;
$$ LANGUAGE sql STABLE;

CREATE OR REPLACE FUNCTION oriva_guard_version_child() RETURNS trigger AS $$
DECLARE
  vid text;
  st text;
BEGIN
  IF TG_OP = 'DELETE' THEN vid := OLD."versionId"; ELSE vid := NEW."versionId"; END IF;
  st := oriva_version_status(vid);
  -- st IS NULL: la versión ya se ha eliminado (borrado en cascada de un borrador).
  IF st IS NOT NULL AND st <> 'DRAFT' THEN
    RAISE EXCEPTION 'ORIVA_IMMUTABLE: % pertenece a una versión emitida.', TG_TABLE_NAME;
  END IF;
  IF TG_OP = 'UPDATE' AND OLD."versionId" IS DISTINCT FROM NEW."versionId" THEN
    RAISE EXCEPTION 'ORIVA_IMMUTABLE: no se puede mover % entre versiones.', TG_TABLE_NAME;
  END IF;
  IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER oriva_guard_aitool BEFORE INSERT OR UPDATE OR DELETE ON "AITool"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_version_child();
CREATE TRIGGER oriva_guard_aiuse BEFORE INSERT OR UPDATE OR DELETE ON "AIUse"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_version_child();
CREATE TRIGGER oriva_guard_evidence BEFORE INSERT OR UPDATE OR DELETE ON "Evidence"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_version_child();
CREATE TRIGGER oriva_guard_profile BEFORE INSERT OR UPDATE OR DELETE ON "TransparencyProfile"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_version_child();

-- Nietas (vía AIUse)
CREATE OR REPLACE FUNCTION oriva_guard_use_child() RETURNS trigger AS $$
DECLARE
  uid text;
  st text;
BEGIN
  IF TG_OP = 'DELETE' THEN uid := OLD."useId"; ELSE uid := NEW."useId"; END IF;
  SELECT v."status"::text INTO st FROM "AIUse" u JOIN "DeclarationVersion" v ON v."id" = u."versionId" WHERE u."id" = uid;
  IF st IS NOT NULL AND st <> 'DRAFT' THEN
    RAISE EXCEPTION 'ORIVA_IMMUTABLE: % pertenece a una versión emitida.', TG_TABLE_NAME;
  END IF;
  IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER oriva_guard_phase BEFORE INSERT OR UPDATE OR DELETE ON "AIUsagePhase"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_use_child();
CREATE TRIGGER oriva_guard_contribution BEFORE INSERT OR UPDATE OR DELETE ON "CreativeContribution"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_use_child();

-- Registros de solo inserción
CREATE OR REPLACE FUNCTION oriva_append_only() RETURNS trigger AS $$
BEGIN
  RAISE EXCEPTION 'ORIVA_IMMUTABLE: % es un registro de solo inserción.', TG_TABLE_NAME;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER oriva_append_only_acceptance BEFORE UPDATE OR DELETE ON "DeclarationAcceptance"
  FOR EACH ROW EXECUTE FUNCTION oriva_append_only();
CREATE TRIGGER oriva_append_only_verification BEFORE UPDATE OR DELETE ON "VerificationRecord"
  FOR EACH ROW EXECUTE FUNCTION oriva_append_only();
CREATE TRIGGER oriva_append_only_publication BEFORE UPDATE OR DELETE ON "DeclarationPublication"
  FOR EACH ROW EXECUTE FUNCTION oriva_append_only();

-- Auditoría: solo inserción, salvo la seudonimización del actor (actorId -> NULL) al suprimir una cuenta.
CREATE OR REPLACE FUNCTION oriva_guard_audit() RETURNS trigger AS $$
BEGIN
  IF TG_OP = 'UPDATE' AND NEW."actorId" IS NULL
     AND (to_jsonb(NEW) - 'actorId') = (to_jsonb(OLD) - 'actorId') THEN
    RETURN NEW;
  END IF;
  RAISE EXCEPTION 'ORIVA_IMMUTABLE: el registro de auditoría es de solo inserción.';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER oriva_guard_audit BEFORE UPDATE OR DELETE ON "AuditLog"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_audit();

-- Las taxonomías publicadas no se modifican (se crean versiones nuevas).
CREATE OR REPLACE FUNCTION oriva_guard_scheme() RETURNS trigger AS $$
BEGIN
  IF TG_OP = 'DELETE' AND OLD."status" <> 'DRAFT' THEN
    RAISE EXCEPTION 'ORIVA_IMMUTABLE: no se puede eliminar un esquema publicado.';
  END IF;
  IF TG_OP = 'UPDATE' AND OLD."status" <> 'DRAFT' THEN
    IF (to_jsonb(NEW) - 'status') IS DISTINCT FROM (to_jsonb(OLD) - 'status')
       OR NOT (OLD."status" = NEW."status" OR (OLD."status" = 'PUBLISHED' AND NEW."status" = 'DEPRECATED')) THEN
      RAISE EXCEPTION 'ORIVA_IMMUTABLE: un esquema publicado solo puede marcarse como obsoleto.';
    END IF;
  END IF;
  IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER oriva_guard_scheme BEFORE UPDATE OR DELETE ON "TransparencyScheme"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_scheme();

-- Textos legales aceptados: el contenido de una versión no cambia una vez publicada.
CREATE OR REPLACE FUNCTION oriva_guard_legal_text() RETURNS trigger AS $$
BEGIN
  IF OLD."publishedAt" IS NOT NULL AND (
    NEW."content" IS DISTINCT FROM OLD."content" OR NEW."contentHash" IS DISTINCT FROM OLD."contentHash"
    OR NEW."version" IS DISTINCT FROM OLD."version" OR NEW."key" IS DISTINCT FROM OLD."key") THEN
    RAISE EXCEPTION 'ORIVA_IMMUTABLE: el texto legal publicado no puede modificarse; crea una nueva versión.';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER oriva_guard_legal_text BEFORE UPDATE ON "LegalText"
  FOR EACH ROW EXECUTE FUNCTION oriva_guard_legal_text();
