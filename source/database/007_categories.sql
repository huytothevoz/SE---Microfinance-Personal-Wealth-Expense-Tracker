-- Table 07: categories (Personal Income & Expense Categories)
CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  category_type ENUM('INCOME','EXPENSE') NOT NULL,
  archived_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uk_user_category (user_id, name, category_type),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
