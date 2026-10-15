# WealthFlow backend — FR01, FR03–FR06

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
| POST | `/api/auth/password/forgot` | `email` |
| POST | `/api/auth/password/reset` | `email`, `otp`, `newPassword`, `passwordConfirmation` |
| GET | `/api/wallets` | Ví của phiên hiện tại |
| GET/POST | `/api/transactions` | Liệt kê hoặc ghi giao dịch; hỗ trợ `Idempotency-Key` |
| POST | `/api/transactions/transfer` | Chuyển tiền giữa hai ví |
| GET | `/api/transactions/{id}` | Chi tiết giao dịch thuộc user hiện tại |
| POST | `/api/transactions/{id}/reverse` | Đảo giao dịch |
| GET/POST | `/api/categories` | Danh mục của user hiện tại |
| GET | `/api/exchange-rates` | Tỷ giá mới nhất |
| POST | `/api/exchange-rates` | Ghi tỷ giá; chỉ tài khoản ADMIN |
| GET/POST | `/api/budgets` | Liệt kê/tạo ngân sách |
| GET | `/api/budgets/summary` | Tổng hợp ngân sách |
| PUT/DELETE | `/api/budgets/{id}` | Cập nhật/xóa ngân sách thuộc user |
| GET/POST | `/api/recurring` | Liệt kê/tạo lịch định kỳ |
| POST | `/api/recurring/{id}/pause` hoặc `/resume` | Tạm dừng/kích hoạt lịch của user |
| DELETE | `/api/recurring/{id}` | Xóa lịch thuộc user |
| GET | `/api/notifications` | Thông báo của user hiện tại |
| POST | `/api/notifications/{id}/read` hoặc `/read-all` | Đánh dấu thông báo đã đọc |

POST bắt buộc `Content-Type: application/json`, `X-WealthFlow: 1`, cùng origin với APP_URL. Không bật CORS. Response thống nhất `success`, `data`, `error`, `requestId`; lỗi validation có `error.fieldErrors`. Body tối đa 16 KiB.

Các API tài chính xác thực cookie phiên máy chủ WealthFlow; không chấp nhận user ID từ header/query và không có user mặc định. Tỷ giá toàn cục chỉ ADMIN được ghi. Worker lịch định kỳ không có HTTP route; cấu hình scheduler đáng tin cậy chạy `php bin/process_recurring.php`. Worker lấy khóa MySQL độc quyền và xử lý lịch, bút toán, execution record trong cùng transaction.

Chạy thử local từ gốc repository: `php -S 127.0.0.1:8080 -t source source/backend/router.php`. Router này chỉ dành cho PHP built-in development server.

Access JWT 15 phút; refresh JWT 7 ngày, được xoay mỗi lần refresh. Cả hai nằm trong cookie HttpOnly/SameSite=Strict, Secure khi HTTPS; không lưu token trong localStorage. Refresh token chỉ lưu SHA-256 ở database. Mỗi yêu cầu bảo vệ kiểm tra chữ ký HS256, issuer, audience, loại token, hạn dùng, trạng thái ACTIVE và phiên còn tồn tại. Logout xóa phiên ngay; đổi mật khẩu xóa mọi phiên cũ và cấp cặp token mới.

HTTP chỉ được phép với APP_ENV=local và localhost/loopback. Khi triển khai ngoài local, đặt APP_ENV=production và APP_URL=https://...; cấu hình TLS trên máy chủ. Các script XAMPP dành cho phát triển local.

Trên Windows, chạy `.\setup-run-version-windows.ps1` từ thư mục gốc sau khi cài XAMPP và Composer. Script tự dò XAMPP ở các vị trí phổ biến, biến `XAMPP_DIR` và XAMPP Control Panel đang chạy; với vị trí tùy chỉnh dùng `-XamppDir 'D:\apps\xampp'`. Cần Composer trong PATH, `composer.phar` cạnh script/XAMPP, hoặc có thể truyền `-ComposerPhar`; nếu PHP ZIP extension bị tắt, script bật tạm từ `php\ext\php_zip.dll` chỉ trong lúc Composer chạy, không sửa `php.ini`. Nếu cổng 8080 đang bận, chọn cổng khác bằng `-ApachePort`. Script cài mới sẽ sao lưu toàn bộ `htdocs`, chỉ đặt lại database WealthFlow, cấu hình route API/app trực tiếp trong Apache và kiểm tra `/api/health`; không cần `source/.htaccess`. Mở địa chỉ website mà script in ra, không mở `index.html` trực tiếp bằng `file://`.

Mật khẩu từ 8 ký tự, có hoa/thường/số, tối đa 72 byte theo giới hạn bcrypt; cost 12. Giới hạn đăng nhập theo IP và email, đổi mật khẩu theo tài khoản, đăng ký theo IP. Nhật ký bảo mật chỉ ghi loại sự kiện và user ID.

Yêu cầu đặt lại mật khẩu không tiết lộ email có tồn tại hay không. OTP 6 chữ số chỉ lưu dạng HMAC, hết hạn sau 10 phút và cho phép tối đa 5 lần nhập; đặt lại thành công sẽ thu hồi tất cả phiên đăng nhập. Cần cấu hình SMTP để gửi email đặt lại. Migrations `003_password_reset.sql` và `004_password_reset_otp.sql` được áp dụng tự động khi chạy `php bin/setup.php`.

Để gửi OTP qua `wealthflow.voz@gmail.com`, bật xác minh 2 bước cho tài khoản Google, tạo App Password rồi nhập App Password vào `MAIL_PASSWORD` trong `config.local.php`. `MAIL_USERNAME` và `MAIL_FROM_ADDRESS` đã mặc định là địa chỉ WealthFlow này; SMTP Gmail dùng `smtp.gmail.com:587` với TLS. Không dùng mật khẩu đăng nhập Google thường và không commit `config.local.php`.

`EmailService::send(...)` là lớp gửi SMTP dùng lại được; `sendWelcome(...)` tạo email chào mừng. Không đưa mật khẩu người dùng vào email. Email chào mừng lỗi được lưu PENDING; chạy `php bin/send-pending-emails.php` sau khi sửa SMTP. Worker xử lý tối đa 50 email/lần. Hàng đợi có thể gửi lặp nếu tiến trình chết sau khi SMTP nhận thư nhưng trước khi ghi SENT; đây là cơ chế thử lại ít nhất một lần.

Test tự động: từ gốc repository chạy `node tests/auth-integration.mjs` trên Linux có XAMPP, Node 20+, MariaDB server/client. Test dựng database, htdocs và SMTP riêng trong thư mục tạm, kiểm tra cả cài mới/khôi phục và Apache thật.
