-- Restore columns expected by the current goal-allocation DAO on older 023 schemas.
ALTER TABLE goal_allocations
  ADD COLUMN IF NOT EXISTS wallet_id BIGINT UNSIGNED NULL AFTER goal_id,
  ADD COLUMN IF NOT EXISTS allocation_type ENUM('DEPOSIT','WITHDRAWAL') NOT NULL DEFAULT 'DEPOSIT' AFTER wallet_id;
