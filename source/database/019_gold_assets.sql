-- Table 19: gold_assets (Physical Gold Asset Holdings)
CREATE TABLE IF NOT EXISTS gold_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  gold_type ENUM('RING_9999','SJC_BAR','GOLD_24K') NOT NULL,
  quantity_chi DECIMAL(18,4) NOT NULL,
  total_cost_basis DECIMAL(24,8) NOT NULL DEFAULT 0,
  weighted_avg_cost DECIMAL(24,8) NOT NULL DEFAULT 0,
  valuation_per_chi DECIMAL(24,8) NOT NULL,
  valuation_date DATE NOT NULL,
  status ENUM('HELD','LIQUIDATED') NOT NULL DEFAULT 'HELD',
  CHECK (quantity_chi >= 0),
  CHECK (valuation_per_chi >= 0),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
