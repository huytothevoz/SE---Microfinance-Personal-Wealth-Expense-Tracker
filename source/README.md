# WealthFlow — Construction UI Prototype v0.1

Demo giao diện bám theo tài liệu nhóm VOZ trong `SE(1).zip` (`docs/02_elaboration/SRS.md` là baseline; coding convention trong `docs/03_construction/WealthFlow_Coding_Convention.md`).

**Công nghệ:** HTML5, CSS3, JavaScript ES Modules (ES2022), PHP 8.2+, MySQL 8.0+/InnoDB; không phụ thuộc CDN hay npm cho giao diện. Dùng charset UTF-8 và font fallback `Inter`, `Segoe UI`, `Roboto`, `Noto Sans`, Arial để hỗ trợ tiếng Việt và sẵn sàng cho bản dịch tiếng Anh. Ngôn ngữ UI hiện là tiếng Việt; **chưa có bản dịch tiếng Anh**.

## Cấu trúc

```text
WealthFlow_Demo/
├── index.html                 # Landing page + dialog login/register demo
├── app.html                   # SPA shell: các màn hình người dùng & admin
├── frontend/
│   ├── css/styles.css         # Navy/white responsive, mobile/tablet/desktop
│   ├── js/landing.js          # Điều hướng form login demo
│   ├── js/app.js              # View, biểu đồ SVG/CSS, form UI, tương tác demo
│   ├── js/demo-data.js        # Dữ liệu giả; không phải dữ liệu MySQL
│   └── assets/favicon.svg
├── backend/
│   ├── public/index.php       # Read-only API health/features
│   ├── src/Http/JsonResponse.php
│   └── src/Modules/          # Auth, Wallets, Transactions, Budgets, Recurring,
│                            # Loans, Wealth, Goals, Reports, Admin (chưa code)
├── database/
│   └── 001_initial_schema.sql # Schema DRAFT, không bắt buộc import để chạy UI
├── router.php                 # Router PHP *chỉ cho môi trường dev*
└── README.md
```

## Chạy demo — Windows (CMD hoặc PowerShell)

1. Giải nén file ZIP ra thư mục tùy ý, mở terminal **tại thư mục chứa `index.html`**.
2. Nếu `php` có trong PATH:

```cmd
php -S localhost:8000 router.php
```

Nếu dùng XAMPP cài ở `F:\xampp`, ví dụ:

```cmd
"F:\xampp\php\php.exe" -S localhost:8000 router.php
```

Trên **CMD không thêm dấu `&` trước đường dẫn**. Trên PowerShell có thể chạy `& "F:\xampp\php\php.exe" -S localhost:8000 router.php`.

3. Mở `http://localhost:8000/` để xem landing, `http://localhost:8000/app.html` để xem dashboard. Chọn đăng nhập trong landing sẽ mở **form demo**, nhấn tiếp tục để tới `app.html` mà không gửi mật khẩu.
4. Mở `http://localhost:8000/api/health` để xem trạng thái PHP API (nếu muốn). Database và Apache/MySQL/XAMPP không cần khởi động để xem UI.
5. Có thể dùng Python static server `python -m http.server 8000` cho **frontend**, nhưng `/api/health` sẽ không hoạt động.

**Khi dùng XAMPP/Apache:** nếu chép thư mục vào `htdocs`, có thể xem `http://localhost/WealthFlow_Demo/index.html` và `app.html`. Tuy nhiên, đây chỉ là bản demo local; không được upload nguyên cây thư mục lên hosting công khai: `backend/src`, `database` và tài liệu cấu hình phải được đặt ngoài public document root trong triển khai thực tế.

## Những gì đang demo

- Landing page gồm lời giới thiệu, giá trị sản phẩm, 6 cụm tính năng, quy trình 3 bước, dialog đăng nhập/đăng ký minh họa.
- App có **13 màn hình người dùng**: Tổng quan; Giao dịch; Ví; Danh mục; Ngân sách; Định kỳ; Khoản vay; Vàng; Mục tiêu; Báo cáo; Đối soát & CSV; Thông báo; Cài đặt.
- App có **5 màn hình quản trị**: Thống kê vĩ mô; Danh sách người dùng; Danh mục mặc định; Broadcast; Cấu hình/Audit. Nhấn “Xem giao diện Admin” trên sidebar để chuyển view **chỉ cho trình diễn, không phải phân quyền**.
- Dashboard có chart Net Worth, cash flow, danh mục tài sản, mức sử dụng ngân sách, mục tiêu, giao dịch mẫu.
- Bộ lọc giao dịch, tìm giao dịch, chuyển trang, modal form nghiệp vụ; xuất CSV **dữ liệu mẫu** (UTF-8 BOM, có xử lý đầu chuỗi dạng công thức trong trường văn bản).
- Nút biểu mẫu chỉ mở preview và thông báo, **không lưu** dữ liệu hoặc phát sinh giao dịch tài chính.

## Truy vết SRS

| Yêu cầu | Màn hình prototype | Mức hoàn thiện |
|---|---|---|
| FR01, FR15 / UC01 | Landing login/register, Cài đặt & bảo mật | Form demo, **chưa xác thực** |
| FR02, FR04 / UC02 | Ví & tài khoản, tỷ giá quy đổi mẫu | UI |
| FR03 / UC03 | Giao dịch, Chi tiết, Chuyển nội bộ, Danh mục | UI + lọc dữ liệu mẫu |
| FR05 / UC05 | Ngân sách, cảnh báo 80%/100% | UI |
| FR06 / UC04 | Lịch định kỳ, Draft/Auto | UI |
| FR07 / UC09 | Mục tiêu & phân bổ tích lũy | UI |
| FR08 / UC08,UC10 | Vàng, tài sản ròng | UI |
| FR09 / UC06,UC07 | Vay/cho vay, trả gốc vs lãi | UI |
| FR10,FR11 / UC10 | Dashboard, Net Worth, Cash Flow, Saving Rate | UI + đồ thị dữ liệu mẫu |
| FR12 / UC11 | Đối soát / xuất CSV mẫu | UI + download mẫu, **chưa đối soát thật** |
| FR13 / UC13 | Admin danh mục, tham số, broadcast | UI |
| FR14 / UC12 | Admin tổng quan vĩ mô, người dùng, khóa/mở | UI, **không có quyền thực** |
| SYS01–03 | Tỷ giá, định kỳ, nhắc nợ, snapshot | Chỉ nêu trong UI; chưa có scheduled job |

## Những gì CHƯA có

Không có registration/login server, JWT/refresh, RBAC, PDO kết nối DB, sổ cái kép, idempotency, kiểm tra ownership, chuyển tiền thật, đối soát thật, các tác vụ nền hoặc các phép tính tài chính lấy từ ledger. Tuyệt đối không dùng số liệu demo để quyết định tài chính. **Không có thao tác ngân hàng hay giải ngân thật**, đúng với phạm vi Out-of-Scope trong SRS.

**Construction tiếp theo gợi ý:** (1) chốt migrations MySQL + kiểm thử; (2) Auth/JWT/PDO; (3) Wallet + Domain Money BCMath + Ledger/PostExpense atomically; (4) budgets, recurring, loan, wealth, goals; (5) reports, admin; (6) integration tests, security review và deployment. Bảo đảm source of truth chỉ ở backend.

## Bản source đã được format

Phiên bản này chỉ cải thiện **readability** của HTML/CSS/JavaScript và giữ nguyên nghiệp vụ demo.
Xem [CODING_STYLE.md](CODING_STYLE.md) để biết quy ước thụt lề,
template literals và cấu hình Prettier cho nhóm.
