-- Safe for existing databases and fresh installs that already include the column.
SET @response_json_exists = (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'idempotency_keys'
    AND column_name = 'response_json'
);
SET @response_json_ddl = IF(
  @response_json_exists = 0,
  'ALTER TABLE idempotency_keys ADD COLUMN response_json JSON NULL AFTER result_code',
  'SELECT 1'
);
PREPARE response_json_stmt FROM @response_json_ddl;
EXECUTE response_json_stmt;
DEALLOCATE PREPARE response_json_stmt;
