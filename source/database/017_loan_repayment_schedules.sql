-- Table 17: loan_repayment_schedules (Amortization & Installment Schedule)
CREATE TABLE IF NOT EXISTS loan_repayment_schedules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_id BIGINT UNSIGNED NOT NULL,
  period_number INT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  principal_due DECIMAL(24,8) NOT NULL DEFAULT 0,
  interest_due DECIMAL(24,8) NOT NULL DEFAULT 0,
  principal_paid DECIMAL(24,8) NOT NULL DEFAULT 0,
  interest_paid DECIMAL(24,8) NOT NULL DEFAULT 0,
  status ENUM('PENDING','PARTIAL','PAID','OVERDUE') NOT NULL DEFAULT 'PENDING',
  KEY idx_repayment_loan_date (loan_id, due_date),
  FOREIGN KEY (loan_id) REFERENCES micro_loans(id) ON DELETE CASCADE
) ENGINE=InnoDB;
