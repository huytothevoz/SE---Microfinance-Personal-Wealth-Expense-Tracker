#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd -- "$(dirname -- "$0")" && pwd)"
SOURCE_DIR="$PROJECT_ROOT/source"
BACKEND_DIR="$SOURCE_DIR/backend"
ROUTER_PHP="$BACKEND_DIR/router.php"
COMPOSER_JSON="$BACKEND_DIR/composer.json"
MIGRATOR_PHP="$BACKEND_DIR/bin/setup.php"
STORAGE_DIR="$BACKEND_DIR/storage"

XAMPP_DIR="${XAMPP_DIR:-/opt/lampp}"
WEB_PORT="8080"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-wealthflow}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
FRONTEND_VERSION="wallet-refactor-20261018-5"

PID_DIR="$PROJECT_ROOT/.run_pids"
LOG_DIR="$PROJECT_ROOT/.run_logs"
mkdir -p "$PID_DIR" "$LOG_DIR" "$STORAGE_DIR"

info() { printf '  [*] %s\n' "$*"; }
ok() { printf '  [OK] %s\n' "$*"; }
warn() { printf '  [CẢNH BÁO] %s\n' "$*"; }
fail() { printf '  [LỖI] %s\n' "$*" >&2; return 1; }

port_open() {
    local port="$1"
    if command -v nc >/dev/null 2>&1; then
        nc -z 127.0.0.1 "$port" >/dev/null 2>&1
    elif command -v lsof >/dev/null 2>&1; then
        lsof -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1
    else
        return 1
    fi
}

managed_process() {
    local pid="$1"
    local port="$2"
    local command_line
    [[ "$pid" =~ ^[0-9]+$ ]] && kill -0 "$pid" 2>/dev/null || return 1
    command_line="$(ps -p "$pid" -o args= 2>/dev/null || true)"
    [[ "$command_line" == *"$ROUTER_PHP"* && "$command_line" == *"127.0.0.1:$port"* ]]
}

WEB_PID_FILE="$PID_DIR/web_$WEB_PORT.pid"
WEB_LOG="$LOG_DIR/web_$WEB_PORT.log"

PHP_BIN=""
if [[ -x "$XAMPP_DIR/bin/php" ]]; then
    PHP_BIN="$XAMPP_DIR/bin/php"
elif command -v php >/dev/null 2>&1; then
    PHP_BIN="$(command -v php)"
fi

MYSQLADMIN_BIN=""
if [[ -x "$XAMPP_DIR/bin/mysqladmin" ]]; then
    MYSQLADMIN_BIN="$XAMPP_DIR/bin/mysqladmin"
elif command -v mysqladmin >/dev/null 2>&1; then
    MYSQLADMIN_BIN="$(command -v mysqladmin)"
fi

MYSQL_BIN=""
if [[ -x "$XAMPP_DIR/bin/mysql" ]]; then
    MYSQL_BIN="$XAMPP_DIR/bin/mysql"
elif command -v mysql >/dev/null 2>&1; then
    MYSQL_BIN="$(command -v mysql)"
fi

init_env() {
    export APP_URL="http://localhost:$WEB_PORT"
    export APP_ENV="local"
    export APP_KEY="${APP_KEY:-wallet-refactor-local-development-key-2026}"
    export DB_HOST DB_PORT DB_NAME DB_USER DB_PASSWORD
    export MAIL_TRANSPORT="${MAIL_TRANSPORT:-log}"
}

validate_environment() {
    [[ "$WEB_PORT" == "8080" ]] || fail "WealthFlow phải chạy đúng cổng 8080."
    [[ "$DB_PORT" =~ ^[0-9]+$ ]] && ((DB_PORT >= 1 && DB_PORT <= 65535)) \
        || fail "DB_PORT phải nằm trong khoảng 1-65535."
    [[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]] || fail "DB_NAME chỉ được chứa chữ, số và dấu gạch dưới."
    [[ -n "$PHP_BIN" && -x "$PHP_BIN" ]] || fail "Không tìm thấy PHP."
    [[ -f "$ROUTER_PHP" && -f "$COMPOSER_JSON" && -f "$MIGRATOR_PHP" ]] || fail "Source backend không đầy đủ."
    command -v curl >/dev/null 2>&1 || fail "Cần cài curl để kiểm tra dịch vụ."
    "$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' \
        || fail "Chức năng Ví yêu cầu PHP 8.2 trở lên."
    "$PHP_BIN" -r 'foreach (["pdo_mysql", "mbstring", "openssl", "bcmath", "curl"] as $e) { if (!extension_loaded($e)) exit(1); }' \
        || fail "PHP thiếu extension pdo_mysql, mbstring, openssl, bcmath hoặc curl."
}

ensure_dependencies() {
    [[ -f "$BACKEND_DIR/vendor/autoload.php" ]] && return 0
    info "Đang cài Composer dependencies..."
    local composer_bin
    composer_bin="$(command -v composer || true)"
    [[ -n "$composer_bin" ]] || fail "Chưa có Composer và backend/vendor chưa được cài."
    "$PHP_BIN" -d 'include_path=.:/usr/share/php' "$composer_bin" install \
        --working-dir="$BACKEND_DIR" --no-dev --prefer-dist --no-interaction --optimize-autoloader
}

db_running() {
    [[ -n "$MYSQLADMIN_BIN" && -x "$MYSQLADMIN_BIN" ]] || return 1
    MYSQL_PWD="$DB_PASSWORD" "$MYSQLADMIN_BIN" --protocol=tcp \
        --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
        ping --silent >/dev/null 2>&1
}

run_privileged() {
    if ((EUID == 0)); then
        "$@"
        return
    fi
    command -v sudo >/dev/null 2>&1 || fail "Cần sudo để quản lý dịch vụ XAMPP."
    sudo "$@"
}

stop_xampp_all() {
    [[ -x "$XAMPP_DIR/lampp" ]] || return 0
    info "Dừng toàn bộ dịch vụ XAMPP cũ..."
    if run_privileged "$XAMPP_DIR/lampp" stop; then
        ok "Đã dừng Apache, MySQL và FTP của XAMPP."
    else
        fail "Không thể dừng toàn bộ XAMPP; hủy khởi động để tránh trạng thái nửa vời."
        return 1
    fi
}

start_xampp_mysql() {
    [[ -x "$XAMPP_DIR/lampp" ]] || fail "Không tìm thấy bộ điều khiển XAMPP tại $XAMPP_DIR/lampp."
    info "Khởi động lại riêng MySQL của XAMPP tại $DB_HOST:$DB_PORT..."
    if ! run_privileged "$XAMPP_DIR/lampp" startmysql; then
        fail "Không thể yêu cầu XAMPP khởi động MySQL."
        return 1
    fi
    for ((i=0; i<20; i++)); do
        db_running && { ok "MySQL XAMPP đã sẵn sàng."; return 0; }
        sleep 1
    done
    fail "Không kết nối được MySQL XAMPP tại $DB_HOST:$DB_PORT."
    return 1
}

verify_database() {
    [[ -n "$MYSQL_BIN" && -x "$MYSQL_BIN" ]] || fail "Không tìm thấy MySQL CLI để kiểm tra database."
    info "Kiểm tra database '$DB_NAME' và cấu trúc WealthFlow hiện có..."
    local database_exists table_count
    database_exists="$(MYSQL_PWD="$DB_PASSWORD" "$MYSQL_BIN" --protocol=tcp \
        --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" --batch --skip-column-names \
        --execute="SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = '$DB_NAME';" 2>/dev/null || true)"
    if [[ "$database_exists" != "1" ]]; then
        fail "Database '$DB_NAME' chưa tồn tại sau khi khởi tạo."
        return 1
    fi

    table_count="$(MYSQL_PWD="$DB_PASSWORD" "$MYSQL_BIN" --protocol=tcp \
        --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" --batch --skip-column-names \
        --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name IN ('users','wallets','categories','financial_transactions','ledger_entries');" 2>/dev/null || true)"
    if [[ "$table_count" != "5" ]]; then
        fail "Database '$DB_NAME' thiếu bảng WealthFlow bắt buộc sau khi chạy migration."
        return 1
    fi
    ok "Database '$DB_NAME' có đầy đủ các bảng WealthFlow bắt buộc."
}

reset_database() {
    [[ -n "$MYSQL_BIN" && -x "$MYSQL_BIN" ]] || fail "Không tìm thấy MySQL CLI để khởi tạo database."
    info "XÓA database '$DB_NAME' cũ và tạo database mới..."
    if ! MYSQL_PWD="$DB_PASSWORD" "$MYSQL_BIN" --protocol=tcp \
        --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
        --execute="DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"; then
        fail "Không thể drop và tạo lại database '$DB_NAME'."
        return 1
    fi

    info "Áp dụng nguyên bộ migration 001–028 và dữ liệu mẫu..."
    init_env
    if ! "$PHP_BIN" "$MIGRATOR_PHP" --no-config; then
        fail "Migration thất bại; không khởi động web với database chưa hoàn chỉnh."
        return 1
    fi
    verify_database || return 1
    ok "Database '$DB_NAME' đã được tạo mới từ 28 migration."
}

stop_conflicting_wealthflow_web() {
    port_open "$WEB_PORT" || return 0
    command -v lsof >/dev/null 2>&1 \
        || fail "Cổng 8080 đang bận và không có lsof để xác định tiến trình an toàn."

    local pid command_line found=0
    while read -r pid; do
        [[ "$pid" =~ ^[0-9]+$ ]] || continue
        command_line="$(ps -p "$pid" -o args= 2>/dev/null || true)"
        if [[ "$command_line" == *"php"* && "$command_line" == *"127.0.0.1:8080"* \
            && "$command_line" == *"CNPM_ai"* && "$command_line" == *"router.php"* ]]; then
            info "Dừng WealthFlow cũ đang giữ cổng 8080 (PID $pid)..."
            kill -TERM "$pid" 2>/dev/null || true
            found=1
        else
            fail "Cổng 8080 đang do tiến trình ngoài WealthFlow sử dụng: $command_line"
            return 1
        fi
    done < <(lsof -tiTCP:"$WEB_PORT" -sTCP:LISTEN 2>/dev/null | sort -u)

    if ((found == 1)); then
        for ((i=0; i<30; i++)); do
            port_open "$WEB_PORT" || { ok "Đã giải phóng cổng 8080."; return 0; }
            sleep 0.1
        done
    fi
    if port_open "$WEB_PORT"; then
        fail "Không thể giải phóng cổng 8080."
        return 1
    fi
    return 0
}

prepare_clean_runtime() {
    stop_managed_webs || return 1
    stop_xampp_all || return 1
    stop_conflicting_wealthflow_web || return 1
    start_xampp_mysql || return 1
    reset_database || return 1
}

managed_web_running() {
    local pid=""
    [[ -f "$WEB_PID_FILE" ]] && pid="$(cat "$WEB_PID_FILE" 2>/dev/null || true)"
    managed_process "$pid" "$WEB_PORT" && port_open "$WEB_PORT"
}

api_healthy() {
    curl --fail --silent --max-time 3 "http://127.0.0.1:$WEB_PORT/api/health" \
        | grep -q '"success":true'
}

served_file_matches() {
    local relative_path="$1"
    local local_hash remote_hash
    local_hash="$(sha256sum "$SOURCE_DIR/$relative_path" | awk '{print $1}')"
    remote_hash="$(curl --fail --silent --max-time 3 \
        "http://127.0.0.1:$WEB_PORT/$relative_path?runner-check=$(date +%s%N)" \
        | sha256sum | awk '{print $1}')" || return 1
    [[ "$local_hash" == "$remote_hash" ]]
}

frontend_healthy() {
    grep -q "$FRONTEND_VERSION" "$SOURCE_DIR/app.html" \
        && grep -q "$FRONTEND_VERSION" "$SOURCE_DIR/frontend/js/app.js" \
        && served_file_matches 'frontend/js/app.js' \
        && served_file_matches 'frontend/js/app/main.js' \
        && served_file_matches 'frontend/js/app/wallets.js' \
        && served_file_matches 'frontend/js/app/transactions.js' \
        && served_file_matches 'frontend/js/services/wallet-api.js' \
        && served_file_matches 'frontend/js/services/transaction-api.js' \
        && curl --fail --silent --max-time 3 \
            "http://127.0.0.1:$WEB_PORT/frontend/views/overview.html?runner-check=$(date +%s%N)" \
            | grep -q '<h1>Tổng quan</h1>' \
        && curl --fail --silent --max-time 3 \
            "http://127.0.0.1:$WEB_PORT/frontend/views/wallets.html?runner-check=$(date +%s%N)" \
            | grep -q 'id="wallet-card-list"' \
        && curl --fail --silent --max-time 3 \
            "http://127.0.0.1:$WEB_PORT/frontend/views/transactions.html?runner-check=$(date +%s%N)" \
            | grep -q 'id="transaction-table"' \
        && curl --fail --silent --max-time 3 \
            "http://127.0.0.1:$WEB_PORT/frontend/views/categories.html?runner-check=$(date +%s%N)" \
            | grep -q 'id="category-card-list"'
}

system_healthy() {
    managed_web_running && api_healthy && frontend_healthy
}

start_web() {
    if system_healthy; then
        ok "Đúng source wallet-refactor-implementation đang chạy tại http://localhost:$WEB_PORT/."
        return 0
    fi

    if [[ -f "$WEB_PID_FILE" ]]; then
        local stale_pid
        stale_pid="$(cat "$WEB_PID_FILE" 2>/dev/null || true)"
        managed_process "$stale_pid" "$WEB_PORT" || rm -f "$WEB_PID_FILE"
    fi
    if port_open "$WEB_PORT"; then
        fail "Cổng $WEB_PORT đang do tiến trình khác sử dụng; dừng để tránh mở nhầm source."
    fi

    info "Khởi động PHP Web Server trên cổng $WEB_PORT..."
    init_env
    nohup "$PHP_BIN" -S "127.0.0.1:$WEB_PORT" -t "$SOURCE_DIR" "$ROUTER_PHP" \
        >"$WEB_LOG" 2>&1 &
    local web_pid=$!
    printf '%s\n' "$web_pid" >"$WEB_PID_FILE"

    for ((i=0; i<20; i++)); do
        if system_healthy; then
            ok "API, giao diện refactor và module Ví đã sẵn sàng."
            return 0
        fi
        sleep 1
    done

    kill "$web_pid" 2>/dev/null || true
    rm -f "$WEB_PID_FILE"
    fail "Web/API không khởi động đúng source. Xem log: $WEB_LOG"
}

stop_web() {
    local pid=""
    [[ -f "$WEB_PID_FILE" ]] && pid="$(cat "$WEB_PID_FILE" 2>/dev/null || true)"
    if managed_process "$pid" "$WEB_PORT"; then
        kill -TERM "$pid" 2>/dev/null || true
        for ((i=0; i<30; i++)); do
            kill -0 "$pid" 2>/dev/null || break
            sleep 0.1
        done
        kill -0 "$pid" 2>/dev/null && warn "Tiến trình $pid chưa dừng; không dùng SIGKILL để tránh ảnh hưởng tiến trình khác."
    elif port_open "$WEB_PORT"; then
        warn "Cổng $WEB_PORT không thuộc runner này nên được giữ nguyên."
    fi
    rm -f "$WEB_PID_FILE"
    ok "Đã dừng web do runner quản lý; MySQL và dữ liệu được giữ nguyên."
}

stop_managed_webs() {
    local pid_file pid port
    for pid_file in "$PID_DIR"/web_*.pid; do
        [[ -f "$pid_file" ]] || continue
        port="${pid_file##*/web_}"
        port="${port%.pid}"
        pid="$(cat "$pid_file" 2>/dev/null || true)"
        if [[ "$port" =~ ^[0-9]+$ ]] && managed_process "$pid" "$port"; then
            info "Dừng web WealthFlow do runner quản lý tại cổng $port (PID $pid)..."
            kill -TERM "$pid" 2>/dev/null || true
        fi
        rm -f "$pid_file"
    done
    ok "Đã dừng các web WealthFlow cũ do runner này quản lý."
}

stop_all_services() {
    stop_managed_webs
    stop_conflicting_wealthflow_web
    stop_xampp_all
    ok "Đã dừng web WealthFlow và toàn bộ dịch vụ XAMPP."
}

print_access_info() {
    echo
    echo "==============================================================="
    echo "  Website:    http://localhost:8080/"
    echo "  Demo User:  user1@gmail.com / User1234"
    echo "  Admin:      admin@wealthflow.vn / Admin@1234"
    echo "  Database:   $DB_NAME tại $DB_HOST:$DB_PORT"
    echo "              đã drop, tạo mới và chạy migration 001–028"
    echo "==============================================================="
}

check_health() {
    echo
    if system_healthy; then
        ok "Web đang chạy đúng wallet-refactor-implementation tại cổng $WEB_PORT."
    elif port_open "$WEB_PORT"; then
        warn "Cổng $WEB_PORT đang được dùng nhưng không phải đúng tiến trình/source của runner."
    else
        warn "Web chưa chạy."
    fi
    db_running && ok "MySQL đang chạy." || warn "MySQL chưa sẵn sàng."
    printf '  PHP: %s\n' "$PHP_BIN"
    printf '  Log: %s\n' "$WEB_LOG"
}

run_tests() {
    ensure_dependencies
    "$PHP_BIN" "$BACKEND_DIR/tests/wallet_tests.php"
    "$PHP_BIN" "$BACKEND_DIR/tests/run_tests.php"
}

open_browser() {
    local url="http://localhost:$WEB_PORT/app.html#wallets"
    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$url" >/dev/null 2>&1 &
    elif command -v open >/dev/null 2>&1; then
        open "$url" >/dev/null 2>&1 &
    else
        printf '  Mở trình duyệt tại: %s\n' "$url"
    fi
}

validate_environment
init_env

while true; do
    echo
    echo "==============================================================="
    echo "       WEALTHFLOW WALLET REFACTOR - LINUX / XAMPP"
    echo "==============================================================="
    echo "  Demo User: user1@gmail.com / User1234"
    echo "  Admin:     admin@wealthflow.vn / Admin@1234"
    echo "---------------------------------------------------------------"
    echo "  [1] Dọn XAMPP + TẠO LẠI CSDL + chạy tại cổng 8080"
    echo "  [2] Dừng web WealthFlow và toàn bộ XAMPP"
    echo "  [3] Kiểm tra trạng thái"
    echo "  [4] Chạy kiểm thử Ví và backend"
    echo "  [5] Xem log gần nhất"
    echo "  [6] Mở thẳng trang Ví trên trình duyệt"
    echo "  [0] Thoát"
    echo "==============================================================="
    read -rp "  Chọn [0-6]: " choice

    case "$choice" in
        1)
            ensure_dependencies && prepare_clean_runtime && start_web && print_access_info
            ;;
        2)
            stop_all_services
            ;;
        3)
            check_health
            ;;
        4)
            run_tests
            ;;
        5)
            [[ -f "$WEB_LOG" ]] && tail -n 30 "$WEB_LOG" || warn "Chưa có log web."
            ;;
        6)
            open_browser
            ;;
        0)
            exit 0
            ;;
        *)
            warn "Lựa chọn không hợp lệ."
            ;;
    esac
done
