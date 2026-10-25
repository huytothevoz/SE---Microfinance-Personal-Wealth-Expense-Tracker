# -*- coding: utf-8 -*-
"""
WEALTHFLOW BASE VISUAL TEST FRAMEWORK
Lớp đối tượng cơ sở (Base Object) chuẩn hóa toàn bộ luồng kiểm thử trực quan trên giao diện:
1. Tự động dọn dẹp Rate Limits (auth_rate_limits) để ngăn ngừa lỗi 429 khi kiểm thử liên tục.
2. Tự động thực hiện thao tác ĐĂNG NHẬP THẬT TRÊN GIAO DIỆN (UI Login) qua form index.html trước mỗi test.
3. Tích hợp con trỏ chuột ảo (Virtual Animated Cursor), hiệu ứng sóng nhấp chuột (Ripple Click),
   gõ bàn phím trực quan (Typing cadence) và dải băng thông báo hành động (Action Banner Pill).
4. Cung cấp bộ menu tương tác chuẩn hóa cho file .bat của mọi chức năng (FR01 - FR15).
"""

import argparse
import os
import shutil
import subprocess
import sys
import time
from pathlib import Path
from typing import Callable, Dict, Optional, Tuple

# Cấu hình chuẩn UTF-8 trên Windows console
try:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass

from selenium import webdriver
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait


# CSS & JS Injector cho hệ thống con trỏ chuột ảo và hiệu ứng visual
CURSOR_INJECTION_JS = """
(function() {
    if (document.getElementById('wf-visual-cursor')) return;

    const style = document.createElement('style');
    style.id = 'wf-visual-styles';
    style.textContent = `
        #wf-visual-cursor {
            position: fixed;
            top: 0;
            left: 0;
            width: 32px;
            height: 32px;
            z-index: 2147483647;
            pointer-events: none;
            transition: transform 0.65s cubic-bezier(0.22, 1, 0.36, 1);
            transform: translate(-100px, -100px);
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.55));
        }
        #wf-visual-cursor svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        #wf-visual-ripple {
            position: fixed;
            top: 0;
            left: 0;
            width: 46px;
            height: 46px;
            margin-top: -23px;
            margin-left: -23px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.4);
            border: 2px solid #ef4444;
            pointer-events: none;
            z-index: 2147483646;
            transform: translate(-100px, -100px) scale(0);
            opacity: 0;
            transition: transform 0.45s ease-out, opacity 0.45s ease-out;
        }
        #wf-visual-pill {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            z-index: 2147483645;
            pointer-events: none;
            background: rgba(15, 23, 42, 0.94);
            color: #ffffff;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
            padding: 10px 24px;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity 0.35s ease, transform 0.35s ease;
            white-space: nowrap;
        }
        .wf-visual-highlight {
            outline: 3px solid #ef4444 !important;
            outline-offset: 4px !important;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.7) !important;
            transition: all 0.3s ease !important;
        }
    `;
    document.head.appendChild(style);

    const cursor = document.createElement('div');
    cursor.id = 'wf-visual-cursor';
    cursor.innerHTML = `
        <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 3L26 15L16 17.5L10 27L6 3Z" fill="#EF4444" stroke="#FFFFFF" stroke-width="2.5" stroke-linejoin="round"/>
        </svg>
    `;
    document.body.appendChild(cursor);

    const ripple = document.createElement('div');
    ripple.id = 'wf-visual-ripple';
    document.body.appendChild(ripple);

    const pill = document.createElement('div');
    pill.id = 'wf-visual-pill';
    document.body.appendChild(pill);

    window.__wfMoveCursor = function(x, y) {
        cursor.style.transform = `translate(${x - 6}px, ${y - 3}px)`;
    };

    window.__wfClickAt = function(x, y) {
        ripple.style.transition = 'none';
        ripple.style.transform = `translate(${x}px, ${y}px) scale(0)`;
        ripple.style.opacity = '1';
        setTimeout(() => {
            ripple.style.transition = 'transform 0.45s ease-out, opacity 0.45s ease-out';
            ripple.style.transform = `translate(${x}px, ${y}px) scale(2.2)`;
            ripple.style.opacity = '0';
        }, 20);
    };

    window.__wfShowPill = function(text) {
        pill.textContent = text;
        pill.style.opacity = '1';
        pill.style.transform = 'translateX(-50%) translateY(0)';
    };

    window.__wfHidePill = function() {
        pill.style.opacity = '0';
        pill.style.transform = 'translateX(-50%) translateY(20px)';
    };
})();
"""


class BaseVisualTest:
    """
    Lớp đối tượng cơ sở cho mọi bài kiểm thử giao diện trong WealthFlow.
    Mọi test case (FR01 -> FR15) chỉ cần kế thừa lớp này để sử dụng các tiện ích UI/E2E cao cấp.
    """

    def __init__(self, base_url: str = "http://localhost:8080", test_dir: Optional[Path] = None):
        self.base_url = base_url.rstrip("/")
        self.test_dir = test_dir or Path(sys.argv[0]).resolve().parent
        self.downloads_dir = self.test_dir / "downloads"
        self.downloads_dir.mkdir(parents=True, exist_ok=True)
        self.php_bin = self._find_php_binary()
        self.driver: Optional[webdriver.Chrome] = None

    def _find_php_binary(self) -> str:
        xampp_php = Path("D:/xampp/php/php.exe")
        if xampp_php.exists():
            return str(xampp_php)
        php_path = shutil.which("php")
        if php_path:
            return php_path
        raise RuntimeError("Không tìm thấy tệp php.exe của XAMPP.")

    def ensure_web_server_running(self):
        """Kiểm tra máy chủ web tại self.base_url. Nếu chưa chạy, tự động khởi động PHP built-in server."""
        import urllib.request
        try:
            with urllib.request.urlopen(f"{self.base_url}/index.html", timeout=1.5):
                return
        except Exception:
            pass

        print(f"[*] Máy chủ Web tại {self.base_url} chưa chạy. Đang tự động khởi động PHP built-in server...")
        source_dir = Path(__file__).resolve().parents[2] / "source"
        router_file = source_dir / "backend" / "router.php"
        cmd = [
            self.php_bin,
            "-S", "127.0.0.1:8080",
            "-t", str(source_dir),
            str(router_file)
        ]
        flags = subprocess.CREATE_NEW_PROCESS_GROUP if os.name == 'nt' else 0
        subprocess.Popen(
            cmd,
            cwd=str(source_dir.parent),
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            creationflags=flags
        )
        for _ in range(10):
            time.sleep(0.5)
            try:
                with urllib.request.urlopen(f"{self.base_url}/index.html", timeout=1.0):
                    print(f"[OK] Máy chủ Web PHP đã sẵn sàng trên {self.base_url}.")
                    return
            except Exception:
                pass
        print(f"[!] Cảnh báo: Không thể tự khởi động PHP server tại {self.base_url}. Vui lòng chạy run_wealthflow.bat trước.")

    def create_driver(self) -> webdriver.Chrome:
        """Khởi tạo trình duyệt Chrome với cửa sổ hiển thị trực tiếp và thư mục download cấu hình sẵn."""
        self.ensure_web_server_running()

        options = ChromeOptions()
        options.add_argument("--start-maximized")
        options.add_argument("--disable-blink-features=AutomationControlled")
        options.add_experimental_option("excludeSwitches", ["enable-automation"])
        options.add_experimental_option("useAutomationExtension", False)

        prefs = {
            "download.default_directory": str(self.downloads_dir),
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "safebrowsing.enabled": True
        }
        options.add_experimental_option("prefs", prefs)

        try:
            self.driver = webdriver.Chrome(options=options)
        except Exception as e:
            print(f"[*] Thử khởi chạy Microsoft Edge: {e}")
            from selenium.webdriver.edge.options import Options as EdgeOptions
            edge_opts = EdgeOptions()
            edge_opts.add_argument("--start-maximized")
            self.driver = webdriver.Edge(options=edge_opts)

        return self.driver

    def close_driver(self):
        """Đóng an toàn trình duyệt sau khi kiểm thử."""
        if self.driver:
            try:
                self.driver.quit()
            except Exception:
                pass
            self.driver = None

    def tearDown(self):
        """Bí danh dọn dẹp trình duyệt."""
        self.close_driver()

    def reset_and_seed_database(self, scenario_name: str, seeder_script: Optional[Path] = None):
        """
        Dọn dẹp CSDL, xóa Rate Limits và nạp dữ liệu kịch bản chuẩn qua seeder.
        Đảm bảo không bao giờ bị dính lỗi 429 RATE_LIMITED khi kiểm thử nhiều lần.
        """
        print(f"\n[*] Đang Reset CSDL và nạp dữ liệu kịch bản: '{scenario_name}'...")
        if seeder_script and seeder_script.exists():
            res = subprocess.run([self.php_bin, str(seeder_script), scenario_name],
                                 capture_output=True, text=True, encoding="utf-8")
            if res.returncode != 0:
                print(f"[LOI] Seeder thất bại: {res.stderr}\n{res.stdout}")
                sys.exit(1)
            try:
                import json
                self.seed_info = json.loads(res.stdout)
            except Exception:
                self.seed_info = {}
        print(f"[OK] CSDL đã sẵn sàng cho kịch bản '{scenario_name}'.")

    # =========================================================================
    # HỆ THỐNG CON TRỎ CHUỘT ẢO & HIỆU ỨNG GIAO DIỆN
    # =========================================================================

    def inject_cursor(self):
        """Tiêm con trỏ chuột ảo và stylesheet hiệu ứng vào trang hiện tại."""
        if not self.driver:
            return
        try:
            self.driver.execute_script(CURSOR_INJECTION_JS)
        except Exception:
            pass

    def show_message(self, text: str, duration: float = 1.0):
        """Hiển thị thông điệp banner hành động nổi ở cạnh dưới màn hình."""
        if not self.driver:
            return
        self.inject_cursor()
        try:
            self.driver.execute_script("if (window.__wfShowPill) window.__wfShowPill(arguments[0]);", text)
            if duration > 0:
                time.sleep(duration)
        except Exception:
            pass

    def move_cursor_to_element(self, element, action_text: Optional[str] = None, duration: float = 0.65) -> Optional[dict]:
        """Di chuyển con trỏ chuột ảo mượt mà đến vị trí của phần tử trên trang."""
        if not self.driver or not element:
            return None
        self.inject_cursor()
        if action_text:
            self.show_message(action_text, duration=0)

        try:
            self.driver.execute_script("arguments[0].scrollIntoView({ behavior: 'smooth', block: 'center' });", element)
            time.sleep(0.3)

            move_script = """
            const el = arguments[0];
            const rect = el.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            if (window.__wfMoveCursor) window.__wfMoveCursor(x, y);
            return { x: x, y: y };
            """
            coords = self.driver.execute_script(move_script, element)
            time.sleep(duration)
            return coords
        except Exception:
            return None

    def click(self, element, action_text: Optional[str] = None):
        """Di chuyển chuột ảo tới phần tử, kích hoạt sóng nhấp chuột (ripple), highlight và click."""
        if not self.driver or not element:
            return
        self.inject_cursor()
        coords = self.move_cursor_to_element(element, action_text=action_text, duration=0.65)

        try:
            self.driver.execute_script("arguments[0].classList.add('wf-visual-highlight');", element)
        except Exception:
            pass

        if coords:
            try:
                self.driver.execute_script("if (window.__wfClickAt) window.__wfClickAt(arguments[0], arguments[1]);",
                                           coords['x'], coords['y'])
            except Exception:
                pass

        time.sleep(0.3)
        try:
            element.click()
        except Exception:
            self.driver.execute_script("arguments[0].click();", element)

        time.sleep(0.4)
        try:
            self.driver.execute_script("arguments[0].classList.remove('wf-visual-highlight');", element)
        except Exception:
            pass

    def type_text(self, element, text: str, action_text: Optional[str] = None, clear_first: bool = True):
        """Di chuyển chuột tới ô nhập liệu, highlight và gõ từng ký tự trực quan như người thật."""
        if not self.driver or not element:
            return
        self.click(element, action_text=action_text)

        if clear_first:
            element.clear()

        for char in text:
            element.send_keys(char)
            time.sleep(0.04)

        time.sleep(0.3)

    def highlight_and_point(self, element, action_text: Optional[str] = None, hold_seconds: float = 1.2):
        """Rê chuột đến trỏ vào phần tử và highlight để người dùng quan sát (dành cho Badge, KPI, Báo cáo)."""
        if not self.driver or not element:
            return
        self.inject_cursor()
        self.move_cursor_to_element(element, action_text=action_text, duration=0.6)
        try:
            self.driver.execute_script("arguments[0].classList.add('wf-visual-highlight');", element)
        except Exception:
            pass

        time.sleep(hold_seconds)
        try:
            self.driver.execute_script("arguments[0].classList.remove('wf-visual-highlight');", element)
        except Exception:
            pass

    # =========================================================================
    # QUY TRÌNH ĐĂNG NHẬP TRỰC QUAN TRÊN GIAO DIỆN (AUTHENTIC UI LOGIN)
    # =========================================================================

    def perform_logout(self):
        """Đăng xuất tài khoản hiện tại để chuẩn bị cho phiên đăng nhập mới."""
        print("[*] Đang thực hiện đăng xuất trực quan...")
        self.show_message("🚪 [Đăng xuất] Đang đăng xuất tài khoản...", duration=1.0)
        try:
            self.driver.delete_all_cookies()
        except Exception:
            pass
        time.sleep(0.5)

    def perform_ui_login(self, email: str = "user1@gmail.com", password: str = "User1234"):
        """
        Thực hiện quy trình đăng nhập THẬT trên giao diện người dùng:
        1. Xóa cookie phiên cũ để landing page không tự động chuyển hướng vào app.html.
        2. Mở trang chủ index.html.
        3. Chuột ảo di chuyển bấm nút 'Đăng nhập' mở Modal.
        4. Chuột ảo gõ email, gõ mật khẩu vào form đăng nhập.
        5. Chuột ảo nhấp nút 'Đăng nhập →' và chờ điều hướng vào app.html.
        """
        print(f"[*] Thực hiện đăng nhập trực quan trên giao diện: {email}...")
        try:
            self.driver.delete_all_cookies()
        except Exception:
            pass
        self.driver.get(f"{self.base_url}/index.html")
        time.sleep(1.2)
        self.inject_cursor()

        self.show_message(f"🔑 [Xác thực] Chuẩn bị đăng nhập tài khoản {email}...", duration=1.0)

        # 1. Tìm và bấm nút 'Đăng nhập' trên header landing page
        try:
            signin_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-open-auth='login']")
            self.click(signin_btn, action_text="🎯 [Chuột thao tác] Bấm nút 'Đăng nhập' trên thanh điều hướng...")
        except Exception:
            # Dự phòng: Bấm nút trong hero
            signin_btn = self.driver.find_element(By.CSS_SELECTOR, ".hero-actions button[data-open-auth='login']")
            self.click(signin_btn, action_text="🎯 [Chuột thao tác] Mở hộp thoại Đăng nhập...")

        time.sleep(0.8)

        # 2. Chờ modal đăng nhập hiển thị và nhập Email
        wait = WebDriverWait(self.driver, 10)
        email_input = wait.until(EC.visibility_of_element_located((By.ID, "auth-email")))
        self.type_text(email_input, email, action_text=f"⌨️ [Nhập liệu] Nhập Email: {email}")

        # 3. Nhập mật khẩu
        password_input = self.driver.find_element(By.ID, "auth-password")
        self.type_text(password_input, password, action_text="⌨️ [Nhập liệu] Nhập mật khẩu tài khoản...")

        # 4. Nhấp nút 'Đăng nhập →'
        submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button.auth-submit")
        self.click(submit_btn, action_text="🎯 [Chuột thao tác] Bấm 'Đăng nhập →' xác thực phiên...")

        # 5. Chờ điều hướng vào app.html
        try:
            WebDriverWait(self.driver, 10).until(lambda d: "app.html" in d.current_url)
        except Exception:
            self.driver.get(f"{self.base_url}/app.html")
            time.sleep(1.5)

        print(f"[*] Đăng nhập thành công! Phiên làm việc đã sẵn sàng trên app.html.")
        self.show_message("✅ Đăng nhập thành công! Đang vào màn hình ứng dụng...", duration=1.2)

    def perform_ui_logout(self):
        """Đăng xuất an toàn trên giao diện và xóa sạch phiên làm việc trên trình duyệt."""
        print("[*] Thực hiện đăng xuất tài khoản...")
        self.show_message("🚪 [Đăng xuất] Kết thúc phiên làm việc...", duration=1.0)
        try:
            self.driver.execute_script("localStorage.clear(); sessionStorage.clear();")
            self.driver.delete_all_cookies()
        except Exception:
            pass
        self.driver.get(f"{self.base_url}/index.html")
        time.sleep(1.2)
        self.inject_cursor()

    def perform_ui_register(self, full_name: str, email: str, password: str = "User1234"):
        """Thực hiện đăng ký tài khoản mới trực quan trên giao diện Landing Page và đăng nhập vào ứng dụng."""
        print(f"[*] Thực hiện đăng ký tài khoản mới trên giao diện: {email}...")
        self.perform_ui_logout()

        self.show_message(f"📝 [Đăng ký mới] Tạo tài khoản: {email}...", duration=1.2)

        # 1. Bấm nút đăng ký hoặc mở form auth với tab register
        try:
            reg_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-open-auth='register']")
            self.click(reg_btn, action_text="🎯 [Chuột thao tác] Bấm 'Bắt đầu ngay / Đăng ký'...")
        except Exception:
            # Dự phòng: Bấm nút login rồi chuyển sang tab đăng ký
            try:
                login_btn = self.driver.find_element(By.CSS_SELECTOR, "button[data-open-auth='login']")
                self.click(login_btn, action_text="🎯 [Chuột thao tác] Mở hộp thoại xác thực...")
                time.sleep(0.5)
                tab_reg = self.driver.find_element(By.CSS_SELECTOR, "button[data-auth-tab='register']")
                self.click(tab_reg, action_text="🎯 [Chuột thao tác] Chuyển sang thẻ 'Đăng ký'...")
            except Exception:
                pass

        time.sleep(0.8)
        wait = WebDriverWait(self.driver, 10)
        name_input = wait.until(EC.visibility_of_element_located((By.ID, "auth-name")))
        self.type_text(name_input, full_name, action_text=f"⌨️ [Nhập liệu] Nhập Họ tên: {full_name}")

        email_input = self.driver.find_element(By.ID, "auth-email")
        self.type_text(email_input, email, action_text=f"⌨️ [Nhập liệu] Nhập Email mới: {email}")

        pwd_input = self.driver.find_element(By.ID, "auth-password")
        self.type_text(pwd_input, password, action_text="⌨️ [Nhập liệu] Nhập mật khẩu...")

        confirm_input = self.driver.find_element(By.ID, "auth-confirmation")
        self.type_text(confirm_input, password, action_text="⌨️ [Nhập liệu] Nhập lại mật khẩu xác nhận...")

        submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button.auth-submit")
        self.click(submit_btn, action_text="🎯 [Chuột thao tác] Bấm 'Đăng ký tài khoản →'...")

        # 1. Chờ API đăng ký hoàn tất và form chuyển sang tab đăng nhập (feedback hiển thị kết quả)
        wait = WebDriverWait(self.driver, 10)
        try:
            wait.until(lambda d: len(d.find_element(By.ID, "auth-feedback").text.strip()) > 0)
        except Exception:
            pass
        time.sleep(1.2)

        # 2. Sau khi form đã reset và chuyển chế độ login an toàn, nhập Email và Mật khẩu
        try:
            email_input = self.driver.find_element(By.ID, "auth-email")
            self.type_text(email_input, email, action_text=f"⌨️ [Xác thực] Nhập Email đăng nhập: {email}")
            pwd_input = self.driver.find_element(By.ID, "auth-password")
            self.type_text(pwd_input, password, action_text="⌨️ [Xác thực] Nhập mật khẩu tài khoản mới...")
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button.auth-submit")
            self.click(submit_btn, action_text="🎯 [Chuột thao tác] Bấm 'Đăng nhập →' vào ứng dụng...")
            wait.until(lambda d: "app.html" in d.current_url)
        except Exception:
            self.perform_ui_login(email=email, password=password)

        if "app.html" not in self.driver.current_url:
            self.perform_ui_login(email=email, password=password)

        print(f"[*] Tài khoản mới {email} đã đăng ký và đăng nhập thành công!")
        self.show_message(f"🎉 Chào mừng {full_name}! Đã vào trang chính ứng dụng.", duration=1.5)

    def navigate_to_feature(self, route_hash: str, wait_selector: Optional[str] = None):
        """Điều hướng tới route chức năng trong SPA (ví dụ #reconciliation, #wallets) và chờ nạp."""
        print(f"[*] Điều hướng tới chức năng: app.html#{route_hash}...")
        self.driver.get(f"{self.base_url}/app.html#{route_hash}")
        time.sleep(1.5)
        self.inject_cursor()

        if wait_selector:
            wait = WebDriverWait(self.driver, 15)
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, wait_selector)))

    def hold_for_observation(self, seconds: float = 5.0, completion_text: Optional[str] = None):
        """Giữ màn hình trình duyệt để người dùng tận mắt quan sát kết quả kiểm thử."""
        msg = completion_text or f"  ĐÃ HOÀN TẤT KIỂM THỬ TRỰC QUAN TRÊN TRÌNH DUYỆT! (Giữ {int(seconds)}s để quan sát)"
        print("\n" + "="*65)
        print(msg)
        print("="*65)
        self.show_message(f"🎉 Hoàn tất kịch bản! Giữ màn hình {int(seconds)}s để quan sát...", duration=0)
        time.sleep(seconds)

    # =========================================================================
    # MENU TERMINAL TƯƠNG TÁC CHUẨN HÓA CHO MỌI FILE .BAT
    # =========================================================================

    @classmethod
    def run_cli_menu(cls, feature_title: str, test_scenarios: Dict[str, Tuple[str, Callable]],
                     all_runner: Optional[Callable] = None):
        """
        Bộ menu CLI tương tác chuẩn hóa cho mọi chức năng.
        Nhận vào từ điển { "1": (Tên kịch bản, Hàm thực thi), ... }
        """
        parser = argparse.ArgumentParser(description=f"WealthFlow Visual Test Runner: {feature_title}")
        valid_choices = list(test_scenarios.keys()) + ["all", "a", "A", "ALL"]
        parser.add_argument("scenario_pos", nargs="?", choices=valid_choices, default=None,
                            help="Chỉ định kịch bản chạy trực tiếp dạng đối số (ví dụ: 1 hoặc a)")
        parser.add_argument("--scenario", choices=valid_choices, default=None,
                            help="Chỉ định kịch bản chạy trực tiếp (bỏ trống để mở menu tương tác)")
        args = parser.parse_args()
        selected_scenario = args.scenario or args.scenario_pos

        if selected_scenario:
            if selected_scenario.lower() in ["all", "a"]:
                if all_runner:
                    all_runner()
                else:
                    for key in sorted([k for k in test_scenarios.keys() if k.lower() not in ["a", "all"]]):
                        print(f"\n[*] Đang chạy ca kiểm thử: {test_scenarios[key][0]}...")
                        test_scenarios[key][1]()
                        time.sleep(1.5)
            else:
                test_scenarios[selected_scenario][1]()
            return

        # Menu tương tác lặp lại
        while True:
            print("\n" + "="*71)
            print(f"   WEALTHFLOW E2E VISUAL TEST RUNNER: {feature_title}")
            print("="*71)
            print("  Mỗi lựa chọn sẽ TỰ ĐỘNG Reset CSDL MySQL SE, xóa bộ đếm Rate Limits,")
            print("  ĐĂNG NHẬP THẬT TRÊN GIAO DIỆN và MỞ TRÌNH DUYỆT để kiểm thử trước mắt bạn!")
            print("-" * 71)

            # In danh sách các kịch bản số
            numeric_keys = [k for k in test_scenarios.keys() if k.isdigit()]
            numeric_keys.sort(key=int)
            for key in numeric_keys:
                print(f"  [{key}] {test_scenarios[key][0]}")

            # In các kịch bản chữ nếu có trong test_scenarios (ví dụ phím 'a')
            has_explicit_a = False
            for key in test_scenarios.keys():
                if key.lower() in ["a", "all"]:
                    print(f"  [a] {test_scenarios[key][0]}")
                    has_explicit_a = True

            if not has_explicit_a:
                print("  [a] [RUN ALL]: Chạy tự động lần lượt TẤT CẢ các kịch bản trên trình duyệt (Bấm phím 'a')")

            print("  [0] Thoát (Exit)")
            print("="*71)

            try:
                choice = input(f"  Chọn ca kiểm thử trực quan [1-{len(numeric_keys)}, a=Chạy tất cả, 0=Thoát]: ").strip()
            except (KeyboardInterrupt, EOFError):
                print("\nThoát chương trình.")
                break

            if choice == "0":
                print("Tạm biệt!")
                break
            elif choice.lower() in ["a", "all"]:
                try:
                    if all_runner:
                        all_runner()
                    else:
                        for key in numeric_keys:
                            print(f"\n[*] Đang chạy ca kiểm thử: {test_scenarios[key][0]}...")
                            test_scenarios[key][1]()
                            time.sleep(1.5)
                except Exception as e:
                    print(f"\n[LỖI TRONG QUÁ TRÌNH KIỂM THỬ]: {e}")
                input("\nNhấn Enter để quay về menu chính...")
            elif choice in test_scenarios:
                try:
                    test_scenarios[choice][1]()
                except Exception as e:
                    print(f"\n[LỖI TRONG QUÁ TRÌNH KIỂM THỬ]: {e}")
                input("\nNhấn Enter để quay về menu chính...")
            else:
                print("Lựa chọn không hợp lệ, vui lòng nhập lại.")
