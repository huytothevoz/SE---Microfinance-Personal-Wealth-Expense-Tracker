-- Table 01: users (Identity & Access Control)
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('USER','ADMIN') NOT NULL DEFAULT 'USER',
  status ENUM('ACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  base_currency CHAR(3) NOT NULL DEFAULT 'VND',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_email (email)
) ENGINE=InnoDB;

-- Default admin & demo user seeds if not present
INSERT IGNORE INTO users (id, full_name, email, password_hash, role, status, base_currency, created_at, updated_at) VALUES
  (1, 'System Administrator', 'admin@wealthflow.vn', '$2y$12$Q1aXueoEuv8JCuw1EzAuluF2QkmAMXouVZuUCdisKQgTEzo3YeRC.', 'ADMIN', 'ACTIVE', 'VND', '2026-10-15 00:00:00', '2026-10-15 00:00:00'),
  (2, 'Nguyen Van A', 'user1@gmail.com', '$2y$12$3E5SQtCbtDHbZEvyMahaBOeGp5XEtthDK6v8k3QATe.gBCokmPRhm', 'USER', 'ACTIVE', 'VND', '2026-10-15 18:04:56', '2026-10-15 18:04:56'),
  (3, 'Local Administrator', 'admin@wealthflow.local', '$2y$12$3E5SQtCbtDHbZEvyMahaBOeGp5XEtthDK6v8k3QATe.gBCokmPRhm', 'ADMIN', 'ACTIVE', 'VND', '2026-10-15 00:00:00', '2026-10-15 00:00:00');
