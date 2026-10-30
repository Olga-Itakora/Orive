-- ORIDE — disparadores de inmutabilidad (opcionales pero recomendados).
-- La aplicación ya impide modificar declaraciones emitidas; estos disparadores
-- lo garantizan también si alguien modifica la base de datos directamente.
-- Si tu proveedor no permite crear disparadores, instalar.php lo indicará y
-- la aplicación seguirá funcionando.

DROP TRIGGER IF EXISTS oride_version_bu;
DROP TRIGGER IF EXISTS oride_version_bd;
DROP TRIGGER IF EXISTS oride_tool_bi;
DROP TRIGGER IF EXISTS oride_tool_bu;
DROP TRIGGER IF EXISTS oride_tool_bd;
DROP TRIGGER IF EXISTS oride_use_bi;
DROP TRIGGER IF EXISTS oride_use_bu;
DROP TRIGGER IF EXISTS oride_use_bd;
DROP TRIGGER IF EXISTS oride_contribution_bi;
DROP TRIGGER IF EXISTS oride_contribution_bu;
DROP TRIGGER IF EXISTS oride_contribution_bd;
DROP TRIGGER IF EXISTS oride_evidence_bi;
DROP TRIGGER IF EXISTS oride_evidence_bu;
DROP TRIGGER IF EXISTS oride_evidence_bd;
DROP TRIGGER IF EXISTS oride_acceptance_bu;
DROP TRIGGER IF EXISTS oride_acceptance_bd;
DROP TRIGGER IF EXISTS oride_publication_bu;
DROP TRIGGER IF EXISTS oride_publication_bd;
DROP TRIGGER IF EXISTS oride_audit_bu;
DROP TRIGGER IF EXISTS oride_audit_bd;

DELIMITER $$

CREATE TRIGGER oride_version_bu BEFORE UPDATE ON declaration_versions FOR EACH ROW
BEGIN
  IF OLD.status <> 'DRAFT' THEN
    IF NOT (NEW.public_id <=> OLD.public_id AND NEW.declaration_id <=> OLD.declaration_id AND NEW.version_number <=> OLD.version_number
        AND NEW.declarant_name <=> OLD.declarant_name AND NEW.declarant_artistic_name <=> OLD.declarant_artistic_name
        AND NEW.declarant_organization <=> OLD.declarant_organization AND NEW.declarant_role <=> OLD.declarant_role
        AND NEW.scope <=> OLD.scope AND NEW.scope_component_ids <=> OLD.scope_component_ids AND NEW.responsibility_note <=> OLD.responsibility_note
        AND NEW.ai_usage <=> OLD.ai_usage AND NEW.own_ai_usage <=> OLD.own_ai_usage AND NEW.uncertainty_note <=> OLD.uncertainty_note
        AND NEW.process_narrative <=> OLD.process_narrative AND NEW.observations <=> OLD.observations AND NEW.article50_answers <=> OLD.article50_answers
        AND NEW.issued_at <=> OLD.issued_at AND NEW.snapshot <=> OLD.snapshot AND NEW.content_hash <=> OLD.content_hash
        AND NEW.section_digests <=> OLD.section_digests AND NEW.pdf_full <=> OLD.pdf_full AND NEW.pdf_full_hash <=> OLD.pdf_full_hash
        AND NEW.pdf_summary <=> OLD.pdf_summary AND NEW.pdf_summary_hash <=> OLD.pdf_summary_hash AND NEW.supersedes_id <=> OLD.supersedes_id
        AND NEW.scheme_version <=> OLD.scheme_version AND NEW.questionnaire_version <=> OLD.questionnaire_version) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: el contenido de una versión emitida no puede modificarse';
    END IF;
    IF NOT (NEW.status <=> OLD.status
        OR (OLD.status = 'ISSUED' AND NEW.status IN ('SUPERSEDED','REVOKED'))
        OR (OLD.status = 'SUPERSEDED' AND NEW.status = 'REVOKED')) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: transición de estado no permitida';
    END IF;
  ELSEIF NEW.status NOT IN ('DRAFT','ISSUED') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: un borrador solo puede pasar a emitido';
  END IF;
END$$

CREATE TRIGGER oride_version_bd BEFORE DELETE ON declaration_versions FOR EACH ROW
BEGIN
  IF OLD.status <> 'DRAFT' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: no se puede eliminar una versión emitida';
  END IF;
END$$

CREATE TRIGGER oride_tool_bi BEFORE INSERT ON ai_tools FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = NEW.version_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_tool_bu BEFORE UPDATE ON ai_tools FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = OLD.version_id) <> 'DRAFT' OR NEW.version_id <> OLD.version_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_tool_bd BEFORE DELETE ON ai_tools FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = OLD.version_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$

CREATE TRIGGER oride_use_bi BEFORE INSERT ON ai_uses FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = NEW.version_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_use_bu BEFORE UPDATE ON ai_uses FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = OLD.version_id) <> 'DRAFT' OR NEW.version_id <> OLD.version_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_use_bd BEFORE DELETE ON ai_uses FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = OLD.version_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$

CREATE TRIGGER oride_contribution_bi BEFORE INSERT ON contributions FOR EACH ROW
BEGIN
  IF (SELECT v.status FROM ai_uses u JOIN declaration_versions v ON v.id = u.version_id WHERE u.id = NEW.use_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_contribution_bu BEFORE UPDATE ON contributions FOR EACH ROW
BEGIN
  -- Excepción: al emitir se escribe el C derivado (intervention_code) antes de cambiar el estado.
  IF (SELECT v.status FROM ai_uses u JOIN declaration_versions v ON v.id = u.version_id WHERE u.id = OLD.use_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_contribution_bd BEFORE DELETE ON contributions FOR EACH ROW
BEGIN
  IF (SELECT v.status FROM ai_uses u JOIN declaration_versions v ON v.id = u.version_id WHERE u.id = OLD.use_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$

CREATE TRIGGER oride_evidence_bi BEFORE INSERT ON evidence FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = NEW.version_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_evidence_bu BEFORE UPDATE ON evidence FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = OLD.version_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$
CREATE TRIGGER oride_evidence_bd BEFORE DELETE ON evidence FOR EACH ROW
BEGIN
  IF (SELECT status FROM declaration_versions WHERE id = OLD.version_id) <> 'DRAFT' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: versión emitida'; END IF;
END$$

CREATE TRIGGER oride_acceptance_bu BEFORE UPDATE ON acceptances FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: registro de solo inserción'; END$$
CREATE TRIGGER oride_acceptance_bd BEFORE DELETE ON acceptances FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: registro de solo inserción'; END$$
CREATE TRIGGER oride_publication_bu BEFORE UPDATE ON publications FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: registro de solo inserción'; END$$
CREATE TRIGGER oride_publication_bd BEFORE DELETE ON publications FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: registro de solo inserción'; END$$
CREATE TRIGGER oride_audit_bu BEFORE UPDATE ON audit_log FOR EACH ROW
BEGIN
  -- Solo se permite seudonimizar al actor (actor_id -> NULL) al suprimir una cuenta.
  IF NOT (NEW.actor_id IS NULL AND NEW.action <=> OLD.action AND NEW.entity <=> OLD.entity AND NEW.entity_id <=> OLD.entity_id
      AND NEW.metadata <=> OLD.metadata AND NEW.created_at <=> OLD.created_at) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: registro de auditoría de solo inserción';
  END IF;
END$$
CREATE TRIGGER oride_audit_bd BEFORE DELETE ON audit_log FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORIDE_IMMUTABLE: registro de auditoría de solo inserción'; END$$

DELIMITER ;
