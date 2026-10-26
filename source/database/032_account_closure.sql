-- Record account closure separately from administrative account locks.
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS closed_at TIMESTAMP NULL DEFAULT NULL;
