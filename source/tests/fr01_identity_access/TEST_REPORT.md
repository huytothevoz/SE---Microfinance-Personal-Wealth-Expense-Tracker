# BÁO CÁO KẾT QUẢ KIỂM THỬ TỰ ĐỘNG (TEST EXECUTION REPORT)
## Phân Hệ: FR01 - Định Danh, Xác Thực & Quản Lý Phiên (Identity & Access)
## Kiến Trúc: Đa Tầng Theo Thiết Kế BCE+D | Chuẩn UP/RUP Pha Construction

---

## I. THÔNG TIN CHUNG VỀ ĐỢT THỰC THI (EXECUTION OVERVIEW)

* **Phân hệ kiểm thử:** `FR01` - Định danh, xác thực và quản lý phiên truy cập (Identity, Authentication & Session Management).
* **Môi trường kiểm thử:**
  * **Hệ điều hành:** Windows (x86_64).
  * **Ngôn ngữ thực thi test script:** Python 3.13.0 (`pytest` v9.0.3, `pluggy` v1.6.0).
  * **Hạt nhân Backend / SUT:** PHP 8.2.12 (CLI), PDO SQLite In-Memory Database Compatibility Layer.
  * **Bộ mã nguồn kiểm thử:** [test_fr01_suite.py](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr01_identity_access/test_fr01_suite.py), [Thư mục php/ (18 test script PHP độc lập)](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr01_identity_access/php).
* **Tài liệu đặc tả kịch bản:** [TEST_SCENARIOS.md](file:///d:/record_by_me/tdtu/Programming_Engineering/Finalterm/SE---Microfinance-Personal-Wealth-Expense-Tracker/source/tests/fr01_identity_access/TEST_SCENARIOS.md).
* **Thời điểm thực thi:** 22/10/2026.
* **Tổng số kịch bản kiểm thử:** **22 / 22 Test Cases**.
* **Kết quả chung cuộc:** **22 PASSED (100% ĐẠT)**.
* **Tổng thời gian thực thi toàn bộ suite:** **10.44 giây**.

---

## II. BẢNG TỔNG HỢP KẾT QUẢ THỰC THI 22 KỊCH BẢN (EXECUTION MATRIX)

| STT | Mã Kịch Bản | Tầng Kiến Trúc | Tên Kịch Bản Kiểm Thử | Trọng Số | Kết Quả | Thời Gian | Ghi Chú Xác Minh |
| :---: | :---: | :---: | :--- | :---: | :---: | :---: | :--- |
| 1 | **TC-D01.01** | **Data (D)** | Khởi tạo bản ghi người dùng qua `UserDAO::createUser` | P1 | **PASSED** | 0.21s | ID $>0$, role='USER', status='ACTIVE' |
| 2 | **TC-D01.02** | **Data (D)** | Ràng buộc duy nhất Email (Unique Constraint) trên CSDL | P1 | **PASSED** | 0.18s | Bắt lỗi SQLSTATE `23000` (UNIQUE failed) |
| 3 | **TC-D01.03** | **Data (D)** | Tự động sao chép danh mục mẫu qua `copyDefaultCategories` | P1 | **PASSED** | 0.19s | Sao chép đủ 12 danh mục (4 Thu, 8 Chi) |
| 4 | **TC-D01.04** | **Data (D)** | Vòng đời Session trong bảng `auth_sessions` & lọc hết hạn | P1 | **PASSED** | 0.20s | Session sống tìm thấy, session quá hạn bị lọc bỏ |
| 5 | **TC-D01.05** | **Data (D)** | Tính nguyên tử của Transaction (Atomic Rollback khi lỗi) | P1 | **PASSED** | 0.19s | Bản ghi bị xóa sạch sau khi `rollBack()` |
| 6 | **TC-C01.01** | **Control (C)** | Chuẩn hóa dữ liệu đầu vào (Trim họ tên, Lowercase email) | P2 | **PASSED** | 0.35s | Họ tên đã trim khoảng trắng, email về chữ thường |
| 7 | **TC-C01.02** | **Control (C)** | Thẩm định chính sách độ mạnh mật khẩu theo NFR02 | P1 | **PASSED** | 0.38s | 4/4 trường hợp yếu đều bị từ chối với HTTP 422 |
| 8 | **TC-C01.03** | **Control (C)** | Giải thuật băm mật khẩu Bcrypt với Chi phí Cost 12 | P1 | **PASSED** | 0.42s | Hash prefix `$2y$12$`, length 60, verify chính xác |
| 9 | **TC-C01.04** | **Control (C)** | Chống tấn công Brute-force qua Rate Limiting (HTTP 429) | P1 | **PASSED** | 3.25s | Lần thử 1..10 trả 401, lần thử 11..12 trả HTTP 429 |
| 10 | **TC-C01.05** | **Control (C)** | Chống rò rỉ người dùng (Anti User Enumeration) | P2 | **PASSED** | 0.85s | Cùng mã lỗi 401, thông điệp giống nhau, $|\Delta t| < 50ms$ |
| 11 | **TC-C01.06** | **Control (C)** | Cơ chế thu hồi phiên khi Đổi mật khẩu | P1 | **PASSED** | 0.52s | Session cũ bị hủy bỏ, đăng nhập pass mới thành công |
| 12 | **TC-C01.07** | **Control (C)** | Vòng đời & xác thực mã OTP Quên mật khẩu (10 phút) | P1 | **PASSED** | 0.95s | OTP 6 số, TTL 600s, đổi pass OK, chặn Replay (400) |
| 13 | **TC-B01.01** | **Boundary (API)** | `POST /auth/register` - Đăng ký tài khoản thành công (201) | P1 | **PASSED** | 0.32s | HTTP 201 Created, cấu trúc có `requestId` |
| 14 | **TC-B01.02** | **Boundary (API)** | `POST /auth/register` - Xử lý trùng lặp Email (409 Conflict) | P1 | **PASSED** | 0.45s | HTTP 409 Conflict, code `EMAIL_EXISTS` |
| 15 | **TC-B01.03** | **Boundary (API)** | `POST /auth/login` - Đăng nhập hợp lệ & Trả hồ sơ sạch (200) | P1 | **PASSED** | 0.48s | HTTP 200, profile sạch, tuyệt đối không lộ hash |
| 16 | **TC-B01.04** | **Boundary (API)** | `POST /auth/login` - Đăng nhập sai mật khẩu (HTTP 401) | P1 | **PASSED** | 0.35s | HTTP 401, code `INVALID_CREDENTIALS` |
| 17 | **TC-B01.05** | **Boundary (API)** | `GET /auth/me` - Bảo vệ trường nhạy cảm, không rò rỉ secret | P1 | **PASSED** | 0.40s | Chỉ trả các trường an toàn (`id, fullName, email, ...`) |
| 18 | **TC-B01.06** | **Boundary (API)** | `POST /auth/logout` - Thu hồi session trên máy chủ & Xóa session | P1 | **PASSED** | 0.46s | Sessions giảm từ $1 \rightarrow 0$, xóa token cookie |
| 19 | **TC-UI01.01** | **Boundary (UI)** | Kiểm tra Validation trực tiếp trên Form Đăng ký | P2 | **PASSED** | 0.04s | HTML5 attributes (`required`, `email`, `maxlength="150"`) |
| 20 | **TC-UI01.02** | **Boundary (UI)** | Trạng thái Button Loading & Chống Double-Click khi submit | P2 | **PASSED** | 0.03s | `submit.disabled = true;`, khóa tab, chống đóng modal |
| 21 | **TC-UI01.03** | **Boundary (UI)** | Toast thông báo lỗi & Giữ nguyên email khi login sai | P2 | **PASSED** | 0.04s | `#auth-feedback` aria-live='polite', không reset form |
| 22 | **TC-UI01.04** | **Boundary (UI)** | Luồng chuyển hướng (Redirect Flow) sau khi Đăng nhập | P1 | **PASSED** | 0.03s | `window.location.assign('app.html')`, tệp app.html tồn tại |

---

## III. PHÂN TÍCH KẾT QUẢ THEO TỪNG TẦNG KIẾN TRÚC BCE+D

### 1. Tầng D: Data Access & Database Queries (5/5 PASSED - 100%)
* **Bảo vệ SQL Injection:** `UserDAO` sử dụng 100% Prepared Statements (`$stmt->prepare(...)`) với các tham số ràng buộc chuẩn (`?`), không thực hiện nối chuỗi SQL thủ công.
* **Ràng buộc toàn vẹn CSDL:**
  * Đã kiểm chứng mức engine CSDL bắt buộc tính duy nhất trên trường `email` qua ngoại lệ `23000` (`UNIQUE constraint failed: users.email`), ngăn chặn hoàn toàn hiện tượng tạo hai người dùng cùng một email khi có concurrency.
  * Tự động khởi tạo dữ liệu khởi đầu: Khi người dùng đăng ký thành công, hàm `copyDefaultCategories()` nhân bản chính xác **12 danh mục mẫu** (`Lương`, `Ăn uống`, `Đi lại`,...) vào bảng `categories` thuộc sở hữu của người dùng mới.
* **Quản lý phiên bền vững (Session Persistence):**
  * Bảng `auth_sessions` lưu trữ khóa phiên ngẫu nhiên 64 ký tự và băm SHA-256 của Refresh Token.
  * Truy vấn kiểm tra phiên `findActiveSession()` lọc bỏ hoàn toàn các phiên có `expires_at <= UTC_TIMESTAMP()`.
* **Tính nguyên tử của Giao dịch (ACID Atomicity):** Khi xảy ra lỗi hoặc ngoại lệ trong tiến trình đăng ký, thao tác `rollBack()` của PDO hoàn tác toàn bộ bản ghi người dùng và các thực thể liên quan, không để lại rác dữ liệu.

### 2. Tầng C: Control & Business Logic (7/7 PASSED - 100%)
* **Chuẩn hóa dữ liệu (Input Normalization):**
  * Họ và tên được cắt tỉa khoảng trắng dư thừa ở hai đầu (`trim`).
  * Địa chỉ email được chuyển hóa toàn bộ về chữ thường (`strtolower`), đảm bảo tính nhất quán định danh người dùng.
* **Chính sách độ mạnh mật khẩu (NFR02):**
  * Kiểm tra đầy đủ 4 tiêu chí: Độ dài tối thiểu 8 ký tự, tối đa 72 byte (giới hạn an toàn của thuật toán Bcrypt), có ít nhất một chữ in hoa, một chữ in thường và một chữ số.
  * Khối nghiệp vụ ném ra `ApiException('VALIDATION_ERROR', ..., 422)` với danh sách lỗi chi tiết theo từng trường (`fields`).
* **An toàn giải thuật băm (Password Hashing Security):**
  * Sử dụng giải thuật `PASSWORD_BCRYPT` với chi phí tính toán `cost = 12`.
  * Chuỗi hash luôn bắt đầu bằng `$2y$12$` với độ dài cố định 60 ký tự, có khả năng chống lại tấn công Rainbow Table và GPU Cracking mạnh mẽ.
* **Cơ chế chống Brute-force & DoS (Rate Limiting):**
  * Bảng `auth_rate_limits` ghi nhận số lần thử đăng nhập sai theo bucket IP và Email trong cửa sổ thời gian 15 phút (900 giây).
  * Đến lần thử thứ 11, hệ thống từ chối ngay lập tức với mã lỗi HTTP **429 Too Many Requests** và gửi kèm tiêu đề HTTP `Retry-After`.
* **Chống rò rỉ sự tồn tại của người dùng (Anti-User Enumeration):**
  * Khi đăng nhập với email chưa từng đăng ký và khi đăng nhập với email có thật nhưng sai mật khẩu, hệ thống đều trả về cùng một mã HTTP **401 Unauthorized**, cùng mã lỗi `"INVALID_CREDENTIALS"`, cùng nội dung thông điệp `"Email hoặc mật khẩu không chính xác."`.
  * Sử dụng Dummy Bcrypt Hash để thời gian tính toán băm mật khẩu cho cả hai trường hợp chênh lệch không đáng kể ($|\Delta t| = 0.0034s \ll 0.05s$), vô hiệu hóa hoàn toàn kỹ thuật tấn công đo thời gian (Timing Attacks).
* **Vòng đời mã OTP Quên mật khẩu:**
  * Mã OTP 6 chữ số ngẫu nhiên chỉ được lưu trữ dưới dạng băm HMAC-SHA256 với bí mật máy chủ `APP_KEY`.
  * Thời gian sống của OTP được định cấu hình chính xác **600 giây (10 phút)**.
  * Sau khi đặt lại mật khẩu thành công bằng mã OTP, bản ghi trong `password_reset_tokens` bị xóa ngay lập tức. Mọi nỗ lực tái sử dụng cùng mã OTP đó đều bị từ chối với HTTP **400 INVALID_RESET_OTP**.

### 3. Tầng B (API): Boundary Controller & HTTP Contract (6/6 PASSED - 100%)
* **Định dạng phản hồi nhất quán (Standard Envelope):** Mọi endpoint API đều bao bọc dữ liệu trong cấu trúc chuẩn:
  ```json
  {
    "success": true,
    "data": { ... },
    "error": null,
    "requestId": "f0dd6c97f214768d"
  }
  ```
* **Mã trạng thái HTTP ngữ nghĩa (Semantic Status Codes):**
  * `POST /auth/register` thành công trả về **201 Created**.
  * `POST /auth/register` trùng lặp email trả về **409 Conflict**.
  * `POST /auth/login` thông tin không hợp lệ trả về **401 Unauthorized**.
  * Yêu cầu vi phạm định dạng dữ liệu trả về **422 Unprocessable Entity**.
* **Nguyên tắc không rò rỉ dữ liệu nhạy cảm (Zero-Leakage Contract):**
  * Phản hồi của `login` và `me` tuyệt đối không chứa trường `password_hash`, `refresh_hash` hay `token_version`.
  * Chỉ các trường công khai hợp lệ được tuần tự hóa: `id`, `fullName`, `email`, `baseCurrency`, `role`.
* **Thu hồi phiên đăng xuất (Logout Revocation):**
  * Gọi `POST /auth/logout` xóa bản ghi phiên tương ứng trong bảng `auth_sessions`.
  * Xóa bỏ các cookie `wf_access` và `wf_refresh` trên trình duyệt qua cờ hết hạn trong quá khứ.

### 4. Tầng B (UI): Boundary Frontend Contracts (4/4 PASSED - 100%)
* **Thẩm định dữ liệu phía Client (Form Validation):**
  * Thẻ form `#auth-form` khai báo đầy đủ thuộc tính HTML5: `type="email"`, `required`, `maxlength="150"`.
  * Phía JavaScript kiểm tra xác nhận trùng khớp mật khẩu (`password === confirmation`) qua API `setCustomValidity()`.
* **Phòng thủ Double-Click & Trạng thái Loading:**
  * Ngay khi người dùng nhấn submit, nút submit bị vô hiệu hóa (`submit.disabled = true;`) và các tab điều hướng bị khóa để ngăn gửi đồng thời nhiều request.
  * Cửa sổ Modal chặn sự kiện đóng (`cancel` và `close`) khi request đang in-flight.
  * Trạng thái submit được khôi phục an toàn trong khối `finally`.
* **Trải nghiệm người dùng (UX) & Khả năng tiếp cận (Accessibility):**
  * Vùng phản hồi `#auth-feedback` được trang bị `role="status"` và `aria-live="polite"` để phần mềm đọc màn hình thông báo rõ ràng cho người khiếm thị.
  * Khi đăng nhập thất bại, hệ thống hiển thị thông báo lỗi chi tiết và **giữ nguyên giá trị email đã nhập** trong ô `#auth-email`, người dùng chỉ cần nhập lại mật khẩu mà không phải gõ lại email từ đầu.
* **Luồng điều hướng chuyển trang (Redirect Flow):**
  * Khi đăng nhập thành công, form thực hiện xóa trắng và chuyển hướng mượt mà đến trang làm việc chính `window.location.assign('app.html')`.

---

## IV. BẢNG GHI NHẬN VẤN ĐỀ & CẢI TIẾN TRONG QUÁ TRÌNH KIỂM THỬ (DEFECT & OBSERVATION LOG)

| STT | Vấn Đề Ghi Nhận | Tầng Ảnh Hưởng | Nguyên Nhân Gốc | Giải Pháp & Cải Tiến Đã Thực Hiện | Trạng Thái |
| :---: | :--- | :---: | :--- | :--- | :---: |
| 1 | Lỗi thiếu bảng `auth_rate_limits`, `auth_sessions`, `password_reset_tokens` trong SQLite memory | Data / DB | Hàm `Database::initSqliteSchema` chỉ tạo các bảng ví và giao dịch, chưa bao gồm các bảng bảo mật auth. | Bổ sung schema khởi tạo đầy đủ các bảng auth vào Harness bridge `Fr01TestHarness`. | **ĐÃ GIẢI QUYẾT** |
| 2 | Cú pháp `ON DUPLICATE KEY UPDATE` và `FOR UPDATE` không tương thích SQLite | Data / SQL | Backend sử dụng cú pháp đặc thù của MySQL cho Rate Limiting và Pesimistic Locking. | Xây dựng lớp `SqliteCompatPDO` tự động chuyển đổi sang `ON CONFLICT` và strip `FOR UPDATE` trong môi trường test độc lập. | **ĐÃ GIẢI QUYẾT** |
| 3 | Lỗi ném ra `MAIL_PREVIEW_DIRECTORY_NOT_WRITABLE` | Control / Service | Môi trường test local thiếu thư mục `source/backend/storage` để lưu preview email chào mừng. | Khởi tạo thư mục `source/backend/storage` trên ổ đĩa để cơ chế ghi email log hoạt động ổn định. | **ĐÃ GIẢI QUYẾT** |
| 4 | Thuộc tính lỗi `ApiException::$errors` không tồn tại | Control / Test | Trong mã nguồn backend, trường lỗi chi tiết có tên là `$fields` thay vì `$errors`. | Cập nhật lại Harness truy xuất đúng thuộc tính `$e->fields`. | **ĐÃ GIẢI QUYẾT** |
| 5 | Tầng B API trong môi trường CLI không đọc được body từ `php://input` | Boundary / API | Lớp `Request` trong PHP CLI mặc định không có luồng `php://input` như Web Server. | Xây dựng hàm `createTestRequest()` nạp dữ liệu thông qua mảng toàn cục `$_POST` và `$_SERVER`. | **ĐÃ GIẢI QUYẾT** |

---

## V. KẾT LUẬN & ĐÁNH GIÁ SẴN SÀNG (SIGN-OFF EVALUATION)

1. **Mức độ hoàn thiện chức năng:** Phân hệ `FR01 - Identity & Access` đã hoàn thành **100%** các yêu cầu chức năng nghiệp vụ, bao gồm Đăng ký, Đăng nhập, Đổi mật khẩu, Quên mật khẩu qua OTP và Đăng xuất phiên.
2. **Tiêu chuẩn chất lượng (Quality Standards):**
   * Tuân thủ 100% mô hình phân tách trách nhiệm BCE+D (Boundary, Control, Entity/Data).
   * Đạt toàn bộ các yêu cầu phi chức năng về bảo mật: Bcrypt Cost 12 (`NFR02`), Rate Limiting chống Brute-force, Anti-User Enumeration, kiểm soát phiên HttpOnly Cookie (`NFR06`).
   * Bộ kiểm thử tự động hóa bằng Python (`pytest`) chạy độc lập, tốc độ cao (10.44s cho 22 kịch bản), dễ dàng tích hợp vào quy trình CI/CD.
3. **Đánh giá bàn giao:** **ĐẠT TIÊU CHUẨN NGHIỆM THU (ACCEPTED FOR SIGN-OFF)**. Sẵn sàng tích hợp sang phân hệ tiếp theo: **FR02 (Quản Lý Ví & Tài Khoản Thanh Toán)**.
