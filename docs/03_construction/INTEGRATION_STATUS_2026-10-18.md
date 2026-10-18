# Construction — đối chiếu source refactor & yêu cầu (18/10/2026)

## Nguồn và nguyên tắc

- Nguồn A: `cai nay tu nhanh refractor.zip` do nhóm cung cấp (thiếu `.git`); nguồn B: `SE.zip` và bản sửa cục bộ trước đó.
- Cấu trúc Backend `Core + Modules / Controller–Service–DAO` và 28 file SQL của refactor được giữ làm nền. Không copy các class `Repository` cũ lên gây trùng lớp, không chuyển tiếp các migration `001_initial_schema` của main vào cùng DB.
- Đã giải quyết conflict text với dấu `<<<<<<<`/`=======`/`>>>>>>>` tồn tại trong source ban đầu; bổ sung CSS, assets, `auth-api.js`, `live-data.js`, `live-pages.js`, `ui-core.js` bị thiếu.
- Tài liệu này **không thay đổi yêu cầu chuẩn tắc SRS**, chỉ ghi rõ hiện trạng implementation/gap và các quyết định bảo vệ dữ liệu để nhóm xử lý theo change control.

## Theo dõi mức độ tích hợp

| FR / mục | Thực hiện trong source | Giới hạn & tiêu chí còn phải test |
|---|---|---|
| FR01 / OTP | Khôi phục auth API phía frontend, mật khẩu, reset OTP; runner cho phép chủ động dùng `SE_MAIL_TRANSPORT=smtp`, tạo thư mục lưu `.eml` khi local | Chưa gửi Gmail thật trong môi trường kiểm tra; cần thử inbox, spam, expiry, rate-limit. Không công khai secret. |
| FR02 Ví | API GET/POST/PUT/archive gắn service/DAO refactor; màn tạo ví, đổi tên, lưu trữ lấy dữ liệu thật. Điều kiện số dư=0 và không còn recurring ACTIVE trước archive. Không xóa cứng. | Số dư đầu kỳ dương chỉ VND non-CREDIT; chưa quy đổi ngoại tệ, chưa hoàn tất IDP cho tạo ví. `FR02.02` chưa đủ chức năng tổng quy đổi. |
| FR03 kế toán kép | Controller–Service–DAO refactor nối API giao dịch; form từ live-data dùng loại thu/chi, ví, danh mục, khóa Idempotency-Key. | Chưa chạy E2E MySQL, cần test double-entry, rollback, transfer, reversal, idempotency cùng nhiều request song song. |
| FR04 tỷ giá | Giữ backend ExchangeRateService, không sinh tỷ giá ảo; chặn mở số dư USD/EUR dương khi chưa chốt FX | Cần kiểm chứng API rates, snapshot, WAC, FX gain/loss; tính quy đổi trên Dashboard còn thiếu. |
| FR05 ngân sách | GET/POST budget, các ngưỡng hiển thị từ dữ liệu API; không lấy fixture. | Test liên kết category, tháng, ngưỡng 80/100%, không gửi trùng notification. |
| FR06 định kỳ | GET/POST recurring, form thực gửi `executionMode=DRAFT_REMINDER`, thêm thao tác pause/resume theo trạng thái thực; chưa tự ý auto-post. | Test scheduler thật, ngày neo 29–31, DST, cancel/pause/resume, thử quyền sở hữu. |
| Cài đặt & bảo mật | 4 nhóm Hồ sơ, Bảo mật, Dữ liệu & riêng tư, Thông báo & trải nghiệm, đổi mật khẩu/đăng xuất | Hồ sơ edit/tài khoản delete/tùy chỉnh theme chưa có API xác minh: giao diện chỉ giải thích hoặc vô hiệu, không giả lưu. |
| FR07–FR15 | Giữ các thư mục Modules, docs refactor để kế thừa | Chưa kết luận API đã hoàn chỉnh; không hiển thị số liệu mẫu trong ứng dụng live. |

## Kiểm thử đã thực hiện

- `php -l` quét các file PHP ứng dụng, `node --check` quét JS ứng dụng: không báo lỗi cú pháp.
- `node scripts/contract-smoke-test.mjs`: 10 kiểm tra frontend/API contract offline qua stub data: PASS.
- `php source/backend/tests/mail_smoke_test.php`: 7 kiểm tra định dạng và log OTP (không Gmail thật): PASS.
- Bộ test backend MySQL/SQLite chưa chạy: PHP ở môi trường kiểm thử thiếu `pdo_sqlite`, `pdo_mysql`, `bcmath`, `mbstring`. **Không được suy diễn 36/36 PASS từ báo cáo nhánh refactor ban đầu.**
- Chưa test Browser UI tương tác thật (Playwright/browser), người dùng/DB thật, concurrency, SMTP live. Cần test trên XAMPP của nhóm.

## An toàn dữ liệu khi triển khai

1. Backup MySQL `wealthflow` cũ và toàn bộ source; chạy trên bản sao/nhánh riêng trước, không đè `main`/`htdocs`.
2. 28 migrations refactor không tương đương 7 migrations main. Installer chặn schema cũ chưa có `schema_migrations`; phải viết migration chuyển đổi chuyên biệt (tài khoản, giao dịch, khóa ngoại và ledger) nếu muốn giữ dữ liệu cũ.
3. Runner mặc định web 8080, SQL 3307, không dừng listener không thuộc WealthFlow. Không chọn reset nếu không thực sự muốn hủy dữ liệu; xác nhận và backup SQL trước reset.
4. `config.local.php`, SMTP App Password, mã OTP `.eml`, APP_KEY không được commit/chia sẻ.

## Các bước chốt nghiệm thu thật

- PHP Composer dependencies; chạy 28 migrations trên **database mới trống**, xác minh SQL health, login/signup, người dùng mới không có dữ liệu.
- Tạo 2 ví; thu/chi, chuyển ví, kiểm tra dòng DEBIT/CREDIT và cached_balance, thử các lỗi số dư thiếu, retry và quyền người dùng khác.
- Tạo ngân sách tháng, định kỳ DRAFT_REMINDER và chạy worker; kiểm tra cảnh báo trùng.
- Đổi mật khẩu, OTP smtp Gmail thật, hết hạn mã, giới hạn gửi, thiết bị khác logout.
- Chạy đầy đủ test, so sánh 28 migrations với SRS và API design, chỉ sau đó mới xem xét PR merge vào GitHub `main`.

## Ghi chú SMTP cho CHAY_SE.bat

Cả `CHAY_SE.bat` và `run_wealthflow.bat` đều mặc định `log` (ghi `.eml`) để tránh gửi OTP thật ngoài ý muốn. Muốn gửi Gmail thật phải cấu hình `config.local.php` (MAIL_USERNAME, MAIL_PASSWORD là Gmail App Password, MAIL_FROM_ADDRESS) rồi **trước lúc khởi chạy** đặt `SE_MAIL_TRANSPORT=smtp`, dừng đúng tiến trình WealthFlow cũ trước khi đổi. Không lưu mật khẩu trong source.
