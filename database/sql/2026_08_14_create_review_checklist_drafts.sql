CREATE TABLE IF NOT EXISTS review_checklist_drafts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  source_system VARCHAR(32) NOT NULL,
  application_id VARCHAR(64) NOT NULL,
  phase_id BIGINT UNSIGNED NULL,
  phase_status VARCHAR(64) NOT NULL DEFAULT '',
  checklist_json JSON NOT NULL,
  progress_json JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_checklist_drafts_scope (source_system, application_id, phase_status),
  KEY idx_review_checklist_drafts_phase (phase_id),
  KEY idx_review_checklist_drafts_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
