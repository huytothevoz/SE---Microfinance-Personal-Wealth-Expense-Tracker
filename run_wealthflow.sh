#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd -- "$(dirname -- "$0")" && pwd)"
SOURCE_DIR="$PROJECT_ROOT/source"
BACKEND_DIR="$SOURCE_DIR/backend"
ROUTER_PHP="$BACKEND_DIR/router.php"
MIGRATOR_PHP="$BACKEND_DIR/bin/setup.php"
STORAGE_DIR="$BACKEND_DIR/storage"

WEB_PORT="${WEB_PORT:-8080}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-wealthflow}"

PID_DIR="$PROJECT_ROOT/.run_pids"
WEB_PID_FILE="$PID_DIR/web_$WEB_PORT.pid"
LOG_DIR="$PROJECT_ROOT/.run_logs"
WEB_LOG="$LOG_DIR/web_$WEB_PORT.log"

mkdir -p "$PID_DIR" "$LOG_DIR" "$STORAGE_DIR"

# 1. Tim PHP
PHP_BIN=""
if command -v php >/dev/null 2>&1; then
    PHP_BIN="$(command -v php)"
elif [[ -x "/opt/lampp/bin/php" ]]; then
    PHP_BIN="/opt/lampp/bin/php"
elif [[ -x "/usr/bin/php" ]]; then
    PHP_BIN="/usr/bin/php"
fi

if [[ -z "$PHP_BIN" ]]; then
    echo -e "\033[0;31m[LỖI] Không tìm thấy PHP 8.2+. Vui lòng cài đặt PHP hoặc XAMPP (/opt/lampp).\033[0m"
    exit 1
fi

# 2. Tim MySQL CLI
MYSQL_BIN=""
if command -v mysql >/dev/null 2>&1; then
    MYSQL_BIN="$(command -v mysql)"
elif [[ -x "/opt/lampp/bin/mysql" ]]; then
    MYSQL_BIN="/opt/lampp/bin/mysql"
fi

is_web_running() {
    if [[ -f "$WEB_PID_FILE" ]]; then
        local pid
        pid="$(cat "$WEB_PID_FILE" 2>/dev/null || echo "")"
        if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
            return 0
        fi
    fi
    if command -v lsof >/dev/null 2>&1; then
        lsof -i ":$WEB_PORT" >/dev/null 2>&1 && return 0
    elif command -v nc >/dev/null 2>&1; then
        nc -z 127.0.0.1 "$WEB_PORT" >/dev/null 2>&1 && return 0
    fi
    return 1
}

is_db_running() {
    if command -v nc >/dev/null 2>&1; then
        nc -z 127.0.0.1 "$DB_PORT" >/dev/null 2>&1 && return 0
    elif command -v lsof >/dev/null 2>&1; then
        lsof -i ":$DB_PORT" >/dev/null 2>&1 && return 0
    fi
    return 1
}

init_env() {
    export APP_URL="http://localhost:$WEB_PORT"
    export APP_ENV="local"
    export DB_HOST="127.0.0.1"
    export DB_PORT="$DB_PORT"
    export DB_NAME="$DB_NAME"
    export DB_USER="${DB_USER:-root}"
    export DB_PASSWORD="${DB_PASSWORD:-}"
    export MAIL_TRANSPORT="log"
    if [[ -z "${APP_KEY:-}" ]]; then
        export APP_KEY="$(LC_ALL=C tr -dc 'a-f0-9' < /dev/urandom | head -c 32 || echo '12345678901234567890123456789012')"
    fi
}

start_db() {
    if is_db_running; then
        echo -e "  \033[0;32m[OK] MySQL đang hoạt động trên cổng $DB_PORT.\033[0m"
        return 0
    fi
    echo -e "  \033[0;36m[*] Đang kiểm tra và khởi động dịch vụ MySQL...\033[0m"
    if [[ -x "/opt/lampp/lampp" ]]; then
        sudo "/opt/lampp/lampp" startmysql || true
    elif command -v systemctl >/dev/null 2>&1; then
        sudo systemctl start mysql 2>/dev/null || sudo systemctl start mariadb 2>/dev/null || true
    elif command -v service >/dev/null 2>&1; then
        sudo service mysql start 2>/dev/null || true
    fi
    for ((i=0; i<15; i++)); do
        if is_db_running; then
            echo -e "  \033[0;32m[OK] MySQL đã sẵn sàng trên cổng $DB_PORT.\033[0m"
            return 0
        fi
        sleep 1
    done
    echo -e "  \033[0;33m[WARN] Không thể tự động bật MySQL. Nếu bạn dùng máy chủ khác, hãy đảm bảo MySQL 127.0.0.1:$DB_PORT đang chạy.\033[0m"
    return 1
}

start_web() {
    if is_web_running; then
        echo -e "  \033[0;32m[OK] PHP Web Server đang chạy tại http://localhost:$WEB_PORT\033[0m"
        return 0
    fi
    echo -e "  \033[0;36m[*] Đang khởi động PHP Web Server trên cổng $WEB_PORT...\033[0m"
    init_env
    nohup "$PHP_BIN" -S "127.0.0.1:$WEB_PORT" -t "$SOURCE_DIR" "$ROUTER_PHP" > "$WEB_LOG" 2>&1 &
    local new_pid=$!
    echo "$new_pid" > "$WEB_PID_FILE"
    for ((i=0; i<10; i++)); do
        if is_web_running; then
            echo -e "  \033[0;32m[OK] PHP Web Server đã hoạt động tại http://localhost:$WEB_PORT/\033[0m"
            return 0
        fi
        sleep 1
    done
    echo -e "  \033[0;31m[LỖI] Không thể khởi động PHP Web Server. Xem log: $WEB_LOG\033[0m"
    return 1
}

stop_services() {
    echo -e "  \033[0;36m[*] Đang dừng các dịch vụ WealthFlow...\033[0m"
    if [[ -f "$WEB_PID_FILE" ]]; then
        local pid
        pid="$(cat "$WEB_PID_FILE" 2>/dev/null || echo "")"
        if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
            kill "$pid" 2>/dev/null || true
            echo -e "  \033[0;32m[OK] Đã dừng PHP Web Server (PID $pid).\033[0m"
        fi
        rm -f "$WEB_PID_FILE"
    fi
    echo -e "  \033[0;32m[OK] Đã dừng toàn bộ dịch vụ an toàn.\033[0m"
}

apply_migrations() {
    echo -e "  \033[0;36m[*] Đang kiểm tra & áp dụng CSDL Migrations...\033[0m"
    init_env
    if "$PHP_BIN" "$MIGRATOR_PHP"; then
        echo -e "  \033[0;32m[OK] Áp dụng Migrations thành công!\033[0m"
    else
        echo -e "  \033[0;31m[LỖI] Có lỗi khi chạy migrations.\033[0m"
    fi
}

reset_database() {
    echo ""
    echo -e "  \033[0;33m[CẢNH BÁO] Toàn bộ dữ liệu database '$DB_NAME' sẽ bị XÓA và tạo lại!\033[0m"
    read -rp "  Bạn có chắc chắn muốn reset không? [y/N]: " confirm
    if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
        echo "  [*] Đã hủy thao tác."
        return 0
    fi
    if [[ -n "$MYSQL_BIN" ]]; then
        echo "  [*] Đang xóa và tạo lại database '$DB_NAME'..."
        "$MYSQL_BIN" -h 127.0.0.1 -P "$DB_PORT" -u root -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
    fi
    init_env
    "$PHP_BIN" "$MIGRATOR_PHP"
    echo -e "  \033[0;32m[OK] Đã nạp thành công toàn bộ 28 SQL Migrations và Seeds dữ liệu mẫu!\033[0m"
}

check_health() {
    echo ""
    echo -e "\033[0;36m---------------- TRẠNG THÁI HỆ THỐNG CHI TIẾT ----------------\033[0m"
    if is_web_running; then
        echo -e "  • Web Server (Port $WEB_PORT): \033[0;32mĐANG CHẠY (RUNNING)\033[0m"
    else
        echo -e "  • Web Server (Port $WEB_PORT): \033[0;31mĐÃ DỪNG (STOPPED)\033[0m"
    fi
    if is_db_running; then
        echo -e "  • MySQL Database (Port $DB_PORT): \033[0;32mĐANG CHẠY (RUNNING)\033[0m"
    else
        echo -e "  • MySQL Database (Port $DB_PORT): \033[0;31mĐÃ DỪNG (STOPPED)\033[0m"
    fi
    echo "  • PHP Binary: $PHP_BIN"
    if is_web_running && command -v curl >/dev/null 2>&1; then
        echo -n "  • Kiểm tra /api/health: "
        local res
        res="$(curl -s --max-time 3 "http://127.0.0.1:$WEB_PORT/api/health" || echo "")"
        if echo "$res" | grep -q '"success":true'; then
            echo -e "\033[0;32mHOẠT ĐỘNG TỐT (OK)\033[0m"
            echo "    Response: $res"
        else
            echo -e "\033[0;33mCHƯA PHẢN HỒI HOẶC LỖI\033[0m"
        fi
    fi
    echo -e "\033[0;36m---------------------------------------------------------------\033[0m"
}

run_tests() {
    echo ""
    echo -e "\033[0;36m---------------- BỘ KIỂM THỬ TỰ ĐỘNG (TEST SUITE) -------------\033[0m"
    echo -e "  [*] 1. Chạy 36 ca kiểm thử backend FR03-FR06..."
    "$PHP_BIN" "$BACKEND_DIR/tests/run_tests.php"
    echo ""
    echo -e "  [*] 2. Chạy 7 ca kiểm thử Email Service SMTP..."
    "$PHP_BIN" "$BACKEND_DIR/tests/mail_smoke_test.php"
    echo -e "\033[0;36m---------------------------------------------------------------\033[0m"
}

open_browser() {
    local url="http://localhost:$WEB_PORT/"
    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$url" >/dev/null 2>&1 &
    elif command -v open >/dev/null 2>&1; then
        open "$url" >/dev/null 2>&1 &
    else
        echo "  Vui lòng mở trình duyệt và truy cập: $url"
    fi
}

# VÒNG LẶP MENU TUI
while true; do
    web_state="STOPPED"
    web_color="\033[0;31m"
    if is_web_running; then
        web_state="RUNNING (http://localhost:$WEB_PORT)"
        web_color="\033[0;32m"
    fi

    db_state="STOPPED"
    db_color="\033[0;31m"
    if is_db_running; then
        db_state="RUNNING (Port $DB_PORT)"
        db_color="\033[0;32m"
    fi

    echo ""
    echo -e "\033[0;36m=======================================================================\033[0m"
    echo -e "\033[1;33m     WEALTHFLOW CONTROL CENTER - QUẢN LÝ TÀI CHÍNH CÁ NHÂN (LINUX)     \033[0m"
    echo -e "\033[0;36m=======================================================================\033[0m"
    echo -e "  [Trạng thái] Web: ${web_color}${web_state}\033[0m | DB: ${db_color}${db_state}\033[0m"
    echo -e "  [PHP Path]   $PHP_BIN"
    echo -e "\033[0;36m-----------------------------------------------------------------------\033[0m"
    echo "  [1] Khởi động hệ thống (Start Web + DB) & Mở trình duyệt"
    echo "  [2] Dừng toàn bộ hệ thống (Stop Web & DB an toàn)"
    echo "  [3] Kiểm tra trạng thái chi tiết (Health Check & API status)"
    echo "  [4] Chạy cập nhật CSDL Migrations (28 SQL files)"
    echo -e "  \033[0;33m[5] Reset CSDL & Dữ liệu mẫu (Reset & Seed sạch)\033[0m"
    echo "  [6] Chạy bộ kiểm thử tự động (36 unit tests + 7 mail checks)"
    echo "  [7] Xem nhật ký lỗi & email OTP gần nhất"
    echo "  [8] Mở trình duyệt Web (http://localhost:$WEB_PORT/)"
    echo -e "  \033[0;31m[0] Thoát (Exit)\033[0m"
    echo -e "\033[0;36m=======================================================================\033[0m"
    read -rp "  Chọn chức năng [0-8]: " choice

    case "$choice" in
        1)
            echo ""
            start_db || true
            apply_migrations
            start_web
            echo ""
            echo -e "  \033[0;32m===========================================================\033[0m"
            echo -e "  \033[1;32m     WEALTHFLOW ĐÃ KHỞI ĐỘNG VÀ SẴN SÀNG SỬ DỤNG!          \033[0m"
            echo -e "  \033[0;32m===========================================================\033[0m"
            echo -e "  URL Giao diện:  \033[1mhttp://localhost:$WEB_PORT/\033[0m"
            echo -e "  Demo User:      \033[1;33muser1@gmail.com   / Mật khẩu: User1234\033[0m"
            echo -e "  Admin User:     \033[1;33madmin@wealthflow.vn / Mật khẩu: Admin@1234\033[0m"
            echo -e "  \033[0;32m===========================================================\033[0m"
            open_browser
            ;;
        2)
            echo ""
            stop_services
            ;;
        3)
            check_health
            ;;
        4)
            echo ""
            apply_migrations
            ;;
        5)
            reset_database
            ;;
        6)
            run_tests
            ;;
        7)
            echo ""
            if [[ -f "$WEB_LOG" ]]; then
                echo -e "\033[0;33m=== Web Server Log (15 dòng cuối) ===\033[0m"
                tail -n 15 "$WEB_LOG"
            fi
            if [[ -d "$STORAGE_DIR" ]]; then
                echo -e "\033[0;33m=== Email OTP (.eml) mới nhất ===\033[0m"
                ls -lt "$STORAGE_DIR"/*.eml 2>/dev/null | head -n 3 || echo "  (Chưa có file email nào)"
            fi
            ;;
        8)
            open_browser
            ;;
        0)
            if is_web_running; then
                read -rp "  Web Server vẫn đang chạy. Bạn có muốn giữ chạy ngầm không? [Y/n]: " ans
                if [[ "$ans" == "n" || "$ans" == "N" ]]; then
                    stop_services
                fi
            fi
            echo -e "\033[0;36m  Tạm biệt!\033[0m"
            exit 0
            ;;
        *)
            echo -e "  \033[0;33m[!] Lựa chọn không hợp lệ, vui lòng nhập số từ 0 đến 8.\033[0m"
            ;;
    esac
done
