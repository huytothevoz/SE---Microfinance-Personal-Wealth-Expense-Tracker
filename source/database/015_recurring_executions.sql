-- Table 15: recurring_executions (Audit Log of Recurring Auto-Post / Reminder)
CREATE TABLE IF NOT EXISTS recurring_executions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recurring_schedule_id BIGINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  transaction_id BIGINT UNSIGNED NULL,
  status ENUM('PENDING','COMPLETED','FAILED') NOT NULL DEFAULT 'PENDING',
  executed_at DATETIME NULL,
  error_message VARCHAR(255) NULL,
  UNIQUE KEY uk_recurring_once (recurring_schedule_id, due_date),
  FOREIGN KEY (recurring_schedule_id) REFERENCES recurring_schedules(id) ON DELETE CASCADE,
  FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;
