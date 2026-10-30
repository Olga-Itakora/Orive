-- ORIDE — esquema MySQL 8.0 / MariaDB 10.6+ (InnoDB, utf8mb4).
-- Se ejecuta desde instalar.php o importándolo en phpMyAdmin (IONOS).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('AUTHOR','ADMIN') NOT NULL DEFAULT 'AUTHOR',
  display_name VARCHAR(160) NOT NULL,
  artistic_name VARCHAR(160) NULL,
  organization VARCHAR(200) NULL,
  bio TEXT NULL,
  website VARCHAR(500) NULL,
  -- Se incrementa al cambiar la contraseña: invalida las demás sesiones.
  session_version INT UNSIGNED NOT NULL DEFAULT 1,
  disabled_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  token_hash CHAR(64) PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  rl_key CHAR(64) PRIMARY KEY,
  window_start INT UNSIGNED NOT NULL,
  hits INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_categories (
  ckey VARCHAR(40) PRIMARY KEY,
  label_es VARCHAR(80) NOT NULL,
  label_en VARCHAR(80) NOT NULL,
  grp VARCHAR(80) NOT NULL,
  phase_hints TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS works (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id INT UNSIGNED NOT NULL,
  title VARCHAR(300) NOT NULL,
  subtitle VARCHAR(300) NULL,
  category_key VARCHAR(40) NOT NULL,
  description TEXT NULL,
  creation_period VARCHAR(120) NULL,
  status ENUM('IN_PROGRESS','FINISHED','PUBLISHED') NOT NULL DEFAULT 'IN_PROGRESS',
  publication_date DATE NULL,
  language VARCHAR(60) NULL,
  work_version VARCHAR(60) NULL,
  publisher VARCHAR(200) NULL,
  identifier_type VARCHAR(30) NULL,
  identifier VARCHAR(120) NULL,
  url VARCHAR(500) NULL,
  is_collective TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY ix_works_owner (owner_id),
  CONSTRAINT fk_work_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_work_category FOREIGN KEY (category_key) REFERENCES work_categories(ckey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_components (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  work_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  kind ENUM('TEXT','IMAGE','AUDIO','VIDEO','CODE','INTERACTIVE','OTHER') NOT NULL,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_component_work FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_contributors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  work_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  role VARCHAR(120) NOT NULL,
  component_ids TEXT NOT NULL,
  CONSTRAINT fk_contributor_work FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS declarations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  work_id INT UNSIGNED NOT NULL,
  owner_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_decl_work FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE RESTRICT,
  CONSTRAINT fk_decl_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS declaration_versions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  declaration_id INT UNSIGNED NOT NULL,
  version_number INT UNSIGNED NOT NULL,
  public_id CHAR(18) NULL,
  status ENUM('DRAFT','ISSUED','SUPERSEDED','REVOKED') NOT NULL DEFAULT 'DRAFT',
  visibility ENUM('PRIVATE','PUBLIC','WITHDRAWN') NOT NULL DEFAULT 'PRIVATE',
  -- Respuestas del cuestionario (borrador)
  declarant_name VARCHAR(160) NULL,
  declarant_artistic_name VARCHAR(160) NULL,
  declarant_organization VARCHAR(200) NULL,
  declarant_role VARCHAR(120) NULL,
  scope ENUM('WHOLE_WORK','COMPONENTS','OWN_CONTRIBUTION') NULL,
  scope_component_ids TEXT NULL,
  responsibility_note TEXT NULL,
  ai_usage ENUM('NO','YES','UNCERTAIN','OWN_CONTRIBUTION_ONLY') NULL,
  own_ai_usage ENUM('NO','YES','UNCERTAIN') NULL,
  uncertainty_note TEXT NULL,
  process_narrative TEXT NULL,
  observations TEXT NULL,
  article50_answers TEXT NULL,
  last_step VARCHAR(20) NULL,
  questionnaire_version VARCHAR(10) NOT NULL DEFAULT '1.0',
  scheme_version VARCHAR(10) NOT NULL DEFAULT '1.0',
  -- Emisión (inmutable)
  issued_at DATETIME NULL,
  snapshot LONGTEXT NULL,
  content_hash CHAR(64) NULL,
  section_digests TEXT NULL,
  pdf_full LONGBLOB NULL,
  pdf_full_hash CHAR(64) NULL,
  pdf_summary LONGBLOB NULL,
  pdf_summary_hash CHAR(64) NULL,
  supersedes_id INT UNSIGNED NULL,
  revoked_at DATETIME NULL,
  revocation_reason TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_version_public (public_id),
  UNIQUE KEY uq_version_number (declaration_id, version_number),
  UNIQUE KEY uq_version_supersedes (supersedes_id),
  KEY ix_version_status (status, visibility),
  CONSTRAINT fk_version_decl FOREIGN KEY (declaration_id) REFERENCES declarations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_version_supersedes FOREIGN KEY (supersedes_id) REFERENCES declaration_versions(id) ON DELETE RESTRICT,
  CONSTRAINT ck_issued_complete CHECK (status = 'DRAFT' OR (public_id IS NOT NULL AND content_hash IS NOT NULL AND snapshot IS NOT NULL AND issued_at IS NOT NULL)),
  CONSTRAINT ck_draft_private CHECK (status <> 'DRAFT' OR visibility = 'PRIVATE')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_tools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id INT UNSIGNED NOT NULL,
  identified TINYINT(1) NOT NULL DEFAULT 1,
  name VARCHAR(160) NOT NULL,
  provider VARCHAR(160) NULL,
  model_version VARCHAR(160) NULL,
  system_type VARCHAR(120) NULL,
  purpose TEXT NULL,
  usage_period VARCHAR(120) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_tool_version FOREIGN KEY (version_id) REFERENCES declaration_versions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_uses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id INT UNSIGNED NOT NULL,
  component_id INT UNSIGNED NULL,
  tool_id INT UNSIGNED NULL,
  parent_use_id INT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  functions VARCHAR(40) NOT NULL DEFAULT '[]',
  primary_function VARCHAR(4) NULL,
  phases TEXT NOT NULL,
  objective TEXT NULL,
  activity TEXT NULL,
  result TEXT NULL,
  incorporation ENUM('NONE','INCORPORATED','UNKNOWN') NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_use_version FOREIGN KEY (version_id) REFERENCES declaration_versions(id) ON DELETE CASCADE,
  CONSTRAINT fk_use_component FOREIGN KEY (component_id) REFERENCES work_components(id) ON DELETE RESTRICT,
  CONSTRAINT fk_use_tool FOREIGN KEY (tool_id) REFERENCES ai_tools(id) ON DELETE SET NULL,
  CONSTRAINT fk_use_parent FOREIGN KEY (parent_use_id) REFERENCES ai_uses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pareja B–C de un uso: qué se incorporó (B) y qué hizo la persona con ello (C).
CREATE TABLE IF NOT EXISTS contributions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  use_id INT UNSIGNED NOT NULL,
  ctype ENUM('B1','B2','B3') NOT NULL,
  intervention_answer ENUM('ACCEPTED','MINOR_EDITS','TRANSFORMED','OWN_EXPRESSION','UNSURE') NULL,
  recognizable ENUM('YES','NO','UNSURE') NULL,
  intervention_code VARCHAR(2) NULL,
  description TEXT NULL,
  UNIQUE KEY uq_contribution (use_id, ctype),
  CONSTRAINT fk_contribution_use FOREIGN KEY (use_id) REFERENCES ai_uses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evidence (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id INT UNSIGNED NOT NULL,
  use_id INT UNSIGNED NULL,
  kind ENUM('VERSION_HISTORY','CONVERSATION_LOG','WORK_MATERIAL','SCREENSHOT','OTHER') NOT NULL,
  description TEXT NOT NULL,
  file_name VARCHAR(160) NULL,
  mime_type VARCHAR(60) NULL,
  size_bytes INT UNSIGNED NULL,
  sha256 CHAR(64) NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_evidence_version FOREIGN KEY (version_id) REFERENCES declaration_versions(id) ON DELETE CASCADE,
  CONSTRAINT fk_evidence_use FOREIGN KEY (use_id) REFERENCES ai_uses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resultados de clasificación calculados al emitir (por uso, componente y obra).
CREATE TABLE IF NOT EXISTS profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id INT UNSIGNED NOT NULL,
  scope ENUM('USE','COMPONENT','WORK') NOT NULL,
  use_id INT UNSIGNED NULL,
  component_id INT UNSIGNED NULL,
  status VARCHAR(20) NOT NULL,
  code VARCHAR(12) NULL,
  notation VARCHAR(255) NULL,
  functions VARCHAR(40) NOT NULL,
  pairs TEXT NOT NULL,
  documentation VARCHAR(2) NOT NULL,
  rules_applied TEXT NOT NULL,
  inputs TEXT NOT NULL,
  scheme_version VARCHAR(10) NOT NULL,
  computed_at DATETIME NOT NULL,
  CONSTRAINT fk_profile_version FOREIGN KEY (version_id) REFERENCES declaration_versions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legal_texts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tkey VARCHAR(40) NOT NULL,
  version VARCHAR(10) NOT NULL,
  title VARCHAR(200) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  content_hash CHAR(64) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 0,
  pending_review TINYINT(1) NOT NULL DEFAULT 1,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_legal_text (tkey, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acceptances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  legal_text_id INT UNSIGNED NOT NULL,
  statement_hash CHAR(64) NOT NULL,
  accepted_at DATETIME NOT NULL,
  ip_fingerprint CHAR(32) NULL,
  UNIQUE KEY uq_acceptance_version (version_id),
  CONSTRAINT fk_acceptance_version FOREIGN KEY (version_id) REFERENCES declaration_versions(id) ON DELETE RESTRICT,
  CONSTRAINT fk_acceptance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_acceptance_text FOREIGN KEY (legal_text_id) REFERENCES legal_texts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS publications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version_id INT UNSIGNED NOT NULL,
  actor_id INT UNSIGNED NOT NULL,
  action ENUM('PUBLISH','UPDATE','WITHDRAW') NOT NULL,
  show_name TINYINT(1) NOT NULL DEFAULT 0,
  show_work TINYINT(1) NOT NULL DEFAULT 0,
  show_code TINYINT(1) NOT NULL DEFAULT 0,
  show_summary TINYINT(1) NOT NULL DEFAULT 0,
  show_full TINYINT(1) NOT NULL DEFAULT 0,
  show_evidence TINYINT(1) NOT NULL DEFAULT 0,
  evidence_consent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  KEY ix_publication_version (version_id, id),
  CONSTRAINT fk_publication_version FOREIGN KEY (version_id) REFERENCES declaration_versions(id) ON DELETE RESTRICT,
  CONSTRAINT fk_publication_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legal_references (
  rkey VARCHAR(40) PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  citation VARCHAR(300) NOT NULL,
  url VARCHAR(500) NOT NULL,
  summary TEXT NOT NULL,
  verification_note TEXT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schemes (
  version VARCHAR(10) PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  status ENUM('DRAFT','PUBLISHED','DEPRECATED') NOT NULL,
  spec LONGTEXT NOT NULL,
  published_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incidents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('CONTACT','CONTENT_REPORT','TECHNICAL','DATA_ACCESS','DATA_RECTIFICATION','DATA_ERASURE','DATA_OBJECTION','DATA_PORTABILITY','DATA_RESTRICTION') NOT NULL,
  status ENUM('OPEN','IN_PROGRESS','RESOLVED','REJECTED') NOT NULL DEFAULT 'OPEN',
  name VARCHAR(120) NULL,
  email VARCHAR(254) NOT NULL,
  public_id CHAR(18) NULL,
  user_id INT UNSIGNED NULL,
  message TEXT NOT NULL,
  resolution TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY ix_incident_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(40) NOT NULL,
  entity_id VARCHAR(40) NULL,
  metadata TEXT NULL,
  ip_fingerprint CHAR(32) NULL,
  created_at DATETIME NOT NULL,
  KEY ix_audit_created (created_at),
  KEY ix_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
