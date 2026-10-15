# WealthFlow backend — FR01

PHP 8.2+, PDO MySQL, PHPMailer và firebase/php-jwt. Cài dependencies bằng `composer install`; không commit `vendor` hoặc `config.local.php`. Xem [cài đặt và email](../../FR01_XAMPP.md).

## API

| Method | Route | Dữ liệu JSON |
|---|---|---|
| GET | `/api/health` | Kiểm tra kết nối database |
| GET | `/api/features` | Phạm vi đã triển khai |
| POST | `/api/auth/register` | `fullName`, `email`, `password`, `passwordConfirmation`, `baseCurrency` |
| POST | `/api/auth/login` | `email`, `password` |
| GET | `/api/auth/me` | Hồ sơ từ phiên hiện tại |
| POST | `/api/auth/refresh` | `{}` |
| POST | `/api/auth/logout` | `{}` |
| POST | `/api/auth/change-password` | `currentPassword`, `newPassword`, `passwordConfirmation` |

POST bắt buộc `Content-Type: application/json`, `X-WealthFlow: 1`, cùng origin với APP_URL. Không bật CORS. Response thống nhất `success`, `data`, `error`, `requestId`; lỗi validation có `error.fieldErrors`. Body tối đa 16 KiB.

Access JWT 15 phút; refresh JWT 7 ngày, được xoay mỗi lần refresh. Cả hai nằm trong cookie HttpOnly/SameSite=Strict, Secure khi HTTPS; không lưu token trong localStorage. Refresh token chỉ lưu SHA-256 ở database. Mỗi yêu cầu bảo vệ kiểm tra chữ ký HS256, issuer, audience, loại token, hạn dùng, trạng thái ACTIVE và phiên còn tồn tại. Logout xóa phiên ngay; đổi mật khẩu xóa mọi phiên cũ và cấp cặp token mới.

HTTP chỉ được phép với APP_ENV=local và localhost/loopback. Khi triển khai ngoài local, đặt APP_ENV=production và APP_URL=https://...; cấu hình TLS trên máy chủ. Các script XAMPP dành cho phát triển local.

Mật khẩu từ 8 ký tự, có hoa/thường/số, tối đa 72 byte theo giới hạn bcrypt; cost 12. Giới hạn đăng nhập theo IP và email, đổi mật khẩu theo tài khoản, đăng ký theo IP. Nhật ký bảo mật chỉ ghi loại sự kiện và user ID.

`EmailService::send(...)` là lớp gửi SMTP dùng lại được; `sendWelcome(...)` tạo email chào mừng. Không đưa mật khẩu người dùng vào email. Email lỗi được lưu PENDING; chạy `php bin/send-pending-emails.php` sau khi sửa SMTP. Worker xử lý tối đa 50 email/lần. Hàng đợi có thể gửi lặp nếu tiến trình chết sau khi SMTP nhận thư nhưng trước khi ghi SENT; đây là cơ chế thử lại ít nhất một lần.

Test tự động: từ gốc repository chạy `node tests/auth-integration.mjs` trên Linux có XAMPP, Node 20+, MariaDB server/client. Test dựng database, htdocs và SMTP riêng trong thư mục tạm, kiểm tra cả cài mới/khôi phục và Apache thật.
