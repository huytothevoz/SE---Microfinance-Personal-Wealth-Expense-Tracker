# ĐẶC TẢ KỊCH BẢN KIỂM THỬ CHI TIẾT (TEST SPECIFICATION)
## Phân Hệ: FR12 - Tra Cứu Lịch Sử, Đối Soát Sổ Cái & Xuất Tệp CSV An Toàn
## Kiến Trúc: Đa Tầng Theo Thiết Kế BCE+D | Chuẩn UP/RUP Pha Construction

---

## I. THÔNG TIN CHUNG
* **Mã Phân Hệ:** `FR12`
* **Tên Chức Năng:** Tra cứu lịch sử, đối soát số dư ví với sổ cái kép và xuất tệp CSV an toàn (Reconciliation & Secure CSV Export)
* **Sprint Triển Khai:** `Sprint 3`
* **Người Phụ Trách (Developer):** Võ Văn Khôi Nguyên
* **Người Kiểm Duyệt (Reviewer):** Nguyễn Thành Trung
* **Use Case Tương Ứng:** `UC11` (Báo cáo thống kê, đối soát số dư và xuất dữ liệu CSV)
* **Sơ Đồ Lớp & Tuần Tự:** [CD04 Reconciliation & CSV](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/docs/02_elaboration/uml_modeling/class_diagram.md#24-phân-hệ-4-báo-cáo-đối-soát-sổ-cái--xuất-csv-an-toàn-ledger-reconciliation--secure-csv-export-context---uc11), [SQ07 Sequence](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/docs/02_elaboration/uml_modeling/sequence_diagram.md#37-sq07-báo-cáo-đối-soát-sổ-cái--xuất-dữ-liệu-csv-an-toàn)
* **Quy Tắc Nghiệp Vụ:** 
  * `BR01`: Sổ cái kép bất biến $\sum \text{Debit} = \sum \text{Credit}$.
  * `BR11`: Phân quyền & Cô lập dữ liệu cá nhân (Multi-tenant).
  * `BR12`: Đối soát số dư độc lập giữa bộ đệm `cached_balance` và tổng đại số $\sum (\text{Debit} - \text{Credit})$ từ `ledger_entries`.
  * `BR14`: Tách biệt giao dịch nháp (chỉ tính giao dịch `POSTED`, loại trừ `DRAFT`).
  * `BR18`: Chuẩn xuất CSV an toàn: Chống Formula Injection (OWASP), mã hóa UTF-8 BOM (`\xEF\xBB\xBF`) và lưu vết kiểm toán SHA-256.
* **Tài Liệu Căn Cứ:** [SRS.md v2.1](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/docs/02_elaboration/SRS.md), [api_design.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/docs/02_elaboration/api_design.md)
* **Công Cụ Tự Động Hóa:** Python 3.13 (`pytest`), PHP 8.2 CLI, SQLite In-Memory.

---

## II. MA TRẬN 24 KỊCH BẢN KIỂM THỬ THEO MÔ HÌNH BCE+D

| STT | Mã Ca Test | Tầng Kiến Trúc | Tên Kịch Bản Kiểm Thử | Trọng Số | Loại Kịch Bản |
| :---: | :---: | :---: | :--- | :---: | :---: |
| 1 | **TC-D12.01** | **Data (D)** | Tính tổng đại số Nợ/Có của ví (`ReportDAO::getWalletLedgerSum`) từ các bút toán `POSTED` | P1 | Positive / Query |
| 2 | **TC-D12.02** | **Data (D)** | Tách biệt giao dịch nháp (`DRAFT`): không cộng dồn vào tổng số dư sổ cái ví (BR14) | P1 | Boundary / Filter |
| 3 | **TC-D12.03** | **Data (D)** | Truy vấn số phát sinh Bảng cân đối thử (`ReportDAO::getTrialBalanceRows`) theo từng tài khoản | P1 | Positive / Aggregate |
| 4 | **TC-D12.04** | **Data (D)** | Lọc giao dịch xuất CSV (`ReportDAO::getTransactionsForExport`) theo khoảng ngày và ví | P1 | Filter / Query |
| 5 | **TC-D12.05** | **Data (D)** | Ghi nhận nhật ký kiểm toán tệp xuất (`AuditLogDAO::logExportAudit`) vào `export_audit_logs` | P1 | Audit Trail |
| 6 | **TC-D12.06** | **Data (D)** | Ghi nhận cảnh báo an ninh (`AuditLogDAO::logSecurityEvent`) vào `security_audit_logs` | P1 | Security Log |
| 7 | **TC-C12.01** | **Control (C)** | Đối soát ví thành công khi số dư đệm khớp hoàn toàn với sổ cái ($\Delta = 0 \to$ `MATCHED`) | P1 | Happy Path |
| 8 | **TC-C12.02** | **Control (C)** | Phát hiện sai lệch số dư ví ($\Delta \ne 0 \to$ `MISMATCH`) và kích hoạt cảnh báo an ninh | P1 | Security Alert |
| 9 | **TC-C12.03** | **Control (C)** | Đối soát tự động toàn bộ ví người dùng (`ReconciliationService::reconcileAllWallets`) | P1 | Batch Audit |
| 10 | **TC-C12.04** | **Control (C)** | Bảng cân đối thử xác nhận cân bằng kế toán kép toàn vẹn $\sum \text{Debit} = \sum \text{Credit}$ | P1 | Accounting Equilibrium |
| 11 | **TC-C12.05** | **Control (C)** | Khử độc CSV Formula Injection: Tự động chèn dấu nháy đơn `'` trước các ký tự nguy hiểm | P1 | OWASP Security |
| 12 | **TC-C12.06** | **Control (C)** | Chuẩn hóa RFC 4180: Escape dấu nháy kép `""` và bao bọc trường chứa dấu phẩy / xuống dòng | P2 | Encoding RFC 4180 |
| 13 | **TC-C12.07** | **Control (C)** | Gắn tiền tố mã hóa UTF-8 BOM (`\xEF\xBB\xBF`) và tính mã băm SHA-256 tệp CSV xuất khẩu | P1 | Format & Hash |
| 14 | **TC-B12.01** | **Boundary (API)** | `GET /reports/reconcile?walletId=x` - Đối soát 1 ví cụ thể thành công (`200 OK`) | P1 | API Happy Path |
| 15 | **TC-B12.02** | **Boundary (API)** | `GET /reports/reconcile?walletId=999` - Ví không tồn tại từ chối với lỗi (`404 NOT_FOUND`) | P1 | Negative / Error |
| 16 | **TC-B12.03** | **Boundary (API)** | `GET /reports/reconcile` - Đối soát toàn bộ ví trả về tổng hợp và danh sách (`200 OK`) | P1 | API Batch List |
| 17 | **TC-B12.04** | **Boundary (API)** | `GET /reports/trial-balance` - Truy vấn bảng cân đối số phát sinh thử nghiệm (`200 OK`) | P1 | API Equilibrium |
| 18 | **TC-B12.05** | **Boundary (API)** | `GET /reports/export/csv` - Tải xuống tệp CSV kèm headers chuẩn và SHA-256 (`200 OK`) | P1 | File Stream |
| 19 | **TC-B12.06** | **Boundary (API)** | Multi-tenant Isolation: User A không thể đối soát ví hoặc trích xuất giao dịch của User B | P1 | Security Isolation |
| 20 | **TC-UI12.01** | **Boundary (UI)** | Kiểm tra cấu trúc DOM trang Đối soát (`reconciliation.html`) và 4 thẻ KPI cards | P2 | UI Structural Contract |
| 21 | **TC-UI12.02** | **Boundary (UI)** | Kiểm tra logic render badge trạng thái `Khớp sổ 100%` (green) hoặc `Lệch số dư` (red) | P1 | UI Logic Verification |
| 22 | **TC-UI12.03** | **Boundary (UI)** | Kiểm tra Regex bóc tách `filename` từ header `Content-Disposition` trong `report-api.js` | P1 | Filename Extraction |
| 23 | **TC-UI12.04** | **Boundary (UI)** | Cơ chế fallback tạo tên tệp ngẫu nhiên khi header server thiếu tham số `filename` | P2 | Fallback Resilience |
| 24 | **TC-UI12.05** | **Boundary (UI)** | Kiểm tra kích hoạt tải tệp nhị phân Blob và dọn dẹp `URL.revokeObjectURL` chống rò rỉ RAM | P1 | Blob & Memory Cleanup |

---

## III. ĐẶC TẢ CHI TIẾT TỪNG KỊCH BẢN KIỂM THỬ

### TẦNG D: DATA ACCESS & DATABASE QUERIES (6 Kịch Bản)

#### 1. TC-D12.01: Tính tổng đại số Nợ/Có của ví từ các bút toán `POSTED`
* **Mục tiêu:** Thẩm định hàm `ReportDAO::getWalletLedgerSum` tính toán chính xác tổng lũy kế Nợ trừ Có (`DEBIT - CREDIT`) đối với các dòng sổ cái kế toán của tài khoản ví.
* **Tiền điều kiện:** Ví `wallet_id = 1` của `user_id = 1` có 3 giao dịch `POSTED`: Mở sổ (+10.000.000 VND), Chi tiêu (-2.000.000 VND), Nhận tiền (+5.000.000 VND).
* **Kỳ vọng:** `getWalletLedgerSum(1, 1)` trả về chính xác chuỗi `"13000000.00000000"`.

#### 2. TC-D12.02: Tách biệt giao dịch nháp (`DRAFT`) khỏi tổng sổ cái ví
* **Mục tiêu:** Đảm bảo các giao dịch ở trạng thái `DRAFT` không bao giờ được cộng dồn vào số dư sổ cái theo BR14.
* **Tiền điều kiện:** Chèn thêm 1 giao dịch `DRAFT` số tiền 1.000.000 VND gắn với `wallet_id = 1`.
* **Kỳ vọng:** `getWalletLedgerSum(1, 1)` vẫn giữ nguyên giá trị `"13000000.00000000"`, hoàn toàn bỏ qua bản ghi nháp.

#### 3. TC-D12.03: Truy vấn số phát sinh Bảng cân đối thử (`ReportDAO::getTrialBalanceRows`)
* **Mục tiêu:** Kiểm tra truy vấn gom nhóm theo tài khoản kế toán, tính tổng Debit và Credit bằng tiền cơ sở (VND).
* **Kỳ vọng:** Trả về danh sách tài khoản kế toán của user, mỗi dòng có `total_debit >= 0`, `total_credit >= 0`.

#### 4. TC-D12.04: Lọc giao dịch xuất CSV (`ReportDAO::getTransactionsForExport`)
* **Mục tiêu:** Kiểm tra khả năng trích xuất giao dịch kèm thông tin danh mục, ví nguồn/đích và lọc theo `walletId`, `startDate`, `endDate`.
* **Kỳ vọng:** Trả về danh sách bản ghi thỏa mãn điều kiện lọc, sắp xếp theo thời gian giảm dần, không vượt quá 10.000 dòng.

#### 5. TC-D12.05: Ghi nhận nhật ký kiểm toán tệp xuất (`AuditLogDAO::logExportAudit`)
* **Mục tiêu:** Kiểm tra lưu vết tệp xuất vào bảng `export_audit_logs`.
* **Kỳ vọng:** Thao tác chèn thành công, trả về `id > 0`; truy vấn lại kiểm tra đúng `user_id`, `export_type = 'TRANSACTIONS_CSV'`, `row_count`, `file_hash`, `ip_address`.

#### 6. TC-D12.06: Ghi nhận cảnh báo an ninh (`AuditLogDAO::logSecurityEvent`)
* **Mục tiêu:** Kiểm tra lưu vết cảnh báo sai lệch số dư vào bảng `security_audit_logs`.
* **Kỳ vọng:** Thao tác chèn thành công với sự kiện `LEDGER_MISMATCH_WALLET_x`.

---

### TẦNG C: CONTROL & BUSINESS LOGIC (7 Kịch Bản)

#### 7. TC-C12.01: Đối soát ví thành công khi số dư đệm khớp hoàn toàn ($\Delta = 0$)
* **Mục tiêu:** Xác minh khi `cached_balance == ledger_sum`, hệ thống trả về trạng thái `MATCHED`.
* **Kỳ vọng:** `status === 'MATCHED'`, `delta === '0.00000000'`, `isAudited === true`. Không phát sinh log cảnh báo an ninh.

#### 8. TC-C12.02: Phát hiện sai lệch số dư ví ($\Delta \ne 0$) và kích hoạt cảnh báo an ninh
* **Mục tiêu:** Giả lập ví có `cached_balance = 9,999,999` nhưng `ledger_sum = 5,000,000`.
* **Kỳ vọng:** `status === 'MISMATCH'`, `delta === '4999999.00000000'`, `isAudited === false`. Hệ thống tự động ghi 1 bản ghi `LEDGER_MISMATCH_WALLET_x` vào bảng `security_audit_logs`.

#### 9. TC-C12.03: Đối soát tự động toàn bộ ví người dùng (`reconcileAllWallets`)
* **Mục tiêu:** Kiểm tra đối soát hàng loạt trên tất cả các ví đang hoạt động (`ACTIVE`).
* **Kỳ vọng:** Trả về tổng số ví, số ví khớp, số ví lệch, cờ `isAllMatched`, và danh sách đối soát chi tiết của từng ví.

#### 10. TC-C12.04: Bảng cân đối thử xác nhận cân bằng kế toán kép ($\sum \text{Debit} = \sum \text{Credit}$)
* **Mục tiêu:** Xác minh tính cân bằng toàn cục của sổ cái kế toán kép theo BR01.
* **Kỳ vọng:** `totalDebit === totalCredit`, `equilibriumDiff === '0.00000000'`, `isBalanced === true`.

#### 11. TC-C12.05: Khử độc CSV Formula Injection (FR12.04, OWASP)
* **Mục tiêu:** Kiểm tra hàm `sanitizeCsvField` tự động thêm tiền tố nháy đơn `'` trước các ký tự độc hại:
  * `=cmd|' /C calc'!A0` $\to$ `'=cmd|' /C calc'!A0`
  * `+123456` $\to$ `'+123456`
  * `-999999` $\to$ `'-999999`
  * `@SUM(1,2)` $\to$ `'@SUM(1,2)`
  * `\t=1+1` $\to$ `'\t=1+1`
* **Kỳ vọng:** Ngăn chặn triệt để nguy cơ thực thi mã macro / DDE khi mở bằng Excel.

#### 12. TC-C12.06: Chuẩn hóa RFC 4180 cho tệp CSV
* **Mục tiêu:** Kiểm tra các trường chứa dấu phẩy `,`, dấu nháy kép `"` hoặc ký tự xuống dòng `\n` được escape đúng chuẩn.
* **Kỳ vọng:** Trường có `"` chuyển thành `""` và toàn bộ trường được bao bọc trong dấu nháy kép.

#### 13. TC-C12.07: Gắn tiền tố mã hóa UTF-8 BOM và tính mã băm SHA-256
* **Mục tiêu:** Kiểm tra tệp CSV xuất ra có gắn 3 byte BOM `\xEF\xBB\xBF` ở đầu stream và tính đúng SHA-256 hash.
* **Kỳ vọng:** 3 byte đầu tiên là `\xEF\xBB\xBF`; mã băm SHA-256 khớp với nội dung stream.

---

### TẦNG B: BOUNDARY REST API (6 Kịch Bản)

#### 14. TC-B12.01: `GET /reports/reconcile?walletId=x` - Đối soát 1 ví thành công
* **Mục tiêu:** Tiếp nhận HTTP Request và trả về JSON kết quả đối soát của 1 ví.
* **Kỳ vọng:** HTTP Status `200 OK`, JSON có trường `reconciliation.status = 'MATCHED'`.

#### 15. TC-B12.02: `GET /reports/reconcile?walletId=999` - Ví không tồn tại
* **Mục tiêu:** Yêu cầu đối soát ví không tồn tại hoặc không thuộc sở hữu của user.
* **Kỳ vọng:** HTTP Status `404 Not Found`, mã lỗi `WALLET_NOT_FOUND`.

#### 16. TC-B12.03: `GET /reports/reconcile` - Đối soát toàn bộ ví
* **Mục tiêu:** Yêu cầu đối soát toàn bộ ví khi không truyền query param `walletId`.
* **Kỳ vọng:** HTTP Status `200 OK`, JSON có `totalWallets`, `matchedWallets`, `wallets`.

#### 17. TC-B12.04: `GET /reports/trial-balance` - Bảng cân đối số phát sinh
* **Mục tiêu:** Kiểm tra endpoint trả về bảng cân đối thử.
* **Kỳ vọng:** HTTP Status `200 OK`, JSON có `trialBalance.isBalanced = true`.

#### 18. TC-B12.05: `GET /reports/export/csv` - Tải xuống tệp CSV
* **Mục tiêu:** Kiểm tra luồng streaming CSV với đầy đủ headers an toàn.
* **Kỳ vọng:** Headers gồm `Content-Type: text/csv; charset=utf-8`, `Content-Disposition: attachment; filename=...`, `X-Content-SHA256`, `X-Content-Row-Count`. Body chứa UTF-8 BOM.

#### 19. TC-B12.06: Multi-tenant Isolation
* **Mục tiêu:** User A gửi request đối soát ví của User B.
* **Kỳ vọng:** Bị chặn với lỗi `404 Not Found` (không làm lộ sự tồn tại của ví User B). Khi xuất CSV, file chỉ chứa giao dịch của User A.

---

### TẦNG B: BOUNDARY FRONTEND UI (5 Kịch Bản)

#### 20. TC-UI12.01: Cấu trúc DOM trang Đối soát (`reconciliation.html`)
* **Mục tiêu:** Kiểm tra file view `reconciliation.html` chứa đầy đủ các phần tử tương tác:
  * Nút `data-action="export"` (Tải Xuất CSV).
  * Nút `data-action="reconcile"` (Kiểm tra đối soát).
  * Thẻ hiển thị `#recon-tracked-wallets`, `#recon-matched-wallets`, `#recon-posted-count`.
  * Bảng dữ liệu `#recon-table-body`.
* **Kỳ vọng:** Toàn bộ selector DOM tồn tại và có thuộc tính accessibility chuẩn.

#### 21. TC-UI12.02: Logic render Badge trạng thái trong `reports.js`
* **Mục tiêu:** Kiểm tra hàm `updateReconciliationDOM` gán đúng class badge:
  * Ví khớp: badge `green` kèm chữ `"Khớp sổ 100%"`.
  * Ví lệch: badge `red` kèm chữ `"Lệch số dư"`.
* **Kỳ vọng:** Đảm bảo phản hồi trực quan chính xác cho người dùng.

#### 22. TC-UI12.03: Bóc tách `filename` từ Header `Content-Disposition`
* **Mục tiêu:** Kiểm tra regex `/filename="?([^";]+)"?/` trong `report-api.js` trích xuất chuẩn tên file khi có hoặc không có dấu nháy kép.
* **Kỳ vọng:** Lấy đúng `wealthflow_transactions_20261024.csv`.

#### 23. TC-UI12.04: Cơ chế Fallback tên file ngẫu nhiên
* **Mục tiêu:** Kiểm tra khi server không trả header `Content-Disposition` hoặc header rỗng.
* **Kỳ vọng:** Tên file fallback về định dạng `wealthflow_transactions_<timestamp>.csv`.

#### 24. TC-UI12.05: Quản lý vòng đời Blob & Dọn dẹp bộ nhớ RAM
* **Mục tiêu:** Kiểm tra quy trình kích hoạt thẻ `<a>` ảo và gọi `URL.revokeObjectURL` để giải phóng bộ nhớ.
* **Kỳ vọng:** Thẻ `<a>` được click và xóa khỏi DOM; `URL.revokeObjectURL` được gọi chính xác.
