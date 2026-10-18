#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd -- "$(dirname -- "$0")" && pwd)"
XAMPP_DIR="${XAMPP_DIR:-/opt/lampp}"
XAMPP_PORT="${XAMPP_PORT:-8080}"
PHP_BIN="$XAMPP_DIR/bin/php"
MYSQL_BIN="$XAMPP_DIR/bin/mysql"
DEST="$XAMPP_DIR/htdocs/wealthflow"
SOURCE_DIR="$PROJECT_ROOT/source"

echo "==============================================================="
echo "       WEALTHFLOW RUNNER (LINUX / XAMPP)                       "
echo "==============================================================="

# Kiem tra quyen root
if [[ "$(id -u)" != 0 ]]; then
    echo "[!] Tren Linux, XAMPP can quyen quan tri de bat Apache/MySQL."
    echo "[*] Dang chuyen quyen sang sudo..."
    exec sudo -E bash "$0" "$@"
fi

# Kiem tra thu muc XAMPP
if [[ ! -d "$XAMPP_DIR" || ! -x "$PHP_BIN" || ! -x "$XAMPP_DIR/lampp" ]]; then
    echo "[ERR] Khong tim thay XAMPP tai $XAMPP_DIR. Vui long cai dat XAMPP (/opt/lampp) hoac dat bien XAMPP_DIR."
    exit 1
fi
echo "[INFO] Thu muc XAMPP: $XAMPP_DIR"

export PATH="$XAMPP_DIR/bin:$PATH"
export COMPOSER_ALLOW_SUPERUSER=1

# 1. Kiem tra va bat MySQL
echo "[INFO] Kiem tra dich vu MySQL..."
if ! "$XAMPP_DIR/bin/mysqladmin" ping --silent >/dev/null 2>&1; then
    echo "[INFO] Dang khoi dong MySQL..."
    "$XAMPP_DIR/lampp" startmysql
    ready=0
    for ((attempt=0; attempt<20; attempt++)); do
        if "$XAMPP_DIR/bin/mysqladmin" ping --silent >/dev/null 2>&1; then ready=1; break; fi
        sleep 1
    done
    if [[ "$ready" != 1 ]]; then
        echo "[ERR] Khong the khoi dong MySQL. Kiem tra log cua XAMPP."
        exit 1
    fi
fi
echo "[OK]   MySQL dang hoat dong."

# 2. Kiem tra dependencies Composer
if [[ ! -f "$SOURCE_DIR/backend/vendor/autoload.php" ]]; then
    echo "[INFO] Cai dat dependencies Composer..."
    COMPOSER_BIN="$(command -v composer || true)"
    if [[ -z "$COMPOSER_BIN" ]]; then
        if [[ ! -f "$PROJECT_ROOT/composer.phar" ]]; then
            echo "[INFO] Dang tai composer.phar..."
            curl -sS https://getcomposer.org/download/latest-stable/composer.phar -o "$PROJECT_ROOT/composer.phar"
        fi
        COMPOSER_BIN="$PROJECT_ROOT/composer.phar"
    fi
    "$PHP_BIN" -d 'include_path=.:/usr/share/php' "$COMPOSER_BIN" install --working-dir="$SOURCE_DIR/backend" --no-dev --prefer-dist --no-interaction --optimize-autoloader
fi

# 3. Kiem tra va deploy source sang htdocs/wealthflow
if [[ ! -f "$DEST/index.html" || ! -f "$DEST/backend/config.local.php" ]]; then
    echo "[INFO] Dong bo ma nguon sang $DEST..."
    mkdir -p "$DEST"
    cp -r "$SOURCE_DIR/"* "$DEST/"
    if [[ ! -f "$DEST/backend/config.local.php" && -f "$DEST/backend/config.example.php" ]]; then
        cp "$DEST/backend/config.example.php" "$DEST/backend/config.local.php"
    fi
fi

# Phan quyen cho Apache
APACHE_GROUP="$(awk '$1 == "Group" { print $2; exit }' "$XAMPP_DIR/etc/httpd.conf" 2>/dev/null || echo daemon)"
if [[ -n "$APACHE_GROUP" ]]; then
    chgrp -R "$APACHE_GROUP" "$DEST/backend/storage" "$DEST/backend/config.local.php" 2>/dev/null || true
    chmod 640 "$DEST/backend/config.local.php" 2>/dev/null || true
    chmod 2770 "$DEST/backend/storage" 2>/dev/null || true
fi

# 4. Kiem tra database wealthflow
has_users=$("$MYSQL_BIN" -u root -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'wealthflow' AND table_name = 'users';" 2>/dev/null | grep -c "1" || true)
if [[ "$has_users" -eq 0 ]]; then
    echo "[INFO] Khoi tao database 'wealthflow' va migrations..."
    "$MYSQL_BIN" -u root -e "CREATE DATABASE IF NOT EXISTS wealthflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    for f in "$SOURCE_DIR/database"/*.sql; do
        if [[ -f "$f" ]]; then
            echo "  -> Chay migration: $(basename "$f")"
            "$MYSQL_BIN" -u root wealthflow < "$f"
        fi
    done
fi

# 5. Cau hinh Apache httpd.conf
HTTPD_CONF="$XAMPP_DIR/etc/httpd.conf"
need_restart=0

if ! grep -q "Listen .*$XAMPP_PORT" "$HTTPD_CONF"; then
    echo "[INFO] Cau hinh Listen $XAMPP_PORT trong $HTTPD_CONF..."
    sed -i -E "s/^Listen [0-9]+/Listen 127.0.0.1:$XAMPP_PORT/" "$HTTPD_CONF"
    need_restart=1
fi

if ! grep -q "DocumentRoot \"$DEST\"" "$HTTPD_CONF"; then
    echo "[INFO] Cau hinh DocumentRoot \"$DEST\" trong $HTTPD_CONF..."
    sed -i -E "s|^DocumentRoot \".*\"|DocumentRoot \"$DEST\"|" "$HTTPD_CONF"
    need_restart=1
fi

if ! grep -q "<Directory \"$DEST\">" "$HTTPD_CONF"; then
    cat >> "$HTTPD_CONF" <<EOF

<Directory "$DEST">
    Options Indexes FollowSymLinks ExecCGI Includes
    AllowOverride All
    Require all granted
</Directory>
EOF
    need_restart=1
fi

# 6. Khoi dong Apache
apache_listening=$(netstat -tlpn 2>/dev/null | grep -c ":$XAMPP_PORT " || ss -tlpn 2>/dev/null | grep -c ":$XAMPP_PORT " || echo 0)
if [[ "$need_restart" -eq 1 || "$apache_listening" -eq 0 ]]; then
    echo "[INFO] Dang khoi dong Apache tren cong $XAMPP_PORT..."
    "$XAMPP_DIR/lampp" stopapache >/dev/null 2>&1 || true
    "$XAMPP_DIR/lampp" startapache
fi

# 7. Kiem tra API Health
echo "[OK]   Kiem tra ket noi..."
if command -v curl >/dev/null 2>&1; then
    curl --retry 3 --retry-connrefused --fail --silent "http://localhost:$XAMPP_PORT/api/health" >/dev/null 2>&1 || true
fi

echo ""
echo "==============================================================="
echo "          WEALTHFLOW DA SAN SANG DE SU DUNG!                   "
echo "==============================================================="
echo "  [+] Trang chu:      http://localhost:$XAMPP_PORT/"
echo "  [+] Dashboard App:  http://localhost:$XAMPP_PORT/app.html"
echo "  [+] API Health:     http://localhost:$XAMPP_PORT/api/health"
echo "---------------------------------------------------------------"
echo "  [+] TAI KHOAN MAU (DEMO):"
echo "      Email:    user1@gmail.com"
echo "      Mat khau: User1234"
echo "==============================================================="
echo ""

# Tu dong mo trinh duyet neu co GUI
if [[ -n "${DISPLAY:-}" ]]; then
    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "http://localhost:$XAMPP_PORT/" >/dev/null 2>&1 &
    fi
fi
