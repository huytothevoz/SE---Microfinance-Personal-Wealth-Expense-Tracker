-- Table 13: budgets (Monthly Category Spend Limits & Alerts)
CREATE TABLE IF NOT EXISTS budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  budget_month DATE NOT NULL,
  limit_amount DECIMAL(24,8) NOT NULL,
  spent_amount DECIMAL(24,8) NOT NULL DEFAULT 0,
  alert_level ENUM('NORMAL','YELLOW_80','RED_100') NOT NULL DEFAULT 'NORMAL',
  is_yellow_sent BOOLEAN NOT NULL DEFAULT FALSE,
  is_red_sent BOOLEAN NOT NULL DEFAULT FALSE,
  status ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  CHECK (limit_amount > 0),
  UNIQUE KEY uk_budget_month (user_id, category_id, budget_month),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
