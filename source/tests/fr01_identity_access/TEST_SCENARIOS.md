# ĐẶC TẢ KỊCH BẢN KIỂM THỬ CHI TIẾT (TEST SPECIFICATION)
## Phân Hệ: FR01 - Định Danh, Xác Thực & Quản Lý Phiên (Identity & Access)
## Kiến Trúc: Đa Tầng Theo Thiết Kế BCE+D | Chuẩn UP/RUP Pha Construction

---

## I. THÔNG TIN CHUNG
* **Mã Phân Hệ:** `FR01`
* **Tên Chức Năng:** Định danh, xác thực và quản lý phiên truy cập (Identity, Authentication & Session Management)
* **Sprint Triển Khai:** `Sprint 1`
* **Người Phụ Trách (Developer):** Võ Trịnh Quốc Huy
* **Người Kiểm Duyệt (Reviewer):** Nguyễn Thành Trung
* **Use Case Tương Ứng:** `UC01` (Đăng ký, Đăng nhập, Quản lý phiên)
* **Quy Tắc Nghiệp Vụ:** `BR11` (Kiểm soát truy cập & bảo vệ dữ liệu cá nhân)
* **Yêu Cầu Phi Chức Năng:** `NFR02` (Bảo mật mật khẩu), `NFR06` (Quản lý phiên JWT)
* **Tài Liệu Căn Cứ:** [SRS.md v2.1](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/docs/02_elaboration/SRS.md), [component_design.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/docs/02_elaboration/component_design.md)
* **Công Cụ Tự Động Hóa:** Python 3.13 (`pytest`, `requests`, `sqlite3`)

---

## II. MA TRẬN 22 KỊCH BẢN KIỂM THỬ THEO MÔ HÌNH BCE+D

| STT | Mã Ca Test | Tầng Kiến Trúc | Tên Kịch Bản Kiểm Thử | Trọng Số | Loại Kịch Bản |
| :---: | :---: | :---: | :--- | :---: | :---: |
| 1 | **TC-D01.01** | **Data (D)** | Khởi tạo bản ghi người dùng mới qua `UserDAO::createUser` | P1 | Positive |
| 2 | **TC-D01.02** | **Data (D)** | Ràng buộc duy nhất Email (Unique Constraint) trên CSDL | P1 | Negative |
| 3 | **TC-D01.03** | **Data (D)** | Tự động sao chép danh mục mẫu qua `copyDefaultCategories` | P1 | Positive |
| 4 | **TC-D01.04** | **Data (D)** | Vòng đời Session trong bảng `auth_sessions` & hết hạn | P1 | Boundary |
| 5 | **TC-D01.05** | **Data (D)** | Tính nguyên tử của Transaction (Atomic Rollback khi lỗi) | P1 | Exception |
| 6 | **TC-C01.01** | **Control (C)** | Chuẩn hóa dữ liệu đầu vào (Trim họ tên, Lowercase email) | P2 | Normalization |
| 7 | **TC-C01.02** | **Control (C)** | Thẩm định chính sách độ mạnh mật khẩu theo NFR02 | P1 | Negative / Boundary |
| 8 | **TC-C01.03** | **Control (C)** | Giải thuật băm mật khẩu Bcrypt với Chi phí Cost 12 | P1 | Security |
| 9 | **TC-C01.04** | **Control (C)** | Cơ chế chống tấn công Brute-force qua Rate Limiting (429) | P1 | Security / RateLimit |
| 10 | **TC-C01.05** | **Control (C)** | Chống rò rỉ sự tồn tại của người dùng (Anti User Enumeration) | P2 | Security |
| 11 | **TC-C01.06** | **Control (C)** | Cơ chế thu hồi phiên khi Đổi mật khẩu (`token_version`) | P1 | Business Logic |
| 12 | **TC-C01.07** | **Control (C)** | Vòng đời và xác thực mã OTP Quên mật khẩu (10 phút) | P1 | Lifecycle |
| 13 | **TC-B01.01** | **Boundary (API)** | `POST /auth/register` - Đăng ký tài khoản thành công (201) | P1 | Happy Path |
| 14 | **TC-B01.02** | **Boundary (API)** | `POST /auth/register` - Xử lý trùng lặp Email (409 Conflict) | P1 | Negative |
| 15 | **TC-B01.03** | **Boundary (API)** | `POST /auth/login` - Đăng nhập hợp lệ & Cấp Cookie HttpOnly (200) | P1 | Happy Path |
| 16 | **TC-B01.04** | **Boundary (API)** | `POST /auth/login` - Đăng nhập thất bại do sai mật khẩu (401) | P1 | Negative |
| 17 | **TC-B01.05** | **Boundary (API)** | `GET /auth/me` - Xác thực token phiên & Không lộ secret (200) | P1 | Security / Contract |
| 18 | **TC-B01.06** | **Boundary (API)** | `POST /auth/logout` - Đăng xuất và xóa Cookie trình duyệt (200) | P1 | Lifecycle |
| 19 | **TC-UI01.01** | **Boundary (UI)** | Kiểm tra Validation trực tiếp trên Form Đăng ký (`index.html`) | P2 | Client Validation |
| 20 | **TC-UI01.02** | **Boundary (UI)** | Trạng thái Button Loading & Chống Double-Click khi submit | P2 | UX / Concurrency |
| 21 | **TC-UI01.03** | **Boundary (UI)** | Hiển thị Toast thông báo lỗi & Giữ nguyên email khi login sai | P2 | UX / Error Feedback |
| 22 | **TC-UI01.04** | **Boundary (UI)** | Luồng chuyển hướng (Redirect Flow) sau khi Đăng nhập thành công | P1 | E2E Navigation |

---

## III. ĐẶC TẢ CHI TIẾT TỪNG KỊCH BẢN KIỂM THỬ

### TẦNG D: DATA ACCESS & DATABASE QUERIES (5 Kịch Bản)

#### 1. TC-D01.01: Khởi tạo bản ghi người dùng mới qua `UserDAO::createUser`
* **Mục tiêu:** Kiểm tra chức năng chèn bản ghi người dùng mới vào bảng `users` qua Prepared Statement.
* **Tiền điều kiện:** CSDL đã sẵn sàng bảng `users`. Email `test.d01.new@wealthflow.local` chưa tồn tại.
* **Dữ liệu đầu vào (Input):**
  * `fullName`: `"Nguyen Van D"`
  * `email`: `"test.d01.new@wealthflow.local"`
  * `passwordHash`: `"$2y$12$3E5SQtCbtDHbZEvyMahaBOeGp5XEtthDK6v8k3QATe.gBCokmPRhm"`
  * `baseCurrency`: `"VND"`
* **Các bước thực hiện:**
  1. Khởi tạo đối tượng `UserDAO` kết nối DB.
  2. Gọi phương thức: `$userId = $userDao->createUser("Nguyen Van D", "test.d01.new@wealthflow.local", $hash, "VND")`.
  3. Truy vấn SQL: `SELECT * FROM users WHERE id = :id`.
* **Kết quả kỳ vọng (Assertions):**
  * `$userId` là số nguyên $> 0$.
  * Trường `full_name` lưu chính xác `"Nguyen Van D"`.
  * Trường `email` lưu `"test.d01.new@wealthflow.local"`.
  * Trường `role` có giá trị mặc định là `'USER'`.
  * Trường `status` có giá trị mặc định là `'ACTIVE'`.
  * Trường `token_version` có giá trị khởi tạo là `1`.
  * Trường `created_at` được gán mốc thời gian UTC hiện hành.
* **Hậu điều kiện:** Xóa bản ghi thử nghiệm sau khi hoàn tất.

---

#### 2. TC-D01.02: Ràng buộc duy nhất Email (Unique Constraint) trên CSDL
* **Mục tiêu:** Xác minh CSDL ngăn chặn tuyệt đối việc lưu trùng email thông qua ràng buộc `UNIQUE KEY`.
* **Tiền điều kiện:** CSDL đã tồn tại bản ghi có email `unique.test@wealthflow.local`.
* **Dữ liệu đầu vào (Input):**
  * Bản ghi 1: Name: `"User One"`, Email: `"unique.test@wealthflow.local"`
  * Bản ghi 2: Name: `"User Two"`, Email: `"unique.test@wealthflow.local"`
* **Các bước thực hiện:**
  1. Chèn Bản ghi 1 thành công.
  2. Chèn Bản ghi 2 với cùng email trên.
* **Kết quả kỳ vọng (Assertions):**
  * Lần chèn thứ 2 ném ra ngoại lệ `PDOException` (hoặc Python `sqlite3.IntegrityError`).
  * Mã lỗi SQLSTATE là `23000` (MySQL Error `1062 - Duplicate entry`).
  * Thực thi `SELECT COUNT(*) FROM users WHERE email = 'unique.test@wealthflow.local'` trả về đúng `1`.

---

#### 3. TC-D01.03: Tự động sao chép danh mục mẫu qua `copyDefaultCategories`
* **Mục tiêu:** Kiểm tra câu truy vấn `INSERT INTO categories ... SELECT ... FROM default_categories`.
* **Tiền điều kiện:** Bảng `default_categories` có sẵn 10 danh mục mẫu đang hoạt động (`is_active = 1`). Tạo trước User có ID = 777.
* **Các bước thực hiện:**
  1. Gọi phương thức: `$userDao->copyDefaultCategories(777)`.
  2. Truy vấn: `SELECT COUNT(*) FROM categories WHERE user_id = 777`.
  3. Truy vấn: `SELECT name, category_type FROM categories WHERE user_id = 777`.
* **Kết quả kỳ vọng (Assertions):**
  * Số lượng bản ghi tạo ra đúng bằng số danh mục có `is_active = 1` trong `default_categories`.
  * Toàn bộ các dòng mới có cột `user_id = 777`.
  * Phân loại danh mục (`INCOME`, `EXPENSE`) khớp 100% với bảng mẫu.

---

#### 4. TC-D01.04: Vòng đời Session trong bảng `auth_sessions` & hết hạn
* **Mục tiêu:** Kiểm tra tạo session, hash refresh token và lọc bỏ session quá hạn.
* **Tiền điều kiện:** User ID = 101 đang có trạng thái `ACTIVE`.
* **Các bước thực hiện:**
  1. Tạo Session A: ID=`"sess_valid"`, `expires_at = UTC_TIMESTAMP() + 7 days`.
  2. Tạo Session B: ID=`"sess_expired"`, `expires_at = UTC_TIMESTAMP() - 1 hour`.
  3. Gọi `$userDao->findActiveSession("sess_valid", 101)`.
  4. Gọi `$userDao->findActiveSession("sess_expired", 101)`.
* **Kết quả kỳ vọng (Assertions):**
  * Session A: Trả về thông tin user hợp lệ kèm `session_id = "sess_valid"`.
  * Session B: Trả về `null` vì câu truy vấn lọc điều kiện `s.expires_at > UTC_TIMESTAMP()`.
  * Cột `refresh_hash` lưu chuỗi băm độ dài 64 ký tự (SHA-256), không lưu token thô.

---

#### 5. TC-D01.05: Tính nguyên tử của Transaction (Atomic Rollback khi lỗi)
* **Mục tiêu:** Đảm bảo toàn bộ quy trình đăng ký (Tạo user, sao chép danh mục, ghi log) được bao bọc trong 1 Transaction.
* **Tiền điều kiện:** Kết nối DB có hỗ trợ giao dịch ACID.
* **Các bước thực hiện:**
  1. Khởi động giao dịch: `$pdo->beginTransaction()`.
  2. Chèn user mới với email `atomic.fail@wealthflow.local`.
  3. Giả lập phát sinh lỗi ngoại lệ ở bước tiếp theo.
  4. Bắt ngoại lệ và kích hoạt: `$pdo->rollBack()`.
  5. Truy vấn: `SELECT * FROM users WHERE email = 'atomic.fail@wealthflow.local'`.
* **Kết quả kỳ vọng (Assertions):**
  * Bản ghi user hoàn toàn không tồn tại trong CSDL.
  * Không phát sinh bất kỳ bản ghi rác mồ côi nào trong bảng `categories` hay `security_audit_logs`.

---

### TẦNG C: CONTROL & BUSINESS LOGIC (7 Kịch Bản)

#### 6. TC-C01.01: Chuẩn hóa dữ liệu đầu vào (Trim họ tên, Lowercase email)
* **Mục tiêu:** Tránh lỗi do người dùng gõ nhầm khoảng trắng hoặc chữ hoa chữ thường.
* **Dữ liệu đầu vào (Input):**
  * `fullName`: `"   Võ   Văn   Hoàng   "`
  * `email`: `"   Hoang.Vo_Test@GMAIL.Com   "`
* **Các bước thực hiện:**
  1. Gọi hàm `AuthService::register($input)`.
  2. Kiểm tra dữ liệu được lưu thực tế trong CSDL.
* **Kết quả kỳ vọng (Assertions):**
  * `full_name` lưu thành: `"Võ   Văn   Hoàng"` (đã loại bỏ khoảng trắng đầu cuối).
  * `email` lưu thành: `"hoang.vo_test@gmail.com"` (chuẩn hóa toàn bộ chữ thường, không có khoảng trắng).

---

#### 7. TC-C01.02: Thẩm định chính sách độ mạnh mật khẩu theo NFR02
* **Mục tiêu:** Bảo vệ người dùng khỏi việc đặt mật khẩu yếu, dễ bị tấn công brute-force hoặc dictionary.
* **Tiêu chuẩn NFR02:** Độ dài $\ge 8$ ký tự, chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 chữ số.
* **Dữ liệu thử nghiệm:**
  * Case 1: `"1234567"` (Dưới 8 ký tự)
  * Case 2: `"onlylowercase123"` (Thiếu chữ hoa)
  * Case 3: `"ONLYUPPERCASE123"` (Thiếu chữ thường)
  * Case 4: `"NoDigitsHere!"` (Thiếu chữ số)
  * Case 5: Mật khẩu xác nhận không khớp (`password != passwordConfirmation`)
* **Các bước thực hiện:**
  1. Gọi `AuthService::register()` lần lượt với 5 bộ dữ liệu trên.
* **Kết quả kỳ vọng (Assertions):**
  * 100% các trường hợp đều bị từ chối trước khi gọi CSDL.
  * Ném ngoại lệ `ApiException` với mã `VALIDATION_ERROR` (HTTP 422).
  * Mảng `fields` chỉ rõ thông báo lỗi tương ứng với tiêu chí bị vi phạm.

---

#### 8. TC-C01.03: Giải thuật băm mật khẩu Bcrypt với Chi phí Cost 12
* **Mục tiêu:** Mật khẩu được băm an toàn, chi phí tính toán đủ cao để chống giải mã GPU.
* **Dữ liệu đầu vào (Input):** Mật khẩu `"SecurePassword@2026"`
* **Các bước thực hiện:**
  1. Gọi `AuthService::register()` với mật khẩu trên.
  2. Trích xuất trường `password_hash` từ bảng `users`.
* **Kết quả kỳ vọng (Assertions):**
  * Chuỗi hash bắt đầu bằng `$2y$12$`.
  * Độ dài chuỗi hash chính xác 60 ký tự.
  * `password_verify("SecurePassword@2026", $hash)` trả về `true`.
  * `password_verify("SecurePassword@2025", $hash)` trả về `false`.

---

#### 9. TC-C01.04: Cơ chế chống tấn công Brute-force qua Rate Limiting (429)
* **Mục tiêu:** Ngăn chặn kẻ xấu dò mật khẩu liên tục bằng cách khóa IP/tài khoản sau 5 lần thử sai.
* **Tiền điều kiện:** Tài khoản `target@wealthflow.local` có mật khẩu `"CorrectPass@123"`.
* **Các bước thực hiện:**
  1. Gửi liên tiếp 5 lần đăng nhập với mật khẩu sai `"WrongPass"` từ cùng IP trong 60 giây.
  2. Gửi tiếp lần đăng nhập thứ 6 (kể cả khi nhập đúng `"CorrectPass@123"`).
* **Kết quả kỳ vọng (Assertions):**
  * Lần 1 đến 5: Trả về mã lỗi `401 Unauthorized` (`INVALID_CREDENTIALS`).
  * Lần 6: Ném `ApiException` mã `RATE_LIMIT_EXCEEDED` với HTTP Status `429 Too Many Requests`.
  * Bảng `auth_rate_limits` ghi nhận số lần thử và thời gian hết hạn khóa.

---

#### 10. TC-C01.05: Chống rò rỉ sự tồn tại của người dùng (Anti User Enumeration)
* **Mục tiêu:** Ngăn chặn kẻ tấn công dò quét xem email nào đã đăng ký tài khoản trên hệ thống.
* **Các bước thực hiện:**
  1. Đăng nhập với Email KHÔNG tồn tại: `fake.user999@domain.com` / `"Pass1234!"`.
  2. Đăng nhập với Email CÓ tồn tại: `user1@gmail.com` / `"WrongPass1234!"`.
* **Kết quả kỳ vọng (Assertions):**
  * Cả hai trường hợp đều trả chung mã lỗi HTTP `401 Unauthorized`.
  * Cả hai trường hợp đều trả chung mã lỗi JSON: `"INVALID_CREDENTIALS"`.
  * Cả hai trường hợp đều trả chung thông điệp: `"Email hoặc mật khẩu không chính xác."`
  * Hệ thống sử dụng Dummy Bcrypt Hash để thời gian tính toán băm mật khẩu của 2 trường hợp đồng đều nhau ($|\Delta t| < 50ms$).

---

#### 11. TC-C01.06: Cơ chế thu hồi phiên khi Đổi mật khẩu (`token_version`)
* **Mục tiêu:** Đảm bảo khi đổi mật khẩu, toàn bộ các token JWT đã cấp trước đó bị vô hiệu hóa tức thì.
* **Tiền điều kiện:** User ID = 1 sở hữu token JWT A (trên điện thoại) và token JWT B (trên máy tính).
* **Các bước thực hiện:**
  1. Trên điện thoại, gọi `changePassword()` với mật khẩu cũ đúng và mật khẩu mới hợp lệ.
  2. Kiểm tra giá trị `token_version` trong bảng `users`.
  3. Dùng token B cũ trên máy tính gửi request `GET /auth/me`.
* **Kết quả kỳ vọng (Assertions):**
  * Bước 1: Đổi mật khẩu thành công (`200 OK`).
  * Bước 2: Cột `token_version` tăng từ $N \rightarrow N + 1$.
  * Bước 3: Request từ token B cũ bị từ chối với lỗi `401 Unauthorized` (`UNAUTHENTICATED`).

---

#### 12. TC-C01.07: Vòng đời và xác thực mã OTP Quên mật khẩu (10 phút)
* **Mục tiêu:** Mã OTP chỉ có giá trị trong vòng 10 phút, không thể tái sử dụng sau khi đã đổi mật khẩu.
* **Các bước thực hiện:**
  1. Gọi `requestPasswordReset(["email" => "user1@gmail.com"])` $\rightarrow$ sinh OTP 6 số.
  2. Xác thực thời gian hết hạn: `expires_at == created_at + 600s`.
  3. Gọi `resetPassword()` với đúng mã OTP $\rightarrow$ đổi mật khẩu thành công.
  4. Gọi lại `resetPassword()` lần 2 với cùng mã OTP đó.
* **Kết quả kỳ vọng (Assertions):**
  * Bước 3: Đổi mật khẩu thành công (`200 OK`).
  * Bước 4: Bị từ chối với mã lỗi `422` hoặc `400` báo mã OTP không hợp lệ hoặc đã qua sử dụng.

---

### TẦNG B (API): BOUNDARY CONTROLLER & HTTP CONTRACT (6 Kịch Bản)

#### 13. TC-B01.01: `POST /auth/register` - Đăng ký tài khoản thành công (201)
* **Endpoint:** `POST /auth/register`
* **Headers:** `Content-Type: application/json`
* **Payload:**
  ```json
  {
    "fullName": "Phan Hoang Nam",
    "email": "nam.phan@wealthflow.vn",
    "password": "User@1234",
    "passwordConfirmation": "User@1234",
    "baseCurrency": "VND"
  }
  ```
* **Kết quả kỳ vọng (Assertions):**
  * HTTP Status: **`201 Created`**.
  * JSON Response:
    ```json
    {
      "success": true,
      "data": {
        "message": "Đăng ký thành công. Bạn có thể đăng nhập ngay.",
        "emailDelivery": "preview"
      }
    }
    ```

---

#### 14. TC-B01.02: `POST /auth/register` - Xử lý trùng lặp Email (409 Conflict)
* **Endpoint:** `POST /auth/register`
* **Payload:** Gửi lại chính xác payload của `TC-B01.01`.
* **Kết quả kỳ vọng (Assertions):**
  * HTTP Status: **`409 Conflict`**.
  * JSON Response:
    ```json
    {
      "success": false,
      "error": {
        "code": "EMAIL_EXISTS",
        "message": "Email đã được đăng ký.",
        "fields": {
          "email": "Email đã được đăng ký."
        }
      }
    }
    ```

---

#### 15. TC-B01.03: `POST /auth/login` - Đăng nhập hợp lệ & Cấp Cookie HttpOnly (200)
* **Endpoint:** `POST /auth/login`
* **Headers:** `Content-Type: application/json`
* **Payload:** `{"email": "nam.phan@wealthflow.vn", "password": "User@1234"}`
* **Kết quả kỳ vọng (Assertions):**
  * HTTP Status: **`200 OK`**.
  * Headers chứa 2 chỉ thị Cookie an toàn:
    * `Set-Cookie: wf_access=...; Path=/; HttpOnly; SameSite=Lax`
    * `Set-Cookie: wf_refresh=...; Path=/; HttpOnly; SameSite=Lax`
  * JSON Response trả thông tin profile sạch:
    ```json
    {
      "success": true,
      "data": {
        "user": {
          "id": 2,
          "fullName": "Phan Hoang Nam",
          "email": "nam.phan@wealthflow.vn",
          "role": "USER",
          "baseCurrency": "VND"
        }
      }
    }
    ```

---

#### 16. TC-B01.04: `POST /auth/login` - Đăng nhập thất bại do sai mật khẩu (401)
* **Endpoint:** `POST /auth/login`
* **Payload:** `{"email": "nam.phan@wealthflow.vn", "password": "WrongPassword@999"}`
* **Kết quả kỳ vọng (Assertions):**
  * HTTP Status: **`401 Unauthorized`**.
  * JSON Response:
    ```json
    {
      "success": false,
      "error": {
        "code": "INVALID_CREDENTIALS",
        "message": "Email hoặc mật khẩu không chính xác."
      }
    }
    ```
  * Header TUYỆT ĐỐI KHÔNG chứa Set-Cookie phiên đăng nhập.

---

#### 17. TC-B01.05: `GET /auth/me` - Xác thực token phiên & Không lộ secret (200)
* **Endpoint:** `GET /auth/me`
* **Headers:** Gửi kèm Cookie `wf_access` thu được từ `TC-B01.03`.
* **Kết quả kỳ vọng (Assertions):**
  * HTTP Status: **`200 OK`**.
  * JSON Response: `{ "success": true, "data": { "user": { "id": 2, "fullName": "Phan Hoang Nam", "email": "nam.phan@wealthflow.vn", "role": "USER", "baseCurrency": "VND" } } }`.
  * Không bao giờ xuất hiện các trường `password_hash`, `refresh_hash`, `token_version`.

---

#### 18. TC-B01.06: `POST /auth/logout` - Đăng xuất và xóa Cookie trình duyệt (200)
* **Endpoint:** `POST /auth/logout`
* **Headers:** Gửi kèm Cookie phiên hiện hành.
* **Kết quả kỳ vọng (Assertions):**
  * HTTP Status: **`200 OK`**.
  * Header trả về lệnh xóa Cookie: `Set-Cookie: wf_access=; Max-Age=0` và `Set-Cookie: wf_refresh=; Max-Age=0`.
  * Session trong bảng `auth_sessions` bị xóa hoặc thu hồi.
  * Gửi lại `GET /auth/me` ngay sau đó $\rightarrow$ bị từ chối `401 Unauthorized`.

---

### TẦNG B (UI): BOUNDARY FRONTEND FORM & USER INTERACTION (4 Kịch Bản)

#### 19. TC-UI01.01: Kiểm tra Validation trực tiếp trên Form Đăng ký (`index.html`)
* **Màn hình:** Modal Đăng ký trên Landing Page ([source/index.html](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/index.html)).
* **Thao tác:**
  1. Mở Modal Đăng ký.
  2. Nhập Email sai cú pháp: `"user@invalid"`.
  3. Để trống Họ tên.
  4. Nhập Mật khẩu ngắn: `"123"`.
  5. Bấm nút "Đăng ký".
* **Kết quả kỳ vọng (Assertions):**
  * Form không reload trình duyệt.
  * Client ngăn chặn gửi network request lên backend.
  * Các ô nhập liệu bị viền đỏ (`border-color: var(--danger)`).
  * Hiển thị thông báo tiếng Việt dưới từng ô: "Email không hợp lệ", "Vui lòng nhập họ tên", "Mật khẩu phải từ 8 ký tự".

---

#### 20. TC-UI01.02: Trạng thái Button Loading & Chống Double-Click khi submit
* **Màn hình:** Modal Đăng nhập / Đăng ký.
* **Thao tác:**
  1. Nhập thông tin đăng nhập hợp lệ.
  2. Nhấp chuột liên tục 3 lần thật nhanh vào nút "Đăng nhập".
* **Kết quả kỳ vọng (Assertions):**
  * Ngay cú click đầu tiên, nút chuyển sang `disabled = true`.
  * Nút hiển thị text "Đang xử lý..." kèm biểu tượng spinner loading.
  * Bảng điều khiển Network chỉ ghi nhận duy nhất **1 request HTTP POST** được gửi đi.

---

#### 21. TC-UI01.03: Hiển thị Toast thông báo lỗi & Giữ nguyên email khi login sai
* **Màn hình:** Modal Đăng nhập.
* **Thao tác:**
  1. Nhập Email: `"user1@gmail.com"`.
  2. Nhập Mật khẩu sai: `"WrongPassword123"`.
  3. Nhấn "Đăng nhập".
* **Kết quả kỳ vọng (Assertions):**
  * Server trả 401 $\rightarrow$ Client hiển thị Toast màu đỏ: "Email hoặc mật khẩu không chính xác."
  * Ô nhập mật khẩu tự động xóa trắng và được focus để gõ lại.
  * Ô nhập Email vẫn được giữ nguyên `"user1@gmail.com"`, không bắt người dùng nhập lại.

---

#### 22. TC-UI01.04: Luồng chuyển hướng (Redirect Flow) sau khi Đăng nhập thành công
* **Màn hình:** Modal Đăng nhập trên Landing Page.
* **Thao tác:**
  1. Nhập tài khoản hợp lệ: `user1@gmail.com` / `User1234`.
  2. Nhấn "Đăng nhập".
* **Kết quả kỳ vọng (Assertions):**
  * Hiển thị Toast thông báo màu xanh: "Đăng nhập thành công!".
  * Sau $500ms$, modal đóng lại.
  * Trình duyệt chuyển hướng sang trang ứng dụng chính [app.html](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/app.html).
  * Tên người dùng `user1` hiển thị trên thanh điều hướng đầu trang.
