-- Store user-entered notes for personal income and expense categories.
ALTER TABLE categories
  ADD COLUMN IF NOT EXISTS note VARCHAR(500) NULL AFTER category_type;
