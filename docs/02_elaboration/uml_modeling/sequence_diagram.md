# TÀI LIỆU MÔ HÌNH HÓA SƠ ĐỒ TUẦN TỰ (SEQUENCE DIAGRAMS)
## HỆ THỐNG QUẢN LÝ TÀI CHÍNH CÁ NHÂN, TÀI SẢN VÀ TÀI CHÍNH VI MÔ (WEALTHFLOW)

**TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG**  
**KHOA CÔNG NGHỆ THÔNG TIN**  

- **Môn học:** Công nghệ phần mềm (Software Engineering)
- **Nhóm / Lớp:** Voz / N3
- **Giảng viên hướng dẫn:** ThS. Võ Thị Kim Anh
- **Thành viên nhóm:**
  - Võ Trịnh Quốc Huy
  - Võ Văn Khôi Nguyên
  - Nguyễn Thành Trung
  - Phan Nguyễn Tuấn Kiệt
- **Pha tiến trình:** Elaboration (Pha Tinh chế / Phân tích chi tiết)
- **Tài liệu tham chiếu:** [docs/02_elaboration/SRS.md](../SRS.md) v2.1, [usecase_diagram.md](./usecase_diagram.md), [activity_diagram.md](./activity_diagram.md)

---

## 1. Giới thiệu và Quy ước Mô hình hóa Tuần tự (Sequence Modeling Guidelines)

### 1.1 Vai trò của Sequence Diagram trong Pha Elaboration (UP / RUP)
Theo phương pháp luận Unified Process (Ian Sommerville - Chương 5: System Modeling), **Sequence Diagram (Sơ đồ Tuần tự)** là cầu nối trực tiếp giữa Pha Phân tích Yêu cầu (Analysis) và Pha Thiết kế Kiến trúc (Design):
1. **Hiện thực hóa Use Case (Use Case Realization):** Mô hình hóa cụ thể cách các đối tượng phần mềm tương tác với nhau để hoàn thành kịch bản của từng Use Case theo trục thời gian (từ trên xuống dưới).
2. **Phân bổ trách nhiệm theo mô hình BCE (Boundary - Control - Entity):**
   - **Boundary (Biên giới):** Tiếp nhận tương tác từ Actor, kiểm tra hợp lệ sơ bộ tại Client hoặc API Controller (`View`, `Controller`).
   - **Control (Điều khiển):** Chứa các quy tắc nghiệp vụ (Business Rules), điều phối luồng xử lý và tính toán (`Service`, `Engine`).
   - **Entity / Persistence (Thực thể & Dữ liệu):** Lưu trữ trạng thái nghiệp vụ và giao tiếp trực tiếp với cơ sở dữ liệu (`Repository`, `Database`).
3. **Làm rõ ranh giới giao dịch ACID:** Chỉ định rõ thời điểm mở giao dịch cơ sở dữ liệu (Transaction Begin), thời điểm xác nhận (Commit), và thời điểm hoàn tác (Rollback) khi phát sinh lỗi.

### 1.2 Bảng quy ước ký hiệu thông điệp chuẩn UML 2.5

| Thành phần | Cú pháp Mermaid | Cú pháp PlantUML | Ý nghĩa ngữ nghĩa |
| :--- | :---: | :---: | :--- |
| **Thông điệp đồng bộ (Synchronous Call)** | `->>` | `->` | Lời gọi hàm hoặc HTTP request đồng bộ (bên gọi đợi phản hồi). |
| **Thông điệp không đồng bộ (Asynchronous)** | `--)` | `->>` | Lời gọi bất đồng bộ, sự kiện nền hoặc đẩy thông báo hàng đợi. |
| **Thông điệp phản hồi (Reply / Return)** | `-->>` | `-->` | Kết quả trả về sau khi phương thức hoặc truy vấn hoàn tất. |
| **Kích hoạt thực thi (Activation Box)** | `activate / deactivate` | `activate / deactivate` | Khoảng thời gian đối tượng đang trực tiếp chiếm quyền xử lý. |
| **Khối rẽ nhánh điều kiện (Alternative)** | `alt ... else ... end` | `alt ... else ... end` | Rẽ nhánh điều kiện logic tương ứng với Guard Conditions. |
| **Khối tùy chọn (Optional)** | `opt ... end` | `opt ... end` | Hành động chỉ thực thi khi điều kiện thỏa mãn. |
| **Khối lặp (Loop)** | `loop ... end` | `loop ... end` | Lặp lại thao tác xử lý trên danh sách bản ghi. |

---

## 2. Danh mục 08 Sơ đồ Tuần tự Trọng yếu (Sequence Catalog)

| Mã Sơ đồ | Tên Kịch bản Tuần tự | Use Cases bao phủ | Các Đối tượng Lifeline tham gia |
| :---: | :--- | :---: | :--- |
| **SQ01** | Đăng ký tài khoản, Mã hóa mật khẩu & Đăng nhập cấp JWT | `UC01`, `UC02` | `User`, `AuthController`, `AuthService`, `UserRepository`, `Database` |
| **SQ02** | Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm tra Ngân sách *(Lõi)* | `UC03`, `UC05` | `User`, `TransactionController`, `LedgerService`, `BudgetService`, `LedgerRepository`, `Database` |
| **SQ03** | Chuyển tiền Nội bộ & Đảo Giao dịch (Reversal) | `UC03` | `User`, `TransactionController`, `LedgerService`, `LedgerRepository`, `Database` |
| **SQ04** | Xử lý Giao dịch Định kỳ Tự động (Subscription Cron) | `UC04`, `SYS02` | `Scheduler`, `SubscriptionWorker`, `LedgerService`, `NotificationService`, `Database` |
| **SQ05** | Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô *(Microfinance)* | `UC06`, `UC07` | `Borrower`, `LoanController`, `LoanService`, `LedgerService`, `LoanRepository`, `Database` |
| **SQ06** | Quản lý Vàng Tích lũy & Tính toán Sức khỏe Net Worth Dashboard | `UC08`, `UC10` | `User`, `DashboardController`, `AnalyticsService`, `GoldService`, `Database` |
| **SQ07** | Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn | `UC11` | `User`, `ReportController`, `ReconciliationService`, `ExportService`, `Database` |
| **SQ08** | Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo | `UC12`, `UC13` | `Admin`, `AdminController`, `AdminService`, `NotificationService`, `Database` |

---

## 3. Nội dung Chi tiết các Sơ đồ Tuần tự

---

### SQ01: Đăng ký tài khoản, Mã hóa mật khẩu & Đăng nhập cấp JWT
- **Use Case liên quan:** `UC01` (Đăng ký & Xác thực), `UC02` (Quản lý ví).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR02` (Số dư đầu kỳ đối ứng Equity), `BR11` (Bảo mật phiên).
- **Kịch bản:** Người dùng gửi thông tin đăng ký -> Hệ thống kiểm tra trùng email -> Băm mật khẩu Argon2id -> Lưu bản ghi User Active -> Tự động sao chép danh mục mặc định -> Người dùng đăng nhập -> Xác thực mật khẩu -> Ký số cặp JWT Tokens (Access/Refresh).

#### Sơ đồ Mermaid

```mermaid
sequenceDiagram
    autonumber
    actor User as Người dùng (User / Guest)
    participant AC as AuthController
    participant AS as AuthService
    participant UR as UserRepository
    participant DB as PostgreSQL Database

    %% Luồng Đăng ký tài khoản
    rect rgb(245, 243, 255)
        note over User, DB: 1. KỊCH BẢN ĐĂNG KÝ TÀI KHOẢN MỚI
        User ->> AC: POST /api/auth/register (name, email, password, baseCurrency)
        activate AC
        AC ->> AS: register(dto)
        activate AS
        AS ->> UR: findByEmail(email)
        activate UR
        UR ->> DB: SELECT * FROM users WHERE email = ?
        DB -->> UR: Kết quả truy vấn
        UR -->> AS: user / null
        deactivate UR

        alt Email đã tồn tại trong hệ thống
            AS -->> AC: Ném lỗi ConflictException("Email đã được sử dụng")
            AC -->> User: 409 Conflict (Thông báo email trùng)
        else Email hợp lệ (chưa tồn tại)
            AS ->> AS: hashPassword(password, salt) [Argon2id]
            AS ->> UR: createUserWithDefaultCategories(userData)
            activate UR
            UR ->> DB: BEGIN TRANSACTION
            UR ->> DB: INSERT INTO users (name, email, password_hash, base_currency, status='Active')
            UR ->> DB: INSERT INTO categories (SELECT default categories)
            UR ->> DB: COMMIT TRANSACTION
            DB -->> UR: userRecord
            UR -->> AS: userRecord
            deactivate UR
            AS -->> AC: RegistrationResult(userId, email, status)
            deactivate AS
            AC -->> User: 201 Created (Đăng ký thành công, chuyển màn hình Đăng nhập)
        end
        deactivate AC
    end

    %% Luồng Đăng nhập tài khoản
    rect rgb(224, 242, 254)
        note over User, DB: 2. KỊCH BẢN ĐĂNG NHẬP & CẤP PHÁT TOKEN JWT
        User ->> AC: POST /api/auth/login (email, password)
        activate AC
        AC ->> AS: login(email, password)
        activate AS
        AS ->> UR: findByEmail(email)
        activate UR
        UR ->> DB: SELECT * FROM users WHERE email = ?
        DB -->> UR: userRecord
        UR -->> AS: userRecord
        deactivate UR

        alt Không tìm thấy user hoặc sai mật khẩu
            AS ->> AS: verifyPassword(password, hash) -> false
            AS -->> AC: Ném lỗi UnauthorizedException("Email hoặc mật khẩu không chính xác")
            AC -->> User: 401 Unauthorized (Thông báo lỗi đăng nhập chung)
        else Mật khẩu chính xác nhưng tài khoản bị khóa (Status == Locked)
            AS -->> AC: Ném lỗi ForbiddenException("Tài khoản đã bị tạm khóa bởi Admin")
            AC -->> User: 403 Forbidden (Hiển thị lý do khóa tài khoản)
        else Xác thực thành công & Tài khoản Active
            AS ->> AS: verifyPassword(password, hash) -> true
            AS ->> AS: generateTokens(userId, role) [JWT Access (15m) + Refresh (7d)]
            AS ->> UR: saveRefreshToken(userId, hashedRefreshToken)
            activate UR
            UR ->> DB: INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
            DB -->> UR: Success
            deactivate UR
            AS -->> AC: AuthTokens(accessToken, refreshToken)
            deactivate AS
            AC -->> User: 200 OK (Trả Access Token trong JSON, Refresh Token trong HttpOnly Cookie)
        end
        deactivate AC
    end
```

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ01_Dang_Ky_Va_Xac_Thuc
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Người dùng (User)" as User
participant "AuthController" as AC
participant "AuthService" as AS
participant "UserRepository" as UR
database "PostgreSQL Database" as DB

== Đăng ký tài khoản mới ==
User -> AC: POST /api/auth/register (name, email, password, baseCurrency)
activate AC
AC -> AS: register(dto)
activate AS
AS -> UR: findByEmail(email)
activate UR
UR -> DB: SELECT * FROM users WHERE email = ?
DB --> UR: null
UR --> AS: null
deactivate UR

AS -> AS: hashPassword(password, salt) [Argon2id]
AS -> UR: createUserWithDefaultCategories(userData)
activate UR
UR -> DB: BEGIN TRANSACTION
UR -> DB: INSERT INTO users (...)
UR -> DB: INSERT INTO categories (...)
UR -> DB: COMMIT TRANSACTION
DB --> UR: userRecord
UR --> AS: userRecord
deactivate UR
AS --> AC: RegistrationResult
deactivate AS
AC --> User: 201 Created (Đăng ký thành công)
deactivate AC

== Đăng nhập & Cấp phát JWT ==
User -> AC: POST /api/auth/login (email, password)
activate AC
AC -> AS: login(email, password)
activate AS
AS -> UR: findByEmail(email)
activate UR
UR -> DB: SELECT * FROM users WHERE email = ?
DB --> UR: userRecord
UR --> AS: userRecord
deactivate UR

alt Sai mật khẩu hoặc tài khoản bị khóa
  AS --> AC: Ném lỗi xác thực
  AC --> User: 401 Unauthorized / 403 Forbidden
else Hợp lệ (Status == Active)
  AS -> AS: generateTokens(userId) [Access + Refresh]
  AS -> UR: saveRefreshToken(userId, hashedToken)
  activate UR
  UR -> DB: INSERT INTO refresh_tokens (...)
  DB --> UR: Success
  deactivate UR
  AS --> AC: AuthTokens(accessToken, refreshToken)
  deactivate AS
  AC --> User: 200 OK (Đăng nhập thành công, điều hướng Dashboard)
  deactivate AC
end
@enduml
```
</details>

---

### SQ02: Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm tra Ngân sách
- **Use Case liên quan:** `UC03` (Ghi nhận giao dịch), `UC05` (Kiểm soát ngân sách).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR06` (Bất biến sổ cái), `BR07` (Công thức ngân sách), `BR08` (Cảnh báo 80%/100%), `BR13` (Idempotency).
- **Kịch bản:** Người dùng gửi yêu cầu chi tiêu kèm `Idempotency-Key` -> Controller kiểm tra khóa chống trùng lặp -> LedgerService kiểm tra số dư ví khả dụng -> Mở Database Transaction nguyên tử -> Ghi nhận Transaction Posted -> Ghi 2 dòng Nợ/Có sổ cái -> Giảm số dư đệm ví -> Commit Transaction -> Kích hoạt BudgetService tính toán tỷ lệ ngân sách -> Sinh cảnh báo Vàng/Đỏ nếu vượt ngưỡng.

#### Sơ đồ Mermaid

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

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

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

### SQ03: Chuyển tiền Nội bộ & Đảo Giao dịch (Reversal)
- **Use Case liên quan:** `UC03` (Quản lý giao dịch).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR05` (Chuyển tiền không ảnh hưởng Thu/Chi), `BR06` (Bất biến sổ cái & Đảo giao dịch), `BR13` (Idempotency).
- **Kịch bản:**
  - *Nhánh 1:* Chuyển tiền giữa 2 ví cùng chủ sở hữu -> Ghi Nợ Ví Đích / Ghi Có Ví Nguồn -> Cập nhật số dư 2 ví -> Net Worth và Thu/Chi không đổi.
  - *Nhánh 2:* Đảo giao dịch đã ghi sổ -> Kiểm tra bản gốc Posted và chưa bị đảo -> Đánh dấu `isReversed = true` -> Tạo giao dịch REVERSAL đối ứng ngược chiều nguyên tử -> Hoàn trả số dư ví về ban đầu.

#### Sơ đồ Mermaid

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

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

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

### SQ04: Xử lý Giao dịch Định kỳ Tự động (Subscription Cron)
- **Use Case liên quan:** `UC04` (Giao dịch định kỳ), `SYS02` (Tiến trình quét subscription).
- **Quy tắc nghiệp vụ:** `BR07` (Ngân sách), `BR09` (Tính toán ngày neo và lùi ngày cuối tháng).
- **Kịch bản:** Scheduler kích hoạt lúc 01:00 AM -> SubscriptionWorker quét danh sách Active đến hạn -> Kiểm tra chế độ `isAutoPost` và số dư ví -> Tự động ghi sổ Posted hoặc tạo bản nháp Draft -> Đẩy thông báo chuông cho User -> Tính toán ngày đến hạn kế tiếp `nextDueDate` (tự động lùi về ngày 28 cho tháng 2 nếu ngày neo là 29, 30, 31).

#### Sơ đồ Mermaid

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

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

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

### SQ05: Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô (Microfinance)
- **Use Case liên quan:** `UC06` (Khởi tạo khoản vay), `UC07` (Thanh toán trả nợ).
- **Quy tắc nghiệp vụ:** `BR09` (Lịch trả nợ), `BR10` (Phân định Nợ vs Chi phí), `BR17` (Nguyên lý kế toán vi mô cho Vay mượn).
- **Kịch bản:**
  - *Nhánh 1:* Tạo khoản vay có giải ngân -> Tăng tiền ví và tăng Nợ phải trả (Total Liabilities), tuyệt đối cấm ghi nhận vào Thu nhập theo BR17.
  - *Nhánh 2:* Thanh toán trả nợ từng kỳ -> Bóc tách nợ gốc vs lãi/phí -> Giảm ví = Gốc + Lãi -> Giảm dư nợ gốc = Tiền gốc -> Chi phí tài chính = Tiền lãi -> Tự động chuyển `PAID_OFF` khi dư nợ $= 0$.

#### Sơ đồ Mermaid

```mermaid
sequenceDiagram
    autonumber
    actor User as Người vay / Người dùng
    participant LC as LoanController
    participant LoanS as LoanService
    participant LS as LedgerService
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

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

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

### SQ06: Quản lý Vàng Tích lũy & Tính toán Sức khỏe Net Worth Dashboard
- **Use Case liên quan:** `UC08` (Tài sản vàng), `UC10` (Dashboard sức khỏe tài chính & Net Worth).
- **Quy tắc nghiệp vụ:** `BR02` (Vốn chủ sở hữu), `BR03` & `BR04` (Định giá vàng), `BR16` (Công thức Net Worth), `BR18` (An toàn tỷ lệ tiết kiệm).
- **Kịch bản:** Người dùng thêm vàng hoặc cập nhật thị giá -> Hệ thống ghi nhận lịch sử Revaluation -> Người dùng truy cập Dashboard -> Hệ thống tổng hợp song song Total Assets (Ví + Vàng) và Total Liabilities (Thẻ tín dụng + Nợ vay) -> Tính Net Worth = Assets - Liabilities -> Tính Cash Flow = Income - Expense -> Tính toán an toàn Saving Rate (xử lý Thu nhập <= 0 trả về "N/A" tránh chia cho 0).

#### Sơ đồ Mermaid

```mermaid
sequenceDiagram
    autonumber
    actor User as Người dùng (User)
    participant DC as DashboardController
    participant GC as GoldController
    participant AnS as AnalyticsService
    participant GS as GoldService
    participant AR as AssetRepository
    participant DB as PostgreSQL Database

    %% Luồng Định giá lại Vàng
    rect rgb(254, 243, 199)
        note over User, DB: 1. KỊCH BẢN CẬP NHẬT THỊ GIÁ VÀNG (GOLD REVALUATION)
        User ->> GC: PUT /api/assets/gold/{id}/revalue (newMarketPrice, revalDate)
        activate GC
        GC ->> GS: revalueGoldAsset(userId, goldId, dto)
        activate GS
        GS ->> AR: saveRevaluationRecord(goldId, newPrice, revalDate)
        activate AR
        AR ->> DB: BEGIN TRANSACTION
        AR ->> DB: INSERT INTO gold_revaluations (gold_id, market_price, revalued_at)
        AR ->> DB: UPDATE gold_assets SET current_market_value = newPrice WHERE id = goldId
        AR ->> DB: COMMIT TRANSACTION
        DB -->> AR: Success
        AR -->> GS: updatedGoldAsset
        deactivate AR
        GS -->> GC: GoldResponse(goldId, currentMarketValue)
        deactivate GS
        GC -->> User: 200 OK (Cập nhật thị giá thành công, Total Assets tăng)
        deactivate GC
    end

    %% Luồng Tổng hợp Dashboard Net Worth
    rect rgb(240, 253, 244)
        note over User, DB: 2. KỊCH BẢN TỔNG HỢP DASHBOARD SỨC KHỎE TÀI CHÍNH & NET WORTH
        User ->> DC: GET /api/dashboard/overview?period=month
        activate DC
        DC ->> AnS: getFinancialHealthOverview(userId, 'month')
        activate AnS

        %% Truy vấn song song các cấu phần tài chính
        par Truy vấn Tổng ví tài sản
            AnS ->> AR: getTotalWalletAssets(userId)
            activate AR
            AR ->> DB: SELECT SUM(balance) FROM wallets WHERE user_id = ? AND type != 'CREDIT'
            DB -->> AR: walletAssetsTotal (ví dụ: 50.000.000 VND)
            AR -->> AnS: walletAssetsTotal
            deactivate AR
        and Truy vấn Tổng giá trị vàng
            AnS ->> AR: getTotalGoldAssets(userId)
            activate AR
            AR ->> DB: SELECT SUM(current_market_value) FROM gold_assets WHERE user_id = ?
            DB -->> AR: goldAssetsTotal (ví dụ: 17.000.000 VND)
            AR -->> AnS: goldAssetsTotal
            deactivate AR
        and Truy vấn Tổng nợ phải trả
            AnS ->> AR: getTotalLiabilities(userId)
            activate AR
            AR ->> DB: SELECT (SELECT COALESCE(SUM(balance),0) FROM wallets WHERE type='CREDIT') + (SELECT COALESCE(SUM(remaining_principal),0) FROM loans WHERE status='ACTIVE')
            DB -->> AR: totalLiabilities (ví dụ: 15.000.000 VND)
            AR -->> AnS: totalLiabilities
            deactivate AR
        and Truy vấn Dòng tiền trong tháng
            AnS ->> AR: getCashFlowMonthly(userId, month)
            activate AR
            AR ->> DB: SELECT total_income, total_expense FROM monthly_cashflow_view WHERE ...
            DB -->> AR: CashFlowData(income = 20.000.000, expense = 12.000.000)
            AR -->> AnS: CashFlowData
            deactivate AR
        end

        %% Tính toán các chỉ số tài chính
        AnS ->> AnS: calculateTotalAssets() -> 50tr + 17tr = 67.000.000 VND
        AnS ->> AnS: calculateNetWorth() -> Total Assets (67tr) - Total Liabilities (15tr) = 52.000.000 VND
        AnS ->> AnS: calculateCashFlow() -> Income (20tr) - Expense (12tr) = +8.000.000 VND

        %% Xử lý an toàn tỷ lệ tiết kiệm (BR18)
        alt Total Income <= 0
            AnS ->> AnS: setSavingRate("N/A") (Tránh lỗi chia cho 0 an toàn theo BR18)
        else Total Income > 0
            AnS ->> AnS: calculateSavingRate() -> ((20tr - 12tr) / 20tr) * 100% = 40%
        end

        AnS -->> DC: FinancialOverviewData(TotalAssets, TotalLiabilities, NetWorth, CashFlow, SavingRate)
        deactivate AnS
        DC -->> User: 200 OK (Render Dashboard hoàn chỉnh: Cards chỉ số, Biểu đồ biến động Net Worth)
        deactivate DC
    end
```

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ06_Quan_Ly_Vang_Va_Net_Worth
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Người dùng (User)" as User
participant "DashboardController" as DC
participant "AnalyticsService" as AnS
participant "AssetRepository" as AR
database "PostgreSQL Database" as DB

User -> DC: GET /api/dashboard/overview?period=month
activate DC
DC -> AnS: getFinancialHealthOverview(userId, 'month')
activate AnS

par Tổng hợp Tài sản Ví & Vàng
  AnS -> AR: getTotalAssets(userId)
  activate AR
  AR -> DB: SELECT SUM(balance) FROM wallets ...
  AR -> DB: SELECT SUM(current_market_value) FROM gold_assets ...
  DB --> AR: TotalAssets
  AR --> AnS: TotalAssets
  deactivate AR
else Tổng hợp Tổng nợ
  AnS -> AR: getTotalLiabilities(userId)
  activate AR
  AR -> DB: SELECT SUM(remaining_principal) FROM loans ...
  DB --> AR: TotalLiabilities
  AR --> AnS: TotalLiabilities
  deactivate AR
else Dòng tiền thu chi
  AnS -> AR: getMonthlyCashFlow(userId)
  activate AR
  AR -> DB: SELECT total_income, total_expense FROM ...
  DB --> AR: CashFlowData(Income, Expense)
  AR --> AnS: CashFlowData
  deactivate AR
end

AnS -> AnS: NetWorth = TotalAssets - TotalLiabilities
AnS -> AnS: CashFlow = Income - Expense

alt Income <= 0
  AnS -> AnS: SavingRate = "N/A" (Tránh chia 0 theo BR18)
else Income > 0
  AnS -> AnS: SavingRate = ((Income - Expense) / Income) * 100%
end

AnS --> DC: FinancialOverviewData
deactivate AnS
DC --> User: 200 OK (Hiển thị Dashboard toàn cảnh)
deactivate DC
@enduml
```
</details>

---

### SQ07: Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn
- **Use Case liên quan:** `UC11` (Báo cáo & Xuất dữ liệu).
- **Quy tắc nghiệp vụ:** `BR01` (Kế toán kép), `BR12` (Đối soát số dư), `BR14` (Kiểm toán), `BR18` (Chuẩn mã hóa UTF-8 BOM).
- **Kịch bản:**
  - *Nhánh 1:* Đối soát số dư ví -> Lấy số dư bộ đệm ví so sánh với tổng đại số các dòng Nợ/Có sổ cái -> Khớp 100% (Delta = 0).
  - *Nhánh 2:* Xuất file CSV -> Lấy danh sách giao dịch -> Khử ký tự công thức nguy hiểm (`=, +, -, @`) phòng ngừa CSV Formula Injection -> Gắn ký tự UTF-8 BOM (`\uFEFF`) -> Trả Stream file CSV về trình duyệt.

#### Sơ đồ Mermaid

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

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

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

### SQ08: Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo
- **Use Case liên quan:** `UC12` (Quản trị người dùng & Thống kê vĩ mô), `UC13` (Cấu hình hệ thống & Phát thông báo).
- **Quy tắc nghiệp vụ:** `BR11` (Bảo vệ quyền riêng tư người dùng), `BR13` (Quản trị cấu hình & Kiểm toán hành chính).
- **Kịch bản:**
  - *Nhánh 1:* Admin xem Dashboard vĩ mô -> Chỉ hiển thị tổng user, active, tiền cơ sở, tuyệt đối cách ly số dư cá nhân theo BR11.
  - *Nhánh 2:* Admin khóa tài khoản vi phạm -> Kiểm tra lý do khóa bắt buộc -> Kiểm tra không phải Admin cuối cùng -> Cập nhật trạng thái `LOCKED` -> Thu hồi toàn bộ Refresh Token của User -> Ghi nhật ký Admin Audit Log -> User bị đăng xuất ngay lập tức.
  - *Nhánh 3:* Admin phát thông báo Broadcast -> Lưu SystemAnnouncement -> Đẩy vào Notification của toàn bộ người dùng Active.

#### Sơ đồ Mermaid

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Quản trị viên (Admin A02)
    participant AC as AdminController
    participant AS as AdminService
    participant NS as NotificationService
    participant AR as AdminRepository
    participant DB as PostgreSQL Database
    actor EndUser as Người dùng bị ảnh hưởng

    %% Luồng Khóa tài khoản
    rect rgb(254, 226, 226)
        note over Admin, EndUser: 1. KỊCH BẢN KHÓA TÀI KHOẢN VI PHẠM (ACCOUNT LOCKOUT)
        Admin ->> AC: PUT /api/admin/users/{id}/lock (reason='Vi phạm điều khoản')
        activate AC
        AC ->> AS: lockUserAccount(adminId, targetUserId, reason)
        activate AS

        alt Lý do khóa để trống
            AS -->> AC: Ném lỗi UnprocessableEntityException("Lý do khóa tài khoản là bắt buộc")
            AC -->> Admin: 422 Unprocessable Entity
        else Kiểm tra tài khoản bị khóa có phải là Admin cuối cùng
            AS ->> AR: checkIsLastAdmin(targetUserId)
            activate AR
            AR ->> DB: SELECT COUNT(*) FROM users WHERE role = 'ADMIN' AND status = 'ACTIVE' AND id != ?
            DB -->> AR: remainingActiveAdminsCount
            AR -->> AS: remainingActiveAdminsCount
            deactivate AR

            alt remainingActiveAdminsCount == 0 (Là Admin duy nhất còn lại)
                AS -->> AC: Ném lỗi BadRequestException("Không thể khóa tài khoản Quản trị viên duy nhất còn lại")
                AC -->> Admin: 400 Bad Request
            else Hợp lệ -> Thực thi khóa nguyên tử
                AS ->> AR: executeLockUserAndRevokeSessions(adminId, targetUserId, reason)
                activate AR
                AR ->> DB: BEGIN TRANSACTION
                AR ->> DB: UPDATE users SET status = 'LOCKED', locked_reason = reason WHERE id = targetUserId
                AR ->> DB: DELETE FROM refresh_tokens WHERE user_id = targetUserId (thu hồi phiên)
                AR ->> DB: INSERT INTO admin_audit_logs (admin_id, action='LOCK_USER', target_id=targetUserId, note=reason)
                AR ->> DB: COMMIT TRANSACTION
                DB -->> AR: Success
                AR -->> AS: lockSuccess
                deactivate AR

                AS -->> AC: LockResponse(targetUserId, status='LOCKED')
                deactivate AS
                AC -->> Admin: 200 OK (Khóa tài khoản thành công)
                deactivate AC

                %% Tác động tức thì lên Người dùng bị khóa
                EndUser -) AC: Yêu cầu API tiếp theo kèm JWT cũ
                activate AC
                AC -->> EndUser: 403 Forbidden (Tài khoản đã bị khóa kèm lý do)
                deactivate AC
            end
        end
    end

    %% Luồng Phát thông báo Broadcast
    rect rgb(240, 253, 244)
        note over Admin, EndUser: 2. KỊCH BẢN PHÁT THÔNG BÁO TOÀN HỆ THỐNG (BROADCAST ANNOUNCEMENT)
        Admin ->> AC: POST /api/admin/announcements (title, content, priority='MAINTENANCE')
        activate AC
        AC ->> AS: broadcastAnnouncement(adminId, dto)
        activate AS

        alt Tiêu đề hoặc nội dung để trống
            AS -->> AC: Ném lỗi UnprocessableEntityException("Tiêu đề và nội dung không được để trống")
            AC -->> Admin: 422 Unprocessable Entity
        else Hợp lệ -> Phát hành thông báo
            AS ->> AR: saveSystemAnnouncement(adminId, dto)
            activate AR
            AR ->> DB: INSERT INTO system_announcements (admin_id, title, content, priority) RETURNING id
            DB -->> AR: announcementId
            AR -->> AS: announcementId
            deactivate AR

            AS ->> NS: fanoutNotificationToActiveUsers(announcementId, dto)
            activate NS
            NS ->> DB: INSERT INTO notifications (user_id, title, content, priority) SELECT id, title, content, priority FROM users WHERE status = 'ACTIVE'
            DB -->> NS: insertedCount
            NS -->> EndUser: Đẩy thông báo chuông (Notification Center sáng lên)
            deactivate NS

            AS -->> AC: BroadcastResult(announcementId, sentUsersCount)
            deactivate AS
            AC -->> Admin: 201 Created (Phát thông báo thành công cho toàn bộ người dùng)
            deactivate AC
        end
    end
```

<details>
<summary><b>Xem mã PlantUML tương ứng (dùng xuất ảnh nộp báo cáo Word)</b></summary>

```plantuml
@startuml SQ08_Quan_Tri_Nguoi_Dung_Va_Thong_Bao
autonumber
skinparam roundCorner 10
skinparam sequenceMessageAlign center

actor "Quản trị viên (Admin)" as Admin
participant "AdminController" as AC
participant "AdminService" as AS
participant "NotificationService" as NS
participant "AdminRepository" as AR
database "PostgreSQL Database" as DB
actor "Người dùng (End-User)" as EndUser

== Khóa tài khoản vi phạm ==
Admin -> AC: PUT /api/admin/users/{id}/lock (reason)
activate AC
AC -> AS: lockUserAccount(adminId, targetId, reason)
activate AS
AS -> AR: checkIsLastAdmin(targetId)
activate AR
AR -> DB: SELECT COUNT(*) FROM users WHERE role='ADMIN' AND status='ACTIVE'
DB --> AR: count
AR --> AS: count
deactivate AR

alt Là Admin cuối cùng hoặc thiếu lý do
  AS --> AC: Ném lỗi thao tác không hợp lệ
  AC --> Admin: 400 Bad Request / 422 Unprocessable Entity
else Hợp lệ
  AS -> AR: executeLockUserAndRevokeSessions(...)
  activate AR
  AR -> DB: BEGIN TRANSACTION
  AR -> DB: UPDATE users SET status = 'LOCKED' WHERE id = targetId
  AR -> DB: DELETE FROM refresh_tokens WHERE user_id = targetId
  AR -> DB: INSERT INTO admin_audit_logs (...)
  AR -> DB: COMMIT TRANSACTION
  DB --> AR: Success
  AR --> AS: Success
  deactivate AR
  AS --> AC: LockResponse
  deactivate AS
  AC --> Admin: 200 OK (Khóa tài khoản thành công)
  deactivate AC
  EndUser -> AC: Yêu cầu API tiếp theo
  AC --> EndUser: 403 Forbidden (Tài khoản đã bị tạm khóa)
end

== Phát thông báo toàn hệ thống ==
Admin -> AC: POST /api/admin/announcements (title, content, priority)
activate AC
AC -> AS: broadcastAnnouncement(adminId, dto)
activate AS
AS -> AR: saveSystemAnnouncement(...)
activate AR
AR -> DB: INSERT INTO system_announcements (...)
DB --> AR: announcementId
AR --> AS: announcementId
deactivate AR

AS -> NS: fanoutNotificationToActiveUsers(...)
activate NS
NS -> DB: INSERT INTO notifications (...)
NS --> EndUser: Đẩy thông báo chuông cho toàn bộ người dùng Active
deactivate NS
AS --> AC: BroadcastResult
deactivate AS
AC --> Admin: 201 Created (Phát thông báo thành công)
deactivate AC
@enduml
```
</details>

---

## 4. Ma trận Truy vết Tuần tự & Use Cases (Sequence Traceability Matrix)

| Mã Sơ đồ | Tên Kịch bản Tuần tự | Use Cases bao phủ | Quy tắc Nghiệp vụ (BR) | Các Lớp / Thành phần tham gia |
| :---: | :--- | :---: | :---: | :--- |
| **SQ01** | Đăng ký tài khoản, Mã hóa mật khẩu & Đăng nhập JWT | `UC01`, `UC02` | BR01, BR02, BR11, BR15 | `AuthController`, `AuthService`, `UserRepository` |
| **SQ02** | Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm tra Ngân sách | `UC03`, `UC05` | BR01, BR06, BR07, BR08, BR13 | `TransactionController`, `LedgerService`, `BudgetService` |
| **SQ03** | Chuyển tiền Nội bộ & Đảo Giao dịch (Reversal) | `UC03` | BR01, BR05, BR06, BR13 | `TransactionController`, `LedgerService`, `LedgerRepository` |
| **SQ04** | Xử lý Giao dịch Định kỳ Tự động (Subscription Cron) | `UC04`, `SYS02` | BR07, BR09, BR13 | `SubscriptionWorker`, `LedgerService`, `NotificationService` |
| **SQ05** | Khởi tạo Khoản vay & Thanh toán Trả nợ Vi mô | `UC06`, `UC07` | BR09, BR10, BR17 | `LoanController`, `LoanService`, `LedgerService` |
| **SQ06** | Quản lý Vàng Tích lũy & Tính toán Sức khỏe Net Worth | `UC08`, `UC10` | BR02, BR03, BR04, BR16, BR18 | `DashboardController`, `AnalyticsService`, `GoldService` |
| **SQ07** | Báo cáo Đối soát Sổ cái & Xuất Dữ liệu CSV An toàn | `UC11` | BR01, BR12, BR14, BR18 | `ReportController`, `ReconciliationService`, `ExportService` |
| **SQ08** | Quản trị Người dùng, Khóa Tài khoản & Phát Thông báo | `UC12`, `UC13` | BR11, BR13, BR14 | `AdminController`, `AdminService`, `NotificationService` |

---

## 5. Kết luận và Bước kế tiếp trong Tiến trình Unified Process

Tài liệu **Sequence Diagrams** trên đã hoàn thành trọn vẹn việc mô hình hóa các lời gọi phương thức, luồng dữ liệu, tính nguyên tử của giao dịch (ACID) và ranh giới kiến trúc phân tầng BCE của hệ thống **WealthFlow**.

Theo tiến trình Unified Process (luồng kỹ nghệ *Analysis & Design*), sau khi đã hoàn tất Use Case Model, Activity Diagram và Sequence Diagram, các bước kế tiếp bao gồm:
1. **Design Class Diagram (Sơ đồ Lớp thiết kế):** Định nghĩa cụ thể tên lớp, thuộc tính, kiểu dữ liệu, các phương thức nghiệp vụ, quan hệ kế thừa và kết tập giữa các thành phần.
2. **Entity-Relationship Diagram & Database Schema (ERD & DB Schema):** Thiết kế chi tiết các bảng vật lý (Tables), khóa chính (Primary Keys), khóa ngoại (Foreign Keys), ràng buộc (Constraints) và chỉ mục (Indexes) trên hệ quản trị cơ sở dữ liệu PostgreSQL.
