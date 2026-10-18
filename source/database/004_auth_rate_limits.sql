-- Table 04: auth_rate_limits (Brute-force & DoS Defense)
CREATE TABLE IF NOT EXISTS auth_rate_limits (
  bucket CHAR(64) PRIMARY KEY,
  attempts INT UNSIGNED NOT NULL DEFAULT 1,
  expires_at DATETIME NOT NULL,
  KEY idx_limit_expiry (expires_at)
) ENGINE=InnoDB;
