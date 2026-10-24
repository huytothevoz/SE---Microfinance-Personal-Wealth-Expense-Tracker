# -*- coding: utf-8 -*-
"""
WEALTHFLOW MASTER VISUAL TEST RUNNER
Trình quản lý tập trung toàn bộ các bài kiểm thử theo từng phân hệ chức năng (FR01 - FR15).
Mỗi phân hệ FRxx tương ứng trực tiếp với lựa chọn số xx trên terminal (Ví dụ FR12 tương ứng [12]).
"""

import os
import re
import subprocess
import sys
from pathlib import Path

# Cấu hình UTF-8 cho Windows console
try:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass

TESTS_DIR = Path(__file__).resolve().parent

# Danh mục tên mô tả thân thiện cho 15 phân hệ
FR_NAMES = {
    "fr01_identity_access": "FR01: Định danh, xác thực & quản lý phiên",
    "fr02_wallet_management": "FR02: Quản lý tài khoản thanh toán & ví tiền",
    "fr03_double_entry_ledger": "FR03: Giao dịch thu chi & sổ cái kế toán kép",
    "fr04_multi_currency_fx": "FR04: Đa tiền tệ & tỷ giá Vietcombank",
    "fr05_expense_budgeting": "FR05: Ngân sách chi tiêu & cảnh báo hạn mức",
    "fr06_recurring_transactions": "FR06: Xử lý giao dịch định kỳ tự động",
    "fr07_savings_goals": "FR07: Mục tiêu tích lũy tài chính",
    "fr08_personal_wealth_gold": "FR08: Quản lý vàng tích lũy & giá vốn WAC",
    "fr09_microfinance_loans": "FR09: Quản lý khoản vay & nợ vi mô",
    "fr10_net_worth_analytics": "FR10: Phân tích tài sản ròng (Net Worth)",
    "fr11_cash_flow_saving_rate": "FR11: Báo cáo dòng tiền & Tỷ lệ tiết kiệm",
    "fr12_reconciliation_csv_export": "FR12: Đối soát số dư sổ cái & Xuất tệp CSV",
    "fr13_system_config_broadcast": "FR13: Cấu hình hệ thống & Broadcast Admin",
    "fr14_admin_user_management": "FR14: Quản trị người dùng & Audit Log",
    "fr15_api_security_isolation": "FR15: Bảo mật API & Cách ly dữ liệu",
}


def scan_available_visual_tests():
    """Quét các thư mục fr* có chứa file .bat kiểm thử trực quan và ánh xạ số phân hệ (FR12 -> '12')."""
    available = {}
    for item in sorted(TESTS_DIR.iterdir()):
        if item.is_dir() and item.name.startswith("fr"):
            bats = list(item.glob("*.bat"))
            if bats:
                bat_file = bats[0]
                match = re.search(r"fr(\d+)", item.name, re.IGNORECASE)
                if match:
                    fr_num = str(int(match.group(1)))
                else:
                    fr_num = item.name

                label = FR_NAMES.get(item.name, item.name.upper())
                available[fr_num] = {
                    "id": item.name,
                    "label": label,
                    "bat_path": bat_file
                }
    return available


def main():
    while True:
        available = scan_available_visual_tests()
        print("\n" + "="*71)
        print("     WEALTHFLOW MASTER VISUAL TEST CENTER - TOÀN BỘ PHÂN HỆ (FR)")
        print("=======================================================================")
        print("  Chọn số phân hệ FR tương ứng để mở bộ kiểm thử trực quan trên trình duyệt:")
        print("-" * 71)

        if not available:
            print("  [!] Hiện chưa có phân hệ nào có file .bat kiểm thử trực quan.")
        else:
            for fr_key in sorted(available.keys(), key=lambda k: int(k) if k.isdigit() else 999):
                item = available[fr_key]
                print(f"  [{fr_key:>2}] {item['label']} (Chạy: {item['bat_path'].name})")

        print("  [ 0] Thoát (Exit)")
        print("="*71)

        if not available:
            input("\nNhấn Enter để thoát...")
            break

        valid_keys = ", ".join(sorted(available.keys(), key=lambda k: int(k) if k.isdigit() else 999))
        if len(sys.argv) > 1 and sys.argv[1].strip():
            choice = sys.argv[1].strip()
            # Xóa tham số để nếu lặp lại sẽ không bị vô hạn
            sys.argv = [sys.argv[0]]
            is_cli_arg = True
        else:
            is_cli_arg = False
            try:
                choice = input(f"  Chọn phân hệ cần kiểm thử [{valid_keys} hoặc 0]: ").strip()
            except (KeyboardInterrupt, EOFError):
                print("\nThoát chương trình.")
                break

        if choice == "0":
            print("Tạm biệt!")
            break

        # Chuẩn hóa nếu người dùng nhập '12' hoặc 'fr12'
        normalized_choice = choice.lower().replace("fr", "")
        if normalized_choice.isdigit():
            normalized_choice = str(int(normalized_choice))

        if normalized_choice in available:
            target = available[normalized_choice]
            print(f"\n[*] Đang khởi chạy: {target['label']}...")
            print(f"[*] Thực thi tệp: {target['bat_path']}\n")
            subprocess.run(["cmd.exe", "/c", str(target['bat_path'])], cwd=str(target['bat_path'].parent))
            if is_cli_arg:
                break
            continue

        print(f"Lựa chọn không hợp lệ, vui lòng nhập một trong các số: {valid_keys} (hoặc 0 để thoát).")


if __name__ == "__main__":
    main()
