-- Table 24: net_worth_snapshots (Daily Midnight Net Worth Balance Snapshot)
CREATE TABLE IF NOT EXISTS net_worth_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  total_assets DECIMAL(24,8) NOT NULL,
  total_liabilities DECIMAL(24,8) NOT NULL,
  net_worth DECIMAL(24,8) NOT NULL,
  breakdown_json JSON NULL,
  UNIQUE KEY uk_snapshot_day (user_id, snapshot_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
