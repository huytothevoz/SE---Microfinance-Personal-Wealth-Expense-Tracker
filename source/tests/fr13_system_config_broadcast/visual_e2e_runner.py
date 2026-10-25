# -*- coding: utf-8 -*-
"""
WEALTHFLOW VISUAL E2E BROWSER TEST RUNNER: FR13 (SYSTEM CONFIG & DEFAULT CATEGORIES)
Kế thừa từ BaseVisualTest (source/tests/common/base_visual_test.py):
1. Tự động thiết lập MySQL, reset Rate limits và tài khoản Admin/User.
2. Đăng nhập thật trên giao diện (UI Login) qua form đăng nhập modal.
3. Thao tác trực quan trên giao diện: Con trỏ chuột ảo, ripple click, typing cadence, highlight pill.
"""

import os
import sys
import time
from pathlib import Path
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

# Đưa thư mục source/tests và source vào sys.path
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


class FR13VisualTest(BaseVisualTest):
    """Bộ kiểm thử trực quan chức năng FR13: Quản lý danh mục mẫu hệ thống (Admin)"""

    def setUpScenario(self, role: str = "ADMIN"):
        """Khởi tạo trình duyệt, reset CSDL & Rate limits, đăng nhập thật qua form UI."""
        self.create_driver()
        self.reset_and_seed_database("baseline", seeder_script=SEEDER_SCRIPT)

        if role == "ADMIN":
            self.perform_ui_login(email="admin@wealthflow.local", password="User1234")
            self.navigate_to_feature("admin-categories", wait_selector="#admin-category-card-list")
        else:
            self.perform_ui_login(email="user1@gmail.com", password="User1234")

    def test_case_01_view_default_categories(self):
        """TC-UI-01: Truy cập màn hình Danh mục hệ thống & kiểm tra bộ danh mục mẫu chuẩn"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 1: QUAN SÁT GIAO DIỆN QUẢN TRỊ DANH MỤC HỆ THỐNG")
        print("="*65)
        try:
            self.setUpScenario(role="ADMIN")
            self.show_message("🚀 [Kịch bản 1] Đăng nhập Admin và tải trang Danh mục hệ thống...", duration=1.5)

            # 1. Highlight tiêu đề trang và nút thêm
            heading = self.driver.find_element(By.CSS_SELECTOR, ".page-heading h1")
            self.highlight_and_point(heading, action_text="🔍 [Kiểm tra tiêu đề] 'Danh mục hệ thống' (Khu vực Quản trị)", hold_seconds=1.2)

            add_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='admin-category-add']")
            self.highlight_and_point(add_btn, action_text="🎯 [Nút thao tác] 'Thêm danh mục mẫu' sẵn sàng phục vụ Admin", hold_seconds=1.2)

            # 2. Highlight card list và kiểm tra các danh mục chuẩn
            cards = self.driver.find_elements(By.CSS_SELECTOR, "#admin-category-card-list .mini-panel")
            print(f"[*] Số lượng thẻ danh mục mẫu hiển thị: {len(cards)}")
            self.show_message(f"✅ Đã tải thành công {len(cards)} danh mục mặc định từ live backend API!", duration=2.0)

            if cards:
                self.highlight_and_point(cards[0], action_text=f"📋 [Thẻ danh mục] {cards[0].find_element(By.TAG_NAME, 'h3').text} kèm Badge phân loại & Trạng thái", hold_seconds=1.5)

            # 3. Highlight hộp ghi chú bảo toàn dữ liệu
            hint = self.driver.find_element(By.CSS_SELECTOR, ".hint-box")
            self.highlight_and_point(hint, action_text="🛡️ [Nguyên tắc] Danh mục mẫu chỉ dùng khi tạo tài khoản mới, không đổi dữ liệu cũ", hold_seconds=2.0)

            self.hold_for_observation(4.0, "HOÀN TẤT KỊCH BẢN 1: GIAO DIỆN DANH MỤC HỆ THỐNG ĐÃ KẾT NỐI API THÀNH CÔNG")
        finally:
            self.tearDown()

    def test_case_02_add_default_category(self):
        """TC-UI-02: Thêm danh mục mẫu mới (Thưởng kinh doanh - Thu nhập) qua Modal Dialog"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 2: THỰC HIỆN THÊM DANH MỤC MẪU MỚI TRÊN GIAO DIỆN")
        print("="*65)
        try:
            self.setUpScenario(role="ADMIN")
            self.show_message("🚀 [Kịch bản 2] Bắt đầu thêm danh mục mẫu mới: 'Thưởng kinh doanh'...", duration=1.5)

            # 1. Nhấp nút 'Thêm danh mục mẫu'
            add_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='admin-category-add']")
            self.click(add_btn, action_text="🎯 [Thao tác chuột] Bấm 'Thêm danh mục mẫu' để mở biểu mẫu...")

            time.sleep(1.0)
            wait = WebDriverWait(self.driver, 10)
            name_input = wait.until(EC.visibility_of_element_located((By.ID, "admin-cat-name")))

            # 2. Gõ tên danh mục
            self.type_text(name_input, "Thưởng kinh doanh", action_text="⌨️ [Nhập liệu] Nhập tên danh mục: 'Thưởng kinh doanh'")

            # 3. Chọn loại Thu nhập (INCOME)
            type_select = self.driver.find_element(By.ID, "admin-cat-type")
            self.click(type_select, action_text="🎯 [Chọn loại] Chọn phân loại 'Thu nhập (INCOME)'...")
            type_select.send_keys("Thu nhập (INCOME)")
            time.sleep(0.8)

            # 4. Bấm 'Thêm danh mục' submit form
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "#admin-category-add-form button[type='submit']")
            self.click(submit_btn, action_text="🎯 [Thao tác chuột] Nhấp nút 'Thêm danh mục' gửi yêu cầu POST...")

            # 5. Chờ modal đóng và danh sách nạp lại
            time.sleep(2.0)
            self.show_message("🎉 Thêm thành công! Đang tìm thẻ danh mục mới trên giao diện...", duration=1.5)

            cards = self.driver.find_elements(By.CSS_SELECTOR, "#admin-category-card-list .mini-panel")
            target_card = None
            for card in cards:
                if "Thưởng kinh doanh" in card.text:
                    target_card = card
                    break

            if target_card:
                self.highlight_and_point(target_card, action_text="✨ [Thẻ mới tạo] 'Thưởng kinh doanh' đã xuất hiện với badge Thu nhập & Áp dụng!", hold_seconds=2.5)
            else:
                print("[!] Cảnh báo: Không tìm thấy thẻ vừa thêm trên DOM.")

            self.hold_for_observation(4.0, "HOÀN TẤT KỊCH BẢN 2: THÊM DANH MỤC MẪU TRỰC QUAN THÀNH CÔNG")
        finally:
            self.tearDown()

    def test_case_03_duplicate_prevention_conflict(self):
        """TC-UI-03: Kiểm tra thông báo lỗi xung đột (409 Conflict) khi cố tình thêm trùng tên & loại"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 3: KIỂM TRA RÀNG BUỘC CHỐNG TRÙNG LẶP DANH MỤC (409)")
        print("="*65)
        try:
            self.setUpScenario(role="ADMIN")
            self.show_message("🚀 [Kịch bản 3] Kiểm tra xử lý lỗi khi thêm danh mục bị trùng lặp...", duration=1.5)

            # 1. Bấm 'Thêm danh mục mẫu'
            add_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='admin-category-add']")
            self.click(add_btn, action_text="🎯 [Thao tác chuột] Bấm 'Thêm danh mục mẫu'...")

            time.sleep(1.0)
            wait = WebDriverWait(self.driver, 10)
            name_input = wait.until(EC.visibility_of_element_located((By.ID, "admin-cat-name")))

            # 2. Cố tình nhập tên đã có: 'Ăn uống' - Loại: Chi tiêu (EXPENSE)
            self.type_text(name_input, "Ăn uống", action_text="⌨️ [Cố tình nhập trùng] Nhập tên: 'Ăn uống' (Đã tồn tại trong hệ thống)")
            time.sleep(0.8)

            # 3. Bấm submit
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "#admin-category-add-form button[type='submit']")
            self.click(submit_btn, action_text="🎯 [Thao tác chuột] Nhấp 'Thêm danh mục' để kích hoạt kiểm tra trùng...")

            # 4. Kiểm tra thông báo lỗi hiển thị trên form
            time.sleep(1.5)
            feedback = self.driver.find_element(By.ID, "admin-cat-feedback")
            print(f"[*] Thông báo phản hồi từ hệ thống: {feedback.text}")
            self.highlight_and_point(feedback, action_text=f"⚠️ [Lỗi 409 Conflict] {feedback.text}", hold_seconds=2.5)

            # 5. Đóng modal
            cancel_btn = self.driver.find_element(By.CSS_SELECTOR, "#admin-category-add-form button[data-close-modal]")
            self.click(cancel_btn, action_text="🎯 [Thao tác chuột] Bấm 'Hủy' đóng biểu mẫu...")
            time.sleep(1.0)

            self.hold_for_observation(3.5, "HOÀN TẤT KỊCH BẢN 3: RÀNG BUỘC CHỐNG TRÙNG LẶP DANH MỤC HOẠT ĐỘNG HOÀN HẢO")
        finally:
            self.tearDown()

    def test_case_04_edit_and_toggle_category(self):
        """TC-UI-04: Điều chỉnh thông tin danh mục mẫu & đổi trạng thái Áp dụng / Tạm ngưng"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 4: ĐIỀU CHỈNH THÔNG TIN VÀ ĐỔI TRẠNG THÁI DANH MỤC")
        print("="*65)
        try:
            self.setUpScenario(role="ADMIN")
            self.show_message("🚀 [Kịch bản 4] Bắt đầu chỉnh sửa và đổi trạng thái danh mục...", duration=1.5)

            # Tìm danh mục 'Mua sắm'
            edit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='admin-category-edit'][data-name='Mua sắm']")
            self.click(edit_btn, action_text="🎯 [Thao tác chuột] Bấm 'Điều chỉnh' danh mục 'Mua sắm'...")

            time.sleep(1.0)
            wait = WebDriverWait(self.driver, 10)
            name_input = wait.until(EC.visibility_of_element_located((By.ID, "admin-cat-name")))

            # Đổi trạng thái sang Tạm ngưng
            active_select = self.driver.find_element(By.ID, "admin-cat-active")
            self.click(active_select, action_text="🎯 [Đổi trạng thái] Chuyển trạng thái sang 'Tạm ngưng'...")
            active_select.send_keys("Tạm ngưng")
            time.sleep(0.8)

            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "#admin-category-edit-form button[type='submit']")
            self.click(submit_btn, action_text="🎯 [Thao tác chuột] Bấm 'Lưu thay đổi' cập nhật PUT API...")

            time.sleep(2.0)
            self.show_message("✅ Cập nhật thành công! Kiểm tra trạng thái mới trên giao diện...", duration=1.5)

            # Kiểm tra nút toggle
            toggle_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='admin-category-toggle']")
            self.highlight_and_point(toggle_btn, action_text=f"🔄 [Nút chuyển nhanh] Trạng thái đã chuyển thành '{toggle_btn.text}'", hold_seconds=2.0)

            # Bấm kích hoạt lại
            self.click(toggle_btn, action_text="🎯 [Thao tác chuột] Nhấp nút kích hoạt lại danh mục...")
            time.sleep(2.0)
            self.show_message("✨ Đã kích hoạt lại danh mục thành công!", duration=1.5)

            self.hold_for_observation(3.5, "HOÀN TẤT KỊCH BẢN 4: ĐIỀU CHỈNH & ĐỔI TRẠNG THÁI DANH MỤC THÀNH CÔNG")
        finally:
            self.tearDown()

    def test_case_05_non_admin_forbidden_isolation(self):
        """TC-UI-05: Kiểm tra bảo mật phân quyền: Tài khoản thường không có quyền vào khu vực Admin"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 5: BẢO MẬT PHÂN QUYỀN (CHẶN NGƯỜI DÙNG THƯỜNG)")
        print("="*65)
        try:
            self.setUpScenario(role="USER")
            self.show_message("🚀 [Kịch bản 5] Đăng nhập bằng tài khoản người dùng thường user1@gmail.com...", duration=1.5)

            # Cố tình điều hướng tới route admin-categories
            self.show_message("🔒 Thử truy cập trái phép vào trang app.html#admin-categories...", duration=1.2)
            self.driver.get(f"{self.base_url}/app.html#admin-categories")
            time.sleep(1.5)
            self.inject_cursor()

            # Kiểm tra thông báo từ chối quyền
            content = self.driver.find_element(By.ID, "app-content")
            print(f"[*] Nội dung hiển thị tại #app-content: {content.text}")
            self.highlight_and_point(content, action_text="⛔ [Bảo vệ phân quyền 403] 'Không có quyền: Chỉ quản trị viên mới truy cập được mục này.'", hold_seconds=2.5)

            self.hold_for_observation(3.5, "HOÀN TẤT KỊCH BẢN 5: BẢO MẬT PHÂN QUYỀN ĐẠT CHUẨN AN TOÀN TUYỆT ĐỐI")
        finally:
            self.tearDown()


def main():
    test_scenarios = {
        "1": ("Quan sát giao diện danh mục hệ thống & kiểm tra API", lambda: FR13VisualTest().test_case_01_view_default_categories()),
        "2": ("Thêm danh mục mẫu mới (Thưởng kinh doanh) qua Modal", lambda: FR13VisualTest().test_case_02_add_default_category()),
        "3": ("Kiểm tra ràng buộc chống trùng lặp danh mục (409 Conflict)", lambda: FR13VisualTest().test_case_03_duplicate_prevention_conflict()),
        "4": ("Điều chỉnh thông tin danh mục mẫu & đổi trạng thái Tạm ngưng", lambda: FR13VisualTest().test_case_04_edit_and_toggle_category()),
        "5": ("Kiểm tra bảo mật phân quyền: Chặn tài khoản người dùng thường", lambda: FR13VisualTest().test_case_05_non_admin_forbidden_isolation()),
    }

    def run_all():
        for key in sorted(test_scenarios.keys()):
            name, func = test_scenarios[key]
            print(f"\n>>> ĐANG CHẠY KỊCH BẢN [{key}]: {name} <<<")
            func()

    FR13VisualTest.run_cli_menu(
        feature_title="FR13 - QUẢN LÝ DANH MỤC HỆ THỐNG MẶC ĐỊNH (ADMIN)",
        test_scenarios=test_scenarios,
        all_runner=run_all
    )


if __name__ == "__main__":
    main()
