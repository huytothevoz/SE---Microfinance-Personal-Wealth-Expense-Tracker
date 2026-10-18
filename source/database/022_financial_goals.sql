-- Table 22: financial_goals (Savings Targets with Earmarking)
CREATE TABLE IF NOT EXISTS financial_goals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  wallet_id BIGINT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  target_amount DECIMAL(24,8) NOT NULL,
  current_amount DECIMAL(24,8) NOT NULL DEFAULT 0,
  deadline DATE NOT NULL,
  priority ENUM('LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'MEDIUM',
  status ENUM('ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  CHECK (target_amount > 0),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
) ENGINE=InnoDB;
