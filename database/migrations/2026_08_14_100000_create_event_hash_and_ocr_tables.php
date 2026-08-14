<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $path = base_path('database/sql/2026_08_14_create_event_hash_receipts_and_ocr.sql');
        if (is_readable($path)) {
            DB::unprepared(file_get_contents($path));

            return;
        }

        DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS event_hash_receipts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  receipt_id CHAR(36) NOT NULL,
  source_system VARCHAR(32) NOT NULL,
  entity_type VARCHAR(64) NOT NULL,
  entity_id VARCHAR(64) NOT NULL,
  phase_action VARCHAR(64) NOT NULL,
  event_hash CHAR(64) NOT NULL,
  prev_hash CHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_event_hash_receipts_receipt_id (receipt_id),
  KEY idx_event_hash_receipts_source_entity (source_system, entity_type, entity_id),
  KEY idx_event_hash_receipts_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_extracted_texts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  source_system VARCHAR(32) NOT NULL,
  document_key VARCHAR(191) NOT NULL,
  extraction_method VARCHAR(32) NOT NULL DEFAULT 'none',
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  extracted_text LONGTEXT NULL,
  completeness_json JSON NULL,
  error_message VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_document_extracted_texts_key (source_system, document_key),
  KEY idx_document_extracted_texts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS constituency_phase_aging_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  source_system VARCHAR(32) NOT NULL,
  constituency_code VARCHAR(32) NULL,
  constituency_name VARCHAR(191) NULL,
  phase_status VARCHAR(64) NOT NULL,
  application_count INT UNSIGNED NOT NULL DEFAULT 0,
  avg_days_in_phase DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_days_in_phase INT UNSIGNED NOT NULL DEFAULT 0,
  snapshot_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_phase_aging_source_const (source_system, constituency_code),
  KEY idx_phase_aging_snapshot (snapshot_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    public function down(): void
    {
        // Keep shared operational tables; do not drop on rollback.
    }
};
