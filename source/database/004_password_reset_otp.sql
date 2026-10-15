ALTER TABLE password_reset_tokens
  ADD COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER expires_at;
