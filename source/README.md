# WealthFlow — FR01 và giao diện tài chính mẫu

PHP 8.2+, MySQL 8 / MariaDB 10.4+ của XAMPP; HTML/CSS/JavaScript ES Modules, không cần npm cho giao diện.

Đăng ký, đăng nhập, đăng xuất, đổi mật khẩu, kiểm tra/thu hồi phiên và gửi email chào mừng đã kết nối backend. Dữ liệu tài chính ở `frontend/js/demo-data.js` vẫn là mẫu; các form tài chính chưa lưu database.

## Chạy trên XAMPP

Xem [hướng dẫn đầy đủ](../FR01_XAMPP.md). Hai script ở thư mục gốc repository sao lưu toàn bộ `htdocs` rồi cài mới vào `htdocs/wealthflow`; mặc định mở `http://localhost:8080/`. Không chạy script cài mới nếu muốn giữ dữ liệu WealthFlow hiện tại.

## Cấu trúc

```text
source/
├── index.html                # Landing + đăng ký/đăng nhập thật
├── app.html                  # Template giao diện; máy chủ luôn chuyển qua app.php
├── app.php                   # Bảo vệ trang bằng phiên server
├── .htaccess                 # Apache routes + chặn file riêng tư
├── router.php                # PHP development server
├── frontend/js/auth-api.js   # API client + refresh phiên
├── frontend/js/landing.js    # Form đăng ký/đăng nhập
├── frontend/js/app.js        # Hồ sơ thật, đổi mật khẩu, logout + UI tài chính mẫu
├── backend/
│   ├── composer.json, composer.lock
│   ├── config.example.php    # Cấu hình mẫu không có secrets
│   ├── config.local.php      # Sinh khi cài, không commit
│   ├── public/index.php      # API entry point
│   ├── src/Auth/             # AuthService, TokenService
│   ├── src/Mail/             # EmailService, WelcomeEmailQueue
│   ├── bin/setup.php         # Migration không xóa dữ liệu
│   ├── bin/send-pending-emails.php
│   └── storage/              # .eml khi MAIL_TRANSPORT=log
└── database/
    ├── 001_initial_schema.sql
    └── 002_auth.sql
```

## Chạy phát triển, giữ dữ liệu

Từ `source/backend`, chạy `composer install`, tạo `config.local.php` theo mẫu (APP_KEY ngẫu nhiên ít nhất 32 ký tự, APP_URL khớp URL thật), rồi chạy `php bin/setup.php`. Sau đó từ `source` chạy `php -S localhost:8000 router.php`, với APP_URL là `http://localhost:8000`. Dùng đúng PHP 8.2+ của XAMPP.

`bin/setup.php` chỉ áp dụng migration chưa chạy, không xóa bảng hay tài khoản. Khác với hai script setup ngoài repository là cài mới. Không dùng static server Python cho bản có xác thực.

## Phạm vi

- FR01.01: email chuẩn hóa/duy nhất, bcrypt cost 12, gán danh mục mặc định trong transaction.
- FR01.02: đăng nhập, lỗi chung khi sai thông tin; chỉ tài khoản ACTIVE được dùng phiên.
- FR01.03: logout thu hồi phiên server; JWT hết hạn/bị sửa/phiên bị thu hồi bị từ chối.
- FR01.04: đổi mật khẩu cần mật khẩu cũ, hủy các phiên cũ và cấp phiên mới cho thiết bị hiện tại.
- Email đăng ký: SMTP, nội dung HTML và text, lưu hàng đợi để thử lại nếu gửi lỗi.
- FR02–FR14 và nghiệp vụ tài chính: vẫn là UI mẫu. FR15 chỉ áp dụng cho các endpoint xác thực đã triển khai; chưa có API ví/giao dịch để kiểm tra ownership.

Xem [API](backend/README.md) và [database](database/README.md).
