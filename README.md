# WealthFlow — source đã tích hợp theo kiến trúc `refactor`

> **Bản nguồn 18/10/2026:** Tích hợp từ ZIP nhánh `refactor` do nhóm cung cấp, cộng các sửa chữa từ `SE.zip`. Đây là **file-level integration**, không phải Git three-way merge có lịch sử commit, không push lên `main`. Các kiểm thử MySQL thật và gửi Gmail SMTP thật vẫn cần thực hiện tại máy của nhóm.

## Cấu trúc

- `source/backend/src/Core`: Config, Database, Router, AuthContext, SMTP mail.
- `source/backend/src/Modules`: Auth, Wallets, Transactions, Budgets, Recurring và phân hệ còn lại.
- `source/frontend/js/app`, `source/frontend/js/services`, `source/frontend/js/data`: giao diện/module, API adapter, dữ liệu lấy từ tài khoản người dùng.
- `source/frontend/views`, `source/frontend/css`, `source/frontend/assets`: HTML template, CSS và hình ảnh.
- `source/database/001_*.sql` … `028_*.sql`: schema refactor — **không chạy để đè schema cũ**.
- `docs/02_elaboration/SRS.md`, `docs/03_construction/INTEGRATION_STATUS_2026-10-18.md`: phạm vi FR và chênh lệch đã ghi nhận.

## Windows / XAMPP (hai đồ án độc lập)

Cần PHP 8.2+, MySQL/MariaDB, extension `pdo_mysql`, `bcmath`, `mbstring`, `curl`, `openssl`, Composer nếu `source/backend/vendor/` chưa có dependencies.

1. Giải nén vào **thư mục dự án riêng**; KHÔNG chép đè toàn bộ `C:\xampp\htdocs`.
2. Đặt XAMPP ở `C:\xampp` (hoặc `D:\xampp`, `F:\xampp`), nếu khác đặt biến môi trường `XAMPP_DIR`.
3. Chạy `run_wealthflow.bat`, chọn `[1]` để bắt đầu (hoặc `CHAY_SE.bat`). Cả hai dùng chung `setup-se.ps1`, không còn hai luồng khởi tạo SQL khác nhau. Dự án SE sử dụng PHP web **8080**, MySQL riêng **3307**; không chủ động đụng MySQL 3306 của đồ án LTW.
4. Mở `http://localhost:8080/`. Kiểm tra API: `http://localhost:8080/api/health` (phải có `success=true` và `databaseConnected=true`).
5. Nếu backend chưa có dependencies, chạy `composer install` tại `source/backend` rồi thực hiện lại.

**Quan trọng:** Không chạy chức năng `[5] Reset` nếu còn dữ liệu cần giữ; chức năng reset hỏi xác nhận và sao lưu SQL trước khi xóa. Nếu database `wealthflow` cũ chưa có `schema_migrations`, installer sẽ **từ chối tự chuyển đổi** để tránh hỏng dữ liệu. Muốn làm việc với dữ liệu cũ phải sao lưu và lập migration tương thích riêng trước.

### Troubleshooting startup MySQL 3307 (18/10/2026)

Nếu dừng ở `[3/7] Starting the SE-only MySQL instance`: **đây mới là timeout/exit**, không thể suy ra chính xác nguyên nhân khi chưa có log. Runner mới đợi tối đa 45 giây, ngắt sớm nếu `mysqld.exe` thoát, in cuối ba file log, và không reset datadir. Có thể chạy `KIEM_TRA_MYSQL_SE.bat` để xem log và trạng thái cổng.

- Xem: `C:\xampp\se-instance\mysql-error.log`, `mysql-start-*.stderr.log` và `mysql-start-*.stdout.log`.
- **Không xóa** `C:\xampp\se-instance\mysql-data`, `C:\xampp\mysql\data`, không copy qua lại hai datadir, không tắt MySQL cổng 3306 để thử lỗi.
- Nếu log báo lỗi quyền, đường dẫn, InnoDB hoặc bảng hệ thống, hãy gửi 30–40 dòng cuối log trước khi sửa; không đoán hoặc tự reset.
- Hai đồ án vẫn độc lập: SE `PHP :8080 / SQL :3307`; LTW `PHP :8000 / SQL :3306`.

Chi tiết: [`docs/03_construction/FIX_MYSQL_3307_2026-10-18.md`](docs/03_construction/FIX_MYSQL_3307_2026-10-18.md).

## Gửi OTP Gmail thật

Tại `source/backend/config.local.php` trên **máy cá nhân** (không commit file này):

- `MAIL_TRANSPORT = smtp`, `MAIL_HOST = smtp.gmail.com`, `MAIL_PORT = 587`, `MAIL_ENCRYPTION = tls`.
- `MAIL_USERNAME`/`MAIL_FROM_ADDRESS`: Gmail gửi; `MAIL_PASSWORD`: Gmail App Password.

Runner mặc định log `.eml` để tránh gửi mail ngoài ý muốn. Muốn bật SMTP trong **PowerShell**, trước khi chạy menu:

```powershell
$env:SE_MAIL_TRANSPORT = 'smtp'
.\run_wealthflow.bat
```

Email trong chế độ log nằm ở `source/backend/storage/`, không đưa lên Git. Kết quả kiểm tra 7 tình huống mail là **smoke test offline**, không chứng nhận Gmail thực tế.

## Kiểm thử

```powershell
node scripts/contract-smoke-test.mjs
php source/backend/tests/mail_smoke_test.php
php source/backend/tests/run_tests.php
```

Test FR02–FR06 cần PHP `pdo_sqlite` và BCMath. Máy không có `pdo_sqlite` sẽ báo `SKIP` (không phải PASS). Phải chạy thêm kiểm thử E2E trên MySQL 3307 và SMTP thật trên máy nhóm mới có thể chốt nghiệm thu.

## Không đưa dữ liệu giả vào tài khoản mới

Frontend truy xuất API tài khoản đang đăng nhập, không fallback `fixtures.js`. Tài khoản mới không có ví, giao dịch, ngân sách hoặc mục tiêu sẽ thấy trạng thái trống. Các API chưa hỗ trợ phải báo chưa khả dụng thay vì vẽ số dư mẫu.
