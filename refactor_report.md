# Báo Cáo Refactor Hệ Thống WealthFlow

## 1. Nội dung Thay Đổi Chính

### 1.1. Tái Cấu Trúc Backend (2-Tier Modular Monolith)
- **Gom toàn bộ hạ tầng kỹ thuật về `src/Core/`** (`WealthFlow\Core`):
  - Xóa các thư mục phân mảnh cũ: `src/Config/`, `src/Http/`, `src/Auth/`, `src/Mail/`.
  - Hợp nhất thành 10 lớp lõi: `Config`, `Database`, `Router`, `Request`, `JsonResponse`, `ApiException`, `AuthContext`, `TokenService`, `EmailService`, `WelcomeEmailQueue`.
- **Chuẩn hóa Module nghiệp vụ theo mô hình BCE+D** (`src/Modules/<Domain>/`):
  - **Boundary:** `<Name>Controller.php` (nhận Request, validate sơ bộ, trả JsonResponse).
  - **Control:** `<Name>Service.php` (chứa toàn bộ nghiệp vụ, transaction, double-entry, BCMath).
  - **Data Access:** Đổi toàn bộ `*Repository.php` sang `*DAO.php` (`UserDAO`, `WalletDAO`, `LedgerDAO`, `CategoryDAO`, `BudgetDAO`, `RecurringRuleDAO`, `NotificationDAO`).
  - **Entity:** Loại bỏ các lớp POPO rườm rà; dùng 28 bảng SQL làm Single Source of Truth kết hợp Typed Array DTO.

```
source/backend/src/
├── Core/                      # Hạ tầng kỹ thuật chung (10 classes)
└── Modules/                   # Các phân hệ nghiệp vụ độc lập (BCE+D)
    ├── Auth/                  # AuthController, AuthService, UserDAO
    ├── Wallets/               # WalletController, WalletService, WalletDAO
    ├── Transactions/          # TransactionController, TransactionService, LedgerDAO
    ├── Budgets/               # BudgetController, BudgetService, BudgetDAO, CategoryDAO
    ├── Recurring/             # RecurringController, RecurringService, RecurringRuleDAO
    ├── Notifications/         # NotificationController, NotificationService, NotificationDAO
    └── [Loans, Wealth, Goals, Reports, Admin] # Khung sẵn sàng cho giai đoạn kế
```

---

### 1.2. Hợp Nhất Script Điều Khiển (TUI Control Center)
- **Xóa bỏ các file rác / gõ nhầm:** `CHAY_SE.bat`, `DUNG_SE.bat`, `setup-se.ps1`, `stop-se.ps1`, `run_weathflow.bat`, `run_weathflow.sh`.
- **Gom về đúng 2 entrypoints duy nhất:**
  - **Windows:** `run_wealthflow.bat` (gọi `scripts/wealthflow-manager.ps1`)
  - **Linux / macOS:** `run_wealthflow.sh`
- **Tính năng Runner:**
  - Menu tương tác trực quan (Start, Stop, Status, Migrate DB, Test, View Logs, Reset).
  - Tự động nhận diện đường dẫn XAMPP (`D:\xampp`, `C:\xampp`, `$env:XAMPP_DIR`).
  - Chạy cổng độc lập (Web `8080`, MySQL phụ `3307`), không đụng độ XAMPP mặc định (`80`, `3306`).

---

### 1.3. Cơ Sở Dữ Liệu & Seed Dữ Liệu Mẫu
- Chuẩn hóa 28 migration SQL (`source/database/001_` $\rightarrow$ `028_`).
- Cập nhật tài khoản mẫu trong `001_users.sql`:
  - **User thường:** `user1@gmail.com` / `User1234`
  - **Admin chính:** `admin@wealthflow.vn` / `Admin@1234`
  - **Admin phụ:** `admin@wealthflow.local` / `User1234`

---

## 2. Kết Quả Kiểm Thử & Đánh Giá Chất Lượng

| Hạng mục kiểm tra | Công cụ / Kịch bản | Kết quả | Ghi chú |
| :--- | :--- | :---: | :--- |
| **Cú pháp PHP** | `php -l` quét toàn bộ 51 file PHP | **51/51 PASS** | Không có lỗi cú pháp |
| **Unit / Integration Tests** | `source/backend/tests/run_tests.php` | **36/36 PASS (100%)** | Pass toàn bộ Auth, Ví, Sổ cái, Ngân sách, Định kỳ |
| **Email Queue Smoke Test** | `source/backend/tests/mail_smoke_test.php` | **7/7 PASS (100%)** | Kiểm tra hàng đợi và gửi mail chào mừng |
| **Kiến trúc & Quy chuẩn** | Đối soát cấu trúc BCE+D & PSR-12 | **ĐẠT** | Tách bạch Core và Modules, không còn class mồ côi |

---

## 3. Các Bước Tiếp Theo (Kế hoạch Hoàn thiện Construction)

1. **Backend:** Viết logic xử lý cho 5 phân hệ còn lại:
   - `Loans` (FR07): Lịch phân kỳ nợ, tất toán, tách gốc/lãi.
   - `Wealth/Gold` (FR08): Mua bán vàng nhẫn/SJC, tính giá vốn WAC.
   - `Goals` (FR09): Hũ tiết kiệm, cơ chế phong tỏa số dư ví (earmarking).
   - `Reports` (FR10): Đối soát $\sum(\text{Debit} - \text{Credit})$, xuất CSV an toàn chống Formula Injection.
   - `Admin` (FR11, FR12): Khóa user (chặn khóa Admin cuối), broadcast thông báo batch fan-out.
2. **Frontend:** Chuyển 10 màn hình từ mock `fixtures.js` sang gọi REST API thật qua `http-client.js`.
3. **Mở rộng Test:** Bổ sung test case cho 5 module mới lên `run_tests.php`.