import os
import sys
import re
import zlib
import base64
import json
import urllib.request

if sys.stdout:
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

OUT_DIR = os.path.join("docs", "02_elaboration", "uml_modeling", "class_diagram")
MD_FILE = os.path.join("docs", "02_elaboration", "uml_modeling", "class_diagram.md")

os.makedirs(OUT_DIR, exist_ok=True)

with open(MD_FILE, "r", encoding="utf-8") as f:
    md_content = f.read()

# Extract all mermaid classDiagram blocks
blocks = re.findall(r"```mermaid\n(classDiagram[\s\S]*?)\n```", md_content)
print(f"Found {len(blocks)} classDiagram blocks in {MD_FILE}")

TARGETS = [
    ("cd01_identity_access.svg", "Phân hệ 1: Định danh, Xác thực & Quản trị"),
    ("cd02_wallet_management.svg", "Phân hệ 2: Quản lý Ví tài sản & Số dư Mở sổ"),
    ("cd03_double_entry_ledger.svg", "Phân hệ 3: Ghi nhận Giao dịch & Sổ cái Kế toán kép"),
    ("cd04_reconciliation_csv.svg", "Phân hệ 4: Báo cáo Đối soát Sổ cái & Xuất CSV"),
]

for idx, (filename, label) in enumerate(TARGETS):
    if idx >= len(blocks):
        print(f"Skipping {filename}: not enough blocks found.")
        continue
    
    code = blocks[idx]
    
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

    target_path = os.path.join(OUT_DIR, filename)

    print(f"Generating [{idx+1}/4] {filename} ({label})...")
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
            print(f" -> Attempt {attempt} failed for {filename}: {e}")
            import time
            time.sleep(2)
            
    if not success:
        print(f" -> FAILED to generate {filename} after 3 attempts.")

print("All 4 class diagram SVGs processed successfully!")
