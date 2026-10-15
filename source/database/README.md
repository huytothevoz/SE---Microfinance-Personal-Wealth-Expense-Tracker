# Database WealthFlow

`001_initial_schema.sql` là schema nền cho các phân hệ; phần tài chính vẫn là bản phác thảo, chưa triển khai ledger API.

`002_auth.sql` bổ sung FR01: `auth_sessions`, `auth_rate_limits`, `security_audit_logs`, `registration_emails` và danh mục thu/chi mặc định. Tương thích MySQL 8 và MariaDB 10.4+ của XAMPP.

Khởi tạo/cập nhật giữ dữ liệu: sau khi cài dependencies và cấu hình backend, chạy `php source/backend/bin/setup.php` từ gốc repo. Installer tự tạo database nếu chưa có và ghi `schema_migrations`; chạy lại không tạo trùng hay xóa dữ liệu.

**Cài mới qua hai script XAMPP:** toàn bộ htdocs được sao lưu ngoài web; database WealthFlow được dump trước khi DROP/recreate. Các database khác không bị xóa. Chi tiết và phục hồi trong [FR01_XAMPP.md](../../FR01_XAMPP.md).

Registration tạo user ACTIVE, sao chép danh mục và tạo hàng đợi email trong cùng transaction. Không cần xác minh email để đăng nhập theo UC01 hiện tại. Email là thư chào mừng, không phải OTP/link kích hoạt.

API tài chính, ownership ví/giao dịch, bất biến ledger, idempotency và các quy tắc kế toán vẫn phải được triển khai trước khi dùng dữ liệu tài chính thật.
