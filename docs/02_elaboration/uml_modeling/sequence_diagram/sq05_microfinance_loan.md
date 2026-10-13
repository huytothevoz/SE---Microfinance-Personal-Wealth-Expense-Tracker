# SQ05: Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô

> Tham chiếu: [SRS.md](../../SRS.md) v2.1 | [sequence_diagram.md](../sequence_diagram.md) | [class_diagram.md](../class_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ05** |
| **Tên Kịch bản** | **Khởi tạo khoản vay vi mô (Borrowing) & Thanh toán trả nợ từng kỳ (Repayment)** |
| **Use Cases bao phủ** | `UC06` (Quản lý khoản vay và nghĩa vụ nợ vi mô), `UC07` (Ghi nhận thanh toán trả nợ vay) |
| **Quy tắc Nghiệp vụ (BR)** | `BR09` (Lịch trả nợ), `BR10` (Phân định Nợ vs Chi phí), `BR17` (Nguyên lý kế toán vi mô cho Vay mượn) |
| **Yêu cầu Chức năng (FR)** | `FR09.01` - `FR09.04`, `FR15.01` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa 2 nghiệp vụ đặc thù của Tài chính vi mô: (1) Khởi tạo khoản vay có nhận tiền vào ví -> Tăng tiền ví và tăng Nợ phải trả (Total Liabilities), tuyệt đối cấm hạch toán vào Thu nhập (BR17); (2) Thanh toán trả nợ từng kỳ -> Bóc tách nợ gốc (làm giảm nợ phải trả và giảm tiền ví) so với tiền lãi/phí (hạch toán vào Chi phí tài chính và giảm tiền ví), cập nhật dư nợ giảm dần, tự động tất toán `PAID_OFF` khi dư nợ $= 0$. |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **Người vay / User** | Actor | Người dùng tạo hồ sơ khoản vay hoặc thực hiện thanh toán trả nợ. |
| **LoanController** | Boundary | Endpoint tiếp nhận HTTP POST `/api/loans` và `/api/loans/{id}/repayments`. |
| **LoanService** | Control | Kiểm tra logic nghiệp vụ vi mô, bóc tách nợ gốc và tiền lãi. |
| **LoanRepository** | Entity / Persistence | Lưu trữ hồ sơ khoản vay, lịch sử trả nợ và cập nhật trạng thái `PAID_OFF`. |
| **PostgreSQL Database** | Storage | Thực thi các giao dịch hạch toán kép và lưu trữ bất biến sổ sách. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor User as Người vay / Người dùng
    participant LC as LoanController
    participant LoanS as LoanService
    participant LoanR as LoanRepository
    participant DB as PostgreSQL Database

    %% Luồng Tạo khoản vay
    rect rgb(245, 243, 255)
        note over User, DB: 1. KỊCH BẢN KHỞI TẠO KHOẢN VAY (BORROWING)
        User ->> LC: POST /api/loans (title, principalAmount, lender, startDate, dueDate, isDisbursed, walletId)
        activate LC
        LC ->> LoanS: createLoan(userId, dto)
        activate LoanS

        alt Có tích chọn Giải ngân vào ví (isDisbursed == true)
            LoanS ->> LoanR: createLoanWithDisbursement(loanData, walletId)
            activate LoanR
            LoanR ->> DB: BEGIN TRANSACTION
            LoanR ->> DB: INSERT INTO loans (user_id, title, principal_amount, remaining_principal, status='ACTIVE')
            LoanR ->> DB: INSERT INTO transactions (type='DISBURSEMENT', status='POSTED', amount=principalAmount)
            LoanR ->> DB: INSERT INTO journal_entries (type='DEBIT', account='ASSET_WALLET', amount=principalAmount)
            LoanR ->> DB: INSERT INTO journal_entries (type='CREDIT', account='LIABILITY_LOAN', amount=principalAmount)
            LoanR ->> DB: UPDATE wallets SET balance = balance + principalAmount WHERE id = walletId
            note over LoanR, DB: TUYỆT ĐỐI CẤM ghi nhận vào Thu nhập (BR17)
            LoanR ->> DB: COMMIT TRANSACTION
            DB -->> LoanR: commitSuccess
            LoanR -->> LoanS: loanRecord
            deactivate LoanR
        else Không giải ngân vào ví (ghi nhận nghĩa vụ nợ đã có trước)
            LoanS ->> LoanR: createLoanOnly(loanData)
            activate LoanR
            LoanR ->> DB: INSERT INTO loans (user_id, title, principal_amount, remaining_principal, status='ACTIVE')
            DB -->> LoanR: loanRecord
            LoanR -->> LoanS: loanRecord
            deactivate LoanR
        end

        LoanS -->> LC: LoanResponse(loanId, remainingPrincipal, status='ACTIVE')
        deactivate LoanS
        LC -->> User: 201 Created (Khoản vay tạo thành công, Liabilities cập nhật)
        deactivate LC
    end

    %% Luồng Thanh toán trả nợ
    rect rgb(224, 242, 254)
        note over User, DB: 2. KỊCH BẢN THANH TOÁN TRẢ NỢ VAY (LOAN REPAYMENT)
        User ->> LC: POST /api/loans/{id}/repayments (principalPaid, interestPaid, walletId, paymentDate)
        activate LC
        LC ->> LoanS: processRepayment(userId, loanId, dto)
        activate LoanS
        LoanS ->> LoanR: getLoanDetails(loanId, userId)
        activate LoanR
        LoanR ->> DB: SELECT * FROM loans WHERE id = ? AND user_id = ?
        DB -->> LoanR: loanRecord (remaining_principal = 10.000.000 VND, status='ACTIVE')
        LoanR -->> LoanS: loanRecord
        deactivate LoanR

        alt Tiền gốc trả > Dư nợ gốc còn lại (principalPaid > remaining_principal)
            LoanS -->> LC: Ném lỗi UnprocessableEntityException("Tiền gốc trả vượt quá số dư nợ còn lại")
            LC -->> User: 422 Unprocessable Entity
        else Kiểm tra số dư ví chi trả
            LoanS ->> LoanR: checkWalletBalance(walletId, userId)
            activate LoanR
            LoanR ->> DB: SELECT balance FROM wallets WHERE id = ?
            DB -->> LoanR: walletBalance (ví dụ: 5.000.000 VND)
            LoanR -->> LoanS: walletBalance
            deactivate LoanR

            alt walletBalance < (principalPaid + interestPaid)
                LoanS -->> LC: Ném lỗi UnprocessableEntityException("Ví thanh toán không đủ số dư để trả nợ")
                LC -->> User: 422 Unprocessable Entity
            else Hợp lệ -> Thực thi trả nợ nguyên tử
                LoanS ->> LoanR: executeRepayment(loanRecord, dto)
                activate LoanR
                LoanR ->> DB: BEGIN TRANSACTION
                LoanR ->> DB: INSERT INTO loan_repayments (loan_id, principal_paid, interest_paid, wallet_id, date)
                LoanR ->> DB: UPDATE loans SET remaining_principal = remaining_principal - principalPaid WHERE id = ?
                LoanR ->> DB: INSERT INTO journal_entries (type='DEBIT', account='LIABILITY_LOAN', amount=principalPaid)
                LoanR ->> DB: INSERT INTO journal_entries (type='DEBIT', account='EXPENSE_FINANCIAL_INTEREST', amount=interestPaid)
                LoanR ->> DB: INSERT INTO journal_entries (type='CREDIT', account='ASSET_WALLET', amount=(principalPaid + interestPaid))
                LoanR ->> DB: UPDATE wallets SET balance = balance - (principalPaid + interestPaid) WHERE id = walletId
                
                alt Dư nợ gốc còn lại = 0 (remaining_principal - principalPaid == 0)
                    LoanR ->> DB: UPDATE loans SET status = 'PAID_OFF' WHERE id = ?
                end

                LoanR ->> DB: COMMIT TRANSACTION
                DB -->> LoanR: commitSuccess
                LoanR -->> LoanS: repaymentResult
                deactivate LoanR

                LoanS -->> LC: RepaymentResponse(repaymentId, newRemainingPrincipal, loanStatus)
                deactivate LoanS
                LC -->> User: 200 OK (Trả nợ thành công, dư nợ giảm dần, chi phí chỉ tăng phần lãi)
            end
        end
        deactivate LC
    end
```

---

## 4. Mã nguồn PlantUML Tương ứng

<details>
<summary><b>Nhấn để xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ05_Khoi_Tao_Vay_Va_Tra_No_Vi_Mo
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Người vay (User)" as User
participant "LoanController" as LC
participant "LoanService" as LoanS
participant "LoanRepository" as LoanR
database "PostgreSQL Database" as DB

== Khởi tạo khoản vay mới ==
User -> LC: POST /api/loans (principalAmount, isDisbursed, walletId)
activate LC
LC -> LoanS: createLoan(userId, dto)
activate LoanS

alt Có giải ngân vào ví (isDisbursed == true)
  LoanS -> LoanR: createLoanWithDisbursement(...)
  activate LoanR
  LoanR -> DB: BEGIN TRANSACTION
  LoanR -> DB: INSERT INTO loans (remaining_principal = principal)
  LoanR -> DB: INSERT INTO journal_entries (Debit Wallet / Credit Liability)
  LoanR -> DB: UPDATE wallets SET balance = balance + principal
  note right: CẤM tính vào Thu nhập theo BR17
  LoanR -> DB: COMMIT TRANSACTION
  DB --> LoanR: Success
  LoanR --> LoanS: loanRecord
  deactivate LoanR
else Không giải ngân
  LoanS -> LoanR: createLoanOnly(...)
  activate LoanR
  LoanR -> DB: INSERT INTO loans (...)
  DB --> LoanR: loanRecord
  LoanR --> LoanS: loanRecord
  deactivate LoanR
end

LoanS --> LC: LoanResponse
deactivate LoanS
LC --> User: 201 Created (Khoản vay tạo thành công)
deactivate LC

== Thanh toán trả nợ từng kỳ ==
User -> LC: POST /api/loans/{id}/repayments (principalPaid, interestPaid, walletId)
activate LC
LC -> LoanS: processRepayment(userId, loanId, dto)
activate LoanS
LoanS -> LoanR: getLoanDetails(loanId, userId)
activate LoanR
LoanR -> DB: SELECT * FROM loans WHERE id = ?
DB --> LoanR: loanRecord
LoanR --> LoanS: loanRecord
deactivate LoanR

alt Tiền gốc trả > Dư nợ hoặc Ví thiếu tiền
  LoanS --> LC: 422 Unprocessable Entity
  LC --> User: Báo lỗi thanh toán không hợp lệ
else Hợp lệ
  LoanS -> LoanR: executeRepayment(...)
  activate LoanR
  LoanR -> DB: BEGIN TRANSACTION
  LoanR -> DB: INSERT INTO loan_repayments (...)
  LoanR -> DB: UPDATE loans SET remaining_principal = remaining_principal - principalPaid
  LoanR -> DB: UPDATE wallets SET balance = balance - (principalPaid + interestPaid)
  alt Dư nợ còn lại == 0
    LoanR -> DB: UPDATE loans SET status = 'PAID_OFF' WHERE id = ?
  end
  LoanR -> DB: COMMIT TRANSACTION
  DB --> LoanR: Success
  LoanR --> LoanS: repaymentResult
  deactivate LoanR
  LoanS --> LC: RepaymentResponse
  deactivate LoanS
  LC --> User: 200 OK (Trả nợ thành công, dư nợ cập nhật)
  deactivate LC
end
@enduml
```
</details>

---

## 5. Điều hướng Tài liệu
- **Kịch bản trước:** [SQ04: Xử lý Giao dịch Định kỳ Tự động](./sq04_subscription_cron.md)
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
- **Kịch bản kế tiếp:** [SQ06: Quản lý Vàng & Tổng hợp Net Worth Dashboard](./sq06_gold_networth.md)
