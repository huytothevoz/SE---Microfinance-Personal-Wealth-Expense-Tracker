# WealthFlow — Hướng dẫn sửa lỗi OTP quên mật khẩu

> Hướng dẫn dành cho máy phát triển XAMPP Windows. Không cung cấp Gmail App Password, OTP hay `config.local.php` cho người khác.

## 1. Vì sao OTP không đến email?

Source ban đầu để `MAIL_PASSWORD` trống trong `config.example.php`. PHP sử dụng PHPMailer kết nối SMTP Gmail nên **phải cấu hình Gmail App Password thực** trong `backend/config.local.php`. Source cũ bắt lỗi SMTP nhưng vẫn trả phản hồi bình thường cho frontend, khiến giao diện chuyển đến màn nhập OTP dù email chưa gửi. Bản sửa kiểm tra cấu hình trước khi tạo mã, thêm CLI chẩn đoán và giữ thông báo chung để không lộ tài khoản đã đăng ký.

## 2. Cấu hình SMTP (Gmail)

1. Mở `F:\xampp\htdocs\wealthflow\backend\config.local.php` (đổi ổ đĩa theo máy). Nếu đang chạy bản ZIP bằng lệnh `php -S`, file ở `source/backend/config.local.php`.
2. Bảo đảm `APP_KEY` đã có và giữ nguyên từ bản đã chạy; **không đổi APP_KEY khi có phiên/token đang hoạt động**.
3. Đối với tài khoản Gmail gửi thư của nhóm: bật xác minh hai bước trên tài khoản Google và tạo App Password riêng cho ứng dụng (nếu tài khoản đáp ứng điều kiện của Google). Không dùng mật khẩu đăng nhập Gmail thông thường.
4. Chỉnh các giá trị trong file private:

```php
'MAIL_TRANSPORT' => 'smtp',
'MAIL_HOST' => 'smtp.gmail.com',
'MAIL_PORT' => '587',
'MAIL_ENCRYPTION' => 'tls',
'MAIL_USERNAME' => 'dia-chi-gmail-gui-thu@gmail.com',
'MAIL_PASSWORD' => 'DAN_APP_PASSWORD_CUA_BAN_VAO_DAY',
'MAIL_FROM_ADDRESS' => 'dia-chi-gmail-gui-thu@gmail.com',
'MAIL_FROM_NAME' => 'WealthFlow',
```

`MAIL_USERNAME` và `MAIL_FROM_ADDRESS` phải tương ứng với người gửi mà Google SMTP cho phép. Lưu file; không cần khởi động lại Apache để nạp lại cấu hình PHP cho mỗi request.

## 3. Chạy kiểm tra trước khi nhập OTP

Trong CMD tại thư mục `backend` đã triển khai (nếu đang đứng ở repository thì `cd source\backend`):

```cmd
"F:\xampp\php\php.exe" bin\check-mail.php
"F:\xampp\php\php.exe" bin\check-mail.php --to=email-cua-ban@example.com
```

- `[OK] Mail settings ...` : đã điền đủ cấu hình; **chưa chứng minh kết nối Gmail hoạt động**.
- `[OK] SMTP server accepted ...` : SMTP đã tiếp nhận email thử; kiểm tra Inbox/Spam, có thể còn chậm trễ phía nhà cung cấp.
- `[FAIL] MAIL_PASSWORD is empty` : chưa cấu hình App Password.
- `[FAIL] SMTP authentication failed` : sai/thu hồi App Password hoặc Google không cho phép xác thực.
- `[FAIL] Cannot connect to SMTP` : sai địa chỉ máy chủ/cổng, lỗi firewall, mạng hoặc TLS.
- `[FAIL] TLS handshake failed` : kiểm tra OpenSSL, ngày giờ máy và CA certificates.
- `[FAIL] APP_KEY not initialized` : cần cấu hình ứng dụng trước khi thử gửi.

## 4. Test không dùng Gmail

Trong `config.local.php` trên **máy phát triển local**:

```php
'APP_ENV' => 'local',
'MAIL_TRANSPORT' => 'log',
```

Chạy luồng Quên mật khẩu với email **của một tài khoản WealthFlow đang ACTIVE**. Email OTP được ghi vào `backend/storage/mail-*.eml` trên **máy chủ**, không gửi Gmail. Mở file `.eml` bằng trình xem email văn bản, lấy mã để nhập trong giao diện; tuyệt đối không tải file này lên web hoặc commit Git. Khi chuyển sang gửi thư thật, đổi lại `MAIL_TRANSPORT` thành `smtp`. Không được dùng `log` ở môi trường production.

## 5. Kiểm tra giao diện, API và database

1. Mở website bằng HTTP URL của Apache/PHP, **không mở `index.html` bằng `file://`**.
2. Đăng ký tài khoản và chắc chắn tài khoản có trạng thái ACTIVE. Địa chỉ mới đăng ký phải là địa chỉ nhận thư bạn có quyền truy cập.
3. Bấm **Quên mật khẩu**, nhập email này và **Gửi mã OTP**.
4. Nếu báo dịch vụ OTP chưa sẵn sàng, chạy `bin/check-mail.php` ở trên.
5. Nếu yêu cầu được chấp nhận nhưng Gmail chưa có thư, xem Spam/Junk, thử gửi lại sau 60 giây, chạy công cụ `--to=...` để tách lỗi gửi email khỏi lỗi lưu OTP.
6. Nhập mã 6 chữ số trong 10 phút, chọn mật khẩu mới hợp lệ (8 ký tự trở lên, chữ hoa/thường/số); khi lưu thành công, đăng nhập lại.
7. Nếu báo OTP sai dù email mới nhận, kiểm tra **cơ sở dữ liệu đã chạy migration `003_password_reset.sql` và `004_password_reset_otp.sql`**, rồi đối chiếu APP_KEY giữa các lần gửi và xác minh. Tuyệt đối không dùng mã đã hết hạn hoặc đã bị gửi mã mới thay thế.

Lỗi phía backend nên tìm trong Apache `error.log` hoặc stderr của PHP server (dòng `WealthFlow OTP delivery failed` có mã nhóm lỗi nhưng không chứa OTP). Với Gmail thật, trạng thái frontend **không thể chứng nhận đã nhận thư** nếu SMTP gặp lỗi sau bước kiểm tra cấu hình; lệnh test SMTP là cách phân biệt rõ nhất.

## 6. Khi cập nhật bản sửa

- Sao lưu code và database trước khi thay file.
- Chỉ chép đè file đã sửa từ ZIP; **không ghi đè `backend/config.local.php`** hoặc thư mục `backend/storage` của bản đang chạy.
- Không chạy lại `setup-run-version-windows.ps1` chỉ để cập nhật OTP, vì script cài mới có thể reset database hiện tại.
- Không đưa `config.local.php` hoặc email `.eml` vào repo.

## 7. Test tự động

Sau khi cài `composer install` trong backend:

```cmd
"F:\xampp\php\php.exe" tests\mail_smoke_test.php
```

Bộ test xác nhận cấu hình thiếu mật khẩu bị từ chối, chế độ log xuất mail và môi trường production không cho phép dùng log. Bộ test không kiểm tra kết nối SMTP Gmail thật.
