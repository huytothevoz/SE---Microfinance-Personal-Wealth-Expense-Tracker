# Khảo Sát & Đặc Tả API Ngân Sách & Cảnh Báo (PLAN-01)

> **Mã công việc:** `PLAN-01` (Sprint 1)  
> **Phụ trách:** Phạm Nguyễn Tuấn Kiệt  
> **Người rà soát:** Võ Trịnh Quốc Huy  
> **Phân hệ liên quan:** `FR05` (Ngân sách chi tiêu), `FR14` (Thông báo), `BR07`, `BR08`, `BR11`, `BR12`  

---

## 1. Mục Đích Tài Liệu & Kết Quả Khảo Sát Hiện Trạng

Tài liệu này ghi nhận kết quả khảo sát chi tiết mã nguồn backend và frontend của phân hệ **Ngân sách chi tiêu (Budgets)** và **Thông báo cảnh báo (Notifications)** trong kho mã nguồn `WealthFlow`, đối chiếu trực tiếp với [SRS.md](./02_elaboration/SRS.md) và [api_design.md](./02_elaboration/api_design.md).

### 1.1. Tóm tắt hiện trạng trong Source Code
| Thành phần | Vị trí File | Trạng thái Khảo sát | Đánh giá & Ghi chú |
| :--- | :--- | :---: | :--- |
| **Migration Ngân sách** | [013_budgets.sql](../source/database/013_budgets.sql) | Đã có | Bảng `budgets` có `uk_budget_month (user_id, category_id, budget_month)`, `CHECK (limit_amount > 0)`, trạng thái `ACTIVE`/`ARCHIVED` và các cột theo dõi ngưỡng cảnh báo. |
| **Migration Thông báo** | [025_notifications.sql](../source/database/025_notifications.sql) | Đã có | Bảng `notifications` có `uk_notification_dedup (user_id, dedup_key)` đảm bảo chống gửi trùng ở cấp độ CSDL. |
| **Data Access (DAO)** | [BudgetDAO.php](../source/backend/src/Modules/Budgets/BudgetDAO.php)<br>[NotificationDAO.php](../source/backend/src/Modules/Notifications/NotificationDAO.php) | Đã có | Truy vấn luôn gắn `user_id` từ phiên đăng nhập (`BR11`); tính chi tiêu ròng từ `financial_transactions` với `transaction_type = 'EXPENSE'` và `status = 'POSTED'`. |
| **Business Logic (Service)** | [BudgetService.php](../source/backend/src/Modules/Budgets/BudgetService.php)<br>[NotificationService.php](../source/backend/src/Modules/Notifications/NotificationService.php) | Đã có | Kiểm tra danh mục thuộc loại `EXPENSE`, tính `%` sử dụng, phân loại `GREEN`/`YELLOW`/`RED` ở ngưỡng `80%` và `100%`, sinh thông báo kèm `dedup_key`. |
| **REST Controller** | [BudgetController.php](../source/backend/src/Modules/Budgets/BudgetController.php)<br>[NotificationController.php](../source/backend/src/Modules/Notifications/NotificationController.php) | Đã có | Đã đăng ký đủ routes trong [public/index.php](../source/backend/public/index.php). |
| **Trigger khi chi tiêu** | [TransactionController.php](../source/backend/src/Modules/Transactions/TransactionController.php)<br>[RecurringWorker.php](../source/backend/src/Modules/Recurring/RecurringWorker.php) | Đã tích hợp | Tự động gọi `BudgetService::checkBudgetAndNotify()` ngay sau khi ghi sổ giao dịch `EXPENSE` thành công. |
| **Frontend Live UI** | [live-data.js](../source/frontend/js/data/live-data.js)<br>[live-pages.js](../source/frontend/js/app/live-pages.js) | Đã nối API | Không sử dụng dữ liệu mẫu (fixtures); hỗ trợ xem tổng quan ngân sách, tạo mới, sửa hạn mức, lưu trữ ngân sách và thao tác đánh dấu đã đọc thông báo. |

---

## 2. Kiến Trúc Phân Hệ Ngân Sách (BCE+D)

Phân hệ Ngân sách tuân thủ kiến trúc **Boundary – Control – Entity/DAO (BCE+D)**:

```
HTTP Client (live-pages.js / budget-api.js)
   │
   ├── GET/POST/PUT/DELETE /api/budgets*
   ▼
[Boundary] BudgetController (source/backend/src/Modules/Budgets/BudgetController.php)
   │  - Xác thực userId qua AuthContext::requirePersonalUser()
   │  - Đọc query/body JSON và chuẩn hóa phản hồi qua JsonResponse
   ▼
[Control]  BudgetService (source/backend/src/Modules/Budgets/BudgetService.php)
   │  - Kiểm tra ràng buộc nghiệp vụ BR07 (limitAmount > 0, category EXPENSE, duy nhất mỗi tháng)
   │  - Tính chi tiêu ròng (Net Spend), phần trăm sử dụng (Usage %) và mức cảnh báo (Alert Level)
   │  - Kiểm tra ngưỡng 80% / 100% và phát thông báo chống trùng lặp (BR08)
   ├──► [Control] NotificationService (source/backend/src/Modules/Notifications/NotificationService.php)
   │       └──► [DAO] NotificationDAO (bảng `notifications`)
   ▼
[DAO]      BudgetDAO (source/backend/src/Modules/Budgets/BudgetDAO.php)
           └──► Bảng `budgets`, `categories`, `financial_transactions`
```

---

## 3. Quy Tắc Tính Chi Tiêu Ròng (Net Spend - BR07) & Ngưỡng Cảnh Báo (BR08)

### 3.1. Công thức tính Chi tiêu ròng (`getCategoryNetExpenseInMonth`)
Theo quy tắc **BR07**, chi tiêu thực tế của một danh mục trong kỳ ngân sách `YYYY-MM` được tính bằng tổng giá trị quy đổi tiền cơ sở của các giao dịch chi tiêu đã ghi sổ (`POSTED`):

$$\text{NetSpend}(u, c, m) = \sum_{\substack{t \in \text{Transactions} \\ t.\text{user\_id} = u,\; t.\text{category\_id} = c \\ t.\text{transaction\_type} = \text{'EXPENSE'} \\ t.\text{status} = \text{'POSTED'} \\ t.\text{occurred\_on} \in [m\text{-01}, m\text{-end}]}} \left(t.\text{amount} \times t.\text{fx\_rate\_snapshot}\right)$$

**Các giao dịch được loại trừ tuyệt đối khỏi ngân sách (`TC-13`):**
1. **Chuyển tiền nội bộ (`TRANSFER`):** Có `transaction_type = 'TRANSFER'` và `category_id = NULL`, chỉ dịch chuyển số dư giữa 2 ví tài sản nên không làm tăng `NetSpend`.
2. **Nhận tiền vay (`LOAN_DISBURSEMENT`) & Trả nợ gốc (`LOAN_PAYMENT`):** Trả nợ gốc chỉ làm giảm nghĩa vụ nợ (`Liability`) và giảm số dư ví; chỉ phần lãi/phí vay (nếu hạch toán vào danh mục chi phí `EXPENSE`) mới tính vào chi tiêu.
3. **Giao dịch nháp (`DRAFT`):** Chưa ghi sổ (`status = 'DRAFT'`), không ảnh hưởng số dư ví và không cộng vào ngân sách (`BR14`).
4. **Giao dịch đã đảo (`REVERSED`):** Khi đảo giao dịch qua `LedgerService::reverseTransaction()`, giao dịch gốc được cập nhật `status = 'REVERSED'` và giao dịch đảo sinh ra cũng mang `status = 'REVERSED'`. Do đó truy vấn lọc `status = 'POSTED'` tự động triệt tiêu khoản chi đã đảo khỏi `NetSpend`.

### 3.2. Phân loại ngưỡng sử dụng và chống gửi trùng thông báo (`BR08`, `TC-12`)
Tỷ lệ sử dụng ngân sách được tính theo công thức:

$$\text{UsagePercent} = \text{round}\left(\frac{\text{NetSpend}}{\text{LimitAmount}} \times 100,\; 1\right)$$

| Mức độ sử dụng (`UsagePercent`) | Mã trạng thái API (`alertLevel`) | Mã trạng thái DB (`budgets.alert_level`) | Hành vi hệ thống & Khóa chống trùng (`dedup_key`) |
| :--- | :---: | :---: | :--- |
| `< 80.0%` | `GREEN` | `NORMAL` | Trong hạn mức an toàn. Không sinh thông báo cảnh báo. |
| `80.0%` đến `< 100.0%` | `YELLOW` | `YELLOW_80` | Phát tối đa **01** thông báo Cảnh báo Vàng với khóa `BUDGET_YELLOW_{userId}_{categoryId}_{YYYY-MM}`. |
| `>= 100.0%` | `RED` | `RED_100` | Phát tối đa **01** thông báo Cảnh báo Đỏ với khóa `BUDGET_RED_{userId}_{categoryId}_{YYYY-MM}`. Đồng thời đánh dấu ngưỡng Vàng đã kích hoạt để không phát bù cảnh báo Vàng nếu giao dịch bị đảo giảm về mức `80%–99%`. |

> **Lưu ý quan trọng (Soft-Limit Policy):** Theo đúng đặc tả [SRS.md §1.2](./02_elaboration/SRS.md), khi chi tiêu vượt 100% hạn mức ngân sách, hệ thống **không chặn giao dịch chi tiêu** của người dùng mà vẫn cho phép ghi sổ (nếu ví đủ số dư khả dụng) và trả kèm đối tượng `budgetStatus` trong phản hồi API kèm thông báo cảnh báo Đỏ.

---

## 4. Đặc Tả Hợp Đồng RESTful API (Endpoints Contract)

### 4.1. `GET /api/budgets` — Danh sách ngân sách theo tháng
- **Quyền truy cập:** Người dùng cá nhân đã xác thực (`USER`).
- **Query Parameters:**
  - `month` (tùy chọn, mặc định là tháng hiện tại `YYYY-MM`, ví dụ `2026-10`).
- **Response `200 OK`:**
```json
{
  "success": true,
  "data": {
    "month": "2026-10",
    "budgets": [
      {
        "id": 1,
        "categoryId": 1,
        "categoryName": "Ăn uống",
        "month": "2026-10",
        "limitAmount": 2000000,
        "spentAmount": 1650000,
        "remainingAmount": 350000,
        "usagePercent": 82.5,
        "alertLevel": "YELLOW"
      }
    ],
    "count": 1
  }
}
```

### 4.2. `GET /api/budgets/summary` — Tổng hợp tình hình ngân sách tháng
- **Quyền truy cập:** `USER`.
- **Query Parameters:** `month=YYYY-MM`.
- **Response `200 OK`:**
```json
{
  "success": true,
  "data": {
    "month": "2026-10",
    "totalBudgetLimit": 5000000,
    "totalSpent": 2150000,
    "overallRemaining": 2850000,
    "overallUsagePercent": 43.0,
    "overBudgetCount": 1,
    "warningCount": 0,
    "budgetCount": 2
  }
}
```

### 4.3. `POST /api/budgets` — Thiết lập ngân sách danh mục mới
- **Quyền truy cập:** `USER`.
- **Headers:** `Content-Type: application/json`, `X-WealthFlow: 1`.
- **Request Body:**
```json
{
  "categoryId": 1,
  "month": "2026-10",
  "limitAmount": 2000000
}
```
- **Ràng buộc kiểm tra (`422 Unprocessable Entity`):**
  - `limitAmount <= 0`: `"Hạn mức ngân sách phải lớn hơn 0."`
  - `month` sai định dạng `YYYY-MM`: `"Kỳ ngân sách không hợp lệ (định dạng chuẩn: YYYY-MM)."`
  - `categoryId` không tồn tại hoặc của người dùng khác (`BR11`): `"Danh mục không tồn tại hoặc không thuộc quyền sở hữu."`
  - Danh mục thuộc loại `INCOME`: `"Chỉ có thể thiết lập ngân sách cho danh mục Chi phí (EXPENSE)."`
  - Đã có ngân sách `ACTIVE` cùng `(userId, categoryId, month)`: `"Ngân sách cho danh mục này trong tháng YYYY-MM đã tồn tại. Vui lòng chỉnh sửa hạn mức thay vì tạo mới."`
  - Nếu trước đó ngân sách của `(userId, categoryId, month)` đã được lưu trữ (`ARCHIVED`), hệ thống khôi phục bản ghi sang `ACTIVE` với hạn mức mới.

### 4.4. `PUT /api/budgets/{id}` — Cập nhật hạn mức ngân sách
- **Quyền truy cập:** `USER` (chỉ chủ sở hữu bản ghi).
- **Request Body:**
```json
{
  "limitAmount": 2500000
}
```
- **Response `200 OK`:** Trả về đối tượng `budget` sau khi tính lại `usagePercent`, `remainingAmount` và `alertLevel`.

### 4.5. `DELETE /api/budgets/{id}` — Lưu trữ ngân sách (`Archive`)
- **Quyền truy cập:** `USER` (chỉ chủ sở hữu bản ghi).
- **Hành vi:** Chuyển `status = 'ARCHIVED'`, bảo toàn lịch sử và trả về `200 OK` (hoặc `404 Not Found` nếu không thuộc quyền sở hữu).

### 4.6. Các Endpoint Trung tâm Thông báo (`/api/notifications`)
- **`GET /api/notifications?limit=30&unreadOnly=0`:** Trả về danh sách thông báo của người dùng hiện tại (`notifications`), tổng số bản ghi (`count`) và số thông báo chưa đọc (`unreadCount`).
- **`POST /api/notifications/{id}/read`:** Đánh dấu 1 thông báo của người dùng là đã đọc (`read_at = CURRENT_TIMESTAMP`). Trả về `404` nếu thông báo không tồn tại hoặc thuộc về người dùng khác.
- **`POST /api/notifications/read-all`:** Đánh dấu toàn bộ thông báo chưa đọc của người dùng là đã đọc.

---

## 5. Ma Trận Kiểm Thử & Đối Chiếu Nghiệm Thu

| Mã Test Case | Kịch bản kiểm thử | Kỳ vọng | Vị trí kiểm chứng |
| :---: | :--- | :--- | :--- |
| **TC-F05.01** | Tạo ngân sách tháng với hạn mức 2.000.000 VND | Tạo thành công, trả về `limitAmount = 2000000`, `alertLevel = GREEN`. | `source/backend/tests/run_tests.php` |
| **TC-F05.02.1** (`TC-12`) | Ghi nhận chi tiêu đạt `82.5%` hạn mức | Trả về `alertTriggered = 'YELLOW'`, tạo đúng 1 thông báo Vàng trong bảng `notifications`. | `source/backend/tests/run_tests.php` |
| **TC-F05.02.2** (`TC-12`) | Ghi nhận thêm chi tiêu đẩy mức dùng lên `107.5%` | Trả về `alertTriggered = 'RED'`, tạo đúng 1 thông báo Đỏ trong bảng `notifications`. | `source/backend/tests/run_tests.php` |
| **TC-F05.02.3** (`TC-12`) | Ghi nhận tiếp chi tiêu khi đã ở mức `> 100%` | Không tạo thông báo trùng (`alertTriggered = null`). | `source/backend/tests/run_tests.php` |
| **TC-F05.03** (`TC-13`) | Thực hiện chuyển ví nội bộ (`TRANSFER`) và đảo giao dịch (`REVERSED`) | Chi tiêu ròng của ngân sách không cộng giao dịch chuyển ví và trừ bỏ giao dịch đã đảo. | `source/backend/tests/run_tests.php` |
| **TC-F05.04** | Nhảy vọt từ `< 80%` lên `>= 100%` trong 1 giao dịch rồi đảo giảm về `85%` | Chỉ phát 1 cảnh báo Đỏ lúc vượt 100%; không phát bù cảnh báo Vàng sau khi đảo. | `source/backend/tests/run_tests.php` |
