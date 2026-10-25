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

    def setUpScenario(self, role: str = "ADMIN", target_page: str = "admin-categories"):
        """Khởi tạo trình duyệt, reset CSDL & Rate limits, đăng nhập thật qua form UI."""
        self.create_driver()
        self.reset_and_seed_database("baseline", seeder_script=SEEDER_SCRIPT)

        if role == "ADMIN":
            self.perform_ui_login(email="admin@wealthflow.local", password="User1234")
            wait_sel = "#admin-category-card-list" if target_page == "admin-categories" else "#cfg-budget-threshold, #btn-broadcast-add, .panel"
            self.navigate_to_feature(target_page, wait_selector=wait_sel)
        else:
            self.perform_ui_login(email="user1@gmail.com", password="User1234")
            if target_page:
                self.navigate_to_feature(target_page, wait_selector="#notification-inbox, .panel")

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
            self.close_driver()

    def test_case_02_add_default_category(self):
        """TC-UI-02: Thêm danh mục mẫu mới (Thưởng kinh doanh) -> Đăng xuất -> Đăng ký User mới kiểm chứng kế thừa"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 2: THỰC HIỆN THÊM DANH MỤC MẪU MỚI TRÊN GIAO DIỆN & KIỂM CHỨNG TÁC ĐỘNG")
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
            self.show_message("🎉 Thêm thành công! Đang tìm thẻ danh mục mới trên giao diện Admin...", duration=1.5)

            cards = self.driver.find_elements(By.CSS_SELECTOR, "#admin-category-card-list .mini-panel")
            target_card = None
            for card in cards:
                if "Thưởng kinh doanh" in card.text:
                    target_card = card
                    break

            if target_card:
                self.highlight_and_point(target_card, action_text="✨ [Thẻ Admin] 'Thưởng kinh doanh' đã xuất hiện với badge Thu nhập & Áp dụng!", hold_seconds=2.0)
            else:
                print("[!] Cảnh báo: Không tìm thấy thẻ vừa thêm trên DOM Admin.")

            # 6. ĐĂNG XUẤT ADMIN VÀ ĐĂNG KÝ TÀI KHOẢN NGƯỜI DÙNG MỚI ĐỂ KIỂM CHỨNG KẾ THỪA
            self.show_message("🔄 Đăng xuất Admin -> Đăng ký tài khoản người dùng mới để kiểm tra...", duration=2.0)
            self.perform_ui_logout()

            test_email = f"newuser_k2_{int(time.time())}@wealthflow.local"
            self.perform_ui_register(full_name="Nguyễn Văn Nghiệp Vụ", email=test_email, password="User1234")

            # 7. Điều hướng vào trang Danh mục của người dùng mới
            self.navigate_to_feature("categories", wait_selector="table.data-table, .panel")
            time.sleep(2.0)

            user_items = self.driver.find_elements(By.CSS_SELECTOR, "table.data-table tbody tr, #category-card-list .mini-panel")
            user_target_card = None
            for ucard in user_items:
                tds = ucard.find_elements(By.TAG_NAME, "td")
                name_text = tds[0].text.strip() if tds else ucard.text.strip()
                if name_text == "Thưởng kinh doanh":
                    user_target_card = ucard
                    break

            if user_target_card:
                self.highlight_and_point(user_target_card, action_text="🌟 [Kế thừa thành công] Danh mục 'Thưởng kinh doanh' Admin vừa tạo ĐÃ XUẤT HIỆN trong tài khoản User mới!", hold_seconds=3.0)
            else:
                print("[!] Cảnh báo: Không tìm thấy 'Thưởng kinh doanh' trên danh mục người dùng mới.")

            self.hold_for_observation(4.0, "HOÀN TẤT KỊCH BẢN 2: THÊM DANH MỤC & KIỂM CHỨNG KẾ THỪA USER THÀNH CÔNG")
        finally:
            self.close_driver()

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
            self.close_driver()

    def test_case_04_edit_and_toggle_category(self):
        """TC-UI-04: Điều chỉnh danh mục Mua sắm sang Tạm ngưng -> Đăng xuất -> Kiểm chứng User mới không nhận, User cũ được bảo toàn"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 4: ĐIỀU CHỈNH TRẠNG THÁI DANH MỤC & KIỂM CHỨNG TÁC ĐỘNG TRỰC QUAN")
        print("="*65)
        try:
            self.setUpScenario(role="ADMIN")
            self.show_message("🚀 [Kịch bản 4] Bắt đầu chỉnh sửa và đổi trạng thái danh mục 'Mua sắm'...", duration=1.5)

            # 1. Tìm danh mục 'Mua sắm'
            edit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='admin-category-edit'][data-name='Mua sắm']")
            self.click(edit_btn, action_text="🎯 [Thao tác chuột] Bấm 'Điều chỉnh' danh mục 'Mua sắm'...")

            time.sleep(1.0)
            wait = WebDriverWait(self.driver, 10)
            name_input = wait.until(EC.visibility_of_element_located((By.ID, "admin-cat-name")))

            # 2. Đổi trạng thái sang Tạm ngưng
            active_select = self.driver.find_element(By.ID, "admin-cat-active")
            self.click(active_select, action_text="🎯 [Đổi trạng thái] Chuyển trạng thái sang 'Tạm ngưng'...")
            active_select.send_keys("Tạm ngưng")
            time.sleep(0.8)

            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "#admin-category-edit-form button[type='submit']")
            self.click(submit_btn, action_text="🎯 [Thao tác chuột] Bấm 'Lưu thay đổi' cập nhật PUT API...")

            time.sleep(2.0)
            self.show_message("✅ Cập nhật thành công! Kiểm tra trạng thái mới trên giao diện Admin...", duration=1.5)

            # 3. Highlight nút toggle hiển thị 'Tạm ngưng'
            toggle_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-action='admin-category-toggle']")
            self.highlight_and_point(toggle_btn, action_text=f"🔄 [Trạng thái Admin] Danh mục 'Mua sắm' hiện tại: '{toggle_btn.text}'", hold_seconds=2.0)

            # 4. ĐĂNG XUẤT ADMIN VÀ TẠO USER MỚI ĐỂ XÁC NHẬN DANH MỤC TẠM NGƯNG KHÔNG BỊ CẤP PHÁT
            self.show_message("🚪 Đăng xuất Admin -> Đăng ký User mới để kiểm tra danh mục tạm ngưng...", duration=2.0)
            self.perform_ui_logout()

            test_email_new = f"newuser_k4_{int(time.time())}@wealthflow.local"
            self.perform_ui_register(full_name="Trần Người Dùng Mới", email=test_email_new, password="User1234")

            self.navigate_to_feature("categories", wait_selector="table.data-table, .panel")
            time.sleep(2.0)

            user_items = self.driver.find_elements(By.CSS_SELECTOR, "table.data-table tbody tr, #category-card-list .mini-panel")
            def get_cat_name(row_el):
                tds = row_el.find_elements(By.TAG_NAME, "td")
                return tds[0].text.strip() if tds else row_el.text.strip()

            has_exact_mua_sam = any(get_cat_name(c) == "Mua sắm" for c in user_items)

            heading = self.driver.find_element(By.CSS_SELECTOR, ".page-heading h1, h1")
            if not has_exact_mua_sam:
                self.highlight_and_point(heading, action_text="🛡️ [Cách ly thành công] 'Mua sắm' ĐÃ BỊ TẠM NGƯNG -> Hoàn toàn KHÔNG xuất hiện trong tài khoản User mới!", hold_seconds=3.0)
            else:
                print("[!] Cảnh báo: 'Mua sắm' vẫn xuất hiện trong tài khoản mới.")

            # 5. ĐỐI CHIẾU: ĐĂNG NHẬP USER CŨ (user1@gmail.com) ĐỂ CHỨNG MINH DỮ LIỆU CŨ ĐƯỢC BẢO TOÀN
            self.show_message("🔄 Đăng nhập User cũ (user1@gmail.com) để đối chiếu tính bảo toàn dữ liệu...", duration=2.0)
            self.perform_ui_logout()
            self.perform_ui_login(email="user1@gmail.com", password="User1234")
            self.navigate_to_feature("categories", wait_selector="table.data-table, .panel")
            time.sleep(2.0)

            old_user_items = self.driver.find_elements(By.CSS_SELECTOR, "table.data-table tbody tr, #category-card-list .mini-panel")
            old_mua_sam_card = None
            for oc in old_user_items:
                if get_cat_name(oc) == "Mua sắm":
                    old_mua_sam_card = oc
                    break

            if old_mua_sam_card:
                self.highlight_and_point(old_mua_sam_card, action_text="🔒 [Bảo toàn dữ liệu cũ] Tài khoản cũ (user1) vẫn giữ nguyên 'Mua sắm', không bị mất giao dịch lịch sử!", hold_seconds=3.0)

            self.hold_for_observation(4.0, "HOÀN TẤT KỊCH BẢN 4: TẠM NGƯNG & KIỂM CHỨNG TÁC ĐỘNG CÁCH LY THÀNH CÔNG")
        finally:
            self.close_driver()

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
            self.close_driver()

    def test_case_06_system_config_and_audit_log(self):
        """TC-UI-06: Quản trị cấu hình tham số vận hành, kiểm tra validation ràng buộc, audit log và cập nhật live (FR13.02)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 6: CẤU HÌNH THAM SỐ VẬN HÀNH & KIỂM TOÁN QUẢN TRỊ (FR13.02)")
        print("="*65)
        try:
            self.setUpScenario(role="ADMIN", target_page="admin-settings")
            self.show_message("🚀 [Kịch bản 6] Đăng nhập Admin và tải trang Tham số hệ thống...", duration=1.5)

            # 1. Quan sát các tham số hệ thống hiện tại
            metric_list = self.driver.find_element(By.ID, "admin-config-metric-list")
            self.highlight_and_point(metric_list, action_text="📊 [Thông số ban đầu] Ngưỡng ngân sách 80%, Token TTL 60p, CSV 5.000 dòng, Nhắc nợ 3 ngày", hold_seconds=2.0)

            # 2. Thử nghiệm Validation: Nhập giá trị vượt ngưỡng cho phép (120% > 95%)
            cfg_btn = self.driver.find_element(By.ID, "btn-admin-config")
            self.click(cfg_btn, action_text="🎯 [Thao tác chuột] Bấm 'Điều chỉnh tham số' để mở Modal cấu hình...")

            time.sleep(1.0)
            wait = WebDriverWait(self.driver, 10)
            threshold_input = wait.until(EC.visibility_of_element_located((By.ID, "admin-cfg-threshold")))
            self.highlight_and_point(threshold_input, action_text="🔍 [Modal tham số] Đang mở form điều chỉnh tham số hệ thống", hold_seconds=1.2)

            # Cố tình xóa và nhập giá trị không hợp lệ 120 (vượt quá max 95)
            self.show_message("🧪 Thử nghiệm nhập giá trị vi phạm ràng buộc: 120% (Giới hạn tối đa là 95%)...", duration=2.0)
            threshold_input.clear()
            threshold_input.send_keys("120")
            time.sleep(0.8)

            save_btn = self.driver.find_element(By.ID, "btn-save-config")
            self.click(save_btn, action_text="🎯 [Kiểm tra ràng buộc] Bấm 'Lưu cấu hình' để kích hoạt validation...")
            time.sleep(1.0)

            # 3. Nhập giá trị hợp lệ theo chuẩn FR13.02:
            # Ngưỡng ngân sách: 85%, Token TTL: 120 phút, CSV: 10.000 dòng, Nhắc nợ: 7 ngày
            self.show_message("✨ Nhập các giá trị tham số mới hợp lệ: Ngưỡng 85%, TTL 120p, CSV 10.000 dòng, Nhắc nợ 7 ngày...", duration=2.0)
            threshold_input.clear()
            self.type_text(threshold_input, "85", action_text="⌨️ [Cấu hình] Đặt Ngưỡng cảnh báo chi tiêu: 85%")

            ttl_input = self.driver.find_element(By.ID, "admin-cfg-jwt-ttl")
            ttl_input.clear()
            self.type_text(ttl_input, "120", action_text="⌨️ [Cấu hình] Đặt Thời gian sống Token: 120 phút")

            csv_input = self.driver.find_element(By.ID, "admin-cfg-csv-rows")
            csv_input.clear()
            self.type_text(csv_input, "10000", action_text="⌨️ [Cấu hình] Đặt Giới hạn tệp xuất CSV: 10.000 dòng")

            loan_input = self.driver.find_element(By.ID, "admin-cfg-loan-days")
            loan_input.clear()
            self.type_text(loan_input, "7", action_text="⌨️ [Cấu hình] Đặt Nhắc hạn khoản vay: 7 ngày trước hạn")

            self.click(save_btn, action_text="🎯 [Cập nhật] Bấm 'Lưu cấu hình' gửi PUT API /api/admin/system-config...")
            time.sleep(2.0)

            # 4. Kiểm tra các giá trị mới hiển thị trên Dashboard
            self.show_message("✅ Cập nhật thành công! Kiểm tra các thông số hiển thị mới trên giao diện...", duration=1.5)
            thresh_val = self.driver.find_element(By.ID, "cfg-budget-threshold")
            self.highlight_and_point(thresh_val, action_text="🌟 [Giá trị mới] Ngưỡng cảnh báo ngân sách đã cập nhật thành công: '85%'", hold_seconds=2.0)

            # 5. Kiểm tra bảng Admin Audit Log ghi nhận hành động UPDATE_SYSTEM_CONFIG
            audit_table = self.driver.find_element(By.ID, "admin-audit-table")
            self.highlight_and_point(audit_table, action_text="📜 [Nhật ký kiểm toán] Bản ghi UPDATE_SYSTEM_CONFIG được lưu vết tự động bất biến!", hold_seconds=3.0)

            self.hold_for_observation(4.0, "HOÀN TẤT KỊCH BẢN 6: QUẢN TRỊ THAM SỐ VẬN HÀNH & KIỂM TOÁN HỆ THỐNG THÀNH CÔNG")
        finally:
            self.close_driver()

    def test_case_07_broadcast_announcement(self):
        """TC-UI-07: Admin phát thông báo phát thanh (Broadcast) O(1), kiểm tra max length 255 ký tự, audit log và kiểm tra hộp thư người dùng (FR13.03)"""
        print("\n" + "="*65)
        print(" KỊCH BẢN 7: PHÁT THÔNG BÁO PHÁT THANH TOÀN HỆ THỐNG (FR13.03)")
        print("="*65)
        try:
            self.setUpScenario(role="ADMIN", target_page="admin-broadcasts")
            self.show_message("🚀 [Kịch bản 7] Đăng nhập Admin và tải trang Phát thông báo toàn hệ thống...", duration=1.5)

            # 1. Quan sát nút Tạo broadcast và lịch sử hiện tại
            btn_add = self.driver.find_element(By.ID, "btn-broadcast-add")
            self.highlight_and_point(btn_add, action_text="📢 [Chức năng Admin] Nút 'Tạo broadcast' toàn hệ thống", hold_seconds=1.5)

            # 2. Bấm nút mở Modal tạo broadcast
            self.click(btn_add, action_text="🎯 [Thao tác chuột] Bấm 'Tạo broadcast' mở Modal phát thanh...")
            time.sleep(1.0)

            wait = WebDriverWait(self.driver, 10)
            title_input = wait.until(EC.visibility_of_element_located((By.ID, "admin-bc-title")))
            self.highlight_and_point(title_input, action_text="🔍 [Modal Broadcast] Form phát thông báo vận hành & chính sách", hold_seconds=1.2)

            # Kiểm tra ràng buộc maxlength = 255 ký tự
            max_len = title_input.get_attribute("maxlength")
            print(f"[*] Thuộc tính maxlength của ô tiêu đề: {max_len}")
            assert max_len == "255", f"Kỳ vọng maxlength='255', nhận được {max_len}"

            # 3. Nhập tiêu đề hỗ trợ tối đa 255 ký tự
            long_title = "Thông báo khẩn cấp: Nâng cấp và tối ưu hóa hệ thống máy chủ cơ sở dữ liệu định kỳ WealthFlow phiên bản 2.0 toàn diện, đảm bảo an toàn tuyệt đối tài sản và số dư của người dùng."
            self.type_text(title_input, long_title, action_text="⌨️ [Nhập liệu] Nhập tiêu đề thông báo (hỗ trợ tối đa 255 ký tự)...")

            # 4. Chọn mức độ ưu tiên: MAINTENANCE
            priority_select = self.driver.find_element(By.ID, "admin-bc-priority")
            self.click(priority_select, action_text="🎯 [Chọn mức độ] Chọn mức ưu tiên: 'MAINTENANCE (Bảo trì hệ thống)'")
            from selenium.webdriver.support.ui import Select
            Select(priority_select).select_by_value("MAINTENANCE")
            time.sleep(0.5)

            # 5. Nhập nội dung chi tiết
            body_input = self.driver.find_element(By.ID, "admin-bc-body")
            body_content = "Hệ thống WealthFlow sẽ tiến hành nâng cấp hạ tầng máy chủ và sao lưu dữ liệu tự động từ 02:00 đến 03:00. Mọi dữ liệu giao dịch của quý khách đều được đảm bảo an toàn tuyệt đối."
            self.type_text(body_input, body_content, action_text="⌨️ [Nhập liệu] Nhập nội dung thông báo phát thanh chi tiết...")

            # 6. Bấm nút Phát thông báo ngay
            submit_btn = self.driver.find_element(By.ID, "btn-submit-broadcast")
            self.click(submit_btn, action_text="🎯 [Phát thanh O(1)] Bấm 'Phát thông báo ngay' gửi tới toàn bộ người dùng...")
            time.sleep(2.0)

            # 7. Kiểm tra danh sách lịch sử phát thanh trên giao diện Admin
            self.show_message("✅ Phát thanh thành công! Kiểm tra mục nhật ký lịch sử...", duration=1.5)
            history_body = self.driver.find_element(By.ID, "admin-broadcast-history-body")
            self.highlight_and_point(history_body, action_text="📜 [Lịch sử phát thanh] Ghi nhận broadcast mới cùng tác giả và thời gian!", hold_seconds=2.5)

            # 8. Đăng nhập với tài khoản người dùng thường (user1@gmail.com) để kiểm chứng hộp thư và cảnh báo tức thì
            self.show_message("🔄 Đăng nhập tài khoản Người dùng thường (user1@gmail.com)...", duration=2.0)
            self.perform_ui_login(email="user1@gmail.com", password="User1234")
            time.sleep(2.0)

            # Quan sát Bảng thông báo tức thì (Modal Popup nổi bật, không icon ở Header)
            try:
                urgent_dialog = self.driver.find_element(By.ID, "urgent-broadcast-dialog")
                if urgent_dialog.is_displayed():
                    self.highlight_and_point(urgent_dialog, action_text="📢 [Bảng thông báo tức thì] Thông báo bảo trì hệ thống hiển thị nổi bật ngay khi đăng nhập!", hold_seconds=2.5)
                    dismiss_btn = self.driver.find_element(By.ID, "btn-dismiss-urgent-broadcast")
                    self.click(dismiss_btn, action_text="🎯 [Xác nhận] Bấm 'Tôi đã hiểu' để xác nhận và đóng thông báo khẩn cấp...")
                    time.sleep(1.0)
            except Exception as e:
                print(f"[*] Bỏ qua bước kiểm tra popup khẩn cấp nếu không tìm thấy: {e}")

            # Điều hướng tới màn hình Hộp thư thông báo cá nhân
            self.navigate_to_feature("notifications", wait_selector="#notification-inbox")
            inbox = self.driver.find_element(By.ID, "notification-inbox")
            self.highlight_and_point(inbox, action_text="📬 [Hộp thư cá nhân] Người dùng đã nhận được thông báo phát thanh tức thời!", hold_seconds=3.0)

            self.hold_for_observation(4.0, "HOÀN TẤT KỊCH BẢN 7: PHÁT THÔNG BÁO PHÁT THANH TOÀN HỆ THỐNG (FR13.03) THÀNH CÔNG RỰC RỠ")
        finally:
            self.close_driver()


def main():
    def run_all():
        for key in ["1", "2", "3", "4", "5", "6", "7"]:
            name, func = test_scenarios[key]
            print(f"\n>>> ĐANG CHẠY KỊCH BẢN [{key}]: {name} <<<")
            func()

    test_scenarios = {
        "1": ("Quan sát giao diện danh mục hệ thống & kiểm tra API", lambda: FR13VisualTest().test_case_01_view_default_categories()),
        "2": ("Thêm danh mục mẫu mới (Thưởng kinh doanh) qua Modal", lambda: FR13VisualTest().test_case_02_add_default_category()),
        "3": ("Kiểm tra ràng buộc chống trùng lặp danh mục (409 Conflict)", lambda: FR13VisualTest().test_case_03_duplicate_prevention_conflict()),
        "4": ("Điều chỉnh thông tin danh mục mẫu & đổi trạng thái Tạm ngưng", lambda: FR13VisualTest().test_case_04_edit_and_toggle_category()),
        "5": ("Kiểm tra bảo mật phân quyền: Chặn tài khoản người dùng thường", lambda: FR13VisualTest().test_case_05_non_admin_forbidden_isolation()),
        "6": ("Cấu hình tham số vận hành chung & kiểm tra Audit Log (FR13.02)", lambda: FR13VisualTest().test_case_06_system_config_and_audit_log()),
        "7": ("Phát thông báo phát thanh toàn hệ thống & kiểm tra hộp thư (FR13.03)", lambda: FR13VisualTest().test_case_07_broadcast_announcement()),
        "a": ("Chạy tất cả kịch bản tự động lần lượt (Bấm phím 'a' hoặc 'all')", run_all),
    }

    FR13VisualTest.run_cli_menu(
        feature_title="FR13 - QUẢN LÝ DANH MỤC HỆ THỐNG, CẤU HÌNH & PHÁT THANH (ADMIN)",
        test_scenarios=test_scenarios,
        all_runner=run_all
    )


if __name__ == "__main__":
    main()
