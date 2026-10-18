-- Table 23: goal_allocations (Deposit & Withdrawal Allocations)
CREATE TABLE IF NOT EXISTS goal_allocations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id BIGINT UNSIGNED NOT NULL,
  wallet_id BIGINT UNSIGNED NULL,
  allocation_type ENUM('DEPOSIT','WITHDRAWAL') NOT NULL DEFAULT 'DEPOSIT',
  amount DECIMAL(24,8) NOT NULL,
  allocated_on DATE NOT NULL,
  note VARCHAR(250) NULL,
  CHECK (amount > 0),
  FOREIGN KEY (goal_id) REFERENCES financial_goals(id) ON DELETE CASCADE,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
) ENGINE=InnoDB;
