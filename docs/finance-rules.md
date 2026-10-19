# Quy Tắc Nghiệp Vụ Tài Chính & Thiết Kế Mô Hình Vay / Mục Tiêu (PLAN-02 & DOC-06)

> **Mã công việc:** `PLAN-02`, `DOC-06` (Sprint 1)  
> **Phụ trách chính:** Phạm Nguyễn Tuấn Kiệt (Nguyễn Thành Trung hỗ trợ đối chiếu CSDL)  
> **Đối chiếu tài liệu:** [SRS.md](./02_elaboration/SRS.md) · [api_design.md](./02_elaboration/api_design.md) · [database_design.md](./02_elaboration/database_design.md)

---

## 1. Bảng Tổng Hợp Quy Tắc Nghiệp Vụ Tài Chính (`BR01` – `BR12` & Mở Rộng SRS `BR01` – `BR18`)

Bảng dưới đây thống nhất hệ thống mã quy tắc giữa **Bảng Kế hoạch Phân công (`BR01`–`BR12`)** và **Đặc tả Yêu cầu Phần mềm (`SRS.md BR01`–`BR18`)**, xác định rõ nguồn dữ liệu, công thức tính và cách xử lý ngoại lệ:

| Mã Kế Hoạch | Mã SRS | Tên Quy Tắc | Công Thức / Nguyên Lý Kế Toán | Nguồn Bảng CSDL | Ngoại Lệ & Ràng Buộc Bắt Buộc |
| :---: | :---: | :--- | :--- | :--- | :--- |
| **BR01** | **BR16** | **Tài sản ròng (Net Worth)** | $\text{Net Worth} = \text{Total Assets} - \text{Total Liabilities}$ | `wallets`, `gold_assets`, `micro_loans`, `net_worth_snapshots` | Tiền phân bổ vào mục tiêu (`financial_goals`) là khoản đánh dấu (earmark) trên số dư ví, **cấm cộng 2 lần** vào Tổng tài sản (`TC-16`, `TC-19`). |
| **BR02** | **BR18** | **Dòng tiền thuần (Net Cash Flow)** | $\text{Net Cash Flow} = \text{Total Income} - \text{Total Expense}$ | `financial_transactions`, `ledger_entries` | Loại trừ tuyệt đối giao dịch chuyển ví (`TRANSFER`), tiền nhận giải ngân vay (`LOAN_DISBURSEMENT`), trả nợ gốc (`LOAN_PAYMENT`) và giao dịch đã đảo (`REVERSED`). |
| **BR03** | **BR18** | **Tỷ lệ tiết kiệm (Saving Rate)** | $\text{Saving Rate} = \frac{\text{Income} - \text{Expense}}{\text{Income}} \times 100\%$ | `financial_transactions` | Khi $\text{Income} \le 0$: không chia cho 0, trả về `null` và hiển thị `"N/A"` trên giao diện (`TC-20`). Khi $\text{Expense} > \text{Income}$: trả về số âm cảnh báo thâm hụt. |
| **BR04** | **BR01, BR02** | **Cân bằng Sổ kế toán kép (Double-Entry)** | $\sum \text{DebitBase} = \sum \text{CreditBase}$ trên mọi giao dịch `POSTED` | `financial_transactions`, `ledger_accounts`, `ledger_entries` | Mọi phép tính tiền tệ sử dụng số học thập phân chính xác (`DECIMAL(24,8)` / `BCMath` - `BR03` SRS). Giao dịch `DRAFT` chưa sinh bút toán sổ cái (`BR14` SRS). |
| **BR05** | **BR05** | **Chuyển tiền nội bộ (Internal Transfer)** | `DEBIT` Ví đích / `CREDIT` Ví nguồn | `wallets`, `financial_transactions`, `ledger_entries` | Ví nguồn và ví đích phải khác nhau (`fromWalletId != toWalletId`) và thuộc cùng `userId`. Không làm thay đổi Thu nhập hay Chi phí (`TC-07`). |
| **BR06** | **BR06** | **Bất biến sổ cái & Đảo bút toán (Reversal)** | Tạo giao dịch đảo ngược chiều `DEBIT` $\leftrightarrow$ `CREDIT` gắn `original_transaction_id` | `financial_transactions`, `ledger_entries`, `wallets` | Cấm xóa vật lý (`DELETE`) hoặc sửa số tiền của giao dịch `POSTED`. Chỉ giao dịch đang ở trạng thái `POSTED` mới được đảo 1 lần duy nhất (`TC-08`). |
| **BR07** | **BR07** | **Chi tiêu ròng trong Ngân sách (Budget Usage)** | $\text{NetSpend} = \sum (\text{EXPENSE POSTED trong kỳ})$ | `budgets`, `financial_transactions` | Chỉ áp dụng cho danh mục `EXPENSE`. Không tính chuyển tiền nội bộ, trả nợ gốc hoặc giao dịch đã đảo (`TC-13`). |
| **BR08** | **BR08** | **Ngưỡng cảnh báo & Chống trùng (Alert Dedupe)** | `< 80%`: `GREEN` · `80%–99.9%`: `YELLOW` · $\ge 100\%$: `RED` | `budgets`, `notifications` | Khóa duy nhất `(user_id, dedup_key)` đảm bảo mỗi ngưỡng chỉ phát **01** thông báo/ngân sách/tháng (`TC-12`). Nhảy từ `<80%` lên $\ge 100\%$ chỉ phát Đỏ và khóa ngưỡng Vàng. |
| **BR09** | **BR09** | **Neo ngày định kỳ cuối tháng (Monthly Clamp)** | Ngày neo $d \in \{29,30,31\}$ tự lùi về ngày cuối tháng thiếu ngày | `recurring_schedules`, `recurring_executions` | Giữ nguyên `day_of_month` gốc để khôi phục lại ngày 31 ở tháng có 31 ngày (`2026-01-31` $\rightarrow$ `2026-02-28` $\rightarrow$ `2026-03-31`, `TC-14`, `TC-15`). |
| **BR10** | **BR17** | **Hạch toán Gốc & Lãi Khoản vay (Loan Principal vs Interest)** | Trả nợ = Trả gốc (`Principal`) + Trả lãi/phí (`Interest/Fee`) | `micro_loans`, `loan_repayment_schedules`, `loan_payments`, `financial_transactions` | Nhận tiền vay **không phải** `INCOME`. Trả nợ gốc làm giảm dư nợ (`principal_remaining`) và giảm ví, **không phải** `EXPENSE`. Chỉ phần lãi/phí vay tính vào `EXPENSE` (`TC-17`). |
| **BR11** | **BR04, BR15** | **Lát cắt tỷ giá ngoại tệ (FX Snapshot)** | $\text{AmountBase} = \text{AmountOriginal} \times \text{fx\_rate\_snapshot}$ | `exchange_rates`, `financial_transactions`, `ledger_entries` | Tỷ giá lưu bất biến tại thời điểm ghi sổ. Thay đổi tỷ giá thị trường về sau không làm biến đổi `amount_base` của giao dịch lịch sử (`TC-10`). |
| **BR12** | **BR11, BR13** | **Cô lập dữ liệu chủ sở hữu & Idempotency** | `WHERE user_id = :authenticatedUserId` + `Idempotency-Key` | Toàn bộ 28 bảng CSDL (`idempotency_keys`) | Không tin `userId` do client gửi trong body/query; chống IDOR giữa các người dùng và chặn Admin xem chi tiết sổ sách cá nhân (`TC-24`). |

---

## 2. Chốt Mô Hình Nghiệp Vụ Khoản Vay & Cho Vay Vi Mô (`PLAN-02`, `FR07`/`FR10`)

### 2.1. Phân loại Hợp đồng Vay (`micro_loans.loan_direction`)
Hệ thống hỗ trợ 2 chiều công nợ vi mô độc lập trong bảng [016_micro_loans.sql](../source/database/016_micro_loans.sql):

| Thuộc tính | Đi vay (`BORROWED`) | Cho vay (`LENT`) |
| :--- | :--- | :--- |
| **Bản chất kế toán** | **Nợ phải trả (Liability)** | **Khoản phải thu / Tài sản (Receivable Asset)** |
| **Tác động lên Net Worth** | Trừ vào Tổng nợ (`- principal_remaining`) | Cộng vào Tổng tài sản (`+ principal_remaining`) |
| **Khi giải ngân (`Disbursement`)** | Ví nhận tiền tăng (`+ principal_original`), Dư nợ phải trả tăng (`+ principal_original`). **Không tính vào Thu nhập (`INCOME`).** | Ví xuất tiền giảm (`- principal_original`), Dư nợ phải thu tăng (`+ principal_original`). **Không tính vào Chi phí (`EXPENSE`).** |
| **Khi thanh toán định kỳ (`Payment`)** | Ví trả tiền giảm `-(principal + interest)`.<br>• `principal_amount`: giảm `principal_remaining` (Liability).<br>• `interest_fee_amount`: ghi nhận vào `EXPENSE` (Chi phí lãi vay). | Ví thu tiền tăng `+(principal + interest)`.<br>• `principal_amount`: giảm `principal_remaining` (Receivable).<br>• `interest_fee_amount`: ghi nhận vào `INCOME` (Thu nhập lãi cho vay). |
| **Khi tất toán (`principal_remaining = 0`)** | Chuyển `status` từ `ACTIVE` $\rightarrow$ `PAID_OFF`. | Chuyển `status` từ `ACTIVE` $\rightarrow$ `PAID_OFF`. |

### 2.2. Ma trận Bút toán Kế toán Kép cho Khoản Vay (`BR04`, `BR10`)

#### A. Nghiệp vụ Đi vay (`BORROWED`):
1. **Giải ngân khoản đi vay (`LOAN_DISBURSEMENT`):**
   - Ví dụ: Đi vay `5.000.000 VND` giải ngân vào Ví Ngân hàng (`wallet_id = 2`).
   - **Bút toán Nợ (`DEBIT`):** Tài khoản Ví Ngân hàng (`ASSET`) `+5.000.000 VND`.
   - **Bút toán Có (`CREDIT`):** Tài khoản Nợ Vay (`LIABILITY`) `+5.000.000 VND`.
   - **Hệ quả:** Số dư Ví Ngân hàng `+5.000.000`, `principal_remaining = 5.000.000`, `Net Worth` không đổi (`+5tr Asset - 5tr Liability = 0`), `Income = 0`.
2. **Trả nợ gốc + lãi (`LOAN_PAYMENT` — Kiểm chứng `TC-17`):**
   - Ví dụ: Trả gốc `1.000.000 VND` và trả lãi `50.000 VND` từ Ví Ngân hàng (`Tổng trích ví = 1.050.000 VND`).
   - **Phần Gốc (`1.000.000 VND`):**
     - `DEBIT` Tài khoản Nợ Vay (`LIABILITY`): `1.000.000 VND` (giảm dư nợ gốc xuống `4.000.000 VND`).
     - `CREDIT` Tài khoản Ví Ngân hàng (`ASSET`): `1.000.000 VND`.
     - Loại giao dịch: `LOAN_PAYMENT` (không cộng vào `EXPENSE` và không tính vào Ngân sách chi tiêu `BR07`).
   - **Phần Lãi & Phí (`50.000 VND`):**
     - `DEBIT` Tài khoản Chi phí Lãi vay (`EXPENSE`): `50.000 VND`.
     - `CREDIT` Tài khoản Ví Ngân hàng (`ASSET`): `50.000 VND`.
     - Loại giao dịch: `EXPENSE` (tính vào tổng chi phí trong kỳ và báo cáo dòng tiền).
   - **Kết quả sau thanh toán (`TC-17`):**
     - Số dư ví giảm `1.050.000 VND`.
     - Dư nợ gốc (`principal_remaining`) còn `4.000.000 VND`.
     - Tổng Chi phí (`Expense`) trong tháng chỉ tăng đúng `50.000 VND`.

#### B. Nghiệp vụ Cho vay (`LENT`):
1. **Giải ngân cho vay (`LOAN_DISBURSEMENT`):**
   - `DEBIT` Tài khoản Phải thu Cho vay (`ASSET - RECEIVABLE`) / `CREDIT` Tài khoản Ví xuất tiền (`ASSET`).
2. **Thu hồi nợ gốc + lãi (`LOAN_PAYMENT`):**
   - Phần gốc (`principal_amount`): `DEBIT` Ví nhận tiền (`ASSET`) / `CREDIT` Tài khoản Phải thu (`ASSET - RECEIVABLE`).
   - Phần lãi (`interest_fee_amount`): `DEBIT` Ví nhận tiền (`ASSET`) / `CREDIT` Tài khoản Thu nhập Lãi (`INCOME`).

### 2.3. Ràng buộc toàn vẹn khi Ghi nhận Trả nợ (`PLAN-08`)
- `principal_amount >= 0`, `interest_fee_amount >= 0` và `principal_amount + interest_fee_amount > 0`.
- `principal_amount <= micro_loans.principal_remaining` (chặn trả lố dư nợ gốc hiện hành với mã lỗi `422 Unprocessable Entity`).
- Hỗ trợ `Idempotency-Key` để chống ghi nhận thanh toán trùng lặp 2 lần khi mạng chập chờn.
- Khi `principal_remaining` sau giao dịch bằng `0`, hệ thống tự động cập nhật `micro_loans.status = 'PAID_OFF'` và `next_due_date = NULL`.

---

## 3. Chốt Mô Hình Mục Tiêu Tiết Kiệm & Phong Tỏa Logic (`PLAN-02`, `FR09`, `BR10`)

### 3.1. Nguyên lý Earmarking (Phong tỏa logic, không cộng trùng tài sản)
Theo bảng [022_financial_goals.sql](../source/database/022_financial_goals.sql) và [023_goal_allocations.sql](../source/database/023_goal_allocations.sql):
1. **Không sinh tài sản ảo (`TC-16`):**
   - Mục tiêu tiết kiệm (`financial_goals`) là một "hũ mục tiêu" đánh dấu mục đích sử dụng cho số tiền **đang nằm trong ví** (`wallet_id`).
   - Khi người dùng phân bổ (`DEPOSIT`) `1.000.000 VND` vào mục tiêu `"Quỹ khẩn cấp"`, `financial_goals.current_amount` tăng thêm `1.000.000 VND`, nhưng **tổng Tài sản ròng (`Net Worth`) không tăng thêm** vì số tiền đó đã được tính trong `wallets.cached_balance`.
2. **Công thức tính chỉ số Mục tiêu (`BR10`):**
   - **Số tiền đã tích lũy:**
     $$\text{CurrentAmount} = \sum \text{Allocations}(\text{DEPOSIT}) - \sum \text{Allocations}(\text{WITHDRAWAL})$$
   - **Tiến độ hoàn thành (`Progress %`):**
     $$\text{ProgressPercent} = \min\left(100.0,\; \text{round}\left(\frac{\text{CurrentAmount}}{\text{TargetAmount}} \times 100,\; 1\right)\right)$$
     *(Lưu ý: Thanh tiến độ giới hạn ở `100%`, nhưng số tiền thực tế vượt mục tiêu vẫn hiển thị đầy đủ).*
   - **Mức tiết kiệm cần thiết mỗi tháng (`MonthlySavingTarget`):**
     $$\text{RemainingMonths} = \max\left(1,\; (\text{Year}_{\text{deadline}} - \text{Year}_{\text{now}}) \times 12 + (\text{Month}_{\text{deadline}} - \text{Month}_{\text{now}})\right)$$
     $$\text{MonthlySavingTarget} = \max\left(0,\; \frac{\text{TargetAmount} - \text{CurrentAmount}}{\text{RemainingMonths}}\right)$$
3. **Ràng buộc nghiệp vụ:**
   - `target_amount > 0`, `current_amount >= 0`.
   - Khi rút phân bổ (`WITHDRAWAL`), số tiền rút không được vượt quá `current_amount` hiện có của mục tiêu (`422 Unprocessable Entity`).
   - Khi `current_amount >= target_amount`, mục tiêu có thể chuyển trạng thái sang `COMPLETED`.

---

## 4. Quy Tắc Định Giá Vàng (`FR11`), Tài Sản Ròng (`FR12`) & Báo Cáo (`FR13`)

### 4.1. Tài sản Vàng & Giá vốn Bình quân Gia quyền (`WAC`)
- Bảng [019_gold_assets.sql](../source/database/019_gold_assets.sql) và [020_gold_transactions.sql](../source/database/020_gold_transactions.sql) lưu khối lượng theo đơn vị **Chỉ (`quantity_chi`, `DECIMAL(18,4)`)** cho 3 loại vàng: `RING_9999`, `SJC_BAR`, `GOLD_24K`.
- **Khi Mua vàng (`BUY`):**
  $$\text{NewCostBasis} = \text{OldCostBasis} + (\text{BuyQty} \times \text{UnitPrice})$$
  $$\text{NewQty} = \text{OldQty} + \text{BuyQty}$$
  $$\text{WeightedAvgCost (WAC)} = \frac{\text{NewCostBasis}}{\text{NewQty}}$$
- **Khi Bán vàng (`SELL`):**
  - Ràng buộc `SellQty <= HeldQty`.
  - Giảm giá vốn theo tỷ lệ WAC: $\text{NewCostBasis} = \text{OldCostBasis} - (\text{SellQty} \times \text{WAC})$.
- **Định giá thị trường (`Valuation` — `TC-18`):**
  $$\text{MarketValue} = \text{quantity\_chi} \times \text{valuation\_per\_chi}$$
  - Luôn gắn kèm `valuation_date` rõ ràng; tuyệt đối không lấy giá tĩnh hardcode trên UI làm giá thị trường thực.

### 4.2. Công thức Tổng hợp Tài sản ròng (`Net Worth` — `BR01` / `BR16`, `TC-19`)
Tại mọi thời điểm khảo sát (quy đổi về tiền cơ sở `VND`):

$$\text{TotalAssets} = \underbrace{\sum_{w \in \text{AssetWallets}} \text{BalanceBase}(w)}_{\text{Ví Tiền mặt, Ngân hàng, Ví điện tử, Tiết kiệm}} + \underbrace{\sum_{g \in \text{HeldGold}} (\text{quantity\_chi}_g \times \text{valuation\_per\_chi}_g)}_{\text{Tài sản vàng nắm giữ}} + \underbrace{\sum_{l \in \text{ActiveLent}} \text{PrincipalRemainingBase}(l)}_{\text{Khoản cho vay phải thu}}$$

$$\text{TotalLiabilities} = \underbrace{\sum_{w \in \text{CreditWallets}} |\text{NegativeBalanceBase}(w)|}_{\text{Dư nợ ví tín dụng}} + \underbrace{\sum_{b \in \text{ActiveBorrowed}} \text{PrincipalRemainingBase}(b)}_{\text{Dư nợ gốc khoản đi vay}}$$

$$\text{NetWorth} = \text{TotalAssets} - \text{TotalLiabilities}$$

> **Quy tắc bất biến:** Số dư trong `financial_goals` (`current_amount`) **KHÔNG** được cộng vào `TotalAssets` vì tiền đó vẫn nằm trong `AssetWallets`.

---

## 5. Thiết Kế Hợp Đồng API Cho Khoản Vay (`/api/loans`) & Mục Tiêu (`/api/goals`)

### 5.1. Nhóm API Khoản Vay & Nợ Vi Mô (`/api/loans`)

| HTTP Method & Endpoint | Mô tả chức năng | Request Payload chính | Response `200`/`201` chính |
| :--- | :--- | :--- | :--- |
| `GET /api/loans` | Danh sách hợp đồng đi vay (`BORROWED`) và cho vay (`LENT`) của người dùng | Query: `?direction=BORROWED\|LENT&status=ACTIVE\|PAID_OFF` | `{ "loans": [...], "summary": { "totalBorrowedRemaining": ..., "totalLentRemaining": ... } }` |
| `POST /api/loans` | Tạo hợp đồng vay/cho vay mới (kèm tùy chọn giải ngân vào ví và sinh lịch trả nợ `loan_repayment_schedules`) | `{ "title", "partnerName", "loanDirection": "BORROWED\|LENT", "walletId", "principalOriginal", "interestRateYear", "termMonths", "startDate", "nextDueDate" }` | `{ "loan": { "id", "principalOriginal", "principalRemaining", "status": "ACTIVE", ... } }` |
| `GET /api/loans/{id}` | Xem chi tiết khoản vay, lịch phân kỳ (`schedules`) và lịch sử trả nợ (`payments`) | Path param `{id}` | `{ "loan": {...}, "schedules": [...], "payments": [...] }` |
| `POST /api/loans/{id}/payments` | Ghi nhận trả nợ/thu nợ tách bạch gốc (`principalAmount`) và lãi/phí (`interestFeeAmount`) | `{ "walletId", "principalAmount", "interestFeeAmount", "paidOn", "categoryId" }` | `{ "paymentId", "principalRemaining", "loanStatus", "expenseTransactionId" }` |

### 5.2. Nhóm API Mục Tiêu Tiết Kiệm (`/api/goals`)

| HTTP Method & Endpoint | Mô tả chức năng | Request Payload chính | Response `200`/`201` chính |
| :--- | :--- | :--- | :--- |
| `GET /api/goals` | Danh sách mục tiêu tiết kiệm kèm tiến độ `%` và mức tích lũy cần thiết/tháng | Query: `?status=ACTIVE\|COMPLETED` | `{ "goals": [{ "id", "name", "targetAmount", "currentAmount", "progressPercent", "monthlySavingTarget", "deadline", "priority", "status" }] }` |
| `POST /api/goals` | Tạo mục tiêu tiết kiệm mới | `{ "name", "targetAmount", "deadline", "walletId", "priority": "LOW\|MEDIUM\|HIGH" }` | `{ "goal": {...} }` |
| `POST /api/goals/{id}/allocate` | Phân bổ tiền vào mục tiêu (`DEPOSIT`) hoặc hoàn trả (`WITHDRAWAL`) — chỉ earmark, không tăng Net Worth | `{ "allocationType": "DEPOSIT\|WITHDRAWAL", "amount", "walletId", "allocatedOn", "note" }` | `{ "goal": {...}, "allocation": {...} }` |
| `PUT /api/goals/{id}` | Cập nhật thông tin hoặc trạng thái mục tiêu (`ACTIVE`, `COMPLETED`, `CANCELLED`) | `{ "name", "targetAmount", "deadline", "priority", "status" }` | `{ "goal": {...} }` |
| `DELETE /api/goals/{id}` | Hủy mục tiêu tiết kiệm (`status = 'CANCELLED'`) | Path param `{id}` | `{ "message": "Đã hủy mục tiêu tiết kiệm." }` |
