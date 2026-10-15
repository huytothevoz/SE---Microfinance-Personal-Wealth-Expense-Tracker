-- Additive FR01 migration, compatible with MySQL 8 and XAMPP MariaDB 10.4+.
CREATE TABLE IF NOT EXISTS auth_sessions (
  id CHAR(64) PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  refresh_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_auth_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auth_rate_limits (
  bucket CHAR(64) PRIMARY KEY,
  attempts INT UNSIGNED NOT NULL DEFAULT 1,
  expires_at DATETIME NOT NULL,
  KEY idx_limit_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS security_audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  event VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_security_user_time (user_id, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registration_emails (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('PENDING','SENT') NOT NULL DEFAULT 'PENDING',
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  sent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_welcome_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO default_categories (name, category_type) VALUES
  ('Lương', 'INCOME'), ('Thu nhập khác', 'INCOME'),
  ('Ăn uống', 'EXPENSE'), ('Đi lại', 'EXPENSE'),
  ('Nhà ở', 'EXPENSE'), ('Mua sắm', 'EXPENSE'), ('Giải trí', 'EXPENSE');

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `status`, `base_currency`, `created_at`, `updated_at`) VALUES (NULL, 'nguyen', 'user1@gmail.com', '$2y$12$3E5SQtCbtDHbZEvyMahaBOeGp5XEtthDK6v8k3QATe.gBCokmPRhm', 'USER', 'ACTIVE', 'VND', '2026-10-15 18:04:56', '2026-10-15 18:04:56')


