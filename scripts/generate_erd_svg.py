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

BASE_DIR = os.path.join("docs", "02_elaboration", "database_design")
SOURCES_DIR = os.path.join(BASE_DIR, "sources")
OUT_DIR = BASE_DIR

os.makedirs(OUT_DIR, exist_ok=True)
os.makedirs(SOURCES_DIR, exist_ok=True)

DIAGRAMS = [
    ("erd00_overview.mmd", "erd00_overview.svg"),
    ("erd01_auth.mmd", "erd01_auth.svg"),
    ("erd02_ledger.mmd", "erd02_ledger.svg"),
    ("erd03_budgets.mmd", "erd03_budgets.svg"),
    ("erd04_loans.mmd", "erd04_loans.svg"),
    ("erd05_gold.mmd", "erd05_gold.svg"),
    ("erd06_goals.mmd", "erd06_goals.svg"),
    ("erd07_audit.mmd", "erd07_audit.svg"),
]

def render_diagram(src_file, out_file):
    src_path = os.path.join(SOURCES_DIR, src_file)
    out_path = os.path.join(OUT_DIR, out_file)
    
    if not os.path.exists(src_path):
        print(f"Skipping {src_file}: file not found.")
        return False

    with open(src_path, "r", encoding="utf-8") as f:
        code = f.read().strip()

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

    print(f"Generating SVG: {out_file}...")
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
            
            print(f" -> SUCCESS: {out_file} ({len(svg_data)} bytes)")
            return True
        except Exception as e:
            print(f" -> Attempt {attempt} failed for {out_file}: {e}")
            time.sleep(1)

    print(f"FAILED to generate {out_file}.")
    return False

all_ok = True
for src, dst in DIAGRAMS:
    ok = render_diagram(src, dst)
    if not ok:
        all_ok = False
    time.sleep(0.5)

if not all_ok:
    sys.exit(1)
print("\nAll 8 ERD SVGs generated successfully!")
