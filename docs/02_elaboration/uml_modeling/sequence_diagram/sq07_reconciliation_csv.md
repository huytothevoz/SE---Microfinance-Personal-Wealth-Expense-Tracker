# SƠ ĐỒ TUẦN TỰ SQ07: ĐỐI SOÁT SỔ CÁI & XUẤT DỮ LIỆU CSV AN TOÀN
## PHÂN HỆ BÁO CÁO & ĐỐI SOÁT (REPORTING & RECONCILIATION SUBSYSTEM)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG - KHOA CÔNG NGHỆ THÔNG TIN**  
- **Môn học:** Công nghệ phần mềm (Software Engineering)
- **Đề tài:** Hệ thống Quản lý Tài chính Cá nhân, Tài sản và Tài chính Vi mô (WealthFlow)
- **Nhóm / Lớp:** Voz / N3
- **Tài liệu tham chiếu:** [SRS.md](../../SRS.md) v2.1 (UC11) và [sequence_diagram.md](../sequence_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ07** |
| **Tên Kịch bản** | **Đối soát số dư sổ cái (Reconciliation) & Trích xuất tệp CSV an toàn chống Formula Injection** |
| **Use Cases bao phủ** | `UC11` (Báo cáo thống kê & Xuất dữ liệu CSV) |
| **Quy tắc Nghiệp vụ (BR)** | `BR01` (Kế toán kép), `BR12` (Đối soát số dư), `BR14` (Kiểm toán bảo mật), `BR18` (Chuẩn mã hóa UTF-8 BOM) |
| **Yêu cầu Chức năng (FR)** | `FR12.01` - `FR12.04`, `FR15.01` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa 2 luồng: (1) Tính năng đối soát số dư độc lập: so sánh số dư bộ đệm ví với tổng đại số các dòng Nợ/Có sổ cái tích lũy, phát hiện sai lệch và ghi Security Audit Log nếu có; (2) Xuất tệp CSV: duyệt các trường văn bản, tự động trung hòa các ký tự công thức nguy hiểm (`=, +, -, @`) bằng cách thêm dấu nháy đơn `'` ở đầu (FR12.04), gắn tiền tố UTF-8 BOM (`\uFEFF`) để Microsoft Excel hiển thị tiếng Việt chuẩn xác và truyền tải an toàn. |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **User** | Actor | Người dùng yêu cầu đối soát số dư ví hoặc ra lệnh xuất tệp CSV. |
| **ReportController** | Boundary | Endpoint tiếp nhận HTTP GET `/api/reports/reconcile` và `/api/reports/export/csv`. |
| **ReconciliationService** | Control | Tính toán đối soát, phát hiện độ lệch delta và ghi nhật ký kiểm toán. |
| **ExportService** | Control | Vệ sinh dữ liệu (Sanitization) chống injection, gắn BOM và đóng gói CSV Stream. |
| **LedgerRepository** | Entity / Persistence | Truy vấn tổng lũy kế các dòng JournalEntries và danh sách giao dịch. |
| **PostgreSQL Database** | Storage | Lưu trữ dữ liệu sổ cái bất biến và nhật ký kiểm toán hệ thống. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    actor User as Người dùng (User)
    participant RC as ReportController
    participant ReconS as ReconciliationService
    participant ExpS as ExportService
    participant LR as LedgerRepository
    participant DB as PostgreSQL Database

    %% Luồng Đối soát số dư
    rect rgb(245, 243, 255)
        note over User, DB: 1. KỊCH BẢN ĐỐI SOÁT SỐ DƯ SỔ CÁI (RECONCILIATION)
        User ->> RC: GET /api/reports/reconcile?walletId={id}
        activate RC
        RC ->> ReconS: reconcileWalletLedger(userId, walletId)
        activate ReconS

        ReconS ->> LR: getWalletCachedBalance(walletId, userId)
        activate LR
        LR ->> DB: SELECT balance FROM wallets WHERE id = ? AND user_id = ?
        DB -->> LR: cachedBalance (ví dụ: 10.500.000 VND)
        LR -->> ReconS: cachedBalance
        deactivate LR

        ReconS ->> LR: calculateCumulativeLedgerSum(walletId)
        activate LR
        LR ->> DB: SELECT COALESCE(SUM(CASE WHEN type='DEBIT' THEN amount ELSE -amount END), 0) FROM journal_entries WHERE wallet_id = ?
        DB -->> LR: ledgerSum (10.500.000 VND)
        LR -->> ReconS: ledgerSum
        deactivate LR

        ReconS ->> ReconS: compare(cachedBalance, ledgerSum)
        alt cachedBalance == ledgerSum (Khớp đúng 100%)
            ReconS -->> RC: ReconciliationResult(status='MATCHED', delta=0)
            RC -->> User: 200 OK (Xác nhận số dư sổ cái toàn vẹn tuyệt đối)
        else cachedBalance != ledgerSum (Sai lệch số liệu)
            ReconS ->> LR: logSecurityAuditAlert(walletId, delta)
            activate LR
            LR ->> DB: INSERT INTO audit_logs (level='ALERT', event='LEDGER_MISMATCH', ...)
            DB -->> LR: logged
            deactivate LR
            ReconS -->> RC: ReconciliationResult(status='MISMATCHED', delta=deltaValue)
            RC -->> User: 200 OK (Cảnh báo sai lệch số dư kèm mã kiểm toán)
        end
        deactivate ReconS
        deactivate RC
    end

    %% Luồng Xuất tệp CSV an toàn
    rect rgb(240, 253, 244)
        note over User, DB: 2. KỊCH BẢN XUẤT TỆP CSV AN TOÀN (CHỐNG INJECTION)
        User ->> RC: GET /api/reports/export/csv?from=2026-10-01&to=2026-10-31&walletId=1
        activate RC
        RC ->> ExpS: exportTransactionsToCSV(userId, filterDto)
        activate ExpS

        ExpS ->> LR: findTransactionsForExport(userId, filterDto)
        activate LR
        LR ->> DB: SELECT date, type, category_name, wallet_name, amount, currency, note FROM ...
        DB -->> LR: rawTransactionRows
        LR -->> ExpS: rawTransactionRows
        deactivate LR

        %% Khử ký tự công thức nguy hiểm
        loop Duyệt qua từng bản ghi và từng cột văn bản
            ExpS ->> ExpS: sanitizeCSVField(fieldValue)
            note over ExpS: Nếu ô văn bản bắt đầu bằng =, +, -, @<br/>tự động thêm dấu nháy đơn ' ở đầu để vô hiệu hóa mã lệnh Excel
        end

        ExpS ->> ExpS: buildCSVString(sanitizedRows)
        ExpS ->> ExpS: prependUTF8BOM("\uFEFF")
        note over ExpS: Gắn mã BOM để Microsoft Excel tự động hiển thị đúng font tiếng Việt có dấu

        ExpS -->> RC: CSVStreamBuffer(csvData, filename='transactions_202610.csv')
        deactivate ExpS
        RC -->> User: 200 OK (Content-Type: text/csv, Content-Disposition: attachment, Tải file an toàn)
        deactivate RC
    end
```

---

## 4. Mã nguồn PlantUML Tương ứng

<details>
<summary><b>Nhấn để xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ07_Doi_Soat_Va_Xuat_CSV
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Người dùng (User)" as User
participant "ReportController" as RC
participant "ReconciliationService" as ReconS
participant "ExportService" as ExpS
participant "LedgerRepository" as LR
database "PostgreSQL Database" as DB

== Đối soát số dư sổ cái ==
User -> RC: GET /api/reports/reconcile?walletId={id}
activate RC
RC -> ReconS: reconcileWalletLedger(userId, walletId)
activate ReconS
ReconS -> LR: getWalletCachedBalance(walletId, userId)
activate LR
LR -> DB: SELECT balance FROM wallets WHERE id = ?
DB --> LR: cachedBalance
LR --> ReconS: cachedBalance
deactivate LR

ReconS -> LR: calculateCumulativeLedgerSum(walletId)
activate LR
LR -> DB: SELECT SUM(debit - credit) FROM journal_entries WHERE ...
DB --> LR: ledgerSum
LR --> ReconS: ledgerSum
deactivate LR

alt cachedBalance == ledgerSum
  ReconS --> RC: Result(status='MATCHED', delta=0)
  RC --> User: 200 OK (Số dư sổ cái khớp đúng 100%)
else Sai lệch số dư
  ReconS -> LR: logAuditAlert(...)
  ReconS --> RC: Result(status='MISMATCHED', delta)
  RC --> User: 200 OK (Cảnh báo sai lệch số dư)
end
deactivate ReconS
deactivate RC

== Xuất tệp CSV an toàn ==
User -> RC: GET /api/reports/export/csv
activate RC
RC -> ExpS: exportTransactionsToCSV(userId, filters)
activate ExpS
ExpS -> LR: findTransactionsForExport(...)
activate LR
LR -> DB: SELECT ... FROM transactions WHERE ...
DB --> LR: rawRows
LR --> ExpS: rawRows
deactivate LR

ExpS -> ExpS: sanitizeCSVFields() [Khử =, +, -, @ chống Formula Injection]
ExpS -> ExpS: prependUTF8BOM(\uFEFF) [Hỗ trợ font tiếng Việt cho Excel]
ExpS --> RC: CSVFileBuffer
deactivate ExpS
RC --> User: 200 OK (Tải file CSV an toàn về trình duyệt)
deactivate RC
@enduml
```
</details>

---

## 5. Điều hướng Tài liệu
- **Kịch bản trước:** [SQ06: Quản lý Vàng & Tổng hợp Net Worth Dashboard](./sq06_gold_networth.md)
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
- **Kịch bản kế tiếp:** [SQ08: Quản trị Người dùng & Phát Thông báo](./sq08_admin_user_broadcast.md)
