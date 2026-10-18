-- Table 16: micro_loans (Microfinance Contracts: Borrowed / Lent)
CREATE TABLE IF NOT EXISTS micro_loans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  wallet_id BIGINT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  partner_name VARCHAR(150) NOT NULL,
  loan_direction ENUM('BORROWED','LENT') NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  principal_original DECIMAL(24,8) NOT NULL,
  principal_remaining DECIMAL(24,8) NOT NULL,
  interest_rate_year DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  term_months INT UNSIGNED NOT NULL DEFAULT 0,
  start_date DATE NOT NULL,
  next_due_date DATE NULL,
  status ENUM('ACTIVE','PAID_OFF','DEFAULTED') NOT NULL DEFAULT 'ACTIVE',
  CHECK (principal_original > 0),
  CHECK (principal_remaining >= 0),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
) ENGINE=InnoDB;
