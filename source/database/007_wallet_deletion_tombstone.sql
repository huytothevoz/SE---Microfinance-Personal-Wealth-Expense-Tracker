SET @wallet_deleted_at_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND column_name = 'deleted_at'
);
SET @wallet_deleted_at_ddl = IF(
  @wallet_deleted_at_exists = 0,
  'ALTER TABLE wallets ADD COLUMN deleted_at DATETIME NULL AFTER archived_at',
  'SELECT 1'
);
PREPARE wallet_deleted_at_stmt FROM @wallet_deleted_at_ddl;
EXECUTE wallet_deleted_at_stmt;
DEALLOCATE PREPARE wallet_deleted_at_stmt;

SET @wallet_deleted_status_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'wallets'
    AND column_name = 'status'
    AND column_type LIKE '%DELETED%'
);
SET @wallet_deleted_status_ddl = IF(
  @wallet_deleted_status_exists = 0,
  'ALTER TABLE wallets MODIFY status ENUM(''ACTIVE'',''ARCHIVED'',''DELETED'') NOT NULL DEFAULT ''ACTIVE''',
  'SELECT 1'
);
PREPARE wallet_deleted_status_stmt FROM @wallet_deleted_status_ddl;
EXECUTE wallet_deleted_status_stmt;
DEALLOCATE PREPARE wallet_deleted_status_stmt;
