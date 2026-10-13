# Backend PHP – khung dự án

- PHP >= 8.2.
- `public/index.php` hiện chỉ hỗ trợ GET `health` và `features` cho demo.
- `src/Http/JsonResponse.php` chuẩn hóa JSON response theo SRS `success`, `data`, `error`, `requestId`.
- `src/Modules/` chứa tên phân hệ theo coding convention; **chưa có Service, Repository hay API nghiệp vụ**.
- Dữ liệu minh họa nằm trong JS phía trình duyệt, **không** đi qua backend.

Đường dẫn kiểm tra khi chạy server PHP từ gốc theo `router.php`:

- `http://localhost:8000/api/health`
- `http://localhost:8000/api/features`

Không chấp nhận POST để tránh tưởng đã ghi dữ liệu thật. Tuyệt đối không triển khai production với router thử nghiệm. Khi chuyển sang bản chính thức hãy tạo public document root riêng, HTTPS, JWT, RBAC, PDO + BCMath, migrations, ledger transactions và automated tests.
