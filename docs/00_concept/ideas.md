# Ý tưởng ban đầu

## Đề tài

**Microfinance - Personal Wealth - Expense Tracker**  
**Ứng dụng Theo dõi Chi tiêu, Tài sản cá nhân và Quản lý tài chính quy mô nhỏ**

Phân tích đề tài trên, có thể tách thành 3 thành phần chính:

- **Microfinance:** Quản lý các dòng tiền nhỏ, khoản vay nhỏ, tiết kiệm, nợ và kế hoạch tài chính cá nhân.
- **Personal Wealth:** Không chỉ theo dõi tiền mặt mà còn theo dõi tổng tài sản, tổng nợ và tài sản ròng của người dùng.
- **Expense Tracker:** Ghi nhận, phân loại và phân tích các khoản thu/chi hằng ngày.

---

# 1. Ý tưởng tổng quan

Đề tài hướng đến xây dựng một hệ thống quản lý tài chính cá nhân giúp người dùng:

- Theo dõi dòng tiền hằng ngày.
- Kiểm soát chi tiêu.
- Quản lý tài sản và các khoản nợ.
- Xây dựng kế hoạch tiết kiệm.
- Theo dõi sự thay đổi tình hình tài chính theo thời gian.
- Lập kế hoạch tích lũy tài sản từ những khoản tiền nhỏ.

Thay vì chỉ là một ứng dụng ghi chép thu - chi thông thường, hệ thống hướng đến việc giúp người dùng chuyển từ:

> **"Ghi lại mình đã tiêu bao nhiêu."**

sang:

> **"Hiểu tình hình tài chính hiện tại và chủ động cải thiện tài sản của mình."**

## Đối tượng người dùng hướng đến

- Học sinh, sinh viên.
- Người mới đi làm.
- Người có thu nhập thấp đến trung bình.
- Cá nhân hoặc hộ gia đình có nhu cầu quản lý tài chính cơ bản.
- Người bắt đầu tiết kiệm và tích lũy từ những khoản tiền nhỏ.

## Tên đề xuất cho hệ thống

**WealthFlow**

Tên này thể hiện ý tưởng theo dõi và quản lý sự vận động của dòng tiền, tài sản và tình hình tài chính cá nhân theo thời gian.

---

# 2. Tổng quan và mục tiêu của đề tài

Xây dựng một hệ thống giúp cá nhân kiểm soát dòng tiền, quản lý chi tiêu, theo dõi tài sản, quản lý nợ và lập kế hoạch tích lũy tài chính một cách hợp lý.

Hệ thống đặc biệt hướng đến các đối tượng có nguồn tài chính ban đầu chưa lớn như:

- Học sinh, sinh viên.
- Người mới đi làm.
- Người có thu nhập thấp đến trung bình.
- Cá nhân hoặc hộ gia đình có nhu cầu quản lý tài chính cá nhân.

Mục tiêu của hệ thống là giúp người dùng không chỉ biết mình đã tiêu bao nhiêu mà còn hiểu:

- Hiện tại mình đang có bao nhiêu tài sản.
- Tiền của mình đang được sử dụng vào đâu.
- Mình đang tiết kiệm được bao nhiêu.
- Mình đang có bao nhiêu khoản nợ.
- Tình hình tài chính đang tốt lên hay xấu đi.
- Bao lâu nữa có thể đạt được mục tiêu tài chính đã đặt ra.

---

## 2.1. Mục tiêu tổng quát

Mục tiêu cốt lõi của hệ thống là hỗ trợ người dùng chuyển từ:

> **Passive Expense Tracking**

sang:

> **Active Personal Wealth Management**

Tức là chuyển từ:

> **Ghi chép chi tiêu một cách thụ động**

sang:

> **Chủ động quản lý, lập kế hoạch và cải thiện tình hình tài chính cá nhân.**

---

## 2.2. Mục tiêu cụ thể

Hệ thống cần hỗ trợ người dùng:

1. Ghi nhận và quản lý các khoản thu - chi.
2. Quản lý nhiều nguồn tiền như tiền mặt, tài khoản ngân hàng và ví điện tử.
3. Thiết lập ngân sách cho từng nhóm chi tiêu.
4. Theo dõi tài sản và các khoản nợ.
5. Tính toán tài sản ròng - **Net Worth**.
6. Theo dõi khả năng tiết kiệm.
7. Tạo và quản lý các mục tiêu tài chính.
8. Quản lý các khoản vay hoặc khoản nợ nhỏ.
9. Phân tích hành vi chi tiêu.
10. Cung cấp báo cáo tài chính theo tuần, tháng và năm.
11. Cảnh báo khi người dùng có dấu hiệu vượt ngân sách.
12. Cho người dùng thấy xu hướng tài chính của mình theo thời gian.

---

# 3. Các câu hỏi chính mà hệ thống cần trả lời

Hệ thống cần giúp người dùng trả lời được ba câu hỏi chính:

### 1. Tôi đang có bao nhiêu tài sản?

Hệ thống tổng hợp:

- Tiền mặt.
- Tài khoản ngân hàng.
- Ví điện tử.
- Tiền tiết kiệm.
- Các tài sản khác.
- Các khoản nợ.

Từ đó tính toán **Net Worth**.

### 2. Tiền của tôi đang đi đâu?

Thông qua:

- Lịch sử giao dịch.
- Phân loại chi tiêu.
- Thống kê theo danh mục.
- Báo cáo theo tuần/tháng.
- So sánh thu nhập và chi tiêu.

### 3. Tình hình tài chính của tôi đang tốt lên hay xấu đi?

Thông qua các chỉ số:

- Cash Flow.
- Saving Rate.
- Net Worth.
- Tổng tài sản.
- Tổng nợ.
- Mức độ sử dụng ngân sách.
- Tiến độ mục tiêu tài chính.

---

# 4. Các chức năng chính dự kiến

Hệ thống được chia thành các module chính sau.

---

## 4.1. Quản lý người dùng

Các chức năng:

- Đăng ký.
- Đăng nhập.
- Đăng xuất.
- Cập nhật thông tin cá nhân.
- Đổi mật khẩu.

---

## 4.2. Quản lý tài khoản / ví

Người dùng có thể quản lý nhiều nguồn tiền khác nhau:

- Tiền mặt.
- Tài khoản ngân hàng.
- Ví điện tử.
- Tài khoản tiết kiệm.

Các chức năng:

- Thêm tài khoản.
- Cập nhật tài khoản.
- Xóa tài khoản.
- Xem số dư.
- Chuyển tiền giữa các tài khoản.

Ví dụ:

| Tài khoản | Loại | Số dư |
|---|---|---:|
| Tiền mặt | Cash | 1.500.000đ |
| Vietcombank | Bank | 8.000.000đ |
| MoMo | E-Wallet | 500.000đ |
| Tiết kiệm | Saving | 10.000.000đ |

---

## 4.3. Quản lý giao dịch

Các chức năng:

- Thêm khoản thu.
- Thêm khoản chi.
- Chuyển tiền giữa các tài khoản.
- Phân loại giao dịch.
- Chỉnh sửa giao dịch.
- Xóa giao dịch.
- Tìm kiếm giao dịch.
- Xem lịch sử giao dịch.

Ví dụ các nhóm chi tiêu:

- Ăn uống.
- Đi lại.
- Học tập.
- Mua sắm.
- Giải trí.
- Nhà ở.
- Hóa đơn.
- Sức khỏe.

---

## 4.4. Quản lý ngân sách

Người dùng có thể thiết lập giới hạn ngân sách cho từng nhóm chi tiêu.

Ví dụ:

```text
Ăn uống:    2.000.000đ / tháng
Giải trí:     500.000đ / tháng
Đi lại:       700.000đ / tháng
```

Hệ thống theo dõi mức sử dụng ngân sách và cảnh báo khi:

- Gần đạt giới hạn ngân sách.
- Đã sử dụng phần lớn ngân sách.
- Vượt ngân sách đã thiết lập.

Ví dụ:

```text
Ngân sách ăn uống

Đã sử dụng: 1.600.000đ
Giới hạn:    2.000.000đ

Tiến độ: 80%
```

---

## 4.5. Quản lý tài sản và nợ

Hệ thống cho phép người dùng theo dõi:

### Assets

- Tiền mặt.
- Tiền trong tài khoản.
- Tiền tiết kiệm.
- Các tài sản khác.

### Liabilities

- Khoản vay.
- Tiền đang nợ.
- Khoản trả góp.
- Các nghĩa vụ tài chính khác.

Hệ thống tính tài sản ròng theo công thức:

```text
Net Worth = Total Assets - Total Liabilities
```

Trong đó:

- **Total Assets:** Tổng tài sản.
- **Total Liabilities:** Tổng nợ.
- **Net Worth:** Tài sản ròng.

---

## 4.6. Quản lý khoản vay / khoản nợ

Người dùng có thể ghi nhận:

- Tên khoản vay.
- Số tiền vay.
- Người hoặc tổ chức cho vay.
- Ngày bắt đầu.
- Thời hạn.
- Số tiền đã trả.
- Số tiền còn lại.
- Lịch thanh toán.
- Trạng thái khoản vay.

Ví dụ:

```text
Khoản vay: Laptop
Số tiền ban đầu: 15.000.000đ
Đã trả: 9.000.000đ
Còn lại: 6.000.000đ
```

Đây là một trong những chức năng thể hiện yếu tố **Microfinance** của đề tài.

---

## 4.7. Quản lý mục tiêu tài chính

Người dùng có thể tạo các mục tiêu tài chính.

Ví dụ:

```text
Mục tiêu: Mua laptop
Số tiền cần: 30.000.000đ
Hiện có: 12.000.000đ
Thời hạn: 9 tháng
```

Hệ thống có thể tính toán:

```text
Số tiền còn thiếu: 18.000.000đ

Số tiền cần tiết kiệm trung bình:
18.000.000 / 9 = 2.000.000đ / tháng
```

Hệ thống theo dõi tiến độ mục tiêu theo thời gian.

---

## 4.8. Dashboard

Dashboard cung cấp cái nhìn tổng quan về tình hình tài chính của người dùng.

Các thông tin chính:

- Tổng số dư.
- Thu nhập trong tháng.
- Chi tiêu trong tháng.
- Số tiền tiết kiệm.
- Saving Rate.
- Tổng tài sản.
- Tổng nợ.
- Net Worth.
- Mức độ sử dụng ngân sách.
- Tiến độ mục tiêu tài chính.

Ví dụ:

```text
---------------------------------------
          FINANCIAL OVERVIEW
---------------------------------------

Income          8.000.000đ
Expense         5.200.000đ
Saving          2.800.000đ

Net Worth      45.000.000đ

Saving Rate          35%

---------------------------------------
```

---

## 4.9. Báo cáo và phân tích

Hệ thống hỗ trợ tạo báo cáo theo:

- Tuần.
- Tháng.
- Năm.
- Khoảng thời gian tùy chọn.

Các chỉ số chính:

### Cash Flow

```text
Cash Flow = Income - Expense
```

### Saving Rate

```text
Saving Rate =
(Income - Expense) / Income × 100%
```

### Net Worth

```text
Net Worth =
Assets - Liabilities
```

Ngoài ra, hệ thống có thể hiển thị:

- Nhóm chi tiêu cao nhất.
- Xu hướng chi tiêu.
- Thu nhập so với chi tiêu.
- Thay đổi tài sản ròng.
- Tỷ lệ tiết kiệm.
- Tiến độ mục tiêu tài chính.
- So sánh tình hình tài chính giữa các tháng.

---

# 5. Luồng nghiệp vụ tổng quát

```text
Người dùng
    ↓
Đăng ký / Đăng nhập
    ↓
Thiết lập tài khoản / ví
    ↓
Nhập các khoản thu - chi
    ↓
Phân loại giao dịch
    ↓
Cập nhật số dư tài khoản
    ↓
Kiểm tra ngân sách
    ↓
Cập nhật tài sản / nợ
    ↓
Tính Cash Flow
    ↓
Tính Saving Rate
    ↓
Tính Net Worth
    ↓
Cập nhật mục tiêu tài chính
    ↓
Phân tích dữ liệu
    ↓
Dashboard / Báo cáo
    ↓
Cảnh báo và hỗ trợ người dùng
đưa ra quyết định tài chính
```

Có thể tóm tắt luồng giá trị của hệ thống như sau:

```text
Track Money
    ↓
Understand Spending
    ↓
Control Budget
    ↓
Manage Assets & Debt
    ↓
Build Savings
    ↓
Achieve Financial Goals
    ↓
Improve Personal Wealth
```

---

# 6. Sơ đồ kiến trúc dự kiến

Ở giai đoạn đầu, hệ thống dự kiến sử dụng kiến trúc phân tầng:

**Layered Architecture + Modular Monolith**

```text
┌──────────────────────────────────────────────┐
│              PRESENTATION LAYER              │
│                                              │
│                    Web UI                    │
│                                              │
│  - Login / Register                          │
│  - Dashboard                                 │
│  - Account / Wallet                          │
│  - Income / Expense                          │
│  - Budget                                    │
│  - Assets / Liabilities                      │
│  - Loans                                     │
│  - Financial Goals                           │
│  - Reports                                   │
└──────────────────────┬───────────────────────┘
                       │
                 HTTP / REST API
                       │
                       ▼
┌──────────────────────────────────────────────┐
│               APPLICATION LAYER              │
│                                              │
│  Authentication Service                     │
│  Account / Wallet Service                   │
│  Transaction Service                        │
│  Budget Service                             │
│  Wealth Service                             │
│  Loan Service                               │
│  Financial Goal Service                     │
│  Report & Analytics Service                 │
│  Notification Service                       │
└──────────────────────┬───────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────┐
│                BUSINESS LAYER                │
│                                              │
│  Business Rules                             │
│                                              │
│  - Calculate Account Balance                │
│  - Calculate Cash Flow                      │
│  - Calculate Saving Rate                    │
│  - Calculate Net Worth                      │
│  - Check Budget Limit                       │
│  - Track Goal Progress                      │
│  - Loan / Debt Calculation                  │
│  - Generate Financial Insights              │
└──────────────────────┬───────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────┐
│                  DATA LAYER                  │
│                                              │
│  Repository / DAO                           │
│                                              │
│  - User Repository                          │
│  - Account Repository                       │
│  - Transaction Repository                   │
│  - Category Repository                      │
│  - Budget Repository                        │
│  - Asset Repository                         │
│  - Liability Repository                     │
│  - Goal Repository                          │
│  - Loan Repository                          │
│  - Notification Repository                  │
└──────────────────────┬───────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────┐
│                  DATABASE                    │
│                                              │
│             MySQL / PostgreSQL               │
│                                              │
│  users                                       │
│  accounts                                    │
│  transactions                                │
│  categories                                  │
│  budgets                                     │
│  assets                                      │
│  liabilities                                 │
│  loans                                       │
│  financial_goals                             │
│  notifications                               │
└──────────────────────────────────────────────┘
```

---

# 7. Concept tổng quát của hệ thống

Có thể tóm tắt ý tưởng toàn bộ hệ thống bằng công thức:

```text
Expense Tracking
        +
Budget Management
        +
Asset & Debt Management
        +
Financial Goal Planning
        =
Personal Wealth Management
```

Điểm khác biệt của WealthFlow so với một ứng dụng Expense Tracker thông thường là:

Một Expense Tracker thông thường chủ yếu trả lời:

> **"Tôi đã tiêu bao nhiêu?"**

Trong khi WealthFlow hướng tới trả lời:

> **"Tôi hiện có bao nhiêu tài sản?"**

> **"Tiền của tôi đang đi đâu?"**

> **"Tôi đang nợ bao nhiêu?"**

> **"Tôi đang tiết kiệm được bao nhiêu?"**

> **"Tài sản của tôi đang tăng hay giảm?"**

> **"Bao lâu nữa tôi sẽ đạt được mục tiêu tài chính?"**

Có thể mô tả ngắn gọn concept của đề tài như sau:

> **WealthFlow là hệ thống quản lý tài chính cá nhân giúp người dùng bắt đầu từ những khoản tiền nhỏ, kiểm soát dòng tiền hiện tại, quản lý tài sản và nợ, xây dựng kế hoạch tiết kiệm và từng bước cải thiện tài sản cá nhân trong dài hạn.**