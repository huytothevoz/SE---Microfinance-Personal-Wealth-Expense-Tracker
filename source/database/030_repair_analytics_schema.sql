-- Add columns required by the current analytics and wealth DAOs to legacy tables.
ALTER TABLE micro_loans
  ADD COLUMN IF NOT EXISTS wallet_id BIGINT UNSIGNED NULL AFTER user_id;

ALTER TABLE financial_goals
  ADD COLUMN IF NOT EXISTS wallet_id BIGINT UNSIGNED NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS current_amount DECIMAL(24,8) NOT NULL DEFAULT 0 AFTER target_amount,
  ADD COLUMN IF NOT EXISTS priority ENUM('LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'MEDIUM' AFTER deadline;

ALTER TABLE net_worth_snapshots
  ADD COLUMN IF NOT EXISTS breakdown_json JSON NULL AFTER net_worth;
