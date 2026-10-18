-- Table 08: default_categories (System Standard Categories)
CREATE TABLE IF NOT EXISTS default_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category_type ENUM('INCOME','EXPENSE') NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  UNIQUE KEY uk_default_category (name, category_type)
) ENGINE=InnoDB;

-- Initial standard category seeds
INSERT IGNORE INTO default_categories (name, category_type) VALUES
  ('Lương', 'INCOME'),
  ('Thưởng & Phụ cấp', 'INCOME'),
  ('Thu nhập đầu tư', 'INCOME'),
  ('Thu nhập khác', 'INCOME'),
  ('Ăn uống', 'EXPENSE'),
  ('Đi lại & Xăng xe', 'EXPENSE'),
  ('Nhà ở & Tiền điện nước', 'EXPENSE'),
  ('Mua sắm & Tiêu dùng', 'EXPENSE'),
  ('Giải trí & Du lịch', 'EXPENSE'),
  ('Y tế & Sức khỏe', 'EXPENSE'),
  ('Giáo dục & Đào tạo', 'EXPENSE'),
  ('Chi phí tài chính & Lãi vay', 'EXPENSE');
