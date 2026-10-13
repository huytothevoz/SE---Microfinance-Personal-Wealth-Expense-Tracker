# Sơ đồ Lớp (Class Diagram) - WealthFlow

> Tham chiếu: [SRS.md](../SRS.md) v2.1 | [Sequence Diagrams](./sequence_diagram.md)

---

## 1. Sơ đồ Thực thể Nghiệp vụ (Domain Model)

Sơ đồ thể hiện các thực thể dữ liệu chính và mối quan hệ theo nguyên tắc kế toán kép và quản lý tài sản:

```mermaid
classDiagram
    direction TB

    class User {
        +UUID id
        +String email
        +String passwordHash
        +String fullName
        +String role
        +String baseCurrency
        +String status
    }

    class Wallet {
        +UUID id
        +UUID userId
        +String name
        +String type
        +String currency
        +Decimal balance
    }

    class Transaction {
        +UUID id
        +UUID userId
        +String type
        +Date date
        +String status
        +String idempotencyKey
        +UUID originalId
    }

    class JournalEntry {
        +UUID id
        +UUID transactionId
        +UUID walletId
        +String direction
        +Decimal amount
    }

    class Budget {
        +UUID id
        +UUID userId
        +UUID categoryId
        +String monthYear
        +Decimal limitAmount
    }

    class Loan {
        +UUID id
        +UUID userId
        +String title
        +String type
        +Decimal initialPrincipal
        +Decimal remainingPrincipal
        +String status
    }

    class LoanRepayment {
        +UUID id
        +UUID loanId
        +UUID transactionId
        +Decimal principalPaid
        +Decimal interestPaid
        +Date paymentDate
    }

    class GoldAsset {
        +UUID id
        +UUID userId
        +String name
        +String goldType
        +Decimal quantityChi
        +Decimal currentMarketValue
    }

    class Subscription {
        +UUID id
        +UUID userId
        +String name
        +Decimal amount
        +Integer anchorDay
        +Date nextDueDate
        +Boolean isAutoPost
    }

    class FinancialGoal {
        +UUID id
        +UUID userId
        +String title
        +Decimal targetAmount
        +Decimal currentAmount
        +Date deadlineDate
    }

    %% Relationships
    User "1" *-- "1..*" Wallet : owns
    User "1" *-- "0..*" Transaction : creates
    User "1" *-- "0..*" Budget : sets
    User "1" *-- "0..*" Loan : manages
    User "1" *-- "0..*" GoldAsset : holds
    User "1" *-- "0..*" Subscription : schedules
    User "1" *-- "0..*" FinancialGoal : pursues

    Transaction "1" *-- "2..*" JournalEntry : balances (BR01)
    Wallet "1" o-- "0..*" JournalEntry : posts_to
    Transaction "0..1" <-- "0..*" Transaction : reverses (BR06)
    Loan "1" *-- "0..*" LoanRepayment : tracks
```

---

## 2. Sơ đồ Lớp Thiết kế Chi tiết (BCE Architecture)

Phân bổ trách nhiệm theo mô hình **Boundary - Control - Entity** qua 3 tầng:

```mermaid
classDiagram
    direction TB

    %% Controllers (Boundary)
    class AuthController {
        +register(dto)
        +login(dto)
        +refreshToken(dto)
    }

    class TransactionController {
        +createExpense(dto)
        +createIncome(dto)
        +transfer(dto)
        +reverseTransaction(id)
    }

    class LoanController {
        +createLoan(dto)
        +repayLoan(id, dto)
    }

    class DashboardController {
        +getOverview(period)
    }

    class AdminController {
        +lockUser(id, dto)
        +broadcastAnnouncement(dto)
    }

    %% Services (Control)
    class AuthService {
        +register(dto)
        +login(email, pass)
    }

    class LedgerService {
        +recordExpense(dto, idempKey)
        +transfer(dto)
        +reversePostedTransaction(id)
    }

    class BudgetService {
        +evaluateBudget(userId, catId)
    }

    class LoanService {
        +createLoan(dto)
        +processRepayment(id, dto)
    }

    class AnalyticsService {
        +calculateNetWorth(userId)
        +calculateCashFlow(userId)
    }

    class AdminService {
        +lockUserAndRevokeToken(id)
    }

    %% Repositories (Entity / Persistence)
    class UserRepository {
        +findByEmail(email)
        +save(user)
    }

    class LedgerRepository {
        +saveTransactionWithEntries(tx, entries)
        +findCumulativeSum(walletId)
    }

    class WalletRepository {
        +updateBalanceWithLock(id, amount)
    }

    class LoanRepository {
        +saveRepayment(repayment, tx)
        +updateRemaining(id, principal)
    }

    %% Connections
    AuthController --> AuthService
    AuthService --> UserRepository

    TransactionController --> LedgerService
    TransactionController --> BudgetService
    LedgerService --> LedgerRepository
    LedgerService --> WalletRepository

    LoanController --> LoanService
    LoanService --> LoanRepository
    LoanService --> LedgerService

    DashboardController --> AnalyticsService
    AdminController --> AdminService
```

---

## 3. Bảng Ánh xạ Nhanh (Traceability)

| Phân hệ | Boundary (Controller) | Control (Service) | Entity (Repository) | Use Case & Sequence |
| :--- | :--- | :--- | :--- | :---: |
| **Xác thực** | `AuthController` | `AuthService` | `UserRepository` | `UC01` (SQ01) |
| **Sổ cái & Ví** | `TransactionController`, `WalletController` | `LedgerService` | `LedgerRepository`, `WalletRepository` | `UC02, UC03` (SQ02, SQ03) |
| **Ngân sách** | `TransactionController` | `BudgetService` | `BudgetRepository` | `UC05` (SQ02) |
| **Định kỳ** | *(SYS Cron)* | `SubscriptionWorker` | `SubscriptionRepository` | `UC04` (SQ04) |
| **Vay nợ** | `LoanController` | `LoanService` | `LoanRepository` | `UC06, UC07` (SQ05) |
| **Tài sản & Net Worth** | `DashboardController`, `GoldController` | `AnalyticsService`, `GoldService` | `AssetRepository` | `UC08, UC10` (SQ06) |
| **Đối soát & CSV** | `ReportController` | `ReconciliationService`, `ExportService` | `LedgerRepository` | `UC11` (SQ07) |
| **Quản trị** | `AdminController` | `AdminService`, `NotificationService` | `AdminRepository` | `UC12, UC13` (SQ08) |
