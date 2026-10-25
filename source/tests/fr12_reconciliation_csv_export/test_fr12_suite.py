# -*- coding: utf-8 -*-
"""
WEALTHFLOW AUTOMATED TEST SUITE: FR12 - RECONCILIATION & CSV EXPORT
Kiến trúc: Đa tầng theo thiết kế BCE+D (Boundary - Control - Entity/Data)
Chuẩn UP/RUP: Pha Construction - Tra cứu lịch sử, đối soát và xuất tệp CSV

Tập kiểm thử gồm 24 kịch bản tự động hóa 100%:
- Tầng D (Data Access & Database Queries): 6 Test Cases (gọi từng file php/tc_d12_*.php)
- Tầng C (Control & Business Logic): 7 Test Cases (gọi từng file php/tc_c12_*.php)
- Tầng B (Boundary / REST API Controller): 6 Test Cases (gọi từng file php/tc_b12_*.php)
- Tầng B (Boundary / Frontend UI Contracts): 5 Test Cases (kiểm tra contract DOM & JS)
"""

import json
import os
import re
import shutil
import subprocess
from pathlib import Path
import pytest

# Paths
BASE_DIR = Path(__file__).resolve().parents[2]
TEST_DIR = Path(__file__).resolve().parent
PHP_TEST_DIR = TEST_DIR / "php"
RECON_HTML = BASE_DIR / "frontend" / "views" / "reconciliation.html"
REPORTS_JS = BASE_DIR / "frontend" / "js" / "app" / "reports.js"
REPORT_API_JS = BASE_DIR / "frontend" / "js" / "services" / "report-api.js"

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
# TẦNG D: DATA ACCESS & DATABASE QUERIES (6 Test Cases)
# ==============================================================================

class TestLayerD_DataAccess:
    """Kiểm thử tầng Data Access (ReportDAO, AuditLogDAO, SQL Aggregate, Exclusion)"""

    def test_tc_d12_01_ledger_sum_calculation(self):
        """TC-D12.01: Truy vấn tổng đại số Nợ/Có sổ cái qua ReportDAO::getWalletLedgerSum"""
        data = run_php_test("tc_d12_01_ledger_sum_calculation.php")
        assert data["isSum10Valid"] is True, "Ví 10 (Tiền mặt) phải có tổng đại số sổ cái là 15,000,000 VND."
        assert data["isSum11Valid"] is True, "Ví 11 (USD) phải có tổng đại số sổ cái là 1,000 USD."
        assert data["isSumNoneValid"] is True, "Ví không tồn tại phải trả về 0.00000000."
        assert data["sum10"] == "15000000.00000000"
        assert data["sum11"] == "1000.00000000"

    def test_tc_d12_02_draft_exclusion(self):
        """TC-D12.02: Loại trừ giao dịch nháp (DRAFT/VOIDED) khỏi tổng đại số sổ cái"""
        data = run_php_test("tc_d12_02_draft_exclusion.php")
        assert data["draftIgnored"] is True, "Giao dịch DRAFT không được phép làm thay đổi số dư sổ cái."
        assert data["sumBefore"] == data["sumAfter"]

    def test_tc_d12_03_trial_balance_rows(self):
        """TC-D12.03: Truy vấn danh mục tài khoản và số phát sinh cho Bảng cân đối thử"""
        data = run_php_test("tc_d12_03_trial_balance_rows.php")
        assert data["hasRows"] is True, "Phải có ít nhất 1 tài khoản có số dư/phát sinh."
        assert data["count"] >= 3, "Bảng cân đối thử phải trả về ít nhất 3 tài khoản."
        assert data["structureValid"] is True, "Mỗi hàng phải có code, account_type, total_debit, total_credit."

    def test_tc_d12_04_transactions_export_query(self):
        """TC-D12.04: Truy vấn dữ liệu lịch sử phục vụ xuất tệp CSV kèm bộ lọc"""
        data = run_php_test("tc_d12_04_transactions_export_query.php")
        assert data["allCount"] >= 3, "Bộ lọc rỗng phải trả về tất cả giao dịch của user."
        assert data["hasWallet10"] is True, "Bộ lọc walletId=10 phải trả về giao dịch thuộc Ví 10."
        assert data["hasOct15"] is True, "Bộ lọc theo ngày phải trả về đúng giao dịch trong ngày đó."

    def test_tc_d12_05_export_audit_trail(self):
        """TC-D12.05: Ghi nhận vết kiểm toán xuất dữ liệu vào export_audit_logs"""
        data = run_php_test("tc_d12_05_export_audit_trail.php")
        assert data["logId"] > 0, "Bản ghi export_audit_logs phải được ghi thành công với logId > 0."
        assert data["hasLogs"] is True
        assert data["matchHash"] is True, "Mã băm SHA-256 phải khớp với hash đã ghi nhận."
        assert data["matchRowCount"] is True, "Số hàng xuất phải khớp (4 hàng)."
        assert data["matchType"] is True, "Loại xuất dữ liệu phải là TRANSACTIONS_CSV."

    def test_tc_d12_06_security_audit_logging(self):
        """TC-D12.06: Ghi nhật ký cảnh báo an ninh khi phát hiện lệch số dư vào security_audit_logs"""
        data = run_php_test("tc_d12_06_security_audit_logging.php")
        assert data["logId"] > 0, "Bản ghi security_audit_logs phải được tạo khi có biến cố lệch số dư."
        assert data["hasLogs"] is True
        assert data["eventMatches"] is True, "Tên sự kiện phải là LEDGER_MISMATCH_WALLET_12."


# ==============================================================================
# TẦNG C: CONTROL & BUSINESS LOGIC (7 Test Cases)
# ==============================================================================

class TestLayerC_ControlLogic:
    """Kiểm thử tầng nghiệp vụ đối soát, cân đối kế toán và bảo vệ CSV (ReconciliationService, ExportService)"""

    def test_tc_c12_01_reconciliation_matched(self):
        """TC-C12.01: Đối soát ví thành công khi số dư đệm khớp hoàn toàn (Delta = 0)"""
        data = run_php_test("tc_c12_01_reconciliation_matched.php")
        assert data["status"] == "MATCHED", "Trạng thái đối soát phải là MATCHED khi cached_balance == ledger_sum."
        assert data["delta"] == "0.00000000", "Chênh lệch số dư phải bằng 0."
        assert data["isAudited"] is True
        assert data["isMatched"] is True
        assert data["walletName"] == "Tiền mặt"

    def test_tc_c12_02_reconciliation_mismatch_alert(self):
        """TC-C12.02: Phát hiện sai lệch số dư ví (Delta != 0) và kích hoạt cảnh báo an ninh"""
        data = run_php_test("tc_c12_02_reconciliation_mismatch_alert.php")
        assert data["status"] == "MISMATCH", "Trạng thái đối soát phải là MISMATCH khi có sự chênh lệch."
        assert data["delta"] == "4999999.00000000", "Độ lệch phải đúng bằng 9,999,999 - 5,000,000."
        assert data["isAudited"] is False
        assert data["isMismatch"] is True
        assert data["hasMismatchLog"] is True, "Hệ thống phải tự động ghi nhận cảnh báo an ninh khi phát hiện lệch sổ."

    def test_tc_c12_03_reconciliation_all_wallets(self):
        """TC-C12.03: Đối soát tự động toàn bộ ví người dùng (reconcileAllWallets)"""
        data = run_php_test("tc_c12_03_reconciliation_all_wallets.php")
        assert data["totalWallets"] >= 3, "User có ít nhất 3 ví trong hệ thống."
        assert data["matchedWallets"] >= 2, "Có ít nhất 2 ví khớp số dư."
        assert data["mismatchedWallets"] >= 1, "Có ít nhất 1 ví lệch số dư (Ví 12)."
        assert data["validCounts"] is True

    def test_tc_c12_04_trial_balance_equilibrium(self):
        """TC-C12.04: Bảng cân đối thử xác nhận cân bằng kế toán kép (Tổng Nợ = Tổng Có)"""
        data = run_php_test("tc_c12_04_trial_balance_equilibrium.php")
        assert data["isBalanced"] is True, "Bảng cân đối kế toán kép phải cân bằng 100%."
        assert data["equilibriumDiff"] == "0.00000000", "Độ lệch cân bằng kế toán phải bằng 0."
        assert data["totalDebit"] == data["totalCredit"]
        assert data["balancedProof"] is True

    def test_tc_c12_05_formula_injection_defense(self):
        """TC-C12.05: Khử độc CSV Formula Injection (FR12.04, OWASP CSV Injection Defense)"""
        data = run_php_test("tc_c12_05_formula_injection_defense.php")
        assert data["allSanitized"] is True, "Tất cả các ký tự khởi đầu formula phải được vô hiệu hóa bằng tiền tố nháy đơn."
        assert data["equal"].startswith("'="), "=cmd phải được thêm tiền tố nháy đơn '"
        assert data["plus"].startswith("'+"), "+123456 phải được thêm tiền tố nháy đơn '"
        assert data["minus"].startswith("'-"), "-999999 phải được thêm tiền tố nháy đơn '"
        assert "'@SUM(1,2)" in data["at"], "@SUM(1,2) phải chứa tiền tố '@ và được bọc chuẩn."
        assert data["normal"] == "Mua sắm siêu thị", "Văn bản bình thường không bị thay đổi."

    def test_tc_c12_06_csv_rfc4180_escaping(self):
        """TC-C12.06: Chuẩn hóa RFC 4180 cho tệp CSV (escaping dấu phẩy, nháy kép, xuống dòng)"""
        data = run_php_test("tc_c12_06_csv_rfc4180_escaping.php")
        assert data["isCommaQuoted"] is True, "Trường có dấu phẩy phải được bọc trong nháy kép."
        assert data["isQuoteEscaped"] is True, "Dấu nháy kép bên trong phải được nhân đôi thành \"\"."
        assert data["isNewlineQuoted"] is True, "Trường chứa ký tự xuống dòng phải được bọc chuẩn."

    def test_tc_c12_07_utf8_bom_and_sha256(self):
        """TC-C12.07: Gắn tiền tố mã hóa UTF-8 BOM và tính mã băm SHA-256"""
        data = run_php_test("tc_c12_07_utf8_bom_and_sha256.php")
        assert data["hasBom"] is True, "Tệp CSV xuất ra phải bắt đầu bằng 3 byte UTF-8 BOM (\\xEF\\xBB\\xBF)."
        assert data["hashMatches"] is True, "Mã băm SHA-256 lưu trong audit log phải khớp đúng nội dung tệp."
        assert data["validExport"] is True
        assert data["rowCount"] > 0


# ==============================================================================
# TẦNG B: BOUNDARY REST API CONTROLLER (6 Test Cases)
# ==============================================================================

class TestLayerB_ApiController:
    """Kiểm thử tầng tiếp nhận HTTP Request / Response Controller (ReportController)"""

    def test_tc_b12_01_api_reconcile_single_wallet(self):
        """TC-B12.01: GET /reports/reconcile?walletId=10 - Đối soát 1 ví thành công"""
        data = run_php_test("tc_b12_01_api_reconcile_single_wallet.php")
        assert data["success"] is True
        assert data["walletId"] == 10
        assert data["status"] == "MATCHED"
        assert data["delta"] == "0.00000000"
        assert data["isMatched"] is True

    def test_tc_b12_02_api_reconcile_all_wallets(self):
        """TC-B12.02: GET /reports/reconcile - Đối soát hàng loạt toàn bộ ví"""
        data = run_php_test("tc_b12_02_api_reconcile_all_wallets.php")
        assert data["success"] is True
        assert data["totalWallets"] >= 3
        assert data["hasWallets"] is True

    def test_tc_b12_03_api_reconcile_not_found(self):
        """TC-B12.03: GET /reports/reconcile?walletId=99999 - Báo lỗi khi ví không tồn tại"""
        data = run_php_test("tc_b12_03_api_reconcile_not_found.php")
        assert data["success"] is False
        assert data["errorCode"] == "WALLET_NOT_FOUND"
        assert data["http_status"] == 404
        assert data["isNotFound"] is True

    def test_tc_b12_04_api_trial_balance(self):
        """TC-B12.04: GET /reports/trial-balance - Bảng cân đối phát sinh qua API"""
        data = run_php_test("tc_b12_04_api_trial_balance.php")
        assert data["success"] is True
        assert data["isBalanced"] is True
        assert data["hasAccounts"] is True
        assert data["equilibriumDiff"] == "0.00000000"

    def test_tc_b12_05_api_export_csv_headers(self):
        """TC-B12.05: GET /reports/export/csv - Luồng stream tải xuống tệp CSV kèm HTTP Headers"""
        data = run_php_test("tc_b12_05_api_export_csv_headers.php")
        assert data["hasCsvType"] is True, "Header Content-Type: text/csv; charset=utf-8 phải được thiết lập."
        assert data["hasDisposition"] is True, "Header Content-Disposition: attachment phải được thiết lập."
        assert data["hasSha256"] is True, "Header X-Content-SHA256 phải được gửi kèm."
        assert data["hasRowCount"] is True, "Header X-Content-Row-Count phải được gửi kèm."
        assert data["hasBom"] is True, "Stream xuất ra phải có tiền tố UTF-8 BOM."
        assert data["validExportStream"] is True

    def test_tc_b12_06_api_multi_tenant_isolation(self):
        """TC-B12.06: Cách ly dữ liệu đa người dùng (Multi-tenant Isolation)"""
        data = run_php_test("tc_b12_06_api_multi_tenant_isolation.php")
        assert data["crossReconBlocked"] is True, "Không được phép đối soát ví của user khác."
        assert data["csvExcludesOtherUsers"] is True, "File xuất CSV của User 1 không được chứa giao dịch của User 2."
        assert data["multiTenantIsolated"] is True


# ==============================================================================
# TẦNG B: BOUNDARY FRONTEND UI CONTRACTS (5 Test Cases)
# ==============================================================================

class TestLayerB_FrontendUI:
    """Kiểm thử tầng Giao diện Người dùng (DOM contracts, badges, CSV regex, blob download)"""

    def test_tc_ui12_01_dom_elements_exist(self):
        """TC-UI12.01: Cấu trúc DOM trang Đối soát (reconciliation.html)"""
        assert RECON_HTML.exists(), "Tệp views/reconciliation.html phải tồn tại."
        content = RECON_HTML.read_text(encoding="utf-8")

        # Buttons
        assert 'data-action="export"' in content, "Nút Tải Xuất CSV (data-action='export') phải có trong DOM."
        assert 'data-action="reconcile"' in content, "Nút Kiểm tra đối soát (data-action='reconcile') phải có trong DOM."

        # KPI indicators
        assert 'id="recon-tracked-wallets"' in content, "Thẻ đếm ví theo dõi #recon-tracked-wallets phải có."
        assert 'id="recon-matched-wallets"' in content, "Thẻ đếm ví khớp sổ #recon-matched-wallets phải có."
        assert 'id="recon-posted-count"' in content, "Thẻ đếm giao dịch ghi nhận #recon-posted-count phải có."

        # Table body
        assert 'id="recon-table-body"' in content, "Bảng chi tiết #recon-table-body phải có để render danh sách ví."

    def test_tc_ui12_02_badge_classes_and_status(self):
        """TC-UI12.02: Logic render Badge trạng thái trong reports.js"""
        assert REPORTS_JS.exists(), "Tệp reports.js phải tồn tại."
        content = REPORTS_JS.read_text(encoding="utf-8")

        assert "updateReconciliationDOM" in content, "reports.js phải định nghĩa hàm updateReconciliationDOM."
        assert "Khớp sổ 100%" in content, "Badge trạng thái khớp sổ phải có nhãn 'Khớp sổ 100%'."
        assert "Lệch số dư" in content, "Badge cảnh báo lệch số dư phải có nhãn 'Lệch số dư'."
        assert "runReconciliationAudit" in content, "Hàm runReconciliationAudit phải được xuất để gắn sự kiện click."

    def test_tc_ui12_03_filename_regex_content_disposition(self):
        """TC-UI12.03: Bóc tách filename từ Header Content-Disposition trong report-api.js"""
        assert REPORT_API_JS.exists(), "Tệp report-api.js phải tồn tại."
        content = REPORT_API_JS.read_text(encoding="utf-8")

        # Verify regex pattern in source code
        assert r'/filename="?([^";]+)"?/' in content, "report-api.js phải sử dụng regex trích xuất filename chuẩn."

        pattern = re.compile(r'filename="?([^";]+)"?')

        # Case 1: Standard quoted filename
        header1 = 'attachment; filename="wealthflow_transactions_20261024.csv"'
        m1 = pattern.search(header1)
        assert m1 is not None and m1.group(1) == "wealthflow_transactions_20261024.csv"

        # Case 2: Unquoted filename
        header2 = 'attachment; filename=wealthflow_export_custom.csv'
        m2 = pattern.search(header2)
        assert m2 is not None and m2.group(1) == "wealthflow_export_custom.csv"

        # Case 3: With parameters
        header3 = 'attachment; filename="report.csv"; size=1024'
        m3 = pattern.search(header3)
        assert m3 is not None and m3.group(1) == "report.csv"

    def test_tc_ui12_04_fallback_filename_generation(self):
        """TC-UI12.04: Cơ chế Fallback tên file ngẫu nhiên khi header rỗng"""
        assert REPORT_API_JS.exists()
        content = REPORT_API_JS.read_text(encoding="utf-8")

        # Verify fallback pattern in code
        assert "wealthflow_transactions_" in content
        assert "${Date.now()}.csv" in content or "Date.now()" in content, "Phải có fallback timestamp khi server không trả header."

    def test_tc_ui12_05_blob_download_lifecycle(self):
        """TC-UI12.05: Quản lý vòng đời Blob & Dọn dẹp bộ nhớ RAM trong report-api.js"""
        assert REPORT_API_JS.exists()
        content = REPORT_API_JS.read_text(encoding="utf-8")

        # Download pipeline sequence
        assert "response.blob()" in content, "Phải chuyển response thành Blob binary stream."
        assert "URL.createObjectURL(blob)" in content, "Phải tạo Object URL từ Blob."
        assert "document.createElement('a')" in content, "Phải tạo thẻ liên kết <a> ảo."
        assert "link.click()" in content, "Phải kích hoạt click ảo để tải tệp."
        assert "document.body.removeChild(link)" in content, "Phải dọn dẹp thẻ <a> sau khi click."
        assert "URL.revokeObjectURL(downloadUrl)" in content, "Phải giải phóng bộ nhớ URL.revokeObjectURL."


if __name__ == "__main__":
    import sys
    sys.exit(pytest.main([__file__, "-v", "--tb=short"]))
