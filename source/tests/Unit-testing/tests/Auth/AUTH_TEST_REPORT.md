# BÁO CÁO MA TRẬN KIỂM THỬ ĐƠN VỊ (UNIT TEST REPORT) - PHÂN HỆ AUTH

> **Phân hệ:** Xác thực & Quản lý Người dùng (Authentication & User Management - FR01)  
> **Thư mục kiểm thử:** `source/tests/Unit-testing/tests/Auth/`  
> **Môi trường:** PHP 8.2 (CLI) | SQLite In-Memory / Isolated Test DB  
> **Tổng số Test Cases:** **29/29 PASSED (100%)**  
> **Tổng thời gian thực thi:** **~4.91 giây** *(bao gồm chi phí tính toán Bcrypt hash)*  
> **Ngày thực hiện:** 05/11/2026  

---

## 1. Ma trận kiểm thử: `AuthServiceTest` (22 Test Cases)

| STT | Tên Test (Method) | Nội dung kiểm thử | Kỳ vọng (Expected) | Kết quả trả về | Run Time |
|:---:|:---|:---|:---|:---:|:---:|
| 1 | `testRegisterSuccessAndValidation` | Đăng ký tài khoản hợp lệ; kiểm tra validation khi thiếu tên/email | Đăng ký thành công trả về User ID & Email; thiếu thông tin ném 422 `VALIDATION_FAILED` | **PASS** | 202.87 ms |
| 2 | `testLoginAuthenticateMeRefreshAndLogout` | Chu trình hoàn chỉnh: Đăng nhập -> Xác thực JWT -> Lấy `/me` -> Refresh Token -> Đăng xuất | Cấp JWT hợp lệ; refresh sinh token mới; logout thu hồi session thành công | **PASS** | 402.01 ms |
| 3 | `testLoginRejectsUnknownAndLockedAccounts` | Đăng nhập với tài khoản không tồn tại hoặc tài khoản bị khoá (`LOCKED`) | Từ chối đăng nhập: 401 `UNAUTHENTICATED` hoặc 403 `ACCOUNT_LOCKED` | **PASS** | 564.86 ms |
| 4 | `testProtectedMethodsRejectMissingAuthentication` | Gọi các API yêu cầu xác thực (`me`, `logout`, `changePassword`, `closeAccount`) khi thiếu Bearer token | Từ chối truy cập: ném 401 `UNAUTHENTICATED` | **PASS** | 0.07 ms |
| 5 | `testPasswordResetValidationAndUnavailableMail` | Yêu cầu reset mật khẩu với email không hợp lệ hoặc khi server mail gặp lỗi | Ném 422 khi email sai định dạng; xử lý ngoại lệ mail an toàn không gây 500 | **PASS** | 0.52 ms |
| 6 | `testPasswordRejectsMissingComplexity` | Đăng ký với mật khẩu yếu (thiếu chữ hoa, chữ thường, số, ký tự đặc biệt hoặc < 8 ký tự) | Từ chối tạo tài khoản: ném 422 `WEAK_PASSWORD` | **PASS** | 0.22 ms |
| 7 | `testPasswordRejectsNullByteAndOver72Bytes` | Đăng ký với mật khẩu chứa ký tự null byte (`\0`) hoặc vượt quá 72 bytes (giới hạn Bcrypt) | Từ chối: ném 422 `PASSWORD_INVALID` / `PASSWORD_TOO_LONG` | **PASS** | 0.33 ms |
| 8 | `testRegisterRejectsDuplicateEmail` | Đăng ký tài khoản với địa chỉ email đã tồn tại trong cơ sở dữ liệu | Từ chối trùng lặp: ném 409 `EMAIL_ALREADY_EXISTS` | **PASS** | 386.92 ms |
| 9 | `testRequestPasswordResetPreventsUserEnumeration` | Yêu cầu reset mật khẩu với email không tồn tại trong hệ thống | Trả về 200 kèm phản hồi chung (ngăn chặn tấn công User Enumeration) | **PASS** | 2.01 ms |
| 10 | `testChangePasswordSuccessAndRevokesOldSessions` | Đổi mật khẩu thành công và tự động thu hồi các phiên đăng nhập cũ | Cập nhật hash mới; toàn bộ session token cũ bị thu hồi ngay lập tức | **PASS** | 787.24 ms |
| 11 | `testChangePasswordRejectsWrongCurrentPassword` | Đổi mật khẩu nhưng nhập sai mật khẩu hiện tại | Từ chối đổi mật khẩu: ném 401 `CURRENT_PASSWORD_INCORRECT` | **PASS** | 409.94 ms |
| 12 | `testChangePasswordRejectsMismatchedConfirmation` | Đổi mật khẩu với mật khẩu mới và mật khẩu xác nhận không trùng khớp | Từ chối: ném 422 `PASSWORD_CONFIRMATION_MISMATCH` | **PASS** | 217.57 ms |
| 13 | `testCloseAccountSuccess` | Đóng tài khoản người dùng khi nhập đúng mật khẩu và cụm từ `DELETE MY ACCOUNT` | Chuyển user sang `CLOSED`, thu hồi mọi session, ghi log audit `CLOSE_ACCOUNT` | **PASS** | 461.22 ms |
| 14 | `testCloseAccountRejectsWrongConfirmationPhrase` | Đóng tài khoản nhưng nhập sai cụm từ xác nhận | Từ chối: ném 422 `CONFIRMATION_PHRASE_MISMATCH`, tài khoản vẫn nguyên vẹn | **PASS** | 0.22 ms |
| 15 | `testCloseAccountRejectsWrongPassword` | Đóng tài khoản nhưng nhập sai mật khẩu xác thực | Từ chối: ném 401 `PASSWORD_INCORRECT` | **PASS** | 458.92 ms |
| 16 | `testCloseAccountRejectsAlreadyClosedAccount` | Thực hiện thao tác đóng trên một tài khoản đã ở trạng thái `CLOSED` | Từ chối thao tác dư thừa: ném 409 `ACCOUNT_ALREADY_CLOSED` | **PASS** | 0.40 ms |
| 17 | `testLoginDifferentiatesClosedVsLockedAccounts` | Kiểm tra phân biệt mã lỗi và thông điệp khi đăng nhập vào tài khoản `CLOSED` vs `LOCKED` | Trả về thông điệp chính xác: `ACCOUNT_CLOSED` (403) và `ACCOUNT_LOCKED` (403) | **PASS** | 703.01 ms |
| 18 | `testResetPasswordSuccessWithValidOtp` | Đặt lại mật khẩu mới khi cung cấp mã OTP hợp lệ và còn hiệu lực | Cập nhật mật khẩu mới, thu hồi OTP đã dùng và huỷ các phiên cũ | **PASS** | 701.77 ms |
| 19 | `testResetPasswordRejectsExpiredOtp` | Đặt lại mật khẩu với mã OTP đã hết hạn hiệu lực (> 15 phút) | Từ chối cập nhật: ném 422 `OTP_EXPIRED` | **PASS** | 0.28 ms |
| 20 | `testResetPasswordLocksAfter5FailedAttempts` | Nhập sai mã OTP liên tiếp 5 lần khi thực hiện reset mật khẩu | Ném 429/403 `TOO_MANY_FAILED_ATTEMPTS`, vô hiệu OTP và khoá tạm thời | **PASS** | 0.30 ms |
| 21 | `testLoginRateLimitingTriggered` | Gửi request đăng nhập sai mật khẩu liên tiếp quá 5 lần | Kích hoạt rate limiting: trả về HTTP 429 `TOO_MANY_ATTEMPTS` | **PASS** | 578.58 ms |
| 22 | `testAuthenticateRejectsTamperedOrRevokedToken` | Xác thực request với token JWT bị chỉnh sửa chữ ký hoặc session đã bị thu hồi | Từ chối truy cập: ném 401 `UNAUTHENTICATED` | **PASS** | 0.22 ms |

---

## 2. Ma trận kiểm thử: `UserDAOTest` (7 Test Cases)

| STT | Tên Test (Method) | Nội dung kiểm thử | Kỳ vọng (Expected) | Kết quả trả về | Run Time |
|:---:|:---|:---|:---|:---:|:---:|
| 23 | `testGetPdoCreateFindAndListUsers` | Khởi tạo PDO, tạo người dùng mới, tìm kiếm theo ID/Email và lấy danh sách | Dữ liệu lưu trữ chính xác, tìm thấy đúng user và bảo toàn các thuộc tính | **PASS** | 0.15 ms |
| 24 | `testPasswordStatusAndCloseAccountMutations` | Cập nhật mật khẩu băm, chuyển đổi trạng thái user (`ACTIVE` -> `LOCKED` -> `CLOSED`) | Trạng thái và hash mật khẩu được lưu trữ chính xác theo từng bước biến đổi | **PASS** | 0.14 ms |
| 25 | `testCopyDefaultsAndQueueRegistrationEmail` | Khởi tạo các danh mục tài chính mặc định cho user mới và xếp hàng email chào mừng | Sinh đầy đủ danh mục thu/chi ban đầu và tạo bản ghi queue email thành công | **PASS** | 0.13 ms |
| 26 | `testSessionLifecycleAndExpiry` | Vòng đời session token: tạo mới, cập nhật `last_seen_at`, và thu hồi (`revoked`) | Session lưu trữ đúng token, cập nhật thời gian truy cập và bị thu hồi thành công | **PASS** | 0.35 ms |
| 27 | `testSecurityAuditIsNormalized` | Ghi nhận sự kiện kiểm toán bảo mật (Security Audit Log) với actor, action, IP, metadata JSON | Dữ liệu audit log được chuẩn hoá và lưu thành công trong bảng `security_audit_logs` | **PASS** | 0.05 ms |
| 28 | `testFindActiveSessionReturnsNullWhenExpired` | Truy vấn tìm session đã vượt quá thời gian hết hạn (`expires_at`) | Trả về `null`, từ chối nhận diện session đã hết hạn | **PASS** | 0.09 ms |
| 29 | `testListUsersPaginationAndRole` | Phân trang danh sách tài khoản và lọc theo vai trò (`ADMIN`, `USER`) | Trả về đúng số lượng trang (limit, offset) và lọc chính xác theo role | **PASS** | 0.14 ms |

---

## 3. Tổng kết & Đánh giá

- **Tỷ lệ vượt qua (Pass Rate):** **100% (29/29 tests)**.
- **Tính toàn vẹn dữ liệu & bảo mật:**
  - Ngăn ngừa hoàn toàn brute-force thông qua Rate Limiting (5 lần thử).
  - Ngăn ngừa rò rỉ danh tính người dùng (User Enumeration Defense).
  - Thu hồi toàn bộ session đang hoạt động khi đổi mật khẩu hoặc đóng tài khoản.
  - Bảo đảm phân quyền nghiêm ngặt giữa `ACTIVE`, `LOCKED` và `CLOSED`.
- **Hiệu năng thực thi:** Các test nghiệp vụ thuần DAO/Validation chạy dưới **1 ms**, các test liên quan Bcrypt hash chạy trung bình **200 - 700 ms** (đạt chuẩn an toàn tính toán mật mã).
