-- Table 18: loan_payments (Actual Debt Payment History)
CREATE TABLE IF NOT EXISTS loan_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_id BIGINT UNSIGNED NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  principal_amount DECIMAL(24,8) NOT NULL,
  interest_fee_amount DECIMAL(24,8) NOT NULL DEFAULT 0,
  paid_on DATE NOT NULL,
  CHECK (principal_amount >= 0),
  CHECK (interest_fee_amount >= 0),
  FOREIGN KEY (loan_id) REFERENCES micro_loans(id) ON DELETE RESTRICT,
  FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;
