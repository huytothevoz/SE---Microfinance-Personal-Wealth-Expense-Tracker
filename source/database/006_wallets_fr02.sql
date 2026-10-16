SET @wallet_active_name_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND column_name = 'active_name_key'
);
SET @wallet_active_name_ddl = IF(
  @wallet_active_name_exists = 0,
  'ALTER TABLE wallets ADD COLUMN active_name_key VARCHAR(120) NULL AFTER name',
  'SELECT 1'
);
PREPARE wallet_active_name_stmt FROM @wallet_active_name_ddl;
EXECUTE wallet_active_name_stmt;
DEALLOCATE PREPARE wallet_active_name_stmt;

SET @wallet_icon_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND column_name = 'icon_key'
);
SET @wallet_icon_ddl = IF(
  @wallet_icon_exists = 0,
  'ALTER TABLE wallets ADD COLUMN icon_key VARCHAR(30) NULL AFTER status',
  'SELECT 1'
);
PREPARE wallet_icon_stmt FROM @wallet_icon_ddl;
EXECUTE wallet_icon_stmt;
DEALLOCATE PREPARE wallet_icon_stmt;

SET @wallet_color_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND column_name = 'color_key'
);
SET @wallet_color_ddl = IF(
  @wallet_color_exists = 0,
  'ALTER TABLE wallets ADD COLUMN color_key VARCHAR(20) NULL AFTER icon_key',
  'SELECT 1'
);
PREPARE wallet_color_stmt FROM @wallet_color_ddl;
EXECUTE wallet_color_stmt;
DEALLOCATE PREPARE wallet_color_stmt;

SET @wallet_archived_at_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND column_name = 'archived_at'
);
SET @wallet_archived_at_ddl = IF(
  @wallet_archived_at_exists = 0,
  'ALTER TABLE wallets ADD COLUMN archived_at DATETIME NULL AFTER color_key',
  'SELECT 1'
);
PREPARE wallet_archived_at_stmt FROM @wallet_archived_at_ddl;
EXECUTE wallet_archived_at_stmt;
DEALLOCATE PREPARE wallet_archived_at_stmt;

SET @wallet_updated_at_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND column_name = 'updated_at'
);
SET @wallet_updated_at_ddl = IF(
  @wallet_updated_at_exists = 0,
  'ALTER TABLE wallets ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
  'SELECT 1'
);
PREPARE wallet_updated_at_stmt FROM @wallet_updated_at_ddl;
EXECUTE wallet_updated_at_stmt;
DEALLOCATE PREPARE wallet_updated_at_stmt;

UPDATE wallets
SET active_name_key = CASE WHEN status = 'ACTIVE' THEN LOWER(TRIM(name)) ELSE NULL END;

SET @wallet_old_unique_exists = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND index_name = 'uk_wallet_user_name'
);
SET @wallet_old_unique_ddl = IF(
  @wallet_old_unique_exists > 0,
  'ALTER TABLE wallets DROP INDEX uk_wallet_user_name',
  'SELECT 1'
);
PREPARE wallet_old_unique_stmt FROM @wallet_old_unique_ddl;
EXECUTE wallet_old_unique_stmt;
DEALLOCATE PREPARE wallet_old_unique_stmt;

SET @wallet_active_unique_exists = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'wallets' AND index_name = 'uk_wallet_user_active_name'
);
SET @wallet_active_unique_ddl = IF(
  @wallet_active_unique_exists = 0,
  'ALTER TABLE wallets ADD UNIQUE KEY uk_wallet_user_active_name (user_id, active_name_key)',
  'SELECT 1'
);
PREPARE wallet_active_unique_stmt FROM @wallet_active_unique_ddl;
EXECUTE wallet_active_unique_stmt;
DEALLOCATE PREPARE wallet_active_unique_stmt;
