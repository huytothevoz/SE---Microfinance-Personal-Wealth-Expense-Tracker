-- Table 25: notifications (In-app Notification Center)
CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  notification_type ENUM('BUDGET','LOAN','RECURRING','BROADCAST','SYSTEM') NOT NULL,
  priority ENUM('INFO','WARNING','CRITICAL','MAINTENANCE','URGENT') NOT NULL DEFAULT 'INFO',
  dedup_key VARCHAR(150) NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_notification_dedup (user_id, dedup_key),
  KEY idx_notif_user_unread (user_id, read_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
