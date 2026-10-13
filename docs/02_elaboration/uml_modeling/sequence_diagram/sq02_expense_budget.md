# SQ02: Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm tra Ngân sách

> Tham chiếu: [SRS.md](../../SRS.md) v2.1 | [sequence_diagram.md](../sequence_diagram.md) | [class_diagram.md](../class_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ02** |
| **Tên Kịch bản** | **Ghi nhận Chi tiêu, Hạch toán Kép nguyên tử & Kiểm tra Ngân sách tháng** |
| **Use Cases bao phủ** | `UC03` (Ghi nhận giao dịch tài chính), `UC05` (Kiểm soát ngân sách chi tiêu), `SYS03` (Kiểm tra ngân sách) |
| **Quy tắc Nghiệp vụ (BR)** | `BR01` (Kế toán kép), `BR06` (Bất biến sổ cái), `BR07` (Công thức ngân sách), `BR08` (Cảnh báo 80%/100%), `BR13` (Idempotency) |
| **Yêu cầu Chức năng (FR)** | `FR03.01` - `FR03.04`, `FR05.01` - `FR05.03`, `FR15.01` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa quy trình kiểm tra số dư ví khả dụng, chống trùng lặp mạng bằng `Idempotency-Key`, thực thi Transaction ACID hạch toán kép (Nợ Chi phí / Có Ví tài sản), cập nhật số dư đệm, và kích hoạt tác vụ kiểm tra hạn mức ngân sách để sinh thông báo Cảnh báo Vàng (80%) hoặc Cảnh báo Đỏ (100%). |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **User** | Actor | Người dùng chủ động nhập liệu giao dịch chi tiêu trên giao diện. |
| **TransactionController** | Boundary | Tiếp nhận HTTP POST `/api/transactions` kèm header `Idempotency-Key`. |
| **LedgerService** | Control | Thẩm định số dư ví, chống trùng lặp và điều phối hạch toán kép. |
| **LedgerRepository** | Entity / Persistence | Mở transaction, lưu bản ghi Transaction, JournalEntries, cập nhật ví. |
| **BudgetService** | Control | Tính toán lũy kế chi tiêu ròng trong tháng và đối chiếu hạn mức ngân sách. |
| **NotificationService** | Control | Đẩy thông báo cảnh báo ngân sách vào cơ sở dữ liệu và WebSocket client. |
| **PostgreSQL Database** | Storage | Lưu trữ dữ liệu sổ cái và thực thi giao dịch ACID. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor User as Người dùng (User)
    participant TC as TransactionController
    participant LS as LedgerService
    participant LR as LedgerRepository
    participant BS as BudgetService
    participant NS as NotificationService
    participant DB as PostgreSQL Database

    User ->> TC: POST /api/transactions (walletId, categoryId, amount, date, note)<br/>Header: Idempotency-Key: uuid-v4
    activate TC
    TC ->> LS: createExpenseTransaction(userId, dto, idempotencyKey)
    activate LS

    %% Kiểm tra Idempotency
    LS ->> LR: findIdempotencyRecord(idempotencyKey)
    activate LR
    LR ->> DB: SELECT response_cached FROM idempotency_keys WHERE key = ?
    DB -->> LR: null (chưa xử lý)
    LR -->> LS: null
    deactivate LR

    %% Kiểm tra số dư ví
    LS ->> LR: getWalletBalance(walletId, userId)
    activate LR
    LR ->> DB: SELECT balance FROM wallets WHERE id = ? AND user_id = ?
    DB -->> LR: walletRecord (balance = 1.000.000 VND)
    LR -->> LS: currentBalance
    deactivate LR

    alt Số dư ví không đủ chi trả (currentBalance < amount)
        LS -->> TC: Ném lỗi UnprocessableEntityException("Số dư ví không đủ chi trả")
        TC -->> User: 422 Unprocessable Entity (Thông báo lỗi số dư)
    else Đủ số dư khả dụng (currentBalance >= amount)
        %% Giao dịch nguyên tử
        LS ->> LR: executeExpenseTransaction(txData)
        activate LR
        LR ->> DB: BEGIN TRANSACTION
        LR ->> DB: INSERT INTO transactions (id, user_id, type='EXPENSE', status='POSTED', amount, date)
        LR ->> DB: INSERT INTO journal_entries (tx_id, account='EXPENSE', type='DEBIT', amount)
        LR ->> DB: INSERT INTO journal_entries (tx_id, account='ASSET_WALLET', type='CREDIT', amount)
        LR ->> DB: UPDATE wallets SET balance = balance - amount WHERE id = ?
        LR ->> DB: INSERT INTO idempotency_keys (key, response_hash, status='COMMITTED')
        LR ->> DB: COMMIT TRANSACTION
        DB -->> LR: commitSuccess
        LR -->> LS: postedTransactionResult
        deactivate LR

        %% Phản hồi ngay cho người dùng
        LS -->> TC: TransactionResponse(txId, newBalance, status='POSTED')
        deactivate LS
        TC -->> User: 201 Created (Giao dịch ghi sổ thành công, số dư cập nhật)
        deactivate TC

        %% Kiểm tra ngân sách (Tiến trình sự kiện tiếp nối)
        activate BS
        TC -) BS: checkCategoryBudget(userId, categoryId, transactionMonth)
        BS ->> LR: getMonthlyCategoryExpense(userId, categoryId, month)
        activate LR
        LR ->> DB: SELECT SUM(amount) FROM journal_entries WHERE ...
        DB -->> LR: netExpenseAmount (ví dụ: 1.600.000 VND)
        LR -->> BS: netExpenseAmount
        deactivate LR

        BS ->> BS: getBudgetLimit(categoryId, month) -> limitAmount (2.000.000 VND)
        BS ->> BS: calculateUsage() -> (1.600.000 / 2.000.000) * 100% = 80%

        alt 80% <= Usage < 100% (Tiệm cận ngưỡng)
            BS ->> NS: createBudgetAlert(userId, categoryId, level='YELLOW', usage=80%)
            activate NS
            NS ->> DB: INSERT INTO notifications (user_id, title='Cảnh báo Ngân sách Vàng', content, type='BUDGET_WARNING')
            DB -->> NS: notifSaved
            NS -->> User: Đẩy WebSocket / Notification Center (Badge Vàng)
            deactivate NS
        else Usage >= 100% (Vượt ngân sách)
            BS ->> NS: createBudgetAlert(userId, categoryId, level='RED', usage=105%)
            activate NS
            NS ->> DB: INSERT INTO notifications (user_id, title='Cảnh báo Ngân sách Đỏ', content, type='BUDGET_EXCEEDED')
            DB -->> NS: notifSaved
            NS -->> User: Đẩy WebSocket / Notification Center (Badge Đỏ)
            deactivate NS
        end
        deactivate BS
    end
```

---

## 4. Mã nguồn PlantUML Tương ứng

<details>
<summary><b>Nhấn để xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ02_Ghi_Nhan_Chi_Tieu_Va_Ngan_Sach
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Người dùng (User)" as User
participant "TransactionController" as TC
participant "LedgerService" as LS
participant "LedgerRepository" as LR
participant "BudgetService" as BS
participant "NotificationService" as NS
database "PostgreSQL Database" as DB

User -> TC: POST /api/transactions (walletId, categoryId, amount)
activate TC
TC -> LS: createExpenseTransaction(userId, dto, idempotencyKey)
activate LS
LS -> LR: getWalletBalance(walletId, userId)
activate LR
LR -> DB: SELECT balance FROM wallets WHERE id = ?
DB --> LR: balance
LR --> LS: balance
deactivate LR

alt Số dư không đủ
  LS --> TC: 422 Unprocessable Entity
  TC --> User: Thông báo số dư không đủ
else Đủ số dư
  LS -> LR: executeExpenseTransaction(txData)
  activate LR
  LR -> DB: BEGIN TRANSACTION
  LR -> DB: INSERT INTO transactions (status='POSTED')
  LR -> DB: INSERT INTO journal_entries (Debit Expense / Credit Wallet)
  LR -> DB: UPDATE wallets SET balance = balance - amount
  LR -> DB: COMMIT TRANSACTION
  DB --> LR: Success
  LR --> LS: postedTransactionResult
  deactivate LR

  LS --> TC: TransactionResponse
  deactivate LS
  TC --> User: 201 Created (Lưu thành công, số dư cập nhật)
  deactivate TC

  TC -> BS: checkCategoryBudget(userId, categoryId, month)
  activate BS
  BS -> LR: getMonthlyCategoryExpense(...)
  activate LR
  LR -> DB: SELECT SUM(amount) FROM ...
  DB --> LR: netExpense
  LR --> BS: netExpense
  deactivate LR

  BS -> BS: calculateUsage() -> (Expense / Limit) * 100%
  alt Usage >= 80%
    BS -> NS: createBudgetAlert(userId, level, usage)
    activate NS
    NS -> DB: INSERT INTO notifications (...)
    NS --> User: Đẩy chuông thông báo (Vàng hoặc Đỏ)
    deactivate NS
  end
  deactivate BS
end
@enduml
```
</details>

---

## 5. Điều hướng Tài liệu
- **Kịch bản trước:** [SQ01: Đăng ký & Xác thực người dùng](./sq01_auth_jwt.md)
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
- **Kịch bản kế tiếp:** [SQ03: Chuyển tiền Nội bộ & Đảo Giao dịch](./sq03_transfer_reversal.md)
