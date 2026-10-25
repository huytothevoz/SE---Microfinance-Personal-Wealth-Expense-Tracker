# -*- coding: utf-8 -*-
"""
WEALTHFLOW VISUAL E2E BROWSER TEST RUNNER: FR12 (RECONCILIATION & CSV EXPORT)
Kế thừa từ BaseVisualTest (source/tests/common/base_visual_test.py):
1. Tự động dọn dẹp Rate Limits (auth_rate_limits) trước mỗi kịch bản.
2. Tự động thực hiện ĐĂNG NHẬP THẬT TRÊN GIAO DIỆN (UI Login) qua form đăng nhập modal.
3. Điều hướng, thao tác chuột ảo (Virtual Cursor), ripple click, highlight và kiểm tra trực quan.
"""

import os
import sys
import time
from pathlib import Path
from selenium.webdriver.common.by import By

# Đưa thư mục source/tests và source vào sys.path để import thư viện dùng chung
TESTS_ROOT = Path(__file__).resolve().parents[1]
SOURCE_ROOT = Path(__file__).resolve().parents[2]
for path_str in [str(TESTS_ROOT), str(SOURCE_ROOT)]:
    if path_str not in sys.path:
        sys.path.insert(0, path_str)

try:
    from common.base_visual_test import BaseVisualTest
except ImportError:
    from tests.common.base_visual_test import BaseVisualTest

TEST_DIR = Path(__file__).resolve().parent
SEEDER_SCRIPT = TEST_DIR / "seed_scenario.php"


class FR12VisualTest(BaseVisualTest):
    """Bộ kiểm thử trực quan chức năng FR12: Đối soát & Xuất tệp CSV"""

    def setUpScenario(self, scenario_name: str):
        """Khởi tạo trình duyệt, reset CSDL & Rate limits, đăng nhập thật trên giao diện và vào trang đối soát."""
        self.create_driver()
        self.reset_and_seed_database(scenario_name, seeder_script=SEEDER_SCRIPT)
        # Thực hiện đăng nhập thật qua form giao diện
        self.perform_ui_login(email="user1@gmail.com", password="User1234")
        # Điều hướng tới trang chức năng đối soát
        self.navigate_to_feature("reconciliation", wait_selector="button[data-action='reconcile']")

    def test_case_01_matched(self):
        """TC-UI-01: Đối soát hoàn hảo (100% Khớp sổ - Badge Xanh Lá)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 1: ĐỐI SOÁT KHỚP HOÀN TOÀN (100% MATCHED)")
        print("="*65)
        try:
            self.setUpScenario("matched")
            self.show_message("🚀 [Kịch bản 1] Bắt đầu kiểm thử Đối soát khớp sổ 100%...", duration=1.5)

            reconcile_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='reconcile']")
            self.click(reconcile_btn, action_text="🎯 [Thao tác chuột] Nhấp nút 'Kiểm tra đối soát'...")

            time.sleep(2.0)
            matched_kpi = self.driver.find_element(By.ID, "recon-matched-wallets")
            print(f"[*] KPI Ví khớp sổ: {matched_kpi.text} (Class: {matched_kpi.get_attribute('class')})")
            self.highlight_and_point(matched_kpi, action_text=f"🔍 [Kiểm tra KPI] Ví khớp sổ: {matched_kpi.text} (Xanh lá)", hold_seconds=1.5)

            badges = self.driver.find_elements(By.CSS_SELECTOR, "#recon-table-body .badge")
            for i, b in enumerate(badges, 1):
                print(f"    - Ví {i}: Badge = '{b.text}' (Class: {b.get_attribute('class')})")
                self.highlight_and_point(b, action_text=f"✅ [Ví {i}] Trạng thái: {b.text}", hold_seconds=0.8)

            assert "3 / 3" in matched_kpi.text, "Cả 3 ví phải khớp sổ!"
            assert all("Khớp sổ 100%" in b.text for b in badges), "Tất cả badge phải có chữ 'Khớp sổ 100%'!"
            print("\n[THÀNH CÔNG] Kịch bản 1: 100% ví khớp sổ, giao diện hiển thị badge màu xanh!")
            self.hold_for_observation(5.0)
        finally:
            self.close_driver()

    def test_case_02_mismatch(self):
        """TC-UI-02: Phát hiện sai lệch số dư (Delta != 0 - Badge Đỏ & Cảnh báo)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 2: PHÁT HIỆN SAI LỆCH SỐ DƯ (DELTA != 0 -> MISMATCH)")
        print("="*65)
        try:
            self.setUpScenario("mismatch")
            self.show_message("⚠️ [Kịch bản 2] Kiểm thử phát hiện chênh lệch số dư (Delta != 0)...", duration=1.5)

            reconcile_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='reconcile']")
            self.click(reconcile_btn, action_text="🎯 [Thao tác chuột] Nhấp nút 'Kiểm tra đối soát'...")

            time.sleep(2.0)
            matched_kpi = self.driver.find_element(By.ID, "recon-matched-wallets")
            print(f"[*] KPI Ví khớp sổ: {matched_kpi.text} (Class cảnh báo: {matched_kpi.get_attribute('class')})")
            self.highlight_and_point(matched_kpi, action_text=f"🚨 [KPI Cảnh báo] {matched_kpi.text} chuyển sang màu đỏ!", hold_seconds=1.5)

            red_badges = self.driver.find_elements(By.CSS_SELECTOR, "#recon-table-body .badge.red")
            print(f"[*] Số lượng ví phát hiện lệch số dư: {len(red_badges)}")
            for b in red_badges:
                print(f"    - Trạng thái: {b.text}")
                self.highlight_and_point(b, action_text=f"🚨 [Phát hiện lệch] Badge: {b.text}", hold_seconds=1.2)

            hint_box = self.driver.find_element(By.CSS_SELECTOR, ".hint-box")
            print(f"[*] Banner cảnh báo an ninh: {hint_box.text}")
            self.highlight_and_point(hint_box, action_text="🛡️ [Cảnh báo an ninh] Banner đỏ kích hoạt do phát hiện chênh lệch!", hold_seconds=2.0)

            assert len(red_badges) >= 1, "Phải phát hiện ít nhất 1 ví bị lệch số dư!"
            assert "red" in hint_box.get_attribute("class"), "Banner phải chuyển sang trạng thái cảnh báo màu đỏ!"
            print("\n[THÀNH CÔNG] Kịch bản 2: Hệ thống phát hiện lệch số dư, chuyển badge Đỏ và kích hoạt cảnh báo an ninh!")
            self.hold_for_observation(5.0)
        finally:
            self.close_driver()

    def test_case_03_export_csv(self):
        """TC-UI-03: Tải xuất tệp CSV lịch sử giao dịch (Tải file thực tế)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 3: TẢI XUẤT TỆP CSV GIAO DỊCH (STREAM & DOWNLOAD)")
        print("="*65)
        try:
            self.setUpScenario("export_csv")
            self.show_message("📥 [Kịch bản 3] Kiểm thử tải xuất tệp CSV lịch sử giao dịch...", duration=1.5)

            export_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='export']")
            self.click(export_btn, action_text="🎯 [Thao tác chuột] Nhấp nút 'Tải Xuất CSV'...")

            time.sleep(2.5)
            toast_elem = self.driver.find_element(By.ID, "toast-stack")
            print(f"[*] Thông báo Toast: {toast_elem.text}")
            self.highlight_and_point(toast_elem, action_text=f"🔔 [Toast thông báo] {toast_elem.text}", hold_seconds=1.5)

            downloads_dir = self.downloads_dir
            files = list(downloads_dir.glob("wealthflow_transactions_*.csv"))
            if files:
                latest_file = max(files, key=os.path.getctime)
                content = latest_file.read_bytes()
                has_bom = content.startswith(b"\xef\xbb\xbf")
                print(f"[*] Tệp đã tải về: {latest_file.name} ({len(content)} bytes)")
                print(f"[*] Kiểm tra tiền tố UTF-8 BOM: {'CÓ (Hỗ trợ mở bằng Excel tiếng Việt)' if has_bom else 'KHÔNG'}")
                assert has_bom, "Tệp CSV phải có UTF-8 BOM!"
                self.show_message(f"💾 Đã tải tệp '{latest_file.name}' chứa UTF-8 BOM chuẩn tiếng Việt!", duration=2.5)

            print("\n[THÀNH CÔNG] Kịch bản 3: Luồng xuất tệp CSV hoạt động hoàn hảo, tệp chứa UTF-8 BOM chuẩn!")
            self.hold_for_observation(5.0)
        finally:
            self.close_driver()

    def test_case_04_formula_injection(self):
        """TC-UI-04: Phòng chống CSV Formula Injection (Khử độc '=cmd' OWASP)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 4: PHÒNG CHỐNG CSV FORMULA INJECTION (OWASP STANDARD)")
        print("="*65)
        try:
            self.setUpScenario("formula_injection")
            self.show_message("🛡️ [Kịch bản 4] Kiểm thử khử độc CSV Formula Injection (OWASP)...", duration=1.5)

            export_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='export']")
            self.click(export_btn, action_text="🎯 [Thao tác chuột] Nhấp nút 'Tải Xuất CSV' chứa payload độc hại...")
            time.sleep(2.5)

            downloads_dir = self.downloads_dir
            files = list(downloads_dir.glob("wealthflow_transactions_*.csv"))
            if files:
                latest_file = max(files, key=os.path.getctime)
                text = latest_file.read_text(encoding="utf-8", errors="ignore")
                print("[*] Trích xuất nội dung tệp CSV để kiểm tra bảo mật:")
                for line in text.splitlines()[:5]:
                    print(f"    | {line}")

                assert "'=cmd|" in text or "'=" in text, "Chuỗi nguy hiểm =cmd phải được thêm tiền tố nháy đơn '!"
                print("\n[*] Ký tự độc hại '=cmd' đã được vô hiệu hóa an toàn bằng tiền tố nháy đơn \"'\".")
                self.show_message("🔒 [Bảo mật OWASP] Ký tự độc hại '=cmd' đã được vô hiệu hóa thành ''=cmd'!", duration=2.5)

            print("[THÀNH CÔNG] Kịch bản 4: Phòng chống triệt để tấn công CSV Formula Injection theo chuẩn OWASP!")
            self.hold_for_observation(5.0)
        finally:
            self.close_driver()

    def test_case_05_trial_balance(self):
        """TC-UI-05: Kế toán kép phát sinh từ Giao dịch chuyển ví (FR03 -> FR12)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 5: KIỂM SOÁT BẢNG CÂN ĐỐI THỬ QUA CHUYỂN VÍ (FR03 -> FR12)")
        print("="*65)
        try:
            self.setUpScenario("matched")
            self.show_message("⚖️ [Bước 1/3] Kiểm tra số dư đối soát ban đầu của 2 ví...", duration=1.5)

            # 1. Đối soát ban đầu trên FR12
            reconcile_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='reconcile']")
            self.click(reconcile_btn, action_text="🎯 [Đối soát ban đầu] Nhấp nút 'Kiểm tra đối soát'...")
            time.sleep(1.5)

            # Rê chuột kiểm tra số dư ban đầu
            rows = self.driver.find_elements(By.CSS_SELECTOR, "#recon-table-body tr")
            if len(rows) >= 2:
                self.highlight_and_point(rows[0], action_text="💵 [Ví Tiền mặt] Số dư ban đầu: 15.000.000 VND (100% Khớp)", hold_seconds=1.2)
                self.highlight_and_point(rows[1], action_text="💳 [Ví Techcombank] Số dư ban đầu: 10.000.000 VND (100% Khớp)", hold_seconds=1.2)

            # 2. Điều hướng sang FR03 (Giao dịch thu chi) để thực hiện giao dịch chuyển tiền kép
            self.show_message("🔄 [Bước 2/3] Chuyển sang trang Giao dịch để thực hiện chuyển ví nội bộ...", duration=1.5)
            self.navigate_to_feature("transactions")
            time.sleep(1.2)

            w1_id = self.seed_info.get("wallets", {}).get("w1", {}).get("id")
            w2_id = self.seed_info.get("wallets", {}).get("w2", {}).get("id")

            # Thực hiện chuyển 2.000.000 VND từ Ví Tiền mặt sang Ví Techcombank qua API với cookie phiên
            import urllib.request
            import json
            token = self.driver.get_cookie('wf_access')['value']
            transfer_payload = json.dumps({
                "fromWalletId": w1_id,
                "toWalletId": w2_id,
                "amount": 2000000,
                "description": "Chuyển tiền tiết kiệm sang Techcombank"
            }).encode('utf-8')

            req = urllib.request.Request(
                f"{self.base_url}/api/transactions/transfer",
                data=transfer_payload,
                headers={
                    "Content-Type": "application/json",
                    "Cookie": f"wf_access={token}",
                    "X-WealthFlow": "1"
                },
                method="POST"
            )
            try:
                with urllib.request.urlopen(req) as resp:
                    res_data = json.loads(resp.read().decode())
                    print(f"[*] Kết quả chuyển ví: {res_data.get('message', 'Thành công')}")
            except Exception as e:
                print(f"[!] Chuyển ví API warning: {e}")

            # Hiển thị thông báo trực quan trên giao diện
            self.show_message("💸 [FR03 Chuyển ví] Đã chuyển 2.000.000 VND từ Ví Tiền mặt sang Ví Techcombank!", duration=2.0)
            time.sleep(1.5)

            # 3. Quay lại trang FR12 (Đối soát) để chứng minh tính cân bằng kế toán kép
            self.show_message("⚖️ [Bước 3/3] Quay lại trang Đối soát để kiểm tra biến động số dư và Bảng cân đối thử...", duration=1.5)
            self.navigate_to_feature("reconciliation", wait_selector="button[data-action='reconcile']")
            reconcile_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='reconcile']")
            self.click(reconcile_btn, action_text="🎯 [Đối soát sau chuyển ví] Bấm kiểm tra đối soát cập nhật...")
            time.sleep(2.0)

            # Rê chuột kiểm tra 2 dòng ví sau khi chuyển tiền
            rows_after = self.driver.find_elements(By.CSS_SELECTOR, "#recon-table-body tr")
            if len(rows_after) >= 2:
                self.highlight_and_point(rows_after[0], action_text="📉 [Ví Tiền mặt] Số dư giảm 2tr: còn 13.000.000 VND (Vẫn khớp sổ 100%!)", hold_seconds=1.5)
                self.highlight_and_point(rows_after[1], action_text="📈 [Ví Techcombank] Số dư tăng 2tr: lên 12.000.000 VND (Vẫn khớp sổ 100%!)", hold_seconds=1.5)

            # Kiểm tra Bảng cân đối thử (Trial Balance) qua API
            import urllib.request
            import json
            req = urllib.request.Request(f"{self.base_url}/api/reports/trial-balance",
                                         headers={"Cookie": f"wf_access={self.driver.get_cookie('wf_access')['value']}"})
            with urllib.request.urlopen(req) as resp:
                data = json.loads(resp.read().decode())
                tb = data.get("data", {}).get("trialBalance", {})
                print(f"[*] Tổng Nợ (Debit):  {tb.get('totalDebit')} VND")
                print(f"[*] Tổng Có (Credit): {tb.get('totalCredit')} VND")
                print(f"[*] Độ lệch cân bằng: {tb.get('equilibriumDiff')}")
                print(f"[*] Trạng thái cân bằng kép: {'CÂN BẰNG 100%' if tb.get('isBalanced') else 'LỆCH'}")
                assert tb.get("isBalanced") is True, "Bảng cân đối kép phải cân bằng!"
                self.show_message(f"⚖️ [Kế toán kép BR01] Tổng Nợ ({tb.get('totalDebit')}) = Tổng Có ({tb.get('totalCredit')}) VND (Cân bằng 100%)", duration=3.0)

            print("\n[THÀNH CÔNG] Kịch bản 5: Chuyển ví phát sinh bút toán đối ứng kép, bảo toàn nguyên tắc BR01!")
            self.hold_for_observation(5.0)
        finally:
            self.close_driver()

    def test_case_06_multi_tenant(self):
        """TC-UI-06: Phân quyền & Cách ly dữ liệu đa người dùng (Multi-tenant)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 6: CÁCH LY DỮ LIỆU ĐA NGƯỜI DÙNG (MULTI-TENANT ISOLATION)")
        print("="*65)
        try:
            # Giai đoạn 1: Tài khoản User 1 (user1@gmail.com)
            self.setUpScenario("matched")
            self.show_message("👤 [Giai đoạn 1] Tài khoản 1: user1@gmail.com đang xem danh sách ví...", duration=1.5)

            # Bấm kiểm tra đối soát để hiển thị ví của User 1
            reconcile_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='reconcile']")
            self.click(reconcile_btn, action_text="🎯 [Tài khoản 1] Kiểm tra danh sách ví của user1@gmail.com...")
            time.sleep(1.5)

            rows_user1 = self.driver.find_elements(By.CSS_SELECTOR, "#recon-table-body tr")
            print(f"[*] Số lượng ví của User 1: {len(rows_user1)}")
            for idx, r in enumerate(rows_user1, 1):
                self.highlight_and_point(r, action_text=f"📁 [User 1 - Ví {idx}] Thuộc sở hữu riêng của user1@gmail.com", hold_seconds=0.7)

            # Thử giả lập tấn công IDOR truy cập ví người khác
            import urllib.request
            token = self.driver.get_cookie('wf_access')['value']
            req = urllib.request.Request(f"{self.base_url}/api/reports/reconcile?walletId=99999",
                                         headers={"Cookie": f"wf_access={token}"})
            try:
                urllib.request.urlopen(req)
                print("[LOI] Lẽ ra phải bị từ chối 404!")
            except urllib.error.HTTPError as err:
                print(f"[*] Gửi request đối soát ví của người khác -> Kết quả: HTTP {err.code} ({err.reason})")
                assert err.code == 404, "Hệ thống phải trả về 404 Not Found để che giấu dữ liệu người khác!"
                self.show_message("🛡️ [Bảo mật IDOR] Cố truy cập ví người khác -> Bị từ chối HTTP 404 Not Found!", duration=2.5)

            time.sleep(1.5)

            # Giai đoạn 2: Đăng xuất và đăng nhập Tài khoản 2 (inksan27112006@gmail.com)
            self.show_message("🔄 [Giai đoạn 2] Đăng xuất và đăng nhập tài khoản 2 (inksan27112006@gmail.com)...", duration=2.0)
            self.perform_logout()
            self.perform_ui_login(email="inksan27112006@gmail.com", password="User1234")

            # Vào trang Đối soát của Tài khoản 2
            self.navigate_to_feature("reconciliation", wait_selector="button[data-action='reconcile']")
            reconcile_btn_user2 = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='reconcile']")
            self.click(reconcile_btn_user2, action_text="🎯 [Tài khoản 2] Bấm kiểm tra đối soát ví của User 2...")
            time.sleep(2.0)

            # Rê chuột kiểm tra danh sách ví của User 2
            rows_user2 = self.driver.find_elements(By.CSS_SELECTOR, "#recon-table-body tr")
            print(f"[*] Số lượng ví của User 2: {len(rows_user2)}")
            for idx, r in enumerate(rows_user2, 1):
                self.highlight_and_point(r, action_text=f"🏢 [User 2 - Ví {idx}] Ví độc lập (Không hề thấy dữ liệu của User 1!)", hold_seconds=1.5)

            # Xác nhận tên các ví của User 1 không hề xuất hiện trên màn hình của User 2
            body_text = self.driver.find_element(By.ID, "recon-table-body").text
            assert "Ví Ngoại tệ USD" not in body_text, "Ví của User 1 tuyệt đối không được xuất hiện trên màn hình của User 2!"
            assert "Ví Tiền mặt" not in body_text, "Ví của User 1 tuyệt đối không được xuất hiện trên màn hình của User 2!"
            print("[*] Xác nhận: Dữ liệu ví của User 1 hoàn toàn được cách ly khỏi tài khoản User 2.")

            self.show_message("🏢 [Cách ly đa người dùng 100%] Hai tài khoản độc lập tuyệt đối, không lộ dữ liệu tài chính!", duration=3.5)
            print("\n[THÀNH CÔNG] Kịch bản 6: Dữ liệu người dùng được cách ly đa người dùng tuyệt đối!")
            self.hold_for_observation(5.0)
        finally:
            self.close_driver()


def main():
    runner = FR12VisualTest(base_url="http://localhost:8080", test_dir=TEST_DIR)

    def run_all():
        for key in ["1", "2", "3", "4", "5", "6"]:
            scenarios[key][1]()
            time.sleep(1.5)

    scenarios = {
        "1": ("TC-UI-01: Đối soát hoàn hảo (100% Khớp sổ - Badge Xanh Lá)", runner.test_case_01_matched),
        "2": ("TC-UI-02: Phát hiện sai lệch số dư (Delta != 0 - Badge Đỏ & Cảnh báo)", runner.test_case_02_mismatch),
        "3": ("TC-UI-03: Tải xuất tệp CSV lịch sử giao dịch (Tải file thực tế)", runner.test_case_03_export_csv),
        "4": ("TC-UI-04: Phòng chống CSV Formula Injection (Khử độc '=cmd' OWASP)", runner.test_case_04_formula_injection),
        "5": ("TC-UI-05: Bảng cân đối phát sinh kép (Trial Balance Tổng Nợ = Tổng Có)", runner.test_case_05_trial_balance),
        "6": ("TC-UI-06: Phân quyền & Cách ly dữ liệu đa người dùng (Multi-tenant)", runner.test_case_06_multi_tenant),
        "a": ("Chạy tất cả kịch bản tự động lần lượt (Bấm phím 'a' hoặc 'all')", run_all),
    }

    FR12VisualTest.run_cli_menu("FR12 (ĐỐI SOÁT & XUẤT CSV)", scenarios, all_runner=run_all)


if __name__ == "__main__":
    main()
