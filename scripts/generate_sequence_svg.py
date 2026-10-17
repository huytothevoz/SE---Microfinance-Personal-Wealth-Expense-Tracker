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

BASE_DIR = os.path.join("docs", "02_elaboration", "uml_modeling", "sequence_diagram")
SOURCES_DIR = os.path.join(BASE_DIR, "sources")
OUT_DIR = BASE_DIR

os.makedirs(OUT_DIR, exist_ok=True)
os.makedirs(SOURCES_DIR, exist_ok=True)

FILES = [
    ("sq01_auth_jwt.md", "sq01_auth_jwt.mmd", "sq01_auth_jwt.svg"),
    ("sq02_expense_budget.md", "sq02_expense_budget.mmd", "sq02_expense_budget.svg"),
    ("sq03_transfer_reversal.md", "sq03_transfer_reversal.mmd", "sq03_transfer_reversal.svg"),
    ("sq04_subscription_cron.md", "sq04_subscription_cron.mmd", "sq04_subscription_cron.svg"),
    ("sq05_microfinance_loan.md", "sq05_microfinance_loan.mmd", "sq05_microfinance_loan.svg"),
    ("sq06_gold_networth.md", "sq06_gold_networth.mmd", "sq06_gold_networth.svg"),
    ("sq07_reconciliation_csv.md", "sq07_reconciliation_csv.mmd", "sq07_reconciliation_csv.svg"),
    ("sq08_admin_user_broadcast.md", "sq08_admin_user_broadcast.mmd", "sq08_admin_user_broadcast.svg"),
]

def extract_mermaid_code(md_path):
    with open(md_path, "r", encoding="utf-8") as f:
        content = f.read()
    
    match = re.search(r"```mermaid\s*\n(sequenceDiagram[\s\S]*?)\n```", content)
    if match:
        return match.group(1).strip()
    return None

def render_diagram(code, out_path):
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
                "actorBkg": "#EEF2FF",
                "actorBorder": "#4F46E5",
                "actorTextColor": "#0F172A",
                "actorLineColor": "#64748B",
                "signalColor": "#1E293B",
                "signalTextColor": "#0F172A",
                "labelBoxBkgColor": "#FFFFFF",
                "labelBoxBorderColor": "#CBD5E1",
                "labelTextColor": "#0F172A",
                "loopTextColor": "#0F172A",
                "noteBorderColor": "#93C5FD",
                "noteBkgColor": "#EFF6FF",
                "noteTextColor": "#1E3A8A",
                "fontFamily": "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
                "fontSize": "13px"
            }
        }
    }

    compressed = zlib.compress(json.dumps(payload).encode("utf-8"), 9)
    b64 = base64.urlsafe_b64encode(compressed).decode("ascii")
    url = f"https://mermaid.ink/svg/pako:{b64}"

    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"})

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
                
            with open(out_path, "w", encoding="utf-8") as out_f:
                out_f.write(svg_data)
            
            return True, len(svg_data)
        except Exception as e:
            print(f" -> Attempt {attempt} failed: {e}")
            time.sleep(1)

    return False, 0

all_ok = True
for md_name, mmd_name, svg_name in FILES:
    md_path = os.path.join(BASE_DIR, md_name)
    mmd_path = os.path.join(SOURCES_DIR, mmd_name)
    svg_path = os.path.join(OUT_DIR, svg_name)

    print(f"Processing {md_name}...")
    code = extract_mermaid_code(md_path)
    if not code:
        print(f" -> Error: Could not extract mermaid code from {md_name}")
        all_ok = False
        continue

    # Save source mmd
    with open(mmd_path, "w", encoding="utf-8") as f:
        f.write(code)

    ok, size = render_diagram(code, svg_path)
    if ok:
        print(f" -> SUCCESS: {svg_name} ({size} bytes)")
    else:
        print(f" -> FAILED: {svg_name}")
        all_ok = False
    
    time.sleep(0.5)

if not all_ok:
    print("\nSome diagrams failed to generate.")
    sys.exit(1)

print("\nAll 8 Sequence Diagram SVGs generated successfully!")
