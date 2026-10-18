-- Table 26: system_config (System-wide Configuration Parameters)
CREATE TABLE IF NOT EXISTS system_config (
  config_key VARCHAR(100) PRIMARY KEY,
  config_value VARCHAR(500) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default configuration seeds
INSERT IGNORE INTO system_config (config_key, config_value) VALUES
  ('MAX_FAILED_LOGINS', '5'),
  ('OTP_EXPIRY_MINUTES', '10'),
  ('SESSION_EXPIRY_DAYS', '7'),
  ('DEFAULT_BASE_CURRENCY', 'VND'),
  ('ALLOW_REGISTRATION', 'true');
