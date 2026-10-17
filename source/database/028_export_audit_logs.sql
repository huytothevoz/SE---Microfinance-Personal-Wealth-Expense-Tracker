-- Table 28: export_audit_logs (Safe CSV & Report Export Audit Trail)
CREATE TABLE IF NOT EXISTS export_audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  export_type VARCHAR(50) NOT NULL,
  row_count INT UNSIGNED NOT NULL DEFAULT 0,
  file_hash CHAR(64) NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_export_user_time (user_id, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
