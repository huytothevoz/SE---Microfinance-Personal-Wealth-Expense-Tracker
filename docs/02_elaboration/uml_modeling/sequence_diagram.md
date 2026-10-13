# Sơ đồ Tuần tự (Sequence Diagrams Hub) - WealthFlow

> Tham chiếu: [SRS.md](../SRS.md) v2.1 | [usecase_diagram.md](./usecase_diagram.md) | [class_diagram.md](./class_diagram.md)

---

## Danh mục 08 Sơ đồ Tuần tự Trọng yếu (Sequence Catalog)

Để tối ưu hóa trải nghiệm xem và kết xuất sơ đồ (tránh hiện tượng quá tải bộ nhớ khi nạp đồng thời 8 sơ đồ lớn trong cùng một trang), toàn bộ 08 kịch bản tuần tự đã được tổ chức phân vị độc lập trong thư mục [sequence_diagram/](./sequence_diagram/):

| Mã Sơ đồ | Tên Kịch bản Tuần tự | Use Cases | File Chi tiết | Các Đối tượng Lifeline Chính |
| :---: | :--- | :---: | :---: | :--- |
| **SQ01** | Đăng ký, Mã hóa Argon2id & Đăng nhập cấp JWT | `UC01`, `UC02` | [sq01_auth_jwt.md](./sequence_diagram/sq01_auth_jwt.md) | `User`, `AuthController`, `AuthService`, `UserRepository`, `Database` |
| **SQ02** | Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm tra Ngân sách *(Lõi)* | `UC03`, `UC05` | [sq02_expense_budget.md](./sequence_diagram/sq02_expense_budget.md) | `User`, `TransactionController`, `LedgerService`, `BudgetService`, `LedgerRepository`, `Database` |
| **SQ03** | Chuyển tiền Nội bộ & Đảo Giao dịch (Reversal) | `UC03` | [sq03_transfer_reversal.md](./sequence_diagram/sq03_transfer_reversal.md) | `User`, `TransactionController`, `LedgerService`, `LedgerRepository`, `Database` |
| **SQ04** | Xử lý Giao dịch Định kỳ Tự động (Subscription Cron) | `UC04`, `SYS02` | [sq04_subscription_cron.md](./sequence_diagram/sq04_subscription_cron.md) | `Scheduler`, `SubscriptionWorker`, `LedgerService`, `NotificationService`, `Database` |
| **SQ05** | Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô *(Microfinance)* | `UC06`, `UC07` | [sq05_microfinance_loan.md](./sequence_diagram/sq05_microfinance_loan.md) | `Borrower`, `LoanController`, `LoanService`, `LedgerService`, `LoanRepository`, `Database` |
| **SQ06** | Quản lý Vàng Tích lũy & Tổng hợp Net Worth Dashboard | `UC08`, `UC10` | [sq06_gold_networth.md](./sequence_diagram/sq06_gold_networth.md) | `User`, `DashboardController`, `AnalyticsService`, `GoldService`, `Database` |
| **SQ07** | Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn | `UC11` | [sq07_reconciliation_csv.md](./sequence_diagram/sq07_reconciliation_csv.md) | `User`, `ReportController`, `ReconciliationService`, `ExportService`, `Database` |
| **SQ08** | Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo | `UC12`, `UC13` | [sq08_admin_user_broadcast.md](./sequence_diagram/sq08_admin_user_broadcast.md) | `Admin`, `AdminController`, `AdminService`, `NotificationService`, `Database` |

---

## 3. Tóm lược Nội dung Kiến trúc của Từng Kịch bản

### 3.1 [SQ01: Đăng ký tài khoản, Mã hóa mật khẩu & Đăng nhập JWT](./sequence_diagram/sq01_auth_jwt.md)
- **Use Case:** `UC01` (Đăng ký & Đăng nhập), `UC02` (Khởi tạo tài khoản ví mặc định).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR02` (Số dư ban đầu đối ứng vốn Equity), `BR11` (Bảo mật phiên).
- **Điểm nhấn thiết kế:**
  - Băm mật khẩu bằng giải thuật kháng phần cứng `Argon2id` (kèm muối ngẫu nhiên cryptographically secure).
  - Tự động sao chép danh mục thu/chi hệ thống (`categories`) cho người dùng mới trong cùng Transaction khởi tạo.
  - Cấp phát cặp JWT Tokens: Access Token (15 phút, stateless) và Refresh Token (7 ngày, lưu mã băm SHA-256 trong Database để thu hồi khi cần).

### 3.2 [SQ02: Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm tra Ngân sách](./sequence_diagram/sq02_expense_budget.md)
- **Use Case:** `UC03` (Quản lý giao dịch thu chi), `UC05` (Thiết lập & Cảnh báo ngân sách).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép: Nợ Chi phí / Có Tài sản), `BR06` (Chặn số dư âm ví tiền mặt), `BR08` (Cảnh báo ngưỡng 80% / 100% ngân sách).
- **Điểm nhấn thiết kế:**
  - Khóa bi quan `SELECT ... FOR UPDATE` trên ví nguồn để ngăn chặn Race Condition khi ghi nhận chi tiêu đồng thời.
  - Hạch toán nguyên tử `INSERT INTO transactions` và 2 bút toán `ledger_entries` (Debit Expense / Credit Asset) bảo đảm phương trình kế toán cân bằng tuyệt đối.
  - So sánh chi tiêu lũy kế trong tháng với hạn mức ngân sách; nếu vượt ngưỡng 80% hoặc 100%, tự động sinh thông báo mà không làm gián đoạn việc ghi nhận chi tiêu.

### 3.3 [SQ03: Chuyển tiền Nội bộ & Đảo Giao dịch Reversal](./sequence_diagram/sq03_transfer_reversal.md)
- **Use Case:** `UC03` (Chuyển khoản nội bộ & Chỉnh sửa giao dịch).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR05` (Bất biến sổ cái - Immutability: Không sử dụng `DELETE` hay `UPDATE` trên bút toán tài chính), `BR06` (Kiểm tra đủ số dư chuyển).
- **Điểm nhấn thiết kế:**
  - **Sắp xếp thứ tự ID tài khoản khi Lock** (`minId`, `maxId`) nhằm triệt tiêu hoàn toàn khả năng xảy ra Kẹt tài nguyên (Deadlock) giữa hai giao dịch chuyển khoản chéo nhau cùng thời điểm.
  - Khi người dùng hủy hoặc đảo giao dịch, hệ thống tạo một giao dịch mới mang loại `TRANSFER_REVERSAL` và cặp bút toán ngược chiều (Credit ví đích / Debit ví nguồn), giữ nguyên toàn bộ lịch sử kiểm toán kế toán.

### 3.4 [SQ04: Xử lý Giao dịch Định kỳ Tự động (Subscription Cron)](./sequence_diagram/sq04_subscription_cron.md)
- **Use Case:** `UC04` (Quản lý chi phí cố định định kỳ), `SYS02` (Tác vụ định kỳ hệ thống).
- **Quy tắc nghiệp vụ:** `BR07` (Tự động hạch toán đúng chu kỳ), `BR09` (Nhắc nhở hóa đơn trước 3 ngày), `BR13` (Ghi log tác vụ nền).
- **Điểm nhấn thiết kế:**
  - Cron Job kích hoạt hàng ngày lúc 00:00:00 UTC; xử lý tuần tự từng lượt đăng ký theo khối giao dịch độc lập.
  - Tính toán ngày thực thi tiếp theo (`next_run_date`) chính xác dựa trên chu kỳ `MONTHLY`, `WEEKLY`, `YEARLY` có xử lý ngày cuối tháng (ví dụ ngày 31 tháng 1 chuyển sang ngày 28/29 tháng 2).
  - Tự động gửi thông báo nhắc nhở khi sắp đến hạn thanh toán trong vòng 3 ngày.

### 3.5 [SQ05: Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô](./sequence_diagram/sq05_microfinance_loan.md)
- **Use Case:** `UC06` (Quản lý khoản vay mượn), `UC07` (Ghi nhận thanh toán trả nợ vi mô).
- **Quy tắc nghiệp vụ:** `BR09` (Tính lãi phẳng / giảm dần), `BR10` (Hạch toán trả gốc và trả lãi riêng biệt), `BR17` (Khóa tài khoản nợ khi hoàn tất).
- **Điểm nhấn thiết kế:**
  - Khởi tạo lịch trả nợ vi mô theo từng kỳ (Amortization Schedule) với ngày đáo hạn và số tiền dự kiến.
  - Khi trả nợ, hệ thống chia tách số tiền thanh toán thành 2 phần: Trả nợ gốc (giảm dư nợ phải trả) và Chi phí lãi vay (ghi nhận chi phí tài chính).
  - Tự động chuyển trạng thái khoản vay sang `SETTLED` khi số dư nợ còn lại chạm mức 0.

### 3.6 [SQ06: Quản lý Vàng Tích lũy & Tổng hợp Net Worth Dashboard](./sequence_diagram/sq06_gold_networth.md)
- **Use Case:** `UC08` (Quản lý danh mục vàng tích lũy), `UC10` (Dashboard trực quan hóa tài sản ròng Net Worth).
- **Quy tắc nghiệp vụ:** `BR02` (Định giá tài sản), `BR03` (Chuyển đổi đa tiền tệ), `BR04` (Cập nhật giá vàng thị trường), `BR16` (Phân tích chỉ số thanh khoản).
- **Điểm nhấn thiết kế:**
  - Hỗ trợ lưu trữ số lượng vàng tính theo đơn vị Chỉ / Lượng và loại vàng (SJC, PNJ, 9999).
  - Tự động lấy tỷ giá vàng cập nhật nhất để quy đổi giá trị tài sản vàng sang tiền mặt cơ sở.
  - Tính toán chỉ số Tài sản ròng theo công thức kế toán: $\text{Net Worth} = \sum \text{Tài sản} - \sum \text{Nợ phải trả}$.
  - Tính toán Hệ số an toàn thanh khoản (Liquidity Ratio) và sinh khuyến nghị sức khỏe tài chính tương ứng.

### 3.7 [SQ07: Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn](./sequence_diagram/sq07_reconciliation_csv.md)
- **Use Case:** `UC11` (Báo cáo đối soát sổ cái & Xuất tệp dữ liệu).
- **Quy tắc nghiệp vụ:** `BR01` (Kiểm tra cân bằng Nợ/Có sổ cái), `BR12` (Chống Formula Injection khi xuất CSV), `BR14` (Kiểm toán bảo mật dữ liệu).
- **Điểm nhấn thiết kế:**
  - Kiểm tra toàn vẹn toán học: Tổng số dư ví thực tế phải bằng tổng đại số các bút toán trong sổ cái (`Wallet Balance == SUM(Credits - Debits)`). Nếu có sai lệch, cảnh báo người dùng kiểm tra lại.
  - Khử độc dữ liệu CSV (CSV Sanitization): Tự động phát hiện và thêm dấu nháy đơn `'` trước các ký tự nguy hiểm (`=`, `+`, `-`, `@`) để ngăn chặn tấn công thực thi mã từ xa trong Microsoft Excel.
  - Bổ sung ký tự BOM UTF-8 (`\uFEFF`) ở đầu file để hiển thị tiếng Việt có dấu chuẩn xác trong Excel.

### 3.8 [SQ08: Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo](./sequence_diagram/sq08_admin_user_broadcast.md)
- **Use Case:** `UC12` (Quản trị người dùng & Thống kê vĩ mô), `UC13` (Cấu hình hệ thống & Phát thông báo toàn hệ thống).
- **Quy tắc nghiệp vụ:** `BR11` (Bảo mật quyền riêng tư: Admin không được xem số dư chi tiết cá nhân), `BR13` (Kiểm toán quản trị), `BR14` (Không thể tự khóa tài khoản Admin duy nhất còn lại).
- **Điểm nhấn thiết kế:**
  - Kiểm tra an toàn trước khi khóa tài khoản: Bắt buộc nhập lý do khóa và kiểm tra còn ít nhất 1 tài khoản Admin đang hoạt động khác.
  - Thu hồi tức thời toàn bộ Refresh Token của tài khoản bị khóa và ghi nhật ký kiểm toán hành chính (`admin_audit_logs`).
  - Kỹ thuật Broadcast Fan-out bằng lệnh SQL hàng loạt tối ưu để gửi thông báo nhanh chóng đến toàn bộ người dùng Active.

---

## 4. Ma trận Truy vết Tuần tự & Use Cases (Sequence Traceability Matrix)

| Mã Sơ đồ | Tên Kịch bản Tuần tự | Use Cases | Quy tắc Nghiệp vụ (BR) | Các Lớp BCE Tham gia | Đường dẫn Chi tiết |
| :---: | :--- | :---: | :---: | :--- | :---: |
| **SQ01** | Đăng ký & Đăng nhập JWT | `UC01`, `UC02` | BR01, BR02, BR11, BR15 | `AuthController`, `AuthService`, `UserRepository` | [Xem SQ01](./sequence_diagram/sq01_auth_jwt.md) |
| **SQ02** | Ghi nhận Chi tiêu & Ngân sách | `UC03`, `UC05` | BR01, BR06, BR07, BR08, BR13 | `TransactionController`, `LedgerService`, `BudgetService` | [Xem SQ02](./sequence_diagram/sq02_expense_budget.md) |
| **SQ03** | Chuyển tiền & Đảo Giao dịch | `UC03` | BR01, BR05, BR06, BR13 | `TransactionController`, `LedgerService`, `LedgerRepository` | [Xem SQ03](./sequence_diagram/sq03_transfer_reversal.md) |
| **SQ04** | Giao dịch Định kỳ Subscription | `UC04`, `SYS02` | BR07, BR09, BR13 | `SubscriptionWorker`, `LedgerService`, `NotificationService` | [Xem SQ04](./sequence_diagram/sq04_subscription_cron.md) |
| **SQ05** | Vay mượn & Trả nợ Vi mô | `UC06`, `UC07` | BR09, BR10, BR17 | `LoanController`, `LoanService`, `LedgerService` | [Xem SQ05](./sequence_diagram/sq05_microfinance_loan.md) |
| **SQ06** | Quản lý Vàng & Net Worth | `UC08`, `UC10` | BR02, BR03, BR04, BR16, BR18 | `DashboardController`, `AnalyticsService`, `GoldService` | [Xem SQ06](./sequence_diagram/sq06_gold_networth.md) |
| **SQ07** | Đối soát Sổ cái & Xuất CSV | `UC11` | BR01, BR12, BR14, BR18 | `ReportController`, `ReconciliationService`, `ExportService` | [Xem SQ07](./sequence_diagram/sq07_reconciliation_csv.md) |
| **SQ08** | Quản trị & Phát Thông báo | `UC12`, `UC13` | BR11, BR13, BR14 | `AdminController`, `AdminService`, `NotificationService` | [Xem SQ08](./sequence_diagram/sq08_admin_user_broadcast.md) |

---

## 5. Kết luận và Bước kế tiếp trong Tiến trình Unified Process

Việc phân rã tài liệu sơ đồ tuần tự thành 08 tập tin chuyên biệt trong thư mục [sequence_diagram/](./sequence_diagram/) đem lại các lợi ích kỹ thuật vượt trội:
1. **Khả năng kết xuất (Renderability):** Mỗi tập tin chỉ chứa duy nhất một sơ đồ Mermaid và một mã PlantUML, bảo đảm VS Code, GitHub Preview và các công cụ CI/CD kết xuất đồ họa mượt mà dưới 1 giây, hoàn toàn loại bỏ hiện tượng tràn RAM hoặc đơ trình duyệt.
2. **Tính mô-đun và Quản lý phiên bản (Modularity & Git Diff):** Dễ dàng theo dõi lịch sử chỉnh sửa từng Use Case độc lập mà không gây xung đột Git (Merge Conflict) trong nhóm phát triển.
3. **Chuẩn bị hoàn hảo cho Pha Thiết kế (Design Phase):** Làm cơ sở vững chắc để xây dựng Design Class Diagram và Database ERD Schema.

### Các tài liệu tiếp theo cần thực hiện:
- **Design Class Diagram (`class_diagram.md`):** Mô hình hóa danh sách các Lớp thiết kế chi tiết (Attributes, Operations, Types, Access Modifiers, Relationships).
- **Entity-Relationship Diagram & Database Schema (`erd_database_design.md`):** Mô hình hóa cơ sở dữ liệu quan hệ PostgreSQL chuẩn hóa 3NF, các khóa chính, khóa ngoại, ràng buộc CHECK và Indexes.
