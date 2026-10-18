-- Table 21: exchange_rates (Interbank Currency & Gold Rates)
CREATE TABLE IF NOT EXISTS exchange_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_currency CHAR(3) NOT NULL,
  to_currency CHAR(3) NOT NULL,
  rate DECIMAL(24,10) NOT NULL,
  effective_at DATETIME NOT NULL,
  provider VARCHAR(100) NULL,
  CHECK (rate > 0),
  KEY idx_rate_pair_date (from_currency, to_currency, effective_at)
) ENGINE=InnoDB;
