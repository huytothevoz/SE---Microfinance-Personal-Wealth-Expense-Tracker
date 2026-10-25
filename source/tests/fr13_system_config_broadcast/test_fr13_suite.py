# -*- coding: utf-8 -*-
"""
WEALTHFLOW AUTOMATED TEST SUITE: FR13 - SYSTEM CONFIG & ADMIN BROADCAST
Phân hệ con: FR13.01 - Quản lý danh mục hệ thống mặc định (Default Categories Management)
Kiến trúc: Đa tầng theo thiết kế BCE+D (Boundary - Control - Entity/Data)
Chuẩn UP/RUP: Pha Construction - Quản trị danh mục mẫu & cách ly dữ liệu cá nhân
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
ADMIN_CATEGORIES_HTML = BASE_DIR / "frontend" / "views" / "admin-categories.html"
ADMIN_JS = BASE_DIR / "frontend" / "js" / "app" / "admin.js"
PAGES_JS = BASE_DIR / "frontend" / "js" / "app" / "pages.js"
MAIN_JS = BASE_DIR / "frontend" / "js" / "app" / "main.js"


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

    payload = json.loads(stdout[json_start:])
    assert payload.get("status") == "OK", f"PHP test assertion failed: {payload}"
    return payload.get("data", {})


# =========================================================================
# TẦNG D: DATA ACCESS & REPOSITORY LAYER (AdminDAO)
# =========================================================================

class TestFR13DataLayer:
    """Kiểm tra tầng dữ liệu AdminDAO và các thao tác CRUD danh mục mẫu."""

    def test_d13_01_default_categories_dao_crud(self):
        """TC-D13-01: AdminDAO CRUD đầy đủ: getAll, create, findByNameAndType, update, delete."""
        data = run_php_test("tc_d13_01_default_categories_dao.php")
        assert data.get("passed") is True


# =========================================================================
# TẦNG C: CONTROL & BUSINESS LOGIC LAYER (AdminService & Rules)
# =========================================================================

class TestFR13ControlLayer:
    """Kiểm tra nghiệp vụ AdminService: Phân quyền, chống trùng lặp, kế thừa đăng ký và audit log."""

    def test_c13_01_admin_authorization_enforcement(self):
        """TC-C13-01: Người dùng thường (role USER) bị chặn 403 Forbidden đối với mọi thao tác quản trị."""
        data = run_php_test("tc_c13_01_admin_authorization.php")
        assert data.get("passed") is True
        assert data.get("forbiddenChecks") == 4

    def test_c13_02_validation_and_duplicate_conflict(self):
        """TC-C13-02: Kiểm tra dữ liệu (422) và ràng buộc chống trùng lặp tên & loại (409 Conflict)."""
        data = run_php_test("tc_c13_02_validation_and_conflict.php")
        assert data.get("passed") is True
        assert data.get("validationErrors") == 3
        assert data.get("conflictErrors") == 3

    def test_c13_03_registration_copy_and_isolation(self):
        """TC-C13-03: Danh mục mẫu mới kế thừa sang người dùng mới; người dùng hiện hữu bất biến 100%."""
        data = run_php_test("tc_c13_03_registration_copy_isolation.php")
        assert data.get("passed") is True
        assert "Thưởng dự án" in data.get("user3InheritedCategories", [])
        assert "Mua sắm" not in data.get("user3InheritedCategories", [])
        assert data.get("user2CategoryCountUnchanged") == 2

    def test_c13_04_admin_audit_trail_logging(self):
        """TC-C13-04: Mọi thao tác thêm, sửa, xóa danh mục mẫu đều được ghi nhật ký vào admin_audit_logs."""
        data = run_php_test("tc_c13_04_admin_audit_logging.php")
        assert data.get("passed") is True
        assert "CREATE_DEFAULT_CATEGORY" in data.get("loggedActions", [])
        assert "UPDATE_DEFAULT_CATEGORY" in data.get("loggedActions", [])
        assert "DELETE_DEFAULT_CATEGORY" in data.get("loggedActions", [])


# =========================================================================
# TẦNG B: BOUNDARY / REST API CONTROLLER (AdminController)
# =========================================================================

class TestFR13BoundaryApiLayer:
    """Kiểm tra các endpoint REST API của AdminController."""

    def test_b13_01_api_controller_endpoints(self):
        """TC-B13-01: Kiểm tra GET (200), POST (201), PUT (200), DELETE (200) và 403 Forbidden cho user."""
        data = run_php_test("tc_b13_01_api_controller.php")
        assert data.get("passed") is True
        assert data.get("postStatus") == 201
        assert data.get("userForbiddenStatus") == 403


# =========================================================================
# TẦNG B: BOUNDARY / FRONTEND UI CONTRACTS
# =========================================================================

class TestFR13BoundaryUiContracts:
    """Kiểm tra hợp đồng giao diện frontend (DOM contracts, JavaScript action handlers)."""

    def test_b13_ui_template_contract(self):
        """TC-B13-02: Template admin-categories.html chứa đúng các hook ID và data-action."""
        assert ADMIN_CATEGORIES_HTML.exists(), "Template admin-categories.html không tồn tại"
        content = ADMIN_CATEGORIES_HTML.read_text(encoding="utf-8")

        assert 'data-action="admin-category-add"' in content, "Thiếu nút thêm danh mục data-action='admin-category-add'"
        assert 'id="admin-category-card-list"' in content, "Thiếu container id='admin-category-card-list'"
        assert "Danh mục mặc định chỉ dùng làm mẫu" in content, "Thiếu ghi chú hướng dẫn bảo toàn dữ liệu cá nhân"

    def test_b13_ui_admin_js_contract(self):
        """TC-B13-03: admin.js export renderAdminCategoriesPage và handleAdminCategoryAction kết nối live API."""
        assert ADMIN_JS.exists(), "admin.js không tồn tại"
        content = ADMIN_JS.read_text(encoding="utf-8")

        assert "export async function renderAdminCategoriesPage" in content
        assert "export function handleAdminCategoryAction" in content
        assert "/api/admin/default-categories" in content
        assert "admin-category-edit" in content
        assert "admin-category-toggle" in content
        assert "admin-category-delete" in content

    def test_b13_ui_pages_and_main_routing(self):
        """TC-B13-04: pages.js và main.js định tuyến admin-categories và bắt sự kiện modal."""
        assert PAGES_JS.exists(), "pages.js không tồn tại"
        assert MAIN_JS.exists(), "main.js không tồn tại"

        pages_content = PAGES_JS.read_text(encoding="utf-8")
        main_content = MAIN_JS.read_text(encoding="utf-8")

        assert "renderAdminCategoriesPage" in pages_content
        assert "handleAdminCategoryAction" in main_content
