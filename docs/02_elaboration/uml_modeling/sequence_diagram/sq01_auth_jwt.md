# SQ01: Đăng ký, Mã hóa Argon2id & Đăng nhập JWT

> Tham chiếu: [SRS.md](../../SRS.md) v2.1 | [sequence_diagram.md](../sequence_diagram.md) | [class_diagram.md](../class_diagram.md)

---

## 1. Thông tin Kịch bản Nghiệp vụ

| Thuộc tính | Chi tiết đặc tả |
| :--- | :--- |
| **Mã Sơ đồ** | **SQ01** |
| **Tên Kịch bản** | **Đăng ký tài khoản, Mã hóa mật khẩu Argon2id & Đăng nhập cấp phát JWT** |
| **Use Cases bao phủ** | `UC01` (Đăng ký & Xác thực người dùng), `UC02` (Quản lý ví và số dư mở sổ) |
| **Quy tắc Nghiệp vụ (BR)** | `BR01` (Kế toán kép), `BR02` (Số dư mở sổ đối ứng Equity), `BR11` (Bảo mật phiên & Cách ly dữ liệu), `BR15` (Bảo mật API) |
| **Yêu cầu Chức năng (FR)** | `FR01.01` - `FR01.05`, `FR15.01` |
| **Mục tiêu kỹ nghệ** | Mô hình hóa quy trình băm mật khẩu bảo mật (Argon2id/bcrypt), khởi tạo tài khoản Active nguyên tử, tự động sao chép danh mục thu/chi mặc định, và xác thực cấp cặp Token JWT (Access Token 15 phút, Refresh Token 7 ngày). |

---

## 2. Bảng Danh mục Đối tượng Tham gia (Lifelines)

| Tên Lifeline | Tầng Kiến trúc | Vai trò & Trách nhiệm |
| :--- | :---: | :--- |
| **User** | Actor | Khách vãng lai đăng ký hoặc Người dùng đăng nhập vào hệ thống. |
| **AuthController** | Boundary | Endpoint tiếp nhận HTTP POST `/api/auth/register` và `/api/auth/login`. |
| **AuthService** | Control | Xử lý logic băm mật khẩu, thẩm định tài khoản và ký số JWT. |
| **UserRepository** | Entity / Persistence | Truy vấn và lưu trữ thông tin thực thể User, RefreshToken. |
| **PostgreSQL Database** | Storage | Cơ sở dữ liệu quan hệ lưu trữ bản ghi và thực thi Transaction. |

---

## 3. Sơ đồ Tuần tự (Mermaid Sequence Diagram)

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

---

## 4. Mã nguồn PlantUML Tương ứng

<details>
<summary><b>Nhấn để xem mã PlantUML chính quy (dùng xuất ảnh nộp báo cáo Word)</b></summary>

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

## 5. Điều hướng Tài liệu
- **Về mục lục tổng quan:** [Tài liệu Tổng quan Sơ đồ Tuần tự](../sequence_diagram.md)
- **Kịch bản kế tiếp:** [SQ02: Ghi nhận Chi tiêu, Hạch toán Kép & Kiểm tra Ngân sách](./sq02_expense_budget.md)
