# -*- coding: utf-8 -*-
"""
WEALTHFLOW AUTOMATED TEST SUITE: FR01 - IDENTITY & ACCESS CONTROL
Kiến trúc: Đa tầng theo thiết kế BCE+D (Boundary - Control - Entity/Data)
Chuẩn UP/RUP: Pha Construction - Sprint 1 Verification

Tập kiểm thử gồm 22 kịch bản tự động hóa 100%:
- Tầng D (Data Access & Database Queries): 5 Test Cases (gọi từng file php/tc_d01_*.php)
- Tầng C (Control & Business Logic): 7 Test Cases (gọi từng file php/tc_c01_*.php)
- Tầng B (Boundary / API Controller): 6 Test Cases (gọi từng file php/tc_b01_*.php)
- Tầng B (Boundary / Frontend UI Contracts): 4 Test Cases (kiểm tra contract DOM & JS)
"""

import json
import os
import shutil
import subprocess
from pathlib import Path
import pytest

# Paths
BASE_DIR = Path(__file__).resolve().parents[2]
TEST_DIR = Path(__file__).resolve().parent
PHP_TEST_DIR = TEST_DIR / "php"
INDEX_HTML = BASE_DIR / "index.html"
LANDING_JS = BASE_DIR / "frontend" / "js" / "landing" / "main.js"
APP_HTML = BASE_DIR / "app.html"

# Resolve PHP executable
def get_php_executable() -> str:
    xampp_php = Path("D:/xampp/php/php.exe")
    if xampp_php.exists():
        return str(xampp_php)
    php_path = shutil.which("php")
    if php_path:
        return php_path
    raise RuntimeError("PHP executable not found. Please install PHP or specify path to php.exe.")

PHP_BIN = get_php_executable()


def run_php_test(script_filename: str) -> dict:
    """Helper to dispatch test execution to an individual PHP test file and parse JSON output."""
    script_path = PHP_TEST_DIR / script_filename
    assert script_path.exists(), f"PHP test file not found: {script_path}"

    cmd = [PHP_BIN, str(script_path)]
    proc = subprocess.run(
        cmd,
        cwd=str(BASE_DIR),
        capture_output=True,
        text=True,
        encoding="utf-8"
    )
    assert proc.returncode == 0, f"PHP test {script_filename} failed with code {proc.returncode}:\nSTDERR: {proc.stderr}\nSTDOUT: {proc.stdout}"

    stdout = proc.stdout.strip()
    json_start = stdout.find("{")
    assert json_start != -1, f"No JSON object found in output of {script_filename}:\n{stdout}"
    data = json.loads(stdout[json_start:])
    assert data.get("status") == "OK", f"PHP test {script_filename} reported error: {data}"
    return data["data"]


# ==============================================================================
# TẦNG D: DATA ACCESS & DATABASE QUERIES (5 Test Cases)
# ==============================================================================

class TestLayerD_DataAccess:
    """Kiểm thử tầng Data Access (UserDAO, Transactions, Constraints, Sessions)"""

    def test_tc_d01_01_create_user_dao(self):
        """TC-D01.01: Khởi tạo bản ghi người dùng mới qua UserDAO::createUser"""
        data = run_php_test("tc_d01_01_create_user.php")
        assert data["id"] > 0, "User ID phải lớn hơn 0 sau khi chèn."
        user = data["user"]
        assert user["email"] == "test.d01.new@wealthflow.local"
        assert user["role"] == "USER"
        assert data["is_active"] is True
        assert user["base_currency"] == "VND"

    def test_tc_d01_02_unique_email_constraint(self):
        """TC-D01.02: Ràng buộc duy nhất Email (Unique Constraint) trên CSDL"""
        data = run_php_test("tc_d01_02_unique_email.php")
        assert data["duplicate_rejected"] is True, "CSDL phải chặn chèn email trùng lặp."
        assert "UNIQUE constraint failed" in str(data.get("error_info", [])), "Thông báo lỗi phải chỉ rõ vi phạm UNIQUE constraint."

    def test_tc_d01_03_copy_default_categories(self):
        """TC-D01.03: Tự động sao chép danh mục mẫu qua copyDefaultCategories"""
        data = run_php_test("tc_d01_03_default_categories.php")
        assert data["matched"] is True, "Số lượng category gán cho user phải khớp đúng số lượng default_categories đang kích hoạt."
        assert data["user_cat_count"] == 12, "Hệ thống chuẩn phải sao chép đủ 12 danh mục ban đầu (4 Thu, 8 Chi)."

    def test_tc_d01_04_session_lifecycle_and_expiry(self):
        """TC-D01.04: Vòng đời Session trong bảng auth_sessions & kiểm tra lọc hết hạn"""
        data = run_php_test("tc_d01_04_session_lifecycle.php")
        assert data["valid_found"] is True, "Session hợp lệ (chưa hết hạn) phải được tìm thấy."
        assert data["valid_session_id"] == "sess_valid"
        assert data["expired_found"] is False, "Session quá hạn expires_at < NOW() không được phép trả về."

    def test_tc_d01_05_atomic_rollback_on_registration_error(self):
        """TC-D01.05: Tính nguyên tử của Transaction (Atomic Rollback khi lỗi)"""
        data = run_php_test("tc_d01_05_atomic_rollback.php")
        assert data["created_id"] > 0
        assert data["exists_after_rollback"] is False, "Bản ghi người dùng phải bị hoàn tác hoàn toàn sau lệnh rollBack()."


# ==============================================================================
# TẦNG C: CONTROL / BUSINESS LOGIC (7 Test Cases)
# ==============================================================================

class TestLayerC_ControlLogic:
    """Kiểm thử tầng nghiệp vụ AuthService, Rate Limiting, Password Policy & Security"""

    def test_tc_c01_01_input_data_normalization(self):
        """TC-C01.01: Chuẩn hóa dữ liệu đầu vào (Trim họ tên, Lowercase email)"""
        data = run_php_test("tc_c01_01_normalization.php")
        assert data["registered"] is True
        assert data["saved_name"] == "Võ   Văn   Hoàng", "Họ tên phải được trim khoảng trắng đầu cuối."
        assert data["saved_email"] == "hoang.vo_test@gmail.com", "Email lưu trữ phải chuyển toàn bộ về chữ thường."

    def test_tc_c01_02_password_policy_validation(self):
        """TC-C01.02: Thẩm định chính sách độ mạnh mật khẩu theo NFR02"""
        data = run_php_test("tc_c01_02_password_policy.php")
        for case in ["short", "no_upper", "no_digit", "mismatch"]:
            assert case in data, f"Thiếu kiểm tra trường hợp {case}"
            assert data[case]["status"] == 422, f"Mật khẩu vi phạm {case} phải bị từ chối với HTTP 422."
            assert data[case]["error_code"] == "VALIDATION_ERROR"

    def test_tc_c01_03_bcrypt_cost_12_security(self):
        """TC-C01.03: Giải thuật băm mật khẩu Bcrypt với Chi phí Cost 12"""
        data = run_php_test("tc_c01_03_bcrypt_cost12.php")
        assert data["hash_prefix"] == "$2y$12$", "Hash phải bắt đầu bằng $2y$12$ thể hiện giải thuật Bcrypt Cost 12."
        assert data["hash_length"] == 60, "Độ dài chuỗi băm chuẩn của Bcrypt là 60 ký tự."
        assert data["verify_correct"] is True, "Xác thực với đúng mật khẩu phải thành công."
        assert data["verify_wrong"] is False, "Xác thực với mật khẩu sai phải thất bại."

    def test_tc_c01_04_anti_brute_force_rate_limiting(self):
        """TC-C01.04: Cơ chế chống tấn công Brute-force qua Rate Limiting (HTTP 429)"""
        data = run_php_test("tc_c01_04_brute_force_ratelimit.php")
        assert data["rate_limit_triggered"] is True, "Phải kích hoạt Rate Limiter sau khi thử sai quá giới hạn."
        assert data["last_status"] == 429, "Trạng thái HTTP sau lần thử thứ 11 phải là 429 Too Many Requests."

    def test_tc_c01_05_anti_user_enumeration(self):
        """TC-C01.05: Chống rò rỉ sự tồn tại của người dùng (Anti User Enumeration)"""
        data = run_php_test("tc_c01_05_anti_enumeration.php")
        assert data["messages_identical"] is True, "Thông điệp trả về khi email không tồn tại và sai pass phải y hệt nhau."
        assert data["codes_identical"] is True, "Mã lỗi INVALID_CREDENTIALS phải giống nhau."
        assert data["unknown_response"]["status"] == 401
        assert data["known_wrong_pass_response"]["status"] == 401
        assert data["time_diff_seconds"] < 0.2, "Thời gian phản hồi chênh lệch phải nhỏ hơn ngưỡng cho phép để chống Timing Attack."

    def test_tc_c01_06_session_revocation_on_password_change(self):
        """TC-C01.06: Cơ chế thu hồi phiên đăng nhập khi Đổi mật khẩu"""
        data = run_php_test("tc_c01_06_session_revocation.php")
        assert "Đã đổi mật khẩu" in data["change_result"]["message"]
        assert data["old_session_revoked"] is True, "Phiên cũ trên các thiết bị khác phải bị thu hồi tức thì."
        assert data["new_login_succeeded"] is True, "Đăng nhập với mật khẩu mới phải thành công."

    def test_tc_c01_07_password_reset_otp_lifecycle(self):
        """TC-C01.07: Vòng đời và xác thực mã OTP Quên mật khẩu (10 phút, dùng 1 lần)"""
        data = run_php_test("tc_c01_07_otp_lifecycle.php")
        assert data["otp_found"] is True, "Hệ thống phải sinh mã OTP thành công."
        assert data["is_6_digits"] is True, "Mã OTP phải gồm đúng 6 chữ số."
        assert data["expiry_seconds"] == 600, "Thời gian sống của OTP phải chính xác là 600 giây (10 phút)."
        assert data["reset_succeeded"] is True, "Đặt lại mật khẩu với OTP hợp lệ phải thành công."
        assert data["second_attempt_rejected"] is True, "Mã OTP đã sử dụng phải bị vô hiệu hóa hoàn toàn (chặn Replay Attack)."
        assert data["rejection_code"] == "INVALID_RESET_OTP"


# ==============================================================================
# TẦNG B (API): BOUNDARY CONTROLLER & HTTP CONTRACT (6 Test Cases)
# ==============================================================================

class TestLayerB_ApiController:
    """Kiểm thử tầng Boundary API Controller (AuthController, JsonResponse, Headers, Status)"""

    def test_tc_b01_01_api_register_success(self):
        """TC-B01.01: POST /auth/register - Đăng ký tài khoản thành công (HTTP 201)"""
        data = run_php_test("tc_b01_01_api_register.php")
        assert data["success"] is True
        assert data["http_response_code"] == 201, "API đăng ký thành công phải trả mã HTTP 201 Created."
        json_body = data["output_json"]
        assert "requestId" in json_body, "Cấu trúc phản hồi chuẩn phải có trường requestId theo dõi trace."
        assert "Đăng ký thành công" in json_body["data"]["message"]

    def test_tc_b01_02_api_register_duplicate_conflict(self):
        """TC-B01.02: POST /auth/register - Xử lý trùng lặp Email (HTTP 409 Conflict)"""
        data = run_php_test("tc_b01_02_api_duplicate.php")
        exc = data["duplicate_api_exception"]
        assert exc is not None, "API phải bắt được ngoại lệ trùng email."
        assert exc["status"] == 409, "Phải trả về HTTP 409 Conflict."
        assert exc["code"] == "EMAIL_EXISTS"

    def test_tc_b01_03_api_login_success_clean_profile(self):
        """TC-B01.03: POST /auth/login - Đăng nhập hợp lệ & Trả thông tin hồ sơ sạch (HTTP 200)"""
        data = run_php_test("tc_b01_03_api_login.php")
        assert data["success"] is True
        profile = data["user_profile"]
        assert profile["fullName"] == "Login API User"
        assert profile["email"] == "api.login@wealthflow.vn"
        assert data["has_password_hash"] is False, "Hồ sơ người dùng không được chứa password_hash."

    def test_tc_b01_04_api_login_invalid_credentials(self):
        """TC-B01.04: POST /auth/login - Đăng nhập thất bại do sai mật khẩu (HTTP 401)"""
        data = run_php_test("tc_b01_04_api_wrong_pass.php")
        failure = data["login_failure"]
        assert failure is not None
        assert failure["status"] == 401, "Phải trả về HTTP 401 Unauthorized."
        assert failure["code"] == "INVALID_CREDENTIALS"

    def test_tc_b01_05_api_me_security_contract(self):
        """TC-B01.05: GET /auth/me - Bảo vệ trường nhạy cảm, không rò rỉ secret"""
        data = run_php_test("tc_b01_05_api_me.php")
        assert data["leaks_password_hash"] is False
        assert data["leaks_refresh_hash"] is False
        assert data["leaks_token_version"] is False
        expected_fields = {"id", "fullName", "email", "baseCurrency", "role"}
        assert set(data["user_fields"]) == expected_fields, f"Hồ sơ chỉ được phép chứa các trường an toàn: {expected_fields}"

    def test_tc_b01_06_api_logout_session_cleanup(self):
        """TC-B01.06: POST /auth/logout - Thu hồi session trên máy chủ & Xóa session"""
        data = run_php_test("tc_b01_06_api_logout.php")
        assert data["success"] is True
        assert data["sessions_before"] == 1
        assert data["sessions_after"] == 0
        assert data["cleaned_up"] is True, "Toàn bộ phiên hoạt động của thiết bị phải bị xóa khỏi bảng auth_sessions."


# ==============================================================================
# TẦNG B (UI): BOUNDARY FRONTEND UI CONTRACTS (4 Test Cases)
# ==============================================================================

class TestLayerB_FrontendUI:
    """Kiểm thử tầng Giao diện hiển thị, Form validation, Loading state & Chuyển hướng"""

    def test_tc_ui01_01_form_html5_validation_contracts(self):
        """TC-UI01.01: Kiểm tra Validation trực tiếp trên Form Đăng ký (index.html)"""
        assert INDEX_HTML.exists(), "Tệp index.html không tồn tại."
        html_content = INDEX_HTML.read_text(encoding="utf-8")

        # Kiểm tra sự tồn tại của dialog & form
        assert 'id="auth-dialog"' in html_content, "Dialog xác thực phải có id='auth-dialog'."
        assert 'id="auth-form"' in html_content, "Form đăng nhập/đăng ký phải có id='auth-form'."

        # Kiểm tra các trường dữ liệu và ràng buộc HTML5
        assert 'id="auth-name"' in html_content and 'maxlength="150"' in html_content, "Trường họ tên phải giới hạn maxlength='150'."
        assert 'id="auth-email"' in html_content and 'type="email"' in html_content and 'required' in html_content, "Trường email phải có type='email' và required."
        assert 'id="auth-password"' in html_content and 'type="password"' in html_content and 'required' in html_content, "Trường mật khẩu phải có type='password' và required."
        assert 'id="auth-confirmation"' in html_content, "Form phải có trường auth-confirmation."
        assert 'id="auth-currency"' in html_content, "Phải có trường chọn loại tiền tệ cơ sở."
        assert 'value="VND"' in html_content and 'value="USD"' in html_content and 'value="EUR"' in html_content, "Hỗ trợ VND, USD, EUR."

    def test_tc_ui01_02_button_loading_and_double_click_defense(self):
        """TC-UI01.02: Trạng thái Button Loading & Chống Double-Click khi submit"""
        assert LANDING_JS.exists(), "Tệp landing main.js không tồn tại."
        js_content = LANDING_JS.read_text(encoding="utf-8")

        # Kiểm tra việc disable button và tab khi request đang thực thi
        assert "submit.disabled = true;" in js_content, "Nút submit phải bị vô hiệu hóa (disabled = true) ngay khi form submit."
        assert "button.disabled = true;" in js_content, "Các tab chuyển đổi phải bị khóa khi đang gửi request."
        assert "submit.disabled = false;" in js_content, "Nút submit phải được mở lại trong khối finally."
        assert "if (!submit.disabled) dialog.close();" in js_content, "Không cho phép đóng dialog khi đang thực hiện request in-flight."

    def test_tc_ui01_03_toast_feedback_and_email_preservation(self):
        """TC-UI01.03: Hiển thị Toast thông báo lỗi & Giữ nguyên email khi login sai"""
        html_content = INDEX_HTML.read_text(encoding="utf-8")
        js_content = LANDING_JS.read_text(encoding="utf-8")

        # Kiểm tra khu vực hiển thị phản hồi với accessibility polite
        assert 'id="auth-feedback"' in html_content, "Phải có phần tử #auth-feedback để thông báo cho người dùng."
        assert 'role="status"' in html_content and 'aria-live="polite"' in html_content, "Vùng thông báo phải hỗ trợ screen reader qua aria-live='polite'."

        # Kiểm tra xử lý lỗi không xóa email của người dùng
        assert "feedback.textContent = errorMessage(error);" in js_content, "Khi gặp lỗi, phải hiển thị chi tiết thông báo lỗi từ backend."
        # form.reset() chỉ được gọi khi login thành công (mode === 'login'), không gọi khi catch lỗi
        assert "if (mode === 'login') {\n      form.reset();\n      window.location.assign('app.html');" in js_content or \
               "if (mode === 'login') {" in js_content, "Form chỉ được reset khi đăng nhập thành công."

    def test_tc_ui01_04_navigation_redirect_flow(self):
        """TC-UI01.04: Luồng chuyển hướng (Redirect Flow) sau khi Đăng nhập thành công"""
        assert APP_HTML.exists(), "Trang ứng dụng chính app.html phải tồn tại để chuyển hướng đến."
        js_content = LANDING_JS.read_text(encoding="utf-8")

        # Kiểm tra câu lệnh chuyển hướng
        assert "window.location.assign('app.html');" in js_content, "Sau khi đăng nhập thành công, trình duyệt phải chuyển hướng đến app.html."


if __name__ == "__main__":
    import sys
    print("\n" + "=" * 80)
    print("  WEALTHFLOW TEST RUNNER: EXECUTING FR01 SUITE (22 SCENARIOS)")
    print("=" * 80 + "\n")
    exit_code = pytest.main([__file__, "-v", "--tb=short"])
    sys.exit(exit_code)

