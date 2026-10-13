# SƠ ĐỒ TUẦN TỰ SQ04: XỬ LÝ GIAO DỊCH ĐỊNH KỲ TỰ ĐỘNG (SUBSCRIPTION CRON)
## PHÂN HỆ TIẾN TRÌNH NỀN & ĐỊNH KỲ (SCHEDULED SERVICES & SUBSCRIPTIONS)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG - KHOA CÔNG NGHỆ THÔNG TIN**  
- **Môn học:** Công nghệ phần mềm (Software Engineering)
- **Đề tài:** Hệ thống Quản lý Tài chính Cá nhân, Tài sản và Tài chính Vi mô (WealthFlow)
- **Nhóm / Lớp:** Voz / N3
- **Tài liệu tham chiếu:** [SRS.md](../../SRS.md) v2.1 (UC04, SYS02) và [sequence_diagram.md](../sequence_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ04** |
| **Tên Kịch bản** | **Quét và xử lý tự động các khoản giao dịch định kỳ (Subscription) đến hạn bởi Scheduler** |
| **Use Cases bao phủ** | `UC04` (Thiết lập & Xử lý giao dịch định kỳ), `SYS02` (Tiến trình quét subscription nền) |
| **Quy tắc Nghiệp vụ (BR)** | `BR07` (Ngân sách chi tiêu), `BR09` (Tính toán ngày neo và lùi ngày cuối tháng) |
| **Yêu cầu Chức năng (FR)** | `FR06.01` - `FR06.03`, `FR15.01` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa tiến trình Cron tự động kích hoạt lúc 01:00 AM hàng ngày: quét danh sách Active đến hạn, kiểm tra cờ `isAutoPost` và số dư ví chi trả, tự động ghi sổ Posted hoặc tạo bản nháp Draft, đẩy thông báo chuông cho User, và tự động tính toán ngày đến hạn tiếp theo (đặc biệt lùi về ngày 28 cho tháng 2 nếu ngày neo là 29, 30, 31). |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **Bộ lập lịch (SYS02 Cron)** | Actor / System | Timer hệ thống kích hoạt worker theo lịch trình 01:00 AM hàng ngày. |
| **SubscriptionWorker** | Control | Điều phối tác vụ quét danh sách, kiểm tra điều kiện và tính toán chu kỳ tiếp theo. |
| **LedgerService** | Control | Thực thi tạo giao dịch chi phí tự động khi bật chế độ Auto-Post. |
| **LedgerRepository** | Entity / Persistence | Truy vấn các khoản Subscription đến hạn và lưu cập nhật ngày đến hạn. |
| **NotificationService** | Control | Đẩy thông báo thành công hoặc cảnh báo thiếu tiền vào Notification Center. |
| **PostgreSQL Database** | Storage | Lưu trữ các bản ghi Transactions, Subscriptions và Notifications. |
| **User** | Actor | Người dùng nhận thông báo trên chuông thông báo của ứng dụng. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    participant Sch as Bộ lập lịch nền (SYS02 Cron)
    participant SW as SubscriptionWorker
    participant LS as LedgerService
    participant LR as LedgerRepository
    participant NS as NotificationService
    participant DB as PostgreSQL Database
    actor User as Người dùng (User)

    Sch ->> SW: triggerDueSubscriptionsJob() (Kích hoạt 01:00 AM)
    activate SW
    SW ->> LR: findDueSubscriptions(currentDate)
    activate LR
    LR ->> DB: SELECT * FROM subscriptions WHERE status = 'ACTIVE' AND next_due_date <= CURRENT_DATE
    DB -->> LR: dueSubscriptionsList (nhiều bản ghi)
    LR -->> SW: dueSubscriptionsList
    deactivate LR

    loop Với từng khoản Subscription đến hạn
        alt Chế độ Tự động ghi sổ bật (isAutoPost == true)
            SW ->> LR: checkWalletBalance(sub.walletId)
            activate LR
            LR ->> DB: SELECT balance FROM wallets WHERE id = ?
            DB -->> LR: balance
            LR -->> SW: balance
            deactivate LR

            alt Số dư ví >= sub.amount (Đủ tiền)
                SW ->> LS: autoPostSubscriptionTransaction(sub)
                activate LS
                LS ->> LR: executeExpenseTransaction(subData)
                activate LR
                LR ->> DB: BEGIN TRANSACTION
                LR ->> DB: INSERT INTO transactions (type='EXPENSE', status='POSTED', sub_id=sub.id)
                LR ->> DB: INSERT INTO journal_entries (...)
                LR ->> DB: UPDATE wallets SET balance = balance - sub.amount
                LR ->> DB: COMMIT TRANSACTION
                DB -->> LR: Success
                LR -->> LS: postedTx
                deactivate LR
                LS -->> SW: postedTx
                deactivate LS

                SW ->> NS: notifyAutoPostSuccess(sub.userId, sub.name, sub.amount)
                activate NS
                NS ->> DB: INSERT INTO notifications (...)
                NS -->> User: Gửi thông báo "Đã tự động thanh toán khoản định kỳ"
                deactivate NS
            else Số dư ví < sub.amount (Thiếu tiền)
                SW ->> LR: createDraftTransaction(sub)
                activate LR
                LR ->> DB: INSERT INTO transactions (type='EXPENSE', status='DRAFT', sub_id=sub.id)
                DB -->> LR: draftTx
                LR -->> SW: draftTx
                deactivate LR

                SW ->> NS: notifyInsufficientFunds(sub.userId, sub.name)
                activate NS
                NS ->> DB: INSERT INTO notifications (title='Cảnh báo ví thiếu tiền', ...)
                NS -->> User: Gửi cảnh báo "Ví thiếu tiền thanh toán khoản định kỳ"
                deactivate NS
            end

        else Chế độ Nhắc nhở thủ công (isAutoPost == false)
            SW ->> LR: createDraftTransaction(sub)
            activate LR
            LR ->> DB: INSERT INTO transactions (type='EXPENSE', status='DRAFT', sub_id=sub.id)
            DB -->> LR: draftTx
            LR -->> SW: draftTx
            deactivate LR

            SW ->> NS: notifySubscriptionDue(sub.userId, sub.name)
            activate NS
            NS ->> DB: INSERT INTO notifications (title='Khoản định kỳ đến hạn', ...)
            NS -->> User: Gửi thông báo nhắc nhở vào duyệt thanh toán
            deactivate NS
        end

        %% Tính ngày đến hạn kế tiếp (Quy tắc tháng thiếu ngày)
        SW ->> SW: calculateNextDueDate(sub.anchorDay, currentMonth + 1)
        note over SW: Ví dụ: anchorDay = 31, tháng tiếp theo là tháng 2 (28 ngày)<br/>Hệ thống tự động lùi về ngày 28/02 và giữ nguyên anchorDay = 31 cho tháng 3
        SW ->> LR: updateNextDueDate(sub.id, calculatedNextDueDate)
        activate LR
        LR ->> DB: UPDATE subscriptions SET next_due_date = ?, last_executed_at = NOW() WHERE id = ?
        DB -->> LR: Success
        LR -->> SW: updated
        deactivate LR
    end

    SW -->> Sch: JobCompleted (Số lượng xử lý: N thành công)
    deactivate SW
```

---

## 4. Mã nguồn PlantUML Tương ứng

<details>
<summary><b>Nhấn để xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ04_Xu_Ly_Giao_Dich_Dinh_Ky
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

participant "Bộ lập lịch (SYS02)" as Sch
participant "SubscriptionWorker" as SW
participant "LedgerService" as LS
participant "LedgerRepository" as LR
participant "NotificationService" as NS
database "PostgreSQL Database" as DB
actor "Người dùng (User)" as User

Sch -> SW: triggerDueSubscriptionsJob() (01:00 AM)
activate SW
SW -> LR: findDueSubscriptions(currentDate)
activate LR
LR -> DB: SELECT * FROM subscriptions WHERE status='ACTIVE' AND next_due_date <= CURRENT_DATE
DB --> LR: dueList
LR --> SW: dueList
deactivate LR

loop Với từng khoản định kỳ đến hạn
  alt isAutoPost == true và Ví đủ tiền
    SW -> LS: autoPostSubscriptionTransaction(sub)
    activate LS
    LS -> LR: executeExpenseTransaction(...)
    activate LR
    LR -> DB: BEGIN TRANSACTION
    LR -> DB: INSERT INTO transactions (status='POSTED')
    LR -> DB: UPDATE wallets SET balance = balance - amount
    LR -> DB: COMMIT TRANSACTION
    DB --> LR: Success
    LR --> LS: postedTx
    deactivate LR
    LS --> SW: postedTx
    deactivate LS
    SW -> NS: notifyAutoPostSuccess(...)
    activate NS
    NS -> DB: INSERT INTO notifications (...)
    NS --> User: Thông báo thanh toán thành công
    deactivate NS
  else isAutoPost == false hoặc Ví thiếu tiền
    SW -> LR: createDraftTransaction(sub)
    activate LR
    LR -> DB: INSERT INTO transactions (status='DRAFT')
    DB --> LR: draftTx
    LR --> SW: draftTx
    deactivate LR
    SW -> NS: notifyReminder(...)
    activate NS
    NS -> DB: INSERT INTO notifications (...)
    NS --> User: Gửi nhắc nhở duyệt hoặc cảnh báo thiếu tiền
    deactivate NS
  end

  SW -> SW: calculateNextDueDate(anchorDay) [Xử lý tháng 2 lùi về ngày 28]
  SW -> LR: updateNextDueDate(sub.id, nextDueDate)
  activate LR
  LR -> DB: UPDATE subscriptions SET next_due_date = ?
  DB --> LR: Success
  LR --> SW: updated
  deactivate LR
end

SW --> Sch: JobCompleted
deactivate SW
@enduml
```
</details>

---

## 5. Điều hướng Tài liệu
- **Kịch bản trước:** [SQ03: Chuyển tiền Nội bộ & Đảo Giao dịch](./sq03_transfer_reversal.md)
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
- **Kịch bản kế tiếp:** [SQ05: Khởi tạo Khoản vay & Trả nợ Vi mô](./sq05_microfinance_loan.md)
