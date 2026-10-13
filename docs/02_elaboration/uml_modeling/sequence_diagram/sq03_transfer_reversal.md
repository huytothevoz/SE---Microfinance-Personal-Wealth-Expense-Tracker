# SƠ ĐỒ TUẦN TỰ SQ03: CHUYỂN TIỀN NỘI BỘ & ĐẢO GIAO DỊCH (REVERSAL)
## PHÂN HỆ QUẢN LÝ THU CHI & NGÂN SÁCH (EXPENSE & BUDGET SUBSYSTEM)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG - KHOA CÔNG NGHỆ THÔNG TIN**  
- **Môn học:** Công nghệ phần mềm (Software Engineering)
- **Đề tài:** Hệ thống Quản lý Tài chính Cá nhân, Tài sản và Tài chính Vi mô (WealthFlow)
- **Nhóm / Lớp:** Voz / N3
- **Tài liệu tham chiếu:** [SRS.md](../../SRS.md) v2.1 (UC03) và [sequence_diagram.md](../sequence_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ03** |
| **Tên Kịch bản** | **Chuyển tiền giữa các ví nội bộ & Đảo giao dịch (Reversal) bảo toàn kiểm toán** |
| **Use Cases bao phủ** | `UC03` (Ghi nhận & Quản lý giao dịch tài chính) |
| **Quy tắc Nghiệp vụ (BR)** | `BR01` (Kế toán kép), `BR05` (Chuyển tiền không ảnh hưởng Thu/Chi), `BR06` (Bất biến sổ cái & Đảo giao dịch), `BR13` (Idempotency) |
| **Yêu cầu Chức năng (FR)** | `FR03.01`, `FR03.03`, `FR03.04`, `FR15.01` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa 2 luồng hạch toán đặc thù của kế toán: (1) Chuyển tiền nội bộ giữa 2 ví cùng chủ sở hữu (Ghi Nợ Ví Đích / Ghi Có Ví Nguồn, tuyệt đối không tính vào doanh số Thu nhập hay Chi phí) và (2) Đảo giao dịch Posted (tạo bút toán đảo ngược chiều đối ứng nguyên tử, liên kết `originalId`, hoàn trả số dư ví mà không xóa vật lý bản ghi cũ). |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **User** | Actor | Người dùng thực hiện lệnh chuyển tiền hoặc yêu cầu đảo giao dịch đã ghi sổ. |
| **TransactionController** | Boundary | Tiếp nhận HTTP POST `/api/transactions/transfer` và `/api/transactions/{id}/reverse`. |
| **LedgerService** | Control | Kiểm tra quy tắc ví nguồn/đích, tính hợp lệ của giao dịch gốc và điều phối hạch toán. |
| **LedgerRepository** | Entity / Persistence | Thực thi Database Transaction nguyên tử ghi nhận các dòng Journal Entries. |
| **PostgreSQL Database** | Storage | Lưu trữ bất biến lịch sử sổ cái và cập nhật số dư các ví liên quan. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor User as Người dùng (User)
    participant TC as TransactionController
    participant LS as LedgerService
    participant LR as LedgerRepository
    participant DB as PostgreSQL Database

    %% Luồng Chuyển tiền nội bộ
    rect rgb(245, 243, 255)
        note over User, DB: 1. KỊCH BẢN CHUYỂN TIỀN GIỮA CÁC VÍ NỘI BỘ (TRANSFER)
        User ->> TC: POST /api/transactions/transfer (fromWalletId, toWalletId, amount, note)
        activate TC
        TC ->> LS: transferBetweenWallets(userId, dto)
        activate LS

        alt Ví nguồn trùng ví đích hoặc số tiền <= 0
            LS -->> TC: Ném lỗi BadRequestException("Ví nguồn và ví đích phải khác nhau")
            TC -->> User: 400 Bad Request
        else Hợp lệ -> Kiểm tra số dư ví nguồn
            LS ->> LR: checkBalance(fromWalletId, userId)
            activate LR
            LR ->> DB: SELECT balance FROM wallets WHERE id = ?
            DB -->> LR: balance (ví dụ: 5.000.000 VND)
            LR -->> LS: balance
            deactivate LR

            alt Số dư ví nguồn < amount
                LS -->> TC: Ném lỗi UnprocessableEntityException("Ví nguồn không đủ số dư")
                TC -->> User: 422 Unprocessable Entity
            else Đủ số dư -> Thực thi chuyển tiền nguyên tử
                LS ->> LR: executeTransferTransaction(transferData)
                activate LR
                LR ->> DB: BEGIN TRANSACTION
                LR ->> DB: INSERT INTO transactions (type='TRANSFER', status='POSTED', amount)
                LR ->> DB: INSERT INTO journal_entries (type='DEBIT', account='DEST_WALLET', amount)
                LR ->> DB: INSERT INTO journal_entries (type='CREDIT', account='SOURCE_WALLET', amount)
                LR ->> DB: UPDATE wallets SET balance = balance - amount WHERE id = fromWalletId
                LR ->> DB: UPDATE wallets SET balance = balance + amount WHERE id = toWalletId
                LR ->> DB: COMMIT TRANSACTION
                DB -->> LR: commitSuccess
                LR -->> LS: transferResult
                deactivate LR
                LS -->> TC: TransferResponse(txId, fromBalance, toBalance)
                deactivate LS
                TC -->> User: 200 OK (Chuyển tiền thành công, cả 2 ví cập nhật số dư)
            end
        end
        deactivate TC
    end

    %% Luồng Đảo giao dịch (Reversal)
    rect rgb(254, 243, 199)
        note over User, DB: 2. KỊCH BẢN ĐẢO GIAO DỊCH (TRANSACTION REVERSAL)
        User ->> TC: POST /api/transactions/{id}/reverse (reason)
        activate TC
        TC ->> LS: reverseTransaction(userId, txId, reason)
        activate LS
        LS ->> LR: findTransactionWithEntries(txId, userId)
        activate LR
        LR ->> DB: SELECT * FROM transactions WHERE id = ? AND user_id = ?
        DB -->> LR: originalTxRecord (status='POSTED', is_reversed=false)
        LR -->> LS: originalTxRecord
        deactivate LR

        alt Giao dịch không phải status Posted hoặc đã bị đảo trước đó
            LS -->> TC: Ném lỗi BadRequestException("Giao dịch không hợp lệ để đảo hoặc đã bị đảo")
            TC -->> User: 400 Bad Request
        else Hợp lệ -> Thực thi đảo nguyên tử
            LS ->> LR: executeReversalTransaction(originalTxRecord, reason)
            activate LR
            LR ->> DB: BEGIN TRANSACTION
            LR ->> DB: UPDATE transactions SET is_reversed = true WHERE id = originalTxId
            LR ->> DB: INSERT INTO transactions (type='REVERSAL', status='POSTED', original_id=originalTxId, note=reason)
            LR ->> DB: INSERT INTO journal_entries (đảo ngược chiều toàn bộ dòng Nợ/Có của bản gốc)
            LR ->> DB: UPDATE wallets SET balance = balance + originalAmount WHERE id = walletId (hoàn tiền)
            LR ->> DB: COMMIT TRANSACTION
            DB -->> LR: commitSuccess
            LR -->> LS: reversalResult
            deactivate LR
            LS -->> TC: ReversalResponse(reversalTxId, originalTxId, restoredBalance)
            deactivate LS
            TC -->> User: 200 OK (Giao dịch đảo thành công, số dư được hoàn trả đầy đủ)
        end
        deactivate TC
    end
```

---

## 4. Mã nguồn PlantUML Tương ứng

<details>
<summary><b>Nhấn để xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ03_Chuyen_Tien_Va_Dao_Giao_Dich
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Người dùng (User)" as User
participant "TransactionController" as TC
participant "LedgerService" as LS
participant "LedgerRepository" as LR
database "PostgreSQL Database" as DB

== Chuyển tiền nội bộ ==
User -> TC: POST /api/transactions/transfer (fromWallet, toWallet, amount)
activate TC
TC -> LS: transferBetweenWallets(userId, dto)
activate LS
LS -> LR: checkBalance(fromWallet, userId)
activate LR
LR -> DB: SELECT balance FROM wallets WHERE id = fromWallet
DB --> LR: balance
LR --> LS: balance
deactivate LR

alt Ví nguồn không đủ số dư
  LS --> TC: 422 Unprocessable Entity
  TC --> User: Thông báo ví nguồn không đủ tiền
else Đủ số dư
  LS -> LR: executeTransferTransaction(...)
  activate LR
  LR -> DB: BEGIN TRANSACTION
  LR -> DB: INSERT INTO transactions (type='TRANSFER', status='POSTED')
  LR -> DB: INSERT INTO journal_entries (Debit Dest / Credit Source)
  LR -> DB: UPDATE wallets SET balance = balance - amount (ví nguồn)
  LR -> DB: UPDATE wallets SET balance = balance + amount (ví đích)
  LR -> DB: COMMIT TRANSACTION
  DB --> LR: Success
  LR --> LS: transferResult
  deactivate LR
  LS --> TC: TransferResponse
  deactivate LS
  TC --> User: 200 OK (Chuyển tiền thành công)
  deactivate TC
end

== Đảo giao dịch (Reversal) ==
User -> TC: POST /api/transactions/{id}/reverse (reason)
activate TC
TC -> LS: reverseTransaction(userId, txId, reason)
activate LS
LS -> LR: findTransactionWithEntries(txId, userId)
activate LR
LR -> DB: SELECT * FROM transactions WHERE id = ?
DB --> LR: originalTxRecord
LR --> LS: originalTxRecord
deactivate LR

alt Giao dịch đã bị đảo hoặc không hợp lệ
  LS --> TC: 400 Bad Request
  TC --> User: Báo lỗi không thể đảo
else Hợp lệ
  LS -> LR: executeReversalTransaction(...)
  activate LR
  LR -> DB: BEGIN TRANSACTION
  LR -> DB: UPDATE transactions SET is_reversed = true WHERE id = originalId
  LR -> DB: INSERT INTO transactions (type='REVERSAL', original_id=originalId)
  LR -> DB: INSERT INTO journal_entries (đảo chiều Nợ/Có đối ứng)
  LR -> DB: UPDATE wallets SET balance = balance + amount (hoàn tiền)
  LR -> DB: COMMIT TRANSACTION
  DB --> LR: Success
  LR --> LS: reversalResult
  deactivate LR
  LS --> TC: ReversalResponse
  deactivate LS
  TC --> User: 200 OK (Đảo giao dịch thành công, tiền hoàn lại)
  deactivate TC
end
@enduml
```
</details>

---

## 5. Điều hướng Tài liệu
- **Kịch bản trước:** [SQ02: Ghi nhận Chi tiêu & Kiểm tra Ngân sách](./sq02_expense_budget.md)
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
- **Kịch bản kế tiếp:** [SQ04: Xử lý Giao dịch Định kỳ Tự động](./sq04_subscription_cron.md)
