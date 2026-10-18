import os
import sys
import zlib
import base64
import json
import urllib.request
import re
import time

if sys.stdout:
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

BASE_DIR = os.path.join("docs", "02_elaboration", "uml_modeling", "class_diagram")
SOURCES_DIR = os.path.join(BASE_DIR, "sources")
OUT_DIR = BASE_DIR

os.makedirs(OUT_DIR, exist_ok=True)
os.makedirs(SOURCES_DIR, exist_ok=True)

TARGETS = [
    ("cd00_domain_model.mmd", "cd00_domain_model.svg", "Sơ đồ Thực thể Nghiệp vụ Tổng thể (Domain Model)"),
    ("cd01_identity_access.mmd", "cd01_identity_access.svg", "Phân hệ 1: Định danh, Xác thực & Quản trị"),
    ("cd02_wallet_management.mmd", "cd02_wallet_management.svg", "Phân hệ 2: Quản lý Ví tài sản & Số dư Mở sổ"),
    ("cd03_double_entry_ledger.mmd", "cd03_double_entry_ledger.svg", "Phân hệ 3: Ghi nhận Giao dịch & Sổ cái Kế toán kép"),
    ("cd04_reconciliation_csv.mmd", "cd04_reconciliation_csv.svg", "Phân hệ 4: Báo cáo Đối soát Sổ cái & Xuất CSV"),
    ("cd05_recurring_transaction.mmd", "cd05_recurring_transaction.svg", "Phân hệ 5: Quản lý Giao dịch Định kỳ Tự động"),
    ("cd06_budget_planning.mmd", "cd06_budget_planning.svg", "Phân hệ 6: Thiết lập & Kiểm soát Ngân sách Chi tiêu"),
    ("cd07_microfinance_loans.mmd", "cd07_microfinance_loans.svg", "Phân hệ 7: Quản lý Khoản vay Vi mô & Phân bổ Gốc/Lãi"),
    ("cd08_gold_asset_market.mmd", "cd08_gold_asset_market.svg", "Phân hệ 8: Tích lũy Tài sản Vàng & Định giá Thị trường"),
    ("cd09_goal_saving_earmark.mmd", "cd09_goal_saving_earmark.svg", "Phân hệ 9: Mục tiêu Tiết kiệm & Khóa tiền Earmarking"),
    ("cd10_networth_analytics.mmd", "cd10_networth_analytics.svg", "Phân hệ 10: Dashboard Sức khỏe Net Worth & Snapshot Nền"),
    ("cd11_notification_broadcast.mmd", "cd11_notification_broadcast.svg", "Phân hệ 11: Quản trị & Broadcast Thông báo"),
]

for idx, (src_file, out_svg, label) in enumerate(TARGETS):
    src_path = os.path.join(SOURCES_DIR, src_file)
    if not os.path.exists(src_path):
        print(f"Skipping {src_file}: source file not found at {src_path}")
        continue
    
    with open(src_path, "r", encoding="utf-8") as f:
        code = f.read().strip()
    
    # Configure high contrast theme with solid background
    payload = {
        "code": code,
        "mermaid": {
            "theme": "default",
            "themeVariables": {
                "background": "#FFFFFF",
                "primaryColor": "#EEF2FF",
                "primaryTextColor": "#0F172A",
                "primaryBorderColor": "#4F46E5",
                "lineColor": "#1E293B",
                "secondaryColor": "#FEF3C7",
                "secondaryTextColor": "#0F172A",
                "secondaryBorderColor": "#D97706",
                "tertiaryColor": "#ECFDF5",
                "tertiaryTextColor": "#0F172A",
                "tertiaryBorderColor": "#059669",
                "edgeLabelBackground": "#FFFFFF",
                "fontFamily": "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
                "fontSize": "13px"
            }
        }
    }

    compressed = zlib.compress(json.dumps(payload).encode("utf-8"), 9)
    b64 = base64.urlsafe_b64encode(compressed).decode("ascii")
    url = f"https://mermaid.ink/svg/pako:{b64}"

    target_path = os.path.join(OUT_DIR, out_svg)

    print(f"Generating [{idx+1}/{len(TARGETS)}] {out_svg} ({label})...")
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"})
    
    success = False
    for attempt in range(1, 4):
        try:
            with urllib.request.urlopen(req, timeout=30) as resp:
                svg_data = resp.read().decode("utf-8")
            
            # Inject solid white background rect if missing
            if '<rect id="bg-canvas"' not in svg_data:
                svg_data = re.sub(
                    r"(<svg[^>]*>)",
                    r'\1\n  <rect id="bg-canvas" width="100%" height="100%" fill="#FFFFFF" style="fill:#FFFFFF;"/>',
                    svg_data,
                    count=1
                )
            
            if 'style="' in svg_data:
                svg_data = re.sub(r'style="([^"]*)"', r'style="background-color:#FFFFFF; \1"', svg_data, count=1)
            else:
                svg_data = re.sub(r"(<svg[^>]*)>", r'\1 style="background-color:#FFFFFF;">', svg_data, count=1)
                
            with open(target_path, "w", encoding="utf-8") as out_f:
                out_f.write(svg_data)
            
            print(f" -> SUCCESS: {target_path} ({len(svg_data)} bytes)")
            success = True
            break
        except Exception as e:
            print(f" -> Attempt {attempt} failed for {out_svg}: {e}")
            time.sleep(2)
            
    if not success:
        print(f" -> FAILED to generate {out_svg} after 3 attempts.")

print("All class diagram SVGs processed successfully!")
