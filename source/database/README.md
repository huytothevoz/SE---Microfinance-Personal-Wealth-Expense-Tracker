# Database WealthFlow

`001_initial_schema.sql` chứa schema cho đăng nhập và các phân hệ tài chính FR03–FR06. `002_auth.sql` bổ sung các bảng phục vụ phiên đăng nhập, giới hạn tốc độ, nhật ký bảo mật và email chào mừng.

Các migration `003` trở đi bổ sung luồng khôi phục mật khẩu và thay đổi tương thích schema. Installer áp dụng các file migration dạng số theo thứ tự và ghi nhận từng file trong `schema_migrations`.

Khởi tạo/cập nhật giữ dữ liệu: sau khi cài dependencies và cấu hình backend, chạy `php source/backend/bin/setup.php` từ gốc repo. Installer tự tạo database nếu chưa có và ghi `schema_migrations`; chạy lại không tạo trùng hay xóa dữ liệu.

**Cài mới qua hai script XAMPP:** toàn bộ htdocs được sao lưu ngoài web; database WealthFlow được dump trước khi DROP/recreate. Các database khác không bị xóa. Chi tiết và phục hồi trong [FR01_XAMPP.md](../../FR01_XAMPP.md).

Registration tạo user ACTIVE, sao chép danh mục và tạo hàng đợi email trong cùng transaction. Không cần xác minh email để đăng nhập theo UC01 hiện tại. Email là thư chào mừng, không phải OTP/link kích hoạt.

API tài chính chỉ truy cập dữ liệu của user thuộc phiên đăng nhập; ghi giao dịch dùng ledger kép và idempotency key. Hãy áp dụng toàn bộ migration bằng installer trước khi dùng API.
