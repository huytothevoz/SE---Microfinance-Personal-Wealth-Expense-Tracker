# Sơ đồ Lớp (Class Diagram) - WealthFlow

> **Tham chiếu tài liệu:** [SRS.md](../SRS.md) v2.1 | [Sequence Diagrams](./sequence_diagram.md) | [Activity Diagrams](./activity_diagram.md)

---

## 1. Sơ đồ Thực thể Nghiệp vụ (Domain Model)

*(Nội dung phần này sẽ được cập nhật và chuẩn hóa toàn diện sau khi hoàn thiện chi tiết 6 phân hệ thiết kế lớp BCE).*

---

## 2. Sơ đồ Lớp Thiết kế Chi tiết (BCE Architecture)

Mô hình phân bổ trách nhiệm hướng đối tượng được thiết kế theo kiến trúc **Boundary - Control - Entity (BCE)** (Ivar Jacobson / UP-RUP):
- **Boundary (Tầng Biên / Giao diện)**: Tiếp nhận yêu cầu từ Actor (REST Controllers tiếp nhận HTTP requests, Schedulers/Workers tiếp nhận trigger nền).
- **Control (Tầng Điều khiển / Dịch vụ Nghiệp vụ)**: Thực thi logic tài chính, điều phối giao dịch nguyên tử ACID và kiểm soát các quy tắc nghiệp vụ ([BR01] - [BR18]).
- **Entity (Tầng Thực thể & Lưu trữ Bền vững)**: Gồm các Rich Domain Entities mang dữ liệu và hành vi nghiệp vụ, kết hợp cùng Repositories trừu tượng hóa thao tác CSDL.

### 2.0. Danh mục Sơ đồ Lớp Trực quan (Vector SVG Catalog)

Toàn bộ các sơ đồ lớp được kết xuất thành ảnh Vector SVG chất lượng cao lưu tại [./class_diagram/](./class_diagram/), nền trắng tương phản cao, hiển thị tức thì và bảo đảm 100% không bị quá tải hay sụp đổ Markdown Renderer:

| Mã Phân hệ | Tên Phân hệ Nghiệp vụ (BCE Context) | Use Cases bao phủ | Các Lớp Trọng tâm | Ảnh Vector Trực quan |
| :---: | :--- | :---: | :--- | :---: |
| **CD01** | Phân hệ 1: Định danh, Xác thực & Quản trị Tài khoản | [UC01](../SRS.md#uc01), [UC12](../SRS.md#uc12) | `AuthController`, `AuthService`, `User`, `AdminService` | [cd01_identity_access.svg](./class_diagram/cd01_identity_access.svg) |
| **CD02** | Phân hệ 2: Quản lý Ví tài sản & Số dư Mở sổ | [UC02](../SRS.md#uc02) | `WalletController`, `WalletService`, `Wallet`, `ILedgerEquityPort` | [cd02_wallet_management.svg](./class_diagram/cd02_wallet_management.svg) |
| **CD03** | Phân hệ 3: Ghi nhận Giao dịch Thu chi & Sổ cái Kế toán kép | [UC03](../SRS.md#uc03) | `TransactionController`, `LedgerService`, `FinancialTransaction`, `JournalEntry` | [cd03_double_entry_ledger.svg](./class_diagram/cd03_double_entry_ledger.svg) |
| **CD04** | Phân hệ 4: Báo cáo Đối soát Sổ cái & Xuất CSV An toàn | [UC11](../SRS.md#uc11) | `ReportController`, `ReconciliationService`, `ExportService`, `TrialBalanceReport` | [cd04_reconciliation_csv.svg](./class_diagram/cd04_reconciliation_csv.svg) |

---

### 2.1. Phân hệ 1: Định danh, Xác thực & Quản trị Tài khoản (Identity, Authentication & User Management Context)

> **Tham chiếu nghiệp vụ:** [FR01](../SRS.md#fr01), [FR13](../SRS.md#fr13), [FR15](../SRS.md#fr15)  
> **Use Cases:** [UC01](../SRS.md#uc01) (Đăng ký, Đăng nhập, Quên mật khẩu OTP), [UC12](../SRS.md#uc12) (Quản trị tài khoản & Khóa người dùng)  
> **Sequence Diagrams:** [SQ01](./sequence_diagram/sq01_auth_jwt.md), [SQ08](./sequence_diagram/sq08_admin_user_broadcast.md)  
> **Quy tắc cốt lõi:** [BR11](../SRS.md#br11) (Quyền riêng tư Admin & Cô lập dữ liệu cá nhân)

#### Sơ đồ Lớp Trực quan (Vector SVG):

![CD01 Class Diagram](./class_diagram/cd01_identity_access.svg)

<details>
<summary>🔍 <b>Nhấn để xem mã nguồn Mermaid (CD01)</b></summary>

```mermaid
classDiagram
    direction TB

    %% ==========================================
    %% ACTORS (Tác nhân tương tác)
    %% ==========================================
    class GuestActor {
        <<Actor: Khách vãng lai A01-G>>
        +register(fullName, email, password, currency)
        +login(email, password)
        +requestPasswordReset(email)
        +submitOtpResetPassword(email, otp, newPassword)
    }

    class EndUserActor {
        <<Actor: Người dùng A01>>
        +getProfile()
        +updateProfile(fullName, baseCurrency)
        +changePassword(oldPassword, newPassword)
        +logout()
    }

    class AdminActor {
        <<Actor: Quản trị viên A02>>
        +listUsers(page, filter)
        +lockUser(userId, reason)
        +unlockUser(userId)
        +viewAuditLogs(page)
    }

    %% ==========================================
    %% BOUNDARY LAYER (Controllers)
    %% ==========================================
    class AuthController {
        <<Boundary>>
        +register(dto: RegisterRequestDTO): ResponseEntity
        +login(dto: LoginRequestDTO): ResponseEntity
        +refreshToken(refreshToken: String): ResponseEntity
        +requestPasswordReset(dto: ForgotPasswordDTO): ResponseEntity
        +verifyOtpAndResetPassword(dto: ResetPasswordDTO): ResponseEntity
        +logout(userId: UUID): ResponseEntity
    }

    class UserController {
        <<Boundary>>
        +getProfile(userId: UUID): ResponseEntity
        +updateProfile(userId: UUID, dto: UpdateProfileDTO): ResponseEntity
        +changePassword(userId: UUID, dto: ChangePasswordDTO): ResponseEntity
    }

    class AdminController {
        <<Boundary>>
        +getUsers(page: int, search: String): ResponseEntity
        +lockUser(userId: UUID, dto: LockUserDTO): ResponseEntity
        +unlockUser(userId: UUID): ResponseEntity
        +getAuditLogs(page: int): ResponseEntity
    }

    %% ==========================================
    %% CONTROL LAYER (Services)
    %% ==========================================
    class AuthService {
        <<Control>>
        -userRepository: UserRepository
        -tokenRepository: PasswordResetRepository
        -jwtProvider: JwtTokenProvider
        -passwordEncoder: Argon2PasswordEncoder
        +register(dto: RegisterRequestDTO): User
        +authenticate(email: String, password: String): TokenPair
        +refreshAccessToken(refreshToken: String): TokenPair
        +initiatePasswordReset(email: String): void
        +resetPasswordWithOtp(email: String, otp: String, newPassword: String): boolean
        +revokeUserTokens(userId: UUID): void
    }

    class UserService {
        <<Control>>
        -userRepository: UserRepository
        -passwordEncoder: Argon2PasswordEncoder
        +getUserProfile(userId: UUID): User
        +updateProfile(userId: UUID, dto: UpdateProfileDTO): User
        +changePassword(userId: UUID, oldPass: String, newPass: String): void
    }

    class AdminService {
        <<Control>>
        -userRepository: UserRepository
        -auditRepository: AuditLogRepository
        -authService: AuthService
        +listUsers(page: int, search: String): Page~User~
        +lockUserAccount(adminId: UUID, targetUserId: UUID, reason: String): void
        +unlockUserAccount(adminId: UUID, targetUserId: UUID): void
        +getAuditHistory(page: int): Page~AdminAuditLog~
    }

    %% ==========================================
    %% ENTITY / PERSISTENCE LAYER (Repositories)
    %% ==========================================
    class UserRepository {
        <<Entity / Repository>>
        +findById(id: UUID): Optional~User~
        +findByEmail(email: String): Optional~User~
        +existsByEmail(email: String): boolean
        +save(user: User): User
        +updateStatus(id: UUID, status: UserStatus): void
        +findUsers(page: int, search: String): Page~User~
    }

    class PasswordResetRepository {
        <<Entity / Repository>>
        +findByUserId(userId: UUID): Optional~PasswordResetToken~
        +save(token: PasswordResetToken): void
        +deleteByUserId(userId: UUID): void
    }

    class AuditLogRepository {
        <<Entity / Repository>>
        +save(log: AdminAuditLog): void
        +findAll(page: int): Page~AdminAuditLog~
        +findByTargetUserId(targetId: UUID): List~AdminAuditLog~
    }

    %% ==========================================
    %% DOMAIN ENTITIES & VALUE OBJECTS
    %% ==========================================
    class User {
        <<Entity>>
        -id: UUID
        -fullName: String
        -email: String
        -passwordHash: String
        -role: UserRole
        -status: UserStatus
        -baseCurrency: String
        -createdAt: DateTime
        -updatedAt: DateTime
        +validatePassword(plainPassword: String, encoder: PasswordEncoder): boolean
        +changePassword(newPasswordHash: String): void
        +updateProfile(fullName: String, baseCurrency: String): void
        +lock(): void
        +unlock(): void
        +isActive(): boolean
    }

    class PasswordResetToken {
        <<Entity>>
        -userId: UUID
        -tokenHash: String
        -attempts: int
        -expiresAt: DateTime
        -createdAt: DateTime
        +verifyOtp(inputOtp: String): boolean
        +incrementAttempts(): void
        +isExpired(): boolean
        +isExceededAttempts(): boolean
    }

    class AdminAuditLog {
        <<Entity>>
        -id: UUID
        -adminUserId: UUID
        -targetUserId: UUID
        -action: String
        -reason: String
        -createdAt: DateTime
    }

    class UserRole {
        <<enumeration>>
        USER
        ADMIN
    }

    class UserStatus {
        <<enumeration>>
        ACTIVE
        LOCKED
    }

    %% ==========================================
    %% RELATIONSHIPS & INTERACTIONS
    %% ==========================================
    %% Actors -> Boundary
    GuestActor ..> AuthController : interacts (HTTP/REST)
    EndUserActor ..> AuthController : logout
    EndUserActor ..> UserController : interacts (HTTP/REST)
    AdminActor ..> AdminController : moderates (HTTP/REST)

    %% Boundary -> Control
    AuthController --> AuthService : delegates
    UserController --> UserService : delegates
    AdminController --> AdminService : delegates

    %% Control -> Control / Boundary coordination
    AdminService --> AuthService : revokes_tokens (BR11)

    %% Control -> Repositories
    AuthService --> UserRepository : queries/persists
    AuthService --> PasswordResetRepository : manages_otp
    UserService --> UserRepository : queries/updates
    AdminService --> UserRepository : locks/unlocks
    AdminService --> AuditLogRepository : logs_action

    %% Repositories -> Entities
    UserRepository ..> User : persists
    PasswordResetRepository ..> PasswordResetToken : persists
    AuditLogRepository ..> AdminAuditLog : persists

    %% Entity Relationships
    User "1" o-- "0..1" PasswordResetToken : requests (FR01)
    User "1" o-- "0..*" AdminAuditLog : targeted_in (FR13)
    User --> UserRole : typed_by
    User --> UserStatus : state_of
```
</details>

#### Thuyết minh Chi tiết Trách nhiệm & Phương thức Nghiệp vụ (Class Responsibilities):

1. **Tầng Boundary (Biên tiếp nhận):**
   - **`AuthController`**: Tiếp nhận và điều hướng toàn bộ các yêu cầu phi trạng thái (stateless) từ Khách vãng lai (`GuestActor`) và Người dùng (`EndUserActor`): đăng ký tài khoản mới (`register`), xác thực đăng nhập cấp phát cặp JWT Token (`login`), làm mới Token (`refreshToken`), gửi yêu cầu quên mật khẩu OTP (`requestPasswordReset`) và xác thực OTP đặt lại mật khẩu (`verifyOtpAndResetPassword`).
   - **`UserController`**: Tiếp nhận các yêu cầu quản lý hồ sơ cá nhân của Người dùng sau khi đã xác thực token JWT hợp lệ: xem thông tin cá nhân (`getProfile`), đổi họ tên/tiền tệ cơ sở (`updateProfile`), đổi mật khẩu (`changePassword`).
   - **`AdminController`**: Cung cấp giao diện API quản trị cho Quản trị viên (`AdminActor`): tra cứu danh sách người dùng (`getUsers`), thực thi khóa tài khoản khẩn cấp kèm lý do (`lockUser`), mở khóa (`unlockUser`), truy vết nhật ký kiểm toán hệ thống (`getAuditLogs`).

2. **Tầng Control (Điều khiển & Xử lý nghiệp vụ):**
   - **`AuthService`**: Trung tâm xử lý an ninh và định danh. 
     - Áp dụng thuật toán **Argon2id** với tham số an toàn để băm mật khẩu người dùng trước khi lưu trữ (`FR01`).
     - Sinh mã OTP 6 chữ số ngẫu nhiên an toàn (CSPRNG), băm một chiều lưu vào `PasswordResetToken` với thời hạn hiệu lực chính xác 10 phút. Kiểm soát biến `attempts`, nếu nhập sai quá 5 lần thì hủy mã OTP để triệt tiêu tấn công Brute-force.
     - Sinh cặp JWT Token: Access Token (thời hạn 15 phút, chứa claims định danh `userId`, `role`) và Refresh Token (thời hạn 7 ngày, lưu trữ trong Cookie an toàn `HttpOnly; Secure; SameSite=Strict`).
   - **`UserService`**: Quản lý thông tin hồ sơ và đồng tiền cơ sở (`baseCurrency`). Kiểm tra mật khẩu cũ trước khi cho phép cập nhật mật khẩu mới.
   - **`AdminService`**: Thực thi quy trình quản trị người dùng theo chuẩn an ninh:
     - Khi thực thi khóa tài khoản (`lockUserAccount`), phương thức vừa cập nhật `status = LOCKED` trong CSDL, vừa gọi sang `AuthService.revokeUserTokens(userId)` để thu hồi ngay lập tức mọi phiên làm việc JWT đang hoạt động của người dùng bị khóa ([FR13](../SRS.md#fr13)).
     - Tự động ghi nhận một bản ghi kiểm toán bất biến vào `AdminAuditLog` gồm `adminId`, `targetUserId`, `action = 'LOCK_USER'`, `reason` và thời điểm xảy ra.
     - **Ràng buộc cách ly tuyệt đối ([BR11](../SRS.md#br11)):** `AdminService` hoàn toàn không có bất kỳ kết nối hay tham chiếu nào đến các Service tài chính cá nhân (`WalletService`, `LedgerService`), bảo đảm Quản trị viên tuyệt đối không thể đọc hay can thiệp vào số dư và giao dịch cá nhân của người dùng.

3. **Tầng Entity & Persistence (Thực thể & Kho lưu trữ bền vững):**
   - **`User` (Rich Domain Entity)**: Thực thể trung tâm đại diện cho chủ sở hữu tài khoản. Chứa các phương thức tự thẩm định như `validatePassword()`, `lock()`, `unlock()`, `isActive()`.
   - **`PasswordResetToken`**: Thực thể chứa mã OTP đã băm, thời hạn `expiresAt`, biến đếm `attempts`, kèm phương thức tự kiểm tra `verifyOtp()`, `isExpired()`, `isExceededAttempts()`.
   - **`AdminAuditLog`**: Thực thể lưu trữ bất biến phục vụ truy vết pháp lý và giám sát an ninh hệ thống.
   - **`UserRepository`, `PasswordResetRepository`, `AuditLogRepository`**: Đóng gói toàn bộ câu lệnh truy vấn CSDL, cung cấp giao diện trừu tượng hóa việc lưu trữ bền vững.

### 2.2. Phân hệ 2: Quản lý Ví tài sản & Số dư Mở sổ (Wallet Management Context - UC02)

> **Tham chiếu nghiệp vụ:** [FR02](../SRS.md#fr02), [FR04](../SRS.md#fr04), [FR15](../SRS.md#fr15)  
> **Use Case:** [UC02](../SRS.md#uc02) (Quản lý ví tài sản & số dư mở sổ)  
> **Sequence Diagram:** [SQ02](./sequence_diagram/sq02_expense_budget.md#1-thông-tin-chung-summary), [SQ03](./sequence_diagram/sq03_transfer_reversal.md#1-thông-tin-chung-summary)  
> **Quy tắc cốt lõi:** [BR01](../SRS.md#br01) (Bút toán mở sổ Vốn chủ sở hữu), [BR02](../SRS.md#br02) (Cấm xóa cứng ví đã có giao dịch), [BR04](../SRS.md#br04) (Chặn âm ví, trừ thẻ tín dụng)

#### Sơ đồ Lớp Trực quan (Vector SVG):

![CD02 Class Diagram](./class_diagram/cd02_wallet_management.svg)

<details>
<summary>🔍 <b>Nhấn để xem mã nguồn Mermaid (CD02)</b></summary>

```mermaid
classDiagram
    direction TB

    %% ==========================================
    %% ACTORS (Tác nhân tương tác)
    %% ==========================================
    class EndUserActor {
        <<Actor: Người dùng A01>>
        +createWallet(name, type, currency, openingBalance)
        +getWallets()
        +archiveWallet(walletId)
        +adjustBalance(walletId, actualBalance, reason)
    }

    %% ==========================================
    %% BOUNDARY LAYER (Controllers)
    %% ==========================================
    class WalletController {
        <<Boundary>>
        +createWallet(dto: CreateWalletDTO): ResponseEntity
        +getWallets(userId: UUID): ResponseEntity
        +archiveWallet(walletId: UUID): ResponseEntity
        +adjustBalance(walletId: UUID, dto: AdjustBalanceDTO): ResponseEntity
    }

    %% ==========================================
    %% CONTROL LAYER (Services)
    %% ==========================================
    class WalletService {
        <<Control>>
        -walletRepository: WalletRepository
        -ledgerPort: ILedgerEquityPort
        +createWalletWithOpeningBalance(userId: UUID, dto: CreateWalletDTO): Wallet
        +archiveWallet(userId: UUID, walletId: UUID): void
        +getWalletDetails(walletId: UUID): Wallet
        +validateWalletAvailable(walletId: UUID, amount: Decimal): boolean
    }

    class ILedgerEquityPort {
        <<Interface / Port>>
        +createOpeningEquityEntry(userId: UUID, walletId: UUID, openingBalance: Decimal, currency: String): void
    }

    %% ==========================================
    %% ENTITY / PERSISTENCE LAYER (Repositories)
    %% ==========================================
    class WalletRepository {
        <<Entity / Repository>>
        +findById(id: UUID): Optional~Wallet~
        +findByUserId(userId: UUID): List~Wallet~
        +findWithLock(id: UUID): Optional~Wallet~
        +save(wallet: Wallet): Wallet
        +hasTransactions(walletId: UUID): boolean
        +updateCachedBalance(walletId: UUID, delta: Decimal): void
    }

    %% ==========================================
    %% DOMAIN ENTITIES & VALUE OBJECTS
    %% ==========================================
    class Wallet {
        <<Entity>>
        -id: UUID
        -userId: UUID
        -name: String
        -walletType: WalletType
        -currency: String
        -cachedBalance: Decimal
        -status: WalletStatus
        -createdAt: DateTime
        +deposit(amount: Decimal): void
        +withdraw(amount: Decimal): void
        +canWithdraw(amount: Decimal): boolean
        +archive(): void
        +isArchived(): boolean
    }

    class WalletType {
        <<enumeration>>
        CASH
        BANK
        E_WALLET
        SAVINGS
        CREDIT
    }

    class WalletStatus {
        <<enumeration>>
        ACTIVE
        ARCHIVED
    }

    %% ==========================================
    %% RELATIONSHIPS & INTERACTIONS
    %% ==========================================
    EndUserActor ..> WalletController : interacts (HTTP/REST)
    WalletController --> WalletService : delegates
    WalletService --> WalletRepository : queries/locks
    WalletService ..> ILedgerEquityPort : creates_opening_equity (BR01)
    WalletRepository ..> Wallet : persists
    Wallet --> WalletType : categorized_as
    Wallet --> WalletStatus : state_of
```
</details>

> 🔗 **Liên kết ánh xạ liên phân hệ:** Cổng `ILedgerEquityPort` được hiện thực hóa bởi `LedgerService` tại [Phân hệ 3 (Sổ cái Kế toán kép)](#23-phân-hệ-3-ghi-nhận-giao-dịch-thu-chi--sổ-cái-kế-toán-kép-double-entry-ledger-context---uc03) nhằm hạch toán đối ứng số dư đầu kỳ vào tài khoản Vốn chủ sở hữu (`Equity`).

#### Thuyết minh Trách nhiệm & Phương thức Nghiệp vụ (Class Responsibilities - UC02):

1. **`WalletController` (Boundary):** Tiếp nhận yêu cầu mở ví, xem danh sách ví, đóng ví (`archiveWallet`) và điều chỉnh số dư đối soát (`adjustBalance`).
2. **`WalletService` (Control):**
   - **Mở ví kèm số dư ban đầu ([BR01](../SRS.md#br01), [BR02](../SRS.md#br02)):** Khi tạo ví có `openingBalance > 0`, service gọi qua cổng `ILedgerEquityPort` sang phân hệ Sổ cái để sinh một bút toán kế toán kép cân bằng đối ứng: Ghi Nợ (Debit) Ví tài sản và Ghi Có (Credit) Vốn chủ sở hữu (`Equity`).
   - **Bảo toàn lịch sử ví, cấm xóa cứng ([BR02](../SRS.md#br02)):** Khi người dùng muốn xóa ví, `WalletService` gọi `WalletRepository.hasTransactions()`. Nếu ví đã từng phát sinh giao dịch trong lịch sử sổ cái, hệ thống từ chối xóa cứng (`Hard Delete`) mà chuyển trạng thái sang `ARCHIVED` (đóng ví) để bảo toàn khả năng kiểm toán.
   - **Thẩm định chặn âm ví ([BR04](../SRS.md#br04)):** Phương thức `validateWalletAvailable()` chặn các giao dịch chi vượt số dư đối với tiền mặt, ngân hàng, ví điện tử; chỉ riêng ví Thẻ tín dụng (`CREDIT`) mới được phép mang số dư âm trong hạn mức thấu chi.
3. **`Wallet` (Rich Domain Entity):** Tự đóng gói logic biến động số dư qua `deposit()`, `withdraw()`, tự kiểm tra khả năng chi trả qua `canWithdraw()` và chuyển trạng thái qua `archive()`.
4. **`WalletRepository` (Persistence):** Hỗ trợ phương thức `findWithLock(id)` áp dụng khóa mức dòng (`SELECT ... FOR UPDATE`) để ngăn ngừa Race Condition khi phát sinh giao dịch đồng thời ([BR13](../SRS.md#br13)).

---

### 2.3. Phân hệ 3: Ghi nhận Giao dịch Thu chi & Sổ cái Kế toán kép (Double-Entry Ledger Context - UC03)

> **Tham chiếu nghiệp vụ:** [FR03](../SRS.md#fr03), [FR04](../SRS.md#fr04), [FR15](../SRS.md#fr15)  
> **Use Case:** [UC03](../SRS.md#uc03) (Ghi nhận giao dịch tài chính thu chi & chuyển tiền)  
> **Sequence Diagrams:** [SQ02](./sequence_diagram/sq02_expense_budget.md), [SQ03](./sequence_diagram/sq03_transfer_reversal.md)  
> **Quy tắc cốt lõi:** [BR01](../SRS.md#br01) (Cân bằng Nợ/Có kép), [BR06](../SRS.md#br06) (Bút toán đảo `REVERSED`), [BR12](../SRS.md#br12) (ACID nguyên tử), [BR13](../SRS.md#br13) (Idempotency chống trùng), [BR15](../SRS.md#br15) (Tỷ giá snapshot & Bình quân gia quyền)

#### Sơ đồ Lớp Trực quan (Vector SVG):

![CD03 Class Diagram](./class_diagram/cd03_double_entry_ledger.svg)

<details>
<summary>🔍 <b>Nhấn để xem mã nguồn Mermaid (CD03)</b></summary>

```mermaid
classDiagram
    direction TB

    %% ==========================================
    %% ACTORS (Tác nhân tương tác)
    %% ==========================================
    class EndUserActor {
        <<Actor: Người dùng A01>>
        +createExpense(walletId, categoryId, amount, date)
        +createIncome(walletId, categoryId, amount, date)
        +transfer(srcWalletId, dstWalletId, amount)
        +reverseTransaction(transactionId, reason)
        +getTransactions(filter, page)
    }

    %% ==========================================
    %% BOUNDARY LAYER (Controllers)
    %% ==========================================
    class TransactionController {
        <<Boundary>>
        +createExpense(dto: CreateExpenseDTO, idempKey: String): ResponseEntity
        +createIncome(dto: CreateIncomeDTO, idempKey: String): ResponseEntity
        +transfer(dto: TransferDTO, idempKey: String): ResponseEntity
        +reverseTransaction(txId: UUID, dto: ReverseDTO): ResponseEntity
        +getTransactions(filter: TransactionFilterDTO, page: int): ResponseEntity
    }

    %% ==========================================
    %% CONTROL LAYER (Services & Ports)
    %% ==========================================
    class LedgerService {
        <<Control>>
        -ledgerRepository: LedgerRepository
        -categoryRepository: CategoryRepository
        -idempotencyRepository: IdempotencyRepository
        -walletPort: IWalletLockPort
        -fxPort: IExchangeRatePort
        +recordExpense(userId: UUID, dto: CreateExpenseDTO, idempKey: String): FinancialTransaction
        +recordIncome(userId: UUID, dto: CreateIncomeDTO, idempKey: String): FinancialTransaction
        +transferBetweenWallets(userId: UUID, dto: TransferDTO, idempKey: String): FinancialTransaction
        +reversePostedTransaction(userId: UUID, txId: UUID, reason: String): FinancialTransaction
        +createOpeningEquityEntry(userId: UUID, walletId: UUID, amount: Decimal, currency: String): void
        +validateDoubleEntry(entries: List~JournalEntry~): boolean
    }

    class IWalletLockPort {
        <<Interface / Port>>
        +findWithLock(walletId: UUID): Optional~WalletRef~
        +updateCachedBalance(walletId: UUID, delta: Decimal): void
    }

    class IExchangeRatePort {
        <<Interface / Port>>
        +getLatestRate(fromCurr: String, toCurr: String): Decimal
        +calculateWeightedAverageRate(walletId: UUID, amount: Decimal): Decimal
    }

    %% ==========================================
    %% ENTITY / PERSISTENCE LAYER (Repositories)
    %% ==========================================
    class LedgerRepository {
        <<Entity / Repository>>
        +saveTransactionWithEntries(tx: FinancialTransaction, entries: List~JournalEntry~): FinancialTransaction
        +findTransactionById(id: UUID): Optional~FinancialTransaction~
        +findTransactionsByUser(userId: UUID, filter: TransactionFilterDTO, page: int): Page~FinancialTransaction~
    }

    class CategoryRepository {
        <<Entity / Repository>>
        +findById(id: UUID): Optional~Category~
        +findByUserId(userId: UUID): List~Category~
        +save(category: Category): Category
    }

    class IdempotencyRepository {
        <<Entity / Repository>>
        +findByKey(userId: UUID, key: String): Optional~IdempotencyRecord~
        +save(record: IdempotencyRecord): void
    }

    %% ==========================================
    %% DOMAIN ENTITIES & ENUMS
    %% ==========================================
    class Category {
        <<Entity>>
        -id: UUID
        -userId: UUID
        -name: String
        -categoryType: CategoryType
        -isArchived: boolean
    }

    class CategoryType {
        <<enumeration>>
        INCOME
        EXPENSE
    }

    class FinancialTransaction {
        <<Entity / Aggregate Root>>
        -id: UUID
        -userId: UUID
        -categoryId: UUID
        -transactionType: TransactionType
        -status: TransactionStatus
        -description: String
        -currency: String
        -amount: Decimal
        -fxRateSnapshot: Decimal
        -occurredOn: Date
        -postedAt: DateTime
        -originalTransactionId: UUID
        -entries: List~JournalEntry~
        +addEntry(entry: JournalEntry): void
        +validateBalance(): boolean
        +post(): void
        +createReversal(reversalReason: String): FinancialTransaction
    }

    class TransactionType {
        <<enumeration>>
        INCOME
        EXPENSE
        TRANSFER
        ADJUSTMENT
    }

    class TransactionStatus {
        <<enumeration>>
        DRAFT
        POSTED
        REVERSED
    }

    class JournalEntry {
        <<Entity>>
        -id: UUID
        -transactionId: UUID
        -walletId: UUID
        -entrySide: EntrySide
        -amountOriginal: Decimal
        -currency: String
        -amountBase: Decimal
        -fxRateSnapshot: Decimal
        +isDebit(): boolean
        +isCredit(): boolean
    }

    class EntrySide {
        <<enumeration>>
        DEBIT
        CREDIT
    }

    %% ==========================================
    %% RELATIONSHIPS & INTERACTIONS
    %% ==========================================
    %% Central Spine: Actor -> Controller -> Service -> Repository -> Aggregate Root -> Child
    EndUserActor ..> TransactionController : interacts (HTTP/REST)
    TransactionController --> LedgerService : delegates
    LedgerService --> LedgerRepository : persists_acid (BR12)
    LedgerRepository ..> FinancialTransaction : persists_aggregate
    FinancialTransaction "1" *-- "2..*" JournalEntry : balances (BR01)
    JournalEntry --> EntrySide : side_of

    %% Left Ports (Wallet & FX)
    LedgerService ..> IWalletLockPort : locks_wallet (BR13)
    LedgerService ..> IExchangeRatePort : gets_snapshot_rate (BR15)

    %% Right Branch (Idempotency & Category Hierarchy)
    LedgerService --> IdempotencyRepository : checks_dedup (BR13)
    LedgerService --> CategoryRepository : verifies_category
    CategoryRepository ..> Category : persists
    Category --> CategoryType : typed_by

    %% Enums and Association
    FinancialTransaction ..> Category : categorizes
    FinancialTransaction --> TransactionType : typed_by
    FinancialTransaction --> TransactionStatus : state_of
```
</details>

> 🔗 **Liên kết ánh xạ liên phân hệ:**
> - Cổng `IWalletLockPort` được liên kết trực tiếp với `WalletRepository` tại [Phân hệ 2 (Quản lý Ví)](#22-phân-hệ-2-quản-lý-ví-tài-sản--số-dư-mở-sổ-wallet-management-context---uc02), đồng thời trường `walletId` của `JournalEntry` tham chiếu đến thực thể `Wallet`.
> - Cổng `IExchangeRatePort` cung cấp tỷ giá hối đoái snapshot và tính bình quân gia quyền ([BR15](../SRS.md#br15)).

#### Thuyết minh Trách nhiệm & Phương thức Nghiệp vụ (Class Responsibilities - UC03):

1. **`TransactionController` (Boundary):** Cổng tiếp nhận toàn bộ các giao dịch tài chính thu chi, chuyển tiền liên ví và yêu cầu đảo giao dịch. Bắt buộc nhận `Idempotency-Key` từ HTTP header để chống gửi trùng lặp ([BR13](../SRS.md#br13)).
2. **`LedgerService` (Control - Trọng tâm Kế toán kép & ACID):**
   - **Nguyên tắc kế toán kép bất biến ([BR01](../SRS.md#br01)):** Mọi phát sinh thu, chi, chuyển tiền đều được phân rã thành tối thiểu 2 dòng `JournalEntry` (Nợ / Có). Phương thức `validateDoubleEntry()` bảo đảm $\sum Debit = \sum Credit$; nếu phát hiện mất cân bằng dù chỉ 1 đồng, toàn bộ thao tác bị hủy bỏ và Rollback tức thì.
   - **Giao dịch nguyên tử ACID & Khóa dòng ([BR12](../SRS.md#br12), [BR13](../SRS.md#br13)):** Tương tác với ví thông qua cổng `IWalletLockPort` với lệnh khóa mức dòng (`SELECT ... FOR UPDATE`), đồng thời lưu vết vào `IdempotencyRepository` để loại bỏ triệt để xung đột Race Condition khi người dùng bấm đúp.
   - **Cơ chế đảo giao dịch an toàn ([BR06](../SRS.md#br06)):** Giao dịch đã ở trạng thái `POSTED` tuyệt đối không được sửa hay xóa; phương thức `reversePostedTransaction()` tạo ra một giao dịch mới mang trạng thái `REVERSED` với các dòng Nợ/Có đối ứng ngược lại hoàn toàn, tự trỏ đến `originalTransactionId`.
3. **`IExchangeRatePort` (Control / Port):** Cung cấp giao diện tỷ giá đa tiền tệ ([BR15](../SRS.md#br15)). Ghi nhận snapshot tỷ giá tại thời điểm phát sinh giao dịch và áp dụng phương pháp bình quân gia quyền khi xuất ngoại tệ khỏi ví.
4. **`FinancialTransaction` (Rich Entity / Aggregate Root):** Đóng gói toàn bộ các dòng sổ cái cấu thành (`JournalEntry`). Tự kiểm tra cân bằng Nợ/Có qua `validateBalance()` và tự sinh bút toán đảo đối ứng qua `createReversal()`.

---

### 2.4. Phân hệ 4: Báo cáo Đối soát Sổ cái & Xuất CSV An toàn (Ledger Reconciliation & Secure CSV Export Context - UC11)

> **Tham chiếu nghiệp vụ:** [FR11](../SRS.md#fr11), [FR12](../SRS.md#fr12), [FR15](../SRS.md#fr15)  
> **Use Case:** [UC11](../SRS.md#uc11) (Báo cáo đối soát sổ cái & Xuất dữ liệu CSV)  
> **Sequence Diagram:** [SQ07](./sequence_diagram/sq07_reconciliation_csv.md)  
> **Quy tắc cốt lõi:** [BR01](../SRS.md#br01) (Cân bằng Bảng cân đối thử Trial Balance), [BR14](../SRS.md#br14) (Tách biệt trạng thái giao dịch: chỉ đối soát giao dịch `POSTED`), [BR16](../SRS.md#br16) (Khử độc CSV Formula Injection & UTF-8 BOM)

#### Sơ đồ Lớp Trực quan (Vector SVG):

![CD04 Class Diagram](./class_diagram/cd04_reconciliation_csv.svg)

<details>
<summary>🔍 <b>Nhấn để xem mã nguồn Mermaid (CD04)</b></summary>

```mermaid
classDiagram
    direction TB

    %% ==========================================
    %% ACTORS (Tác nhân tương tác)
    %% ==========================================
    class EndUserActor {
        <<Actor: Người dùng A01>>
        +viewTrialBalance(asOfDate)
        +exportLedgerCsv(dateRange, walletId, categoryId)
    }

    %% ==========================================
    %% BOUNDARY LAYER (Controllers)
    %% ==========================================
    class ReportController {
        <<Boundary>>
        +getTrialBalance(userId: UUID, asOfDate: Date): ResponseEntity
        +exportLedgerCsv(userId: UUID, filter: ExportFilterDTO): ResponseEntity
    }

    %% ==========================================
    %% CONTROL LAYER (Services)
    %% ==========================================
    class ReconciliationService {
        <<Control>>
        -ledgerQueryPort: ILedgerQueryPort
        +generateTrialBalance(userId: UUID, asOfDate: Date): TrialBalanceReport
        +verifyLedgerIntegrity(userId: UUID): boolean
    }

    class ExportService {
        <<Control>>
        -ledgerQueryPort: ILedgerQueryPort
        +sanitizeCsvField(input: String): String
        +generateSecureCsv(transactions: List~ExportTransactionRow~): byte[]
    }

    class ILedgerQueryPort {
        <<Interface / Port>>
        +getTrialBalanceBalances(userId: UUID, asOfDate: Date): Map~UUID, BalancePair~
        +findPostedTransactions(userId: UUID, filter: ExportFilterDTO): List~ExportTransactionRow~
    }

    %% ==========================================
    %% VALUE OBJECTS & REPORT DTOs
    %% ==========================================
    class TrialBalanceReport {
        <<Value Object>>
        -asOfDate: Date
        -totalDebit: Decimal
        -totalCredit: Decimal
        -isBalanced: boolean
        -accounts: List~AccountTrialBalanceRow~
        +checkEquilibrium(): boolean
    }

    class AccountTrialBalanceRow {
        <<Value Object>>
        -accountCode: String
        -accountName: String
        -accountType: String
        -debitAmount: Decimal
        -creditAmount: Decimal
        -endingBalance: Decimal
    }

    class ExportTransactionRow {
        <<Value Object>>
        -transactionId: UUID
        -occurredOn: Date
        -description: String
        -categoryName: String
        -walletName: String
        -amount: Decimal
        -currency: String
        -status: String
    }

    %% ==========================================
    %% RELATIONSHIPS & INTERACTIONS
    %% ==========================================
    EndUserActor ..> ReportController : interacts (HTTP/REST)
    ReportController --> ReconciliationService : requests_trial_balance
    ReportController --> ExportService : requests_csv_export
    ReconciliationService ..> ILedgerQueryPort : queries_ledger_balances
    ExportService ..> ILedgerQueryPort : queries_posted_rows
    ReconciliationService ..> TrialBalanceReport : produces
    TrialBalanceReport *-- AccountTrialBalanceRow : aggregates
    ExportService ..> ExportTransactionRow : sanitizes_and_encodes
```
</details>

> 🔗 **Liên kết ánh xạ liên phân hệ:** Cổng truy vấn `ILedgerQueryPort` được hiện thực hóa trực tiếp bởi `LedgerRepository` tại [Phân hệ 3 (Sổ cái Kế toán kép)](#23-phân-hệ-3-ghi-nhận-giao-dịch-thu-chi--sổ-cái-kế-toán-kép-double-entry-ledger-context---uc03).

#### Thuyết minh Trách nhiệm & Phương thức Nghiệp vụ (Class Responsibilities - UC11):

1. **`ReportController` (Boundary):** Tiếp nhận yêu cầu kiểm toán đối soát sổ sách và yêu cầu xuất báo cáo tài chính ra file CSV an toàn.
2. **`ReconciliationService` (Control - Đối soát Bảng cân đối thử):**
   - **Kiểm soát tính cân bằng toàn vẹn ([BR01](../SRS.md#br01), [NFR03.01](../SRS.md#nfr0301)):** Tổng hợp số dư của toàn bộ 5 loại tài khoản (Tài sản, Nợ phải trả, Vốn chủ sở hữu, Doanh thu, Chi phí) tại ngày chốt sổ `asOfDate`.
   - **Tách biệt trạng thái giao dịch ([BR14](../SRS.md#br14)):** Báo cáo chỉ tổng hợp các giao dịch đã ghi sổ (`POSTED`) và các dòng đảo đối ứng (`REVERSED`), hoàn toàn loại trừ các giao dịch nháp (`DRAFT`). Phương thức `checkEquilibrium()` xác nhận $\sum Debit = \sum Credit$ đạt tỷ lệ chính xác tuyệt đối 100.000%.
3. **`ExportService` (Control - Xuất CSV Bảo mật):**
   - **Khử độc CSV Formula Injection ([BR16](../SRS.md#br16)):** Tự động duyệt qua từng ô dữ liệu chuỗi (mô tả, tên danh mục, tên ví). Nếu phát hiện ký tự bắt đầu là `=, +, -, @, \t, \r`, phương thức `sanitizeCsvField()` tự động chèn thêm ký tự nháy đơn `'` ở đầu để ngăn chặn triệt để mã độc thực thi khi mở file trên Microsoft Excel hoặc LibreOffice.
   - **Chuẩn hóa hiển thị tiếng Việt:** Tự động chèn 3 bytes BOM UTF-8 (`\xEF\xBB\xBF`) vào đầu tệp CSV để phần mềm bảng tính mở trực tiếp không bị lỗi font tiếng Việt.
4. **`TrialBalanceReport` & `AccountTrialBalanceRow` (Value Objects):** Các đối tượng giá trị mang kết quả kiểm toán, không có định danh độc lập, bảo đảm tính bất biến của báo cáo đối soát.

---

---

## 3. Bảng Ánh xạ Nhanh (Traceability)

*(Nội dung phần này sẽ được tổng hợp đầy đủ sau khi hoàn tất thiết kế chi tiết 6 phân hệ).*
