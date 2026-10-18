# Đặc Tả Thiết Kế RESTful API (RESTful API Specification)
## Hệ Thống Quản Lý Tài Chính Cá Nhân & Tài Chính Vi Mô Thông Minh (WealthFlow)

> **Liên kết Tài liệu Liên quan (Related Documents):**  
> - **Yêu cầu & Nghiệp vụ:** [SRS.md (Đặc tả Yêu cầu Phần mềm v2.1)](./SRS.md) | [Business-Modelling.md (Mô hình Hóa Nghiệp vụ)](../01_inception/Business-Modelling.md)
> - **Mô hình Hóa Phân tích & Thiết kế (UML):** [usecase_diagram.md (Sơ đồ Use Case)](./uml_modeling/usecase_diagram.md) | [activity_diagram.md (Sơ đồ Hoạt động)](./uml_modeling/activity_diagram.md) | [class_diagram.md (Sơ đồ Lớp Chi tiết BCE+D)](./uml_modeling/class_diagram.md) | [sequence_diagram.md (Sơ đồ Tuần tự 08 Kịch bản)](./uml_modeling/sequence_diagram.md)
> - **Cơ sở Dữ liệu & Triển khai:** [database_design.md (Thiết kế CSDL Vật lý MySQL 8.2+ 22 Bảng)](./database_design.md)

---

## 1. Giới Thiệu & Quy Chuẩn Thiết Kế API

Tài liệu này xác định chi tiết đặc tả giao diện lập trình ứng dụng RESTful API (RESTful API Specification) cho hệ thống **WealthFlow** (Personal Wealth & Microfinance Tracker), đạt chuẩn **Richardson Maturity Model - Level 2/3**. 

Đặc tả này đóng vai trò bản khế ước kỹ thuật (Technical Contract) bắt buộc giữa tầng Client (Web SPA / Mobile App) và tầng Backend (PHP 8.2+ / MySQL 8.2+ InnoDB), hiện thực hóa 13 Use Cases và 08 kịch bản tuần tự đã được mô hình hóa trong [sequence_diagram.md](./uml_modeling/sequence_diagram.md).

### 1.1. Thông Tin Môi Trường & Giao Thức (General Protocol)
- **Base URL:** `https://api.wealthflow.local/api/v1` (Fallback tương thích: `/api/*`).
- **Giao thức truyền tải:** HTTPS / TLS 1.3 bắt buộc trên môi trường Production.
- **Bộ mã ký tự (Character Encoding):** `utf8mb4` (Content-Type: `application/json; charset=utf-8`).
- **Kiểu dữ liệu thời gian:** Chuẩn ISO 8601 UTC (`YYYY-MM-DDTHH:mm:ssZ`) hoặc định dạng ngày chuẩn kế toán (`YYYY-MM-DD`).
- **Kiểu dữ liệu tiền tệ:** Biểu diễn số thập phân dạng chuỗi số thực hoặc float chính xác cao, không áp dụng làm tròn mềm trên API, đồng bộ với kiểu `DECIMAL(24,8)` trong [database_design.md](./database_design.md).

### 1.2. Cấu Trúc Khung Phản Hồi Chuẩn (Standard Response Envelope)

Toàn bộ các Endpoints của hệ thống WealthFlow đều tuân thủ cấu trúc đóng gói đồng nhất (Uniform Envelope Pattern):

#### 1.2.1. Phản hồi Thành Công (Success Envelope - HTTP 2xx)
```json
{
  "success": true,
  "statusCode": 200,
  "data": {
    /* Dữ liệu đối tượng nghiệp vụ hoặc danh sách mảng */
  },
  "meta": {
    "timestamp": "2026-10-17T08:00:00Z",
    "requestId": "req_8f1a2c3d-4e5b-6a7c-8d9e-0f1a2b3c4d5e",
    "pagination": {
      "page": 1,
      "limit": 20,
      "totalRecords": 85,
      "totalPages": 5,
      "hasNext": true,
      "hasPrev": false
    }
  }
}
```

#### 1.2.2. Phản hồi Lỗi (Error Envelope - HTTP 4xx, 5xx)
```json
{
  "success": false,
  "statusCode": 422,
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "Số dư ví nguồn không đủ để thực hiện giao dịch chuyển khoản.",
    "fields": {
      "amount": "Số tiền chuyển 500,000 VND vượt quá số dư khả dụng 200,000 VND."
    }
  },
  "meta": {
    "timestamp": "2026-10-17T08:00:00Z",
    "requestId": "req_9a8b7c6d-5e4f-3a2b-1c0d-e9f8a7b6c5d4"
  }
}
```

### 1.3. Tiêu Chuẩn Headers Toàn Cục (Standard HTTP Headers)

| Header Name | Bắt buộc | Kiểu Giá trị | Diễn giải & Quy định Nghiệp vụ |
| :--- | :---: | :--- | :--- |
| `Authorization` | Có (các API bảo vệ) | `Bearer <JWT_ACCESS_TOKEN>` | Mã xác thực JWT của người dùng (thời hạn 15 phút). |
| `Content-Type` | Có (POST, PUT, PATCH) | `application/json` | Định dạng dữ liệu tải lên máy chủ. |
| `Accept` | Có | `application/json` | Định dạng Client mong muốn nhận về. |
| `Idempotency-Key`| Bắt buộc cho POST giao dịch | `UUID v4` | Khóa chống trùng lặp giao dịch (Idempotency Key - BR13, FR03.04). |
| `X-WealthFlow` | Có (trên Web Client) | `1` | Header phòng vệ tấn công giả mạo yêu cầu (CSRF Prevention). |
| `X-Request-Id` | Tùy chọn | `UUID v4` | Mã truy vết log xuyên suốt giữa Client và Server (Distributed Tracing). |

### 1.4. Bảng Quy Chuẩn Mã Lỗi HTTP & Error Codes

| HTTP Status | Error Code | Ý nghĩa Nghiệp vụ |
| :---: | :--- | :--- |
| **`400 Bad Request`** | `INVALID_JSON` | Cú pháp JSON tải lên bị lỗi hoặc vượt quá giới hạn 16KB. |
| | `VALIDATION_FAILED` | Dữ liệu đầu vào vi phạm ràng buộc kiểu, độ dài hoặc định dạng. |
| | `CANNOT_LOCK_LAST_ADMIN` | Không thể khóa tài khoản Quản trị viên duy nhất còn lại của hệ thống (BR14). |
| **`401 Unauthorized`** | `UNAUTHORIZED` | Thiếu Header Authorization hoặc Access Token không hợp lệ. |
| | `TOKEN_EXPIRED` | Access Token đã hết hạn 15 phút, yêu cầu gọi Refresh Token. |
| | `INVALID_CREDENTIALS` | Email hoặc mật khẩu đăng nhập không chính xác. |
| **`403 Forbidden`** | `FORBIDDEN` | Người dùng không đủ quyền hạn thực thi (Ví dụ: User gọi API Admin). |
| | `ACCOUNT_LOCKED` | Tài khoản đã bị khóa bởi Quản trị viên vi phạm chính sách (BR11, SQ08). |
| | `CSRF_REJECTED` | Vi phạm chính sách Same-Origin hoặc thiếu header bảo vệ. |
| **`404 Not Found`** | `RESOURCE_NOT_FOUND` | Bản ghi yêu cầu (Ví, Giao dịch, Khoản vay) không tồn tại hoặc không thuộc quyền sở hữu. |
| | `ROUTE_NOT_FOUND` | Đường dẫn URI không tồn tại trong hệ thống. |
| **`405 Method Not Allowed`**| `METHOD_NOT_ALLOWED` | Phương thức HTTP không được hỗ trợ trên Endpoint này. |
| **`409 Conflict`** | `EMAIL_ALREADY_EXISTS` | Email đăng ký đã tồn tại trong bảng `users` (`uk_user_email`). |
| | `IDEMPOTENCY_KEY_DUPLICATED` | Giao dịch đang được xử lý đồng thời với cùng một Idempotency-Key. |
| **`413 Payload Too Large`**| `BODY_TOO_LARGE` | Dung lượng Request Body vượt quá 16,384 bytes cho phép. |
| **`415 Unsupported Media`**| `INVALID_CONTENT_TYPE` | Thiếu hoặc sai định dạng `Content-Type: application/json`. |
| **`422 Unprocessable`** | `INSUFFICIENT_BALANCE` | Ví tiền mặt không đủ số dư để chi tiêu / chuyển khoản (BR06). |
| | `BUDGET_EXCEEDED` | Cảnh báo chi tiêu chạm ngưỡng ngân sách (nếu bật chế độ Hard-Stop). |
| | `ZERO_OR_NEGATIVE_AMOUNT` | Số tiền giao dịch phải là số thực dương lớn hơn 0 (BR01). |
| **`500 Internal Error`** | `INTERNAL_SERVER_ERROR` | Lỗi ngoại lệ chưa được xử lý tại máy chủ Backend. |
| **`503 Unavailable`** | `SERVICE_UNAVAILABLE` | Máy chủ cơ sở dữ liệu MySQL tạm thời mất kết nối hoặc quá tải. |

---

## 2. Danh Mục Tổng Hợp Các Nhóm API Endpoints

Dưới đây là ma trận phân bổ các nhóm API theo phân hệ chức năng, ánh xạ với [class_diagram.md](./uml_modeling/class_diagram.md) và [sequence_diagram.md](./uml_modeling/sequence_diagram.md):

```
/api
├── /auth               (8 Endpoints)  - Định danh, Đăng ký, Đăng nhập JWT, Khôi phục mật khẩu [UC01, SQ01]
├── /wallets            (7 Endpoints)  - Quản lý tài khoản ví tài sản, số dư mở sổ [UC02, SQ01]
├── /transactions       (5 Endpoints)  - Ghi nhận thu chi, chuyển khoản nội bộ, đảo sổ cái [UC03, SQ02, SQ03]
├── /categories         (4 Endpoints)  - Quản lý danh mục thu/chi người dùng [UC03]
├── /exchange-rates     (2 Endpoints)  - Tỷ giá hối đoái ngoại tệ liên ngân hàng [UC03, UC08]
├── /recurring          (6 Endpoints)  - Quản lý chi phí cố định định kỳ & tác vụ Cron [UC04, SQ04]
├── /budgets            (5 Endpoints)  - Lập ngân sách tháng & cảnh báo bội chi [UC05, SQ02]
├── /loans              (5 Endpoints)  - Quản lý nợ vay vi mô, giải ngân & lịch trả nợ [UC06, UC07, SQ05]
├── /gold               (4 Endpoints)  - Danh mục đầu tư vàng vật chất & giá thị trường [UC08, SQ06]
├── /goals              (5 Endpoints)  - Mục tiêu tiết kiệm & quỹ phong tỏa Earmark [UC09]
├── /analytics          (4 Endpoints)  - Dashboard tài sản ròng Net Worth, dòng tiền & sức khỏe [UC10, SQ06]
├── /reports            (3 Endpoints)  - Đối soát sổ cái kép & xuất tệp CSV an toàn [UC11, SQ07]
├── /notifications      (4 Endpoints)  - Trung tâm thông báo đẩy & đánh dấu đã đọc [SYS01, SQ02, SQ04]
└── /admin              (6 Endpoints)  - Khóa tài khoản, phát thông báo toàn hệ thống, cấu hình [UC12, UC13, SQ08]
```

---

## 3. Đặc Tả Chi Tiết Từng Nhóm API Endpoints

### 3.1. Phân Hệ 1: Xác Thực & Quản Lý Phiên (Authentication & Access Control)
> Ánh xạ: [UC01: Đăng ký & Xác thực người dùng](./uml_modeling/usecase_diagram.md#uc01-dang-ky-va-dang-nhap) | [SQ01: Đăng ký & Đăng nhập JWT](./uml_modeling/sequence_diagram.md#31-sq01-đăng-ký-tài-khoản-mã-hóa-argon2id--đăng-nhập-jwt) | [CD01](./uml_modeling/class_diagram.md#21-phân-hệ-1-định-danh-xác-thực--quản-trị-tài-khoản-identity-authentication--user-management-context) | [ERD01](./database_design.md#31-phân-hệ-xác-thực--phân-quyền-authentication--authorization)

#### 3.1.1. `POST /api/auth/register` - Đăng Ký Tài Khoản Mới
- **Quyền truy cập:** Công khai (Guest A01-G).
- **Mô tả nghiệp vụ:** Khởi tạo người dùng mới, tự động băm mật khẩu bảo mật bằng Argon2id (cost factor 12, bộ nhớ 64MB), sao chép toàn bộ danh mục mẫu từ `default_categories` sang `transaction_categories` trong cùng một Database Transaction (ACID).
- **Request Body JSON Schema:**
  ```json
  {
    "fullName": "Nguyễn Văn A",
    "email": "user@example.com",
    "password": "Password123@",
    "baseCurrency": "VND"
  }
  ```
- **Ràng buộc kiểm tra dữ liệu:**
  - `fullName`: Bắt buộc, chuỗi từ 2 đến 150 ký tự.
  - `email`: Bắt buộc, định dạng email chuẩn RFC 5322, tối đa 254 ký tự.
  - `password`: Bắt buộc, tối thiểu 8 ký tự, gồm ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt.
  - `baseCurrency`: Tùy chọn (Mặc định: `'VND'`), mã tiền tệ chuẩn ISO 4217 (VND, USD, EUR, JPY).
- **Phản hồi Thành công (201 Created):**
  ```json
  {
    "success": true,
    "statusCode": 201,
    "data": {
      "user": {
        "id": 15,
        "fullName": "Nguyễn Văn A",
        "email": "user@example.com",
        "role": "USER",
        "status": "ACTIVE",
        "baseCurrency": "VND",
        "createdAt": "2026-10-17T08:15:30Z"
      },
      "message": "Đăng ký tài khoản thành công. Vui lòng đăng nhập."
    }
  }
  ```
- **Phản hồi Lỗi:**
  - `409 Conflict`: `{ "code": "EMAIL_ALREADY_EXISTS", "message": "Email này đã được sử dụng trên hệ thống." }`
  - `422 Unprocessable Entity`: `{ "code": "VALIDATION_FAILED", "message": "Mật khẩu không đạt yêu cầu độ phức tạp." }`

#### 3.1.2. `POST /api/auth/login` - Đăng Nhập & Cấp Phát JWT
- **Quyền truy cập:** Công khai (Guest A01-G).
- **Mô tả nghiệp vụ:** Thẩm định thông tin đăng nhập, xác minh hàm băm Argon2id, kiểm tra trạng thái tài khoản `status == 'ACTIVE'` (từ chối 403 nếu `LOCKED`), sinh cặp Token JWT (Access Token 15 phút, Refresh Token 7 ngày lưu băm SHA-256 vào `auth_sessions`), và trả về đồng thời qua Body và Cookie HttpOnly.
- **Request Body JSON Schema:**
  ```json
  {
    "email": "user@example.com",
    "password": "Password123@"
  }
  ```
- **Phản hồi Thành công (200 OK):**
  ```json
  {
    "success": true,
    "statusCode": 200,
    "data": {
      "user": {
        "id": 15,
        "fullName": "Nguyễn Văn A",
        "email": "user@example.com",
        "role": "USER",
        "baseCurrency": "VND"
      },
      "tokens": {
        "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "tokenType": "Bearer",
        "expiresIn": 900,
        "refreshToken": "wf_ref_8f1a2c3d4e5b6a7c8d9e0f1a2b3c4d5e"
      }
    }
  }
  ```
- **Phản hồi Lỗi:**
  - `401 Unauthorized`: `{ "code": "INVALID_CREDENTIALS", "message": "Email hoặc mật khẩu không chính xác." }`
  - `403 Forbidden`: `{ "code": "ACCOUNT_LOCKED", "message": "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ ban quản trị." }`

#### 3.1.3. `POST /api/auth/refresh` - Cấp Lại Access Token
- **Quyền truy cập:** Người dùng có Refresh Token hợp lệ (Body hoặc Cookie `wf_refresh`).
- **Mô tả nghiệp vụ:** Kiểm tra chuỗi băm SHA-256 của Refresh Token trong bảng `auth_sessions` và thời hạn `expires_at > NOW()`. Nếu hợp lệ, cấp phát Access Token mới (15 phút) mà không yêu cầu người dùng nhập lại mật khẩu.
- **Phản hồi Thành công (200 OK):**
  ```json
  {
    "success": true,
    "statusCode": 200,
    "data": {
      "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.new...",
      "tokenType": "Bearer",
      "expiresIn": 900
    }
  }
  ```

#### 3.1.4. `POST /api/auth/logout` - Đăng Xuất & Hủy Phiên
- **Quyền truy cập:** Người dùng đã xác thực (`USER`, `ADMIN`).
- **Mô tả nghiệp vụ:** Xóa bản ghi phiên làm việc trong bảng `auth_sessions`, thu hồi tức thì quyền làm mới token của thiết bị hiện tại, và xóa cookie HttpOnly.
- **Phản hồi Thành công (200 OK):**
  ```json
  {
    "success": true,
    "statusCode": 200,
    "data": { "message": "Đăng xuất thành công." }
  }
  ```

#### 3.1.5. `GET /api/auth/me` - Xem Thông Tin Tài Khoản Đang Đăng Nhập
- **Quyền truy cập:** Người dùng đã xác thực (`USER`, `ADMIN`).
- **Phản hồi Thành công (200 OK):** Trả về chi tiết User hiện tại, vai trò và tiền tệ cơ sở.

#### 3.1.6. `POST /api/auth/change-password` - Đổi Mật Khẩu
- **Quyền truy cập:** Người dùng đã xác thực.
- **Request Body:** `{ "currentPassword": "...", "newPassword": "..." }`.
- **Mô tả nghiệp vụ:** Xác thực mật khẩu cũ trước khi cập nhật mã băm mới và thu hồi mọi phiên đăng nhập khác.

#### 3.1.7. `POST /api/auth/password/forgot` - Yêu Cầu Mã Khôi Phục Mật Khẩu
- **Quyền truy cập:** Công khai (Guest).
- **Request Body:** `{ "email": "user@example.com" }`.
- **Mô tả nghiệp vụ:** Sinh mã OTP 6 chữ số ngẫu nhiên, lưu băm SHA-256 vào `password_reset_tokens` (hạn 10 phút), giới hạn tần suất gửi mã (Rate limit: tối đa 3 lần/giờ).

#### 3.1.8. `POST /api/auth/password/reset` - Đặt Lại Mật Khẩu Bằng Mã OTP
- **Quyền truy cập:** Công khai (Guest).
- **Request Body:** `{ "email": "user@example.com", "otp": "583921", "newPassword": "..." }`.
- **Mô tả nghiệp vụ:** Xác thực mã OTP (tối đa 5 lần thử sai), cập nhật mật khẩu mới và xóa mã OTP đã sử dụng.

---

### 3.2. Phân Hệ 2: Quản Lý Ví Tiền & Số Dư Mở Sổ (Wallet Management)
> Ánh xạ: [UC02: Quản lý ví và số dư mở sổ](./uml_modeling/usecase_diagram.md#uc02-quan-ly-vi-va-so-du-mo-so) | [CD02](./uml_modeling/class_diagram.md#22-phân-hệ-2-quản-lý-ví-tài-sản--số-dư-mở-sổ-wallet-management-context---uc02) | [ERD02](./database_design.md#32-phân-hệ-ví-danh-mục--sổ-cái-kép-core-wallets--double-entry-ledger)

#### 3.2.1. `GET /api/wallets` - Danh Sách Tài Khoản Ví Của Người Dùng
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Query Parameter:** `include_archived=1` là tùy chọn; mặc định chỉ lấy ví đang hoạt động.
- **Phản hồi Thành công (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "wallets": [
        {
          "id": 1,
          "name": "Tiền mặt",
          "wallet_type": "CASH",
          "cached_balance": "1500000.00000000",
          "currency": "VND",
          "earmarked_balance": "0.00000000",
          "status": "ACTIVE",
          "created_at": "2026-10-01 00:00:00"
        },
        {
          "id": 2,
          "name": "Techcombank",
          "wallet_type": "BANK",
          "cached_balance": "25000000.00000000",
          "currency": "VND",
          "earmarked_balance": "0.00000000",
          "status": "ACTIVE",
          "created_at": "2026-10-01 00:00:00"
        }
      ],
      "count": 2
    },
    "error": null,
    "requestId": "a1b2c3d4e5f60708"
  }
  ```

#### 3.2.2. `POST /api/wallets` - Tạo Ví Mới & Khởi Tạo Số Dư Mở Sổ
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Headers:** `Idempotency-Key: <UUID>` để lần gửi lại cùng yêu cầu không tạo ví trùng.
- **Mô tả nghiệp vụ:** Tạo ví tài chính mới. Nếu `initialBalance > 0`, hệ thống tự động sinh một giao dịch loại `OTHER` mô tả số dư mở sổ, kèm 2 bút toán sổ cái kép: `DEBIT Asset:Wallet` và `CREDIT Equity:OpeningBalance` theo đúng quy tắc kế toán chuẩn ([BR01, BR02](../SRS.md#br01)).
- **Request Body JSON Schema:**
  ```json
  {
    "name": "Ví MoMo",
    "walletType": "E_WALLET",
    "currency": "VND",
    "initialBalance": "500000"
  }
  ```
- **Ràng buộc dữ liệu:**
  - `walletType`: Một trong các giá trị ENUM `['CASH', 'BANK', 'E_WALLET', 'SAVINGS', 'CREDIT']`.
  - `initialBalance`: Số thực $\ge 0$.

#### 3.2.3. `GET /api/wallets/{id}` - Chi Tiết Tài Khoản Ví
- **Quyền truy cập:** Người dùng sở hữu ví. Trả về thông tin số dư, loại ví và tổng thu/chi lũy kế.

#### 3.2.4. `PUT /api/wallets/{id}` - Cập Nhật Thông Tin Ví
- **Quyền truy cập:** Người dùng sở hữu ví.
- **Request Body:** `{ "name": "Techcombank Payroll" }`.
- **Mô tả nghiệp vụ:** Chỉ cập nhật tên ví. Không cho phép đổi trực tiếp số dư đệm tại endpoint này.

#### 3.2.5. `POST /api/wallets/{id}/adjustments` - Điều Chỉnh Số Dư Ví
- **Quyền truy cập:** Người dùng sở hữu ví đang hoạt động.
- **Headers:** `Idempotency-Key: <UUID>` để ngăn ghi nhận lặp.
- **Request Body:** `{ "direction": "INCREASE", "amount": "50000", "note": "Bổ sung tiền mặt" }`.
- **Mô tả nghiệp vụ:** `direction` nhận `INCREASE` hoặc `DECREASE`. Mỗi lần điều chỉnh tạo một giao dịch tài chính cùng hai dòng ghi sổ cân bằng; ví không phải thẻ tín dụng không được giảm xuống số dư âm.

#### 3.2.6. `POST /api/wallets/{id}/archive` - Lưu Trữ Ví
- **Quyền truy cập:** Người dùng sở hữu ví.
- **Mô tả nghiệp vụ:** Chỉ lưu trữ ví đang hoạt động khi `cached_balance = 0`, `earmarked_balance = 0` và không còn lịch định kỳ ở trạng thái `ACTIVE`.

#### 3.2.7. `DELETE /api/wallets/{id}` - Xóa Vĩnh Viễn Ví Không Có Lịch Sử
- **Quyền truy cập:** Người dùng sở hữu ví.
- **Mô tả nghiệp vụ:** Chỉ xóa ví đã lưu trữ, có số dư và tiền phân bổ bằng 0, đồng thời không được tham chiếu bởi sổ giao dịch, lịch định kỳ, khoản vay, giao dịch vàng hoặc mục tiêu tài chính. Ví có lịch sử được giữ ở trạng thái lưu trữ để bảo toàn dữ liệu.

---

### 3.3. Phân Hệ 3: Giao Dịch Thu Chi, Chuyển Khoản & Sổ Cái Kép (Transactions & Ledger)
> Ánh xạ: [UC03: Ghi nhận giao dịch thu chi](./uml_modeling/usecase_diagram.md#uc03-quan-ly-giao-dich-thu-chi-va-chuyen-khoan) | [SQ02: Ghi nhận chi tiêu](./uml_modeling/sequence_diagram.md#32-sq02-ghi-nhận-chi-tiêu-khóa-bi-quan--cảnh-báo-ngân-sách) | [SQ03: Chuyển tiền & Đảo bút toán](./uml_modeling/sequence_diagram.md#33-sq03-chuyển-tiền-nội-bộ--đảo-giao-dịch-reversal) | [CD03](./uml_modeling/class_diagram.md#23-phân-hệ-3-ghi-nhận-giao-dịch-thu-chi--sổ-cái-kế-toán-kép-double-entry-ledger-context---uc03) | [ERD02](./database_design.md#32-phân-hệ-ví-danh-mục--sổ-cái-kép-core-wallets--double-entry-ledger)

#### 3.3.1. `GET /api/transactions` - Lọc & Tìm Kiếm Danh Sách Giao Dịch
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Query Parameters:**
  - `page` (int, mặc định: 1), `limit` (int, mặc định: 20, max: 100).
  - `from` (string YYYY-MM-DD), `to` (string YYYY-MM-DD).
  - `walletId` (int), `categoryId` (int), `transactionType` (ENUM: `INCOME`, `EXPENSE`, `TRANSFER`).
- **Phản hồi Thành công (200 OK):** Trả về mảng giao dịch kèm thông tin phân trang chuẩn Envelope.

#### 3.3.2. `POST /api/transactions` - Ghi Nhận Thu / Chi (Hạch Toán Sổ Cái Kép)
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Headers bắt buộc:** `Idempotency-Key: <UUIDv4>`.
- **Mô tả nghiệp vụ kỹ thuật:**
  1. Kiểm tra khóa `idempotency_keys` ngăn chặn trùng lặp.
  2. Bắt đầu Database Transaction trên `MySQL Database (InnoDB)`.
  3. Thực thi Khóa mức dòng bi quan: `SELECT id, cached_balance, wallet_type FROM wallets WHERE id = ? FOR UPDATE;`.
  4. Nếu chi tiêu từ ví tiền mặt (`CASH`) mà `cached_balance < amount`, lập tức ném ngoại lệ `422 Unprocessable Entity` (BR06).
  5. Thêm bản ghi vào `financial_transactions`.
  6. Sinh 2 dòng cân bằng tuyệt đối trong `ledger_entries` (DEBIT Expense / CREDIT Asset hoặc ngược lại).
  7. Cập nhật `wallets.cached_balance`.
  8. Commit Transaction và kích hoạt ngầm kiểm tra cảnh báo ngân sách 80%/100% ([SQ02](./uml_modeling/sequence_diagram.md#32-sq02-ghi-nhận-chi-tiêu-khóa-bi-quan--cảnh-báo-ngân-sách)).
- **Request Body JSON Schema:**
  ```json
  {
    "walletId": 2,
    "categoryId": 5,
    "transactionType": "EXPENSE",
    "amount": 250000,
    "occurredOn": "2026-10-17",
    "description": "Ăn tối gia đình",
    "note": "Nhà hàng Pizza 4P"
  }
  ```
- **Phản hồi Thành công (201 Created):**
  ```json
  {
    "success": true,
    "statusCode": 201,
    "data": {
      "transactionId": 1042,
      "transactionType": "EXPENSE",
      "amount": "250000.00000000",
      "walletId": 2,
      "newCachedBalance": "24750000.00000000",
      "budgetAlert": {
        "isAlert": true,
        "percentage": 82.5,
        "message": "Danh mục Ăn uống đã đạt 82.5% hạn mức ngân sách tháng."
      }
    }
  }
  ```

#### 3.3.3. `POST /api/transactions/transfer` - Chuyển Tiền Nội Bộ Giữa 2 Ví
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Headers bắt buộc:** `Idempotency-Key: <UUIDv4>`.
- **Mô tả nghiệp vụ kỹ thuật:** Áp dụng thuật toán sắp xếp thứ tự khóa tránh Deadlock (`min(fromId, toId)`, `max(...)`), khóa đồng thời 2 ví theo thứ tự ID toàn cục, trừ số dư ví nguồn, cộng số dư ví đích, và ghi nhận 2 bút toán đối ứng kế toán kép ([SQ03](./uml_modeling/sequence_diagram.md#33-sq03-chuyển-tiền-nội-bộ--đảo-giao-dịch-reversal)).
- **Request Body JSON Schema:**
  ```json
  {
    "fromWalletId": 2,
    "toWalletId": 1,
    "amount": 1000000,
    "occurredOn": "2026-10-17",
    "description": "Rút tiền mặt ATM từ Techcombank"
  }
  ```
- **Phản hồi Thành công (201 Created):**
  ```json
  {
    "success": true,
    "statusCode": 201,
    "data": {
      "transactionId": 1043,
      "fromWalletBalance": "23750000.00000000",
      "toWalletBalance": "2500000.00000000",
      "message": "Chuyển tiền nội bộ thành công."
    }
  }
  ```

#### 3.3.4. `POST /api/transactions/{id}/reverse` - Đảo Bút Toán Giao Dịch (Reversal)
- **Quyền truy cập:** Người dùng sở hữu giao dịch.
- **Mô tả nghiệp vụ kỹ thuật:** Tuân thủ nguyên tắc kế toán bất biến (Accounting Immutability - BR05), không xóa bản ghi gốc mà tạo một giao dịch mới mang loại `TRANSFER_REVERSAL` hoặc `EXPENSE_REVERSAL`, gắn `original_transaction_id = ?`, và tạo các bút toán đảo ngược chiều để hoàn nguyên số dư chính xác.
- **Phản hồi Thành công (200 OK):** Trả về ID giao dịch đảo và số dư mới đã phục hồi.

#### 3.3.5. `GET /api/categories` - Danh Sách Danh Mục Thu/Chi
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Query Parameter:** `type=INCOME|EXPENSE` là tùy chọn.
- **Mô tả nghiệp vụ:** Chỉ trả danh mục đang hoạt động thuộc người dùng hiện tại; có thể lọc riêng danh mục thu nhập hoặc chi tiêu.

#### 3.3.6. `POST /api/categories` - Tạo Danh Mục Cá Nhân
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Request Body:** `{ "name": "Ăn trưa", "type": "EXPENSE" }`.
- **Ràng buộc:** Tên không được để trống; `type` chỉ nhận `INCOME` hoặc `EXPENSE`; tên và loại không được trùng trong cùng tài khoản.

#### 3.3.7. `PUT /api/categories/{id}` - Đổi Tên Danh Mục
- **Quyền truy cập:** Người dùng sở hữu danh mục.
- **Request Body:** `{ "name": "Ăn uống hằng ngày" }`.
- **Mô tả nghiệp vụ:** Chỉ đổi tên; không thay đổi loại thu nhập/chi tiêu của danh mục.

#### 3.3.8. `DELETE /api/categories/{id}` - Lưu Trữ Danh Mục
- **Quyền truy cập:** Người dùng sở hữu danh mục.
- **Mô tả nghiệp vụ:** Đây là xóa mềm bằng `archived_at`; không xóa vật lý nên các giao dịch cũ vẫn giữ được lịch sử danh mục.

#### 3.3.9. `GET /api/exchange-rates` & `POST /api/exchange-rates` - Tỷ Giá Hối Đoái
- Truy vấn và cập nhật tỷ giá quy đổi ngoại tệ (USD, EUR, JPY) sang đơn vị tiền tệ gốc VND.

---

### 3.4. Phân Hệ 4: Giao Dịch Định Kỳ Tự Động (Recurring Schedules)
> Ánh xạ: [UC04: Chi phí cố định định kỳ](./uml_modeling/usecase_diagram.md#uc04-quan-ly-chi-phi-co-dinh-va-giao-dich-dinh-ky) | [SQ04: Subscription Cron](./uml_modeling/sequence_diagram.md#34-sq04-xử-lý-giao-dịch-định-kỳ-tự-động-subscription-cron) | [CD05](./uml_modeling/class_diagram.md#25-phân-hệ-5-quản-lý-giao-dịch-định-kỳ-tự-động-recurring-transaction-context---uc04-sys02) | [ERD03](./database_design.md#33-phân-hệ-ngân-sách--chi-tiêu-định-kỳ-budgets--recurring-schedules)

#### 3.4.1. `GET /api/recurring` - Danh Sách Lịch Giao Dịch Định Kỳ
- Trả về danh sách các dịch vụ đăng ký định kỳ (Netflix, Spotify, Tiền thuê nhà, Tiền điện, v.v.).

#### 3.4.2. `POST /api/recurring` - Thiết Lập Lịch Định Kỳ Mới
- **Request Body JSON Schema:**
  ```json
  {
    "walletId": 2,
    "categoryId": 8,
    "name": "Thuê bao Netflix",
    "amount": 260000,
    "interval": "MONTHLY",
    "anchorDay": 31,
    "startDate": "2026-10-31",
    "autoPost": true
  }
  ```
- **Ràng buộc nghiệp vụ:**
  - `anchorDay` (1-31): Ngày cố định áp dụng quy tắc neo ngày (Anchor Day Rule - BR09, SQ04).
  - `interval`: `DAILY`, `WEEKLY`, `MONTHLY`, `YEARLY`.

#### 3.4.3. `POST /api/recurring/{id}/pause` & `POST /api/recurring/{id}/resume`
- Tạm dừng hoặc kích hoạt lại lịch hạch toán định kỳ tự động.

#### 3.4.4. `DELETE /api/recurring/{id}` - Hủy Bỏ Lịch Định Kỳ
- Chuyển trạng thái lịch sang `CANCELLED`.

#### 3.4.5. `POST /api/recurring/{id}/trigger` - Chạy Thử Nghiệm Hạch Toán Ngay
- Kích hoạt hạch toán ngay lập tức cho kỳ hiện tại phục vụ kiểm thử.

---

### 3.5. Phân Hệ 5: Thiết Lập & Kiểm Soát Ngân Sách (Budget Planning)
> Ánh xạ: [UC05: Thiết lập ngân sách](./uml_modeling/usecase_diagram.md#uc05-thiet-lap-va-theo-doi-ngan-sach) | [SQ02](./uml_modeling/sequence_diagram.md#32-sq02-ghi-nhận-chi-tiêu-khóa-bi-quan--cảnh-báo-ngân-sách) | [CD06](./uml_modeling/class_diagram.md#26-phân-hệ-6-thiết-lập--kiểm-soát-ngân-sách-chi-tiêu-budget-planning-context---uc05-sys03) | [ERD03](./database_design.md#33-phân-hệ-ngân-sách--chi-tiêu-định-kỳ-budgets--recurring-schedules)

#### 3.5.1. `GET /api/budgets` - Danh Sách Ngân Sách Tháng
- **Query Parameters:** `month=10&year=2026`.
- Trả về danh sách ngân sách kèm số tiền đã chi, số tiền còn lại và tỷ lệ % sử dụng.

#### 3.5.2. `POST /api/budgets` - Thiết Lập Hạn Mức Ngân Sách Mới
- **Request Body JSON Schema:**
  ```json
  {
    "categoryId": 5,
    "amount": 5000000,
    "periodMonth": 10,
    "periodYear": 2026
  }
  ```

#### 3.5.3. `GET /api/budgets/summary` - Tổng Quan Tình Trạng Chi Tiêu So Với Ngân Sách
- Tổng hợp toàn bộ ngân sách trong tháng, cảnh báo các danh mục đã chạm ngưỡng 80% hoặc vượt 100%.

#### 3.5.4. `PUT /api/budgets/{id}` & `DELETE /api/budgets/{id}` - Điều Chỉnh / Hủy Ngân Sách

---

### 3.6. Phân Hệ 6: Tài Chính Vi Mô & Quản Lý Khoản Vay (Microfinance & Debt)
> Ánh xạ: [UC06: Khoản cho vay](./uml_modeling/usecase_diagram.md#uc06-quan-ly-khoan-cho-vay-va-thu-no) | [UC07: Khoản đi vay & Trả nợ](./uml_modeling/usecase_diagram.md#uc07-quan-ly-khoan-di-vay-va-tra-no) | [SQ05: Microfinance Loan](./uml_modeling/sequence_diagram.md#35-sq05-khởi-tạo-khoản-vay--thanh-toán-trả-nợ-vi-mô) | [CD07](./uml_modeling/class_diagram.md#27-phân-hệ-7-quản-lý-khoản-vay-vi-mô--phân-bổ-gốclãi-microfinance-loans-context---uc06-uc07) | [ERD04](./database_design.md#34-phân-hệ-tài-chính-vi-mô--quản-lý-khoản-vay-microfinance--debt)

#### 3.6.1. `GET /api/loans` - Danh Sách Khoản Vay / Nợ Vi Mô
- **Query Parameters:** `loanType=BORROWING` (Đi vay) hoặc `LENDING` (Cho vay), `status=ACTIVE`.
- Trả về danh sách khoản vay, tổng dư nợ gốc còn lại, lãi suất và ngày đáo hạn kế tiếp.

#### 3.6.2. `POST /api/loans` - Khởi Tạo Hợp Đồng Vay & Giải Ngân
- **Headers bắt buộc:** `Idempotency-Key: <UUIDv4>`.
- **Mô tả nghiệp vụ:** Khởi tạo khoản vay mới. Tự động giải ngân tiền vào ví nguồn; hạch toán tăng tài sản ví và tăng nghĩa vụ nợ phải trả (Asset / Liability), **tuyệt đối không ghi nhận vào Thu nhập** ([BR17](../SRS.md#br17)); đồng thời tự động sinh bảng kế hoạch phân kỳ trả nợ (Amortization Schedule) trong `loan_repayment_schedules` ([SQ05](./uml_modeling/sequence_diagram.md#35-sq05-khởi-tạo-khoản-vay--thanh-toán-trả-nợ-vi-mô)).
- **Request Body JSON Schema:**
  ```json
  {
    "disbursementWalletId": 2,
    "loanType": "BORROWING",
    "counterpartyName": "Ngân hàng Chính sách Xã hội",
    "principalAmount": 30000000,
    "interestRate": 6.5,
    "termMonths": 12,
    "startDate": "2026-10-17",
    "interestType": "REDUCING_BALANCE"
  }
  ```

#### 3.6.3. `GET /api/loans/{id}` - Chi Tiết Hợp Đồng Vay & Lịch Trả Nợ Phân Kỳ
- Trả về thông tin khoản vay, danh sách các kỳ trả nợ (`loan_repayment_schedules`) kèm số tiền gốc, tiền lãi từng kỳ và lịch sử các lần thanh toán (`loan_payments`).

#### 3.6.4. `POST /api/loans/{id}/payments` - Thanh Toán Trả Nợ Từng Kỳ (Tách Bạch Gốc/Lãi)
- **Headers bắt buộc:** `Idempotency-Key: <UUIDv4>`.
- **Mô tả nghiệp vụ:** Trừ tiền từ ví thanh toán, tách bạch rành mạch số tiền thanh toán thành **Nợ gốc** (giảm `remaining_principal` và ghi giảm Liability) và **Chi phí lãi vay** (ghi nhận vào Expense: Interest), cập nhật trạng thái kỳ phân kỳ sang `PAID`, và tự động chuyển khoản vay sang `SETTLED` khi dư nợ chạm mức 0 ([BR10, BR17, SQ05](../SRS.md#br10)).
- **Request Body JSON Schema:**
  ```json
  {
    "walletId": 2,
    "scheduleItemId": 1,
    "principalPaid": 2500000,
    "interestPaid": 162500,
    "paymentDate": "2026-10-17"
  }
  ```

#### 3.6.5. `POST /api/loans/{id}/settle` - Tất Toán Khoản Vay Sớm Hạn

---

### 3.7. Phân Hệ 7: Quản Lý Danh Mục Vàng & Ngoại Hối (Gold Portfolio & Forex)
> Ánh xạ: [UC08: Danh mục đầu tư vàng](./uml_modeling/usecase_diagram.md#uc08-quan-ly-danh-muc-dau-tu-vang-tich-luy) | [SQ06: Gold Net Worth](./uml_modeling/sequence_diagram.md#36-sq06-quản-lý-vàng-tích-lũy--tổng-hợp-net-worth-dashboard) | [CD08](./uml_modeling/class_diagram.md#28-phân-hệ-8-tích-lũy-tài-sản-vàng--định-giá-thị-trường-gold-asset-market-context---uc08) | [ERD05](./database_design.md#35-phân-hệ-danh-mục-vàng--tỷ-giá-ngoại-hối-gold-portfolio--forex)

#### 3.7.1. `GET /api/gold` - Danh Mục Vàng Đang Sở Hữu
- Trả về danh sách tài sản vàng (`gold_assets`): Loại vàng (SJC, Nhẫn 9999, PNJ), khối lượng đo bằng đơn vị Chỉ / Cây (DECIMAL 18,4), giá vốn bình quân gia quyền và định giá thị trường hiện tại.

#### 3.7.2. `POST /api/gold/transactions` - Ghi Nhận Mua / Bán Vàng
- **Request Body JSON Schema:**
  ```json
  {
    "walletId": 2,
    "goldType": "SJC_BAR",
    "actionType": "BUY",
    "quantityChi": 5.0000,
    "pricePerChi": 8500000,
    "fee": 0,
    "transactionDate": "2026-10-17"
  }
  ```
- **Mô tả nghiệp vụ:** Trừ tiền ví thanh toán (5 * 8.5M = 42.5M VND), cập nhật tăng số dư vàng và tính lại giá vốn bình quân gia quyền ([SQ06](./uml_modeling/sequence_diagram.md#36-sq06-quản-lý-vàng-tích-lũy--tổng-hợp-net-worth-dashboard)).

#### 3.7.3. `GET /api/gold/prices` & `POST /api/gold/prices` - Giá Vàng Thị Trường
- Tra cứu bảng giá vàng mới nhất và cập nhật giá vàng thủ công hoặc qua Cron Job từ các sàn SJC/DOJI.

---

### 3.8. Phân Hệ 8: Mục Tiêu Tiết Kiệm & Phong Tỏa Ví (Goals & Earmarking)
> Ánh xạ: [UC09: Mục tiêu tiết kiệm & Khóa tiền Earmarking](./uml_modeling/usecase_diagram.md#uc09-muc-tieu-tiet-kiem-va-quy-phong-toa-earmarking) | [CD09](./uml_modeling/class_diagram.md#29-phân-hệ-9-mục-tiêu-tiết-kiệm--khóa-tiền-earmarking-goal-saving-earmark-context---uc09) | [ERD06](./database_design.md#36-phân-hệ-mục-tiêu-tài-chính--tài-sản-ròng-goals--net-worth)

#### 3.8.1. `GET /api/goals` - Danh Sách Mục Tiêu Tiết Kiệm
- Trả về các mục tiêu (Mua nhà, Mua xe, Quỹ khẩn cấp), số tiền mục tiêu, số tiền đã tích lũy và ngày hạn chót.

#### 3.8.2. `POST /api/goals` - Tạo Mục Tiêu Tài Chính Mới
- **Request Body:** `{ "name": "Quỹ khẩn cấp", "targetAmount": 50000000, "deadline": "2027-12-31" }`.

#### 3.8.3. `POST /api/goals/{id}/deposit` - Nạp Tiền Vào Mục Tiêu (Phong Tỏa Earmarking)
- **Mô tả nghiệp vụ:** Trích tiền từ ví vào quỹ mục tiêu; số tiền này được ghi nhận vào `earmarked_balance` của ví để ngăn chặn người dùng vô tình chi tiêu vào các khoản sinh hoạt thông thường.

#### 3.8.4. `POST /api/goals/{id}/withdraw` & `DELETE /api/goals/{id}` - Rút Quỹ / Hủy Mục Tiêu

---

### 3.9. Phân Hệ 9: Dashboard Phân Tích Tài Sản Ròng & Sức Khỏe (Analytics & Net Worth)
> Ánh xạ: [UC10: Tài sản ròng & Sức khỏe tài chính](./uml_modeling/usecase_diagram.md#uc10-tong-hop-tai-san-rong-net-worth-va-suc-khoe-tai-chinh) | [SQ06](./uml_modeling/sequence_diagram.md#36-sq06-quản-lý-vàng-tích-lũy--tổng-hợp-net-worth-dashboard) | [CD10](./uml_modeling/class_diagram.md#210-phân-hệ-10-dashboard-sức-khỏe-net-worth--snapshot-nền-net-worth-analytics-context---uc10-sys03) | [ERD06](./database_design.md#36-phân-hệ-mục-tiêu-tài-chính--tài-sản-ròng-goals--net-worth)

#### 3.9.1. `GET /api/analytics/net-worth` - Tổng Hợp Tài Sản Ròng (Net Worth Dashboard)
- **Quyền truy cập:** Người dùng đã xác thực (`USER`).
- **Mô tả nghiệp vụ:** Tổng hợp theo thời gian thực tổng tài sản (Ví + Vàng quy đổi + Cho vay) trừ đi tổng nghĩa vụ nợ (Đi vay vi mô), áp dụng phòng vệ chia cho 0 khi tính toán tỷ lệ tiết kiệm ([BR02, BR18, SQ06](../SRS.md#br02)).
- **Phản hồi Thành công (200 OK):**
  ```json
  {
    "success": true,
    "statusCode": 200,
    "data": {
      "netWorth": "165000000.00000000",
      "currency": "VND",
      "assets": {
        "wallets": "25000000.00000000",
        "gold": "170000000.00000000",
        "receivableLoans": "0.00000000",
        "totalAssets": "195000000.00000000"
      },
      "liabilities": {
        "payableLoans": "30000000.00000000",
        "totalLiabilities": "30000000.00000000"
      },
      "metrics": {
        "savingRate": 35.8,
        "liquidityRatio": 4.2,
        "debtToAssetRatio": 0.15,
        "healthScore": 88,
        "healthGrade": "EXCELLENT"
      }
    }
  }
  ```

#### 3.9.2. `GET /api/analytics/net-worth/history` - Lịch Sử Xu Hướng Tài Sản Ròng
- **Query Parameters:** `days=30` (hoặc `90`, `365`).
- Trả về mảng dữ liệu lấy từ bảng `net_worth_snapshots` chốt cuối ngày phục vụ vẽ biểu đồ đường Line Chart.

#### 3.9.3. `GET /api/analytics/cash-flow` - Báo Cáo Dòng Tiền Thu/Chi Theo Tháng
- Thống kê tổng thu, tổng chi và dòng tiền thuần (Net Cash Flow) theo từng tháng.

#### 3.9.4. `GET /api/analytics/financial-health` - Điểm Số & Khuyến Nghị Tài Chính
- Đánh giá sức khỏe tài chính dựa trên các hệ số thanh khoản, chi tiêu thiết yếu và tỷ lệ đòn bẩy nợ vi mô.

---

### 3.10. Phân Hệ 10: Báo Cáo Đối Soát Sổ Cái & Xuất CSV (Reports & Reconciliation)
> Ánh xạ: [UC11: Báo cáo đối soát & Xuất CSV](./uml_modeling/usecase_diagram.md#uc11-bao-cao-thong-ke-va-xuat-du-lieu-csv) | [SQ07: Đối soát & Xuất CSV](./uml_modeling/sequence_diagram.md#37-sq07-báo-cáo-đối-soát-sổ-cái--xuất-dữ-liệu-csv-an-toàn) | [CD04](./uml_modeling/class_diagram.md#24-phân-hệ-4-báo-cáo-đối-soát-sổ-cái--xuất-csv-an-toàn-ledger-reconciliation--secure-csv-export-context---uc11) | [ERD07](./database_design.md#37-phân-hệ-thông-báo-cấu-hình--kiểm-toán-quản-trị-audit--system)

#### 3.10.1. `GET /api/reports/reconcile` - Đối Soát Số Dư Độc Lập
- **Query Parameters:** `walletId=10`.
- **Mô tả nghiệp vụ:** So sánh trực tiếp `wallets.cached_balance` với tổng đại số các dòng Nợ/Có lũy kế trong `ledger_entries`. Nếu phát hiện sai lệch delta, tự động ghi nhật ký cảnh báo vào `security_audit_logs` ([BR12, BR14, SQ07](../SRS.md#br12)).
- **Phản hồi Thành công (200 OK):**
  ```json
  {
    "success": true,
    "statusCode": 200,
    "data": {
      "walletId": 10,
      "cachedBalance": "15000000.00000000",
      "ledgerSum": "15000000.00000000",
      "delta": "0.00000000",
      "status": "MATCHED",
      "isAudited": true
    }
  }
  ```

#### 3.10.2. `GET /api/reports/trial-balance` - Bảng Cân Đối Số Phát Sinh (Trial Balance)
- Kiểm tra tính cân đối toàn cục của sổ cái kế toán kép: $\sum \text{Debit} = \sum \text{Credit}$.

#### 3.10.3. `GET /api/reports/export/csv` - Trích Xuất Dữ Liệu CSV An Toàn
- **Query Parameters:** `from=2026-10-01&to=2026-10-31&walletId=10`.
- **Headers trả về:**
  - `Content-Type: text/csv; charset=utf-8`
  - `Content-Disposition: attachment; filename="wealthflow_transactions_202610.csv"`
- **Xử lý An toàn Kỹ thuật (BR18, FR12.04):**
  1. Khử độc mã Formula Injection: tự động thêm dấu nháy đơn `'` trước các ký tự `=`, `+`, `-`, `@`.
  2. Gắn tiền tố mã hóa chuẩn **UTF-8 BOM (`\xEF\xBB\xBF`)** ở 3 byte đầu file giúp Microsoft Excel hiển thị tiếng Việt có dấu chuẩn xác 100%.
  3. Tính mã băm SHA-256 và ghi vết tải xuống vào bảng `export_audit_logs`.

---

### 3.11. Phân Hệ 11: Thông Báo & Quản Trị Hệ Thống (Notifications & Admin)
> Ánh xạ: [UC12: Quản trị người dùng](./uml_modeling/usecase_diagram.md#uc12-quan-tri-nguoi-dung-va-thong-ke-vi-mo) | [UC13: Cấu hình & Phát thông báo](./uml_modeling/usecase_diagram.md#uc13-cau-hinh-he-thong-va-phat-thong-bao-toan-he-thong) | [SQ08: Admin Lockout & Broadcast](./uml_modeling/sequence_diagram.md#38-sq08-quản-trị-người-dùng-khóa-tài-khoản--phát-thông-báo) | [CD11](./uml_modeling/class_diagram.md#211-phân-hệ-11-quản-trị--broadcast-thông-báo-notification-broadcast-context---uc13-sys01) | [ERD07](./database_design.md#37-phân-hệ-thông-báo-cấu-hình--kiểm-toán-quản-trị-audit--system)

#### 3.11.1. `GET /api/notifications` - Danh Sách Thông Báo Của Người Dùng
- **Query Parameters:** `unreadOnly=true&limit=20`.
- Trả về danh sách thông báo đẩy trong ứng dụng (Cảnh báo ngân sách, Nhắc hạn nợ, Thông báo Admin).

#### 3.11.2. `POST /api/notifications/{id}/read` & `POST /api/notifications/read-all`
- Đánh dấu 1 thông báo hoặc tất cả thông báo là đã đọc (`read_at = NOW()`).

#### 3.11.3. `GET /api/admin/users` - Quản Trị Danh Sách Người Dùng (Admin Only)
- **Quyền truy cập:** Quản trị viên (`role == 'ADMIN'`).
- Trả về danh sách người dùng kèm trạng thái (`ACTIVE`, `LOCKED`), ngày đăng ký và số lượng ví.

#### 3.11.4. `PUT /api/admin/users/{id}/lock` - Khóa Tài Khoản Vi Phạm & Thu Hồi Phiên
- **Quyền truy cập:** Quản trị viên (`ADMIN`).
- **Mô tả nghiệp vụ kỹ thuật:**
  1. Bắt buộc nhập lý do giải trình `reason` (tối thiểu 5 ký tự).
  2. Kiểm tra điều kiện an toàn: nếu đối tượng là Quản trị viên duy nhất còn lại, từ chối với mã `400 Bad Request` (`CANNOT_LOCK_LAST_ADMIN`).
  3. Bắt đầu Database Transaction: cập nhật `users.status = 'LOCKED'`, xóa sạch mọi phiên trong `auth_sessions`, và ghi nhật ký kiểm toán vào `admin_audit_logs`.
  4. Người dùng bị khóa sẽ nhận mã `403 Forbidden` ngay tại request tiếp theo ([BR11, BR13, BR14, SQ08](../SRS.md#br11)).
- **Request Body JSON Schema:**
  ```json
  {
    "reason": "Gian lận số dư & vi phạm điều khoản bảo mật hệ thống."
  }
  ```
- **Phản hồi Thành công (200 OK):**
  ```json
  {
    "success": true,
    "statusCode": 200,
    "data": {
      "targetUserId": 5,
      "status": "LOCKED",
      "message": "Tài khoản người dùng đã bị khóa thành công."
    }
  }
  ```

#### 3.11.5. `PUT /api/admin/users/{id}/unlock` - Mở Khóa Tài Khoản Người Dùng
- Chuyển trạng thái `status = 'ACTIVE'` và ghi log vào `admin_audit_logs`.

#### 3.11.6. `POST /api/admin/announcements` - Phát Thông Báo Toàn Hệ Thống (Broadcast)
- **Quyền truy cập:** Quản trị viên (`ADMIN`).
- **Mô tả nghiệp vụ kỹ thuật:** Áp dụng kỹ thuật **SQL Batch Fan-out (`INSERT ... SELECT`)** phân phối thông báo vào bảng `notifications` cho toàn bộ tài khoản `ACTIVE` trong một câu lệnh duy nhất ($O(1)$ query), kết hợp kích hoạt sự kiện đẩy Realtime WebSocket/SSE ([UC13, SQ08](./uml_modeling/sequence_diagram.md#38-sq08-quản-trị-người-dùng-khóa-tài-khoản--phát-thông-báo)).
- **Request Body JSON Schema:**
  ```json
  {
    "title": "Bảo trì nâng cấp hạ tầng định kỳ",
    "body": "Hệ thống sẽ bảo trì từ 01:00 đến 03:00 ngày mai. Vui lòng hoàn tất giao dịch trước thời gian trên.",
    "priority": "MAINTENANCE"
  }
  ```
- **Phản hồi Thành công (201 Created):**
  ```json
  {
    "success": true,
    "statusCode": 201,
    "data": {
      "announcementId": 48,
      "recipientCount": 2500,
      "status": "DISPATCHED",
      "message": "Đã phát thông báo thành công tới 2,500 người dùng."
    }
  }
  ```

#### 3.11.7. `GET /api/admin/system-config` & `PUT /api/admin/system-config/{key}`
- Quản trị các tham số cấu hình toàn hệ thống trong bảng `system_config` (Ví dụ: `MAX_FAILED_LOGINS`, `OTP_EXPIRY_MINUTES`).

#### 3.11.8. `GET /api/admin/audit-logs` - Tra Cứu Nhật Ký Kiểm Toán Quản Trị
- Tra cứu lịch sử can thiệp hệ thống của các quản trị viên từ bảng `admin_audit_logs`.

---

## 4. Ma Trận Truy Vết API, Use Cases & Kiến Trúc BCE+D

Bảng dưới đây xác nhận tính toàn vẹn và mức độ bao phủ 100% của đặc tả API đối với các Use Cases, Class Controllers và Bảng cơ sở dữ liệu:

| Phân hệ API | Endpoints Tiêu biểu | Use Cases | Quy tắc BR | Controller Tương ứng (BCE+D) | DAO / Bảng CSDL Tác động | Kịch bản Tuần tự |
| :--- | :--- | :---: | :---: | :--- | :--- | :---: |
| **Auth** | `POST /api/auth/register`<br>`POST /api/auth/login` | `UC01` | BR01, BR11, BR15 | `AuthController` | `UserDAO` (`users`, `auth_sessions`) | [SQ01](./uml_modeling/sequence_diagram.md#31-sq01-đăng-ký-tài-khoản-mã-hóa-argon2id--đăng-nhập-jwt) |
| **Wallets** | `GET /api/wallets`<br>`POST /api/wallets`<br>`GET /api/wallets/{id}`<br>`PUT /api/wallets/{id}`<br>`POST /api/wallets/{id}/adjustments`<br>`POST /api/wallets/{id}/archive`<br>`DELETE /api/wallets/{id}` | `UC02` | BR01, BR02 | `WalletController` | `WalletDAO`, `LedgerDAO` (`wallets`, `financial_transactions`, `ledger_entries`) | [SQ01](./uml_modeling/sequence_diagram.md#31-sq01-đăng-ký-tài-khoản-mã-hóa-argon2id--đăng-nhập-jwt) |
| **Transactions** | `POST /api/transactions`<br>`POST /api/transactions/transfer` | `UC03` | BR01, BR05, BR06 | `TransactionController` | `WalletDAO`, `LedgerDAO` (`transactions`, `ledger_entries`) | [SQ02](./uml_modeling/sequence_diagram.md#32-sq02-ghi-nhận-chi-tiêu-khóa-bi-quan--cảnh-báo-ngân-sách), [SQ03](./uml_modeling/sequence_diagram.md#33-sq03-chuyển-tiền-nội-bộ--đảo-giao-dịch-reversal) |
| **Categories** | `GET /api/categories`<br>`POST /api/categories`<br>`PUT /api/categories/{id}`<br>`DELETE /api/categories/{id}` | `UC03` | BR01 | `TransactionController` | `CategoryDAO` (`categories`) | [SQ02](./uml_modeling/sequence_diagram.md#32-sq02-ghi-nhận-chi-tiêu-khóa-bi-quan--cảnh-báo-ngân-sách) |
| **Recurring** | `POST /api/recurring`<br>`POST /api/recurring/{id}/trigger` | `UC04`, `SYS02` | BR07, BR09, BR13 | `RecurringController` | `RecurringRuleDAO` (`recurring_schedules`, `recurring_executions`) | [SQ04](./uml_modeling/sequence_diagram.md#34-sq04-xử-lý-giao-dịch-định-kỳ-tự-động-subscription-cron) |
| **Budgets** | `GET /api/budgets/summary`<br>`POST /api/budgets` | `UC05`, `SYS03` | BR07, BR08 | `BudgetController` | `BudgetDAO` (`budgets`, `notifications`) | [SQ02](./uml_modeling/sequence_diagram.md#32-sq02-ghi-nhận-chi-tiêu-khóa-bi-quan--cảnh-báo-ngân-sách) |
| **Loans** | `POST /api/loans`<br>`POST /api/loans/{id}/payments` | `UC06`, `UC07` | BR09, BR10, BR17 | `LoanController` | `LoanDAO` (`micro_loans`, `loan_repayment_schedules`, `loan_payments`) | [SQ05](./uml_modeling/sequence_diagram.md#35-sq05-khởi-tạo-khoản-vay--thanh-toán-trả-nợ-vi-mô) |
| **Gold** | `POST /api/gold/transactions`<br>`GET /api/gold` | `UC08` | BR02, BR04 | `GoldController` | `GoldHoldingDAO` (`gold_assets`, `gold_transactions`) | [SQ06](./uml_modeling/sequence_diagram.md#36-sq06-quản-lý-vàng-tích-lũy--tổng-hợp-net-worth-dashboard) |
| **Goals** | `POST /api/goals`<br>`POST /api/goals/{id}/deposit` | `UC09` | BR01, BR06 | `GoalController` | `GoalDAO` (`financial_goals`, `goal_allocations`) | - |
| **Analytics** | `GET /api/analytics/net-worth`<br>`GET /api/analytics/financial-health` | `UC10`, `SYS03` | BR02, BR16, BR18 | `AnalyticsDashboardController`| `NetWorthSnapshotDAO` (`net_worth_snapshots`) | [SQ06](./uml_modeling/sequence_diagram.md#36-sq06-quản-lý-vàng-tích-lũy--tổng-hợp-net-worth-dashboard) |
| **Reports** | `GET /api/reports/reconcile`<br>`GET /api/reports/export/csv` | `UC11` | BR01, BR12, BR14, BR18 | `ReportController` | `LedgerDAO`, `AuditLogDAO` (`security_audit_logs`, `export_audit_logs`)| [SQ07](./uml_modeling/sequence_diagram.md#37-sq07-báo-cáo-đối-soát-sổ-cái--xuất-dữ-liệu-csv-an-toàn) |
| **Admin** | `PUT /api/admin/users/{id}/lock`<br>`POST /api/admin/announcements` | `UC12`, `UC13` | BR11, BR13, BR14 | `AdminController` | `UserDAO`, `NotificationDAO`, `AuditLogDAO` (`admin_audit_logs`, `notifications`) | [SQ08](./uml_modeling/sequence_diagram.md#38-sq08-quản-trị-người-dùng-khóa-tài-khoản--phát-thông-báo) |

---

## 5. Kết Luận & Hướng Dẫn Chuyển Giao Triển Khai (Implementation Readiness)

Tài liệu **Đặc tả RESTful API (`api_design.md`)** đã định hình trọn vẹn hợp đồng giao tiếp giữa các thành phần phần mềm của WealthFlow:
1. **Tính Nhất Quán & Bảo Mật Tuyệt Đối:** Chuẩn hóa toàn diện cơ chế xác thực JWT, bảo vệ CSRF Same-origin, mã băm Argon2id, chống trùng lặp Idempotency-Key và kiểm soát ranh giới phân quyền `USER` / `ADMIN`.
2. **Kế Thừa Hoàn Chỉnh Thiết Kế UML & Database:** Mọi Endpoint đều được đối chiếu chặt chẽ với các phương thức Controller trong [class_diagram.md](./uml_modeling/class_diagram.md), các tương tác động trong [sequence_diagram.md](./uml_modeling/sequence_diagram.md), và cấu trúc 22 bảng trong [database_design.md](./database_design.md).
3. **Sẵn Sàng Cho Pha Xây Dựng (Construction Phase):** Đội ngũ kỹ sư phát triển có thể triển khai trực tiếp các Route Handler và Controller trong thư mục `source/backend/src/Modules/` cùng các giao diện Web Client tương ứng.
