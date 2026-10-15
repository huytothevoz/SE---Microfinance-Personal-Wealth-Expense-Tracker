#!/usr/bin/env bash
set -euo pipefail

# Fresh install: archive ALL old htdocs, deploy only WealthFlow, reset its DB.
PROJECT_ROOT="$(cd -- "$(dirname -- "$0")" && pwd)"
XAMPP_DIR="$(printenv XAMPP_DIR || echo /opt/lampp)"
XAMPP_PORT="$(printenv XAMPP_PORT || echo 8080)"
COMPOSER_BIN="$(printenv COMPOSER_BIN || command -v composer || true)"
PHP_BIN="$XAMPP_DIR/bin/php"
DEST="$XAMPP_DIR/htdocs/wealthflow"
[[ "$XAMPP_PORT" =~ ^[0-9]+$ ]] || { echo 'Invalid XAMPP_PORT'; exit 1; }
[[ -x "$PHP_BIN" && -x "$XAMPP_DIR/lampp" ]] || { echo "XAMPP not found: $XAMPP_DIR"; exit 1; }
[[ -f "$COMPOSER_BIN" ]] || { echo 'Install Composer first, or set COMPOSER_BIN to composer.phar.'; exit 1; }
if [[ "$(id -u)" != 0 ]]; then
  echo 'Run: sudo -E bash setup-run-version-linux.sh'
  exit 1
fi

echo "Fresh install: ALL of $XAMPP_DIR/htdocs will be moved to a backup."
echo 'Only the WealthFlow database is reset. Other databases are preserved.'
export PATH="$XAMPP_DIR/bin:$PATH"
export COMPOSER_ALLOW_SUPERUSER=1
# Fetch dependencies before changing the deployed site or database.
"$PHP_BIN" -d 'include_path=.:/usr/share/php' "$COMPOSER_BIN" install --working-dir="$PROJECT_ROOT/source/backend" --no-dev --prefer-dist --no-interaction --optimize-autoloader
"$XAMPP_DIR/lampp" startmysql
ready=0
for ((attempt=0; attempt<30; attempt++)); do
  if "$XAMPP_DIR/bin/mysqladmin" ping --silent >/dev/null 2>&1; then ready=1; break; fi
  sleep 1
done
[[ "$ready" == 1 ]] || { echo 'MySQL did not start. Check XAMPP logs.'; exit 1; }

"$XAMPP_DIR/lampp" stopapache
if ! "$PHP_BIN" "$PROJECT_ROOT/scripts/install-xampp.php" "$XAMPP_DIR" "$XAMPP_PORT"; then
  "$XAMPP_DIR/lampp" startapache
  exit 1
fi
APACHE_GROUP="$(awk '$1 == "Group" { print $2; exit }' "$XAMPP_DIR/etc/httpd.conf")"
[[ -n "$APACHE_GROUP" ]] || { echo 'Cannot identify Apache Group in httpd.conf'; exit 1; }
chgrp "$APACHE_GROUP" "$DEST/backend/config.local.php" "$DEST/backend/storage"
chmod 640 "$DEST/backend/config.local.php"
chmod 2770 "$DEST/backend/storage"
"$XAMPP_DIR/lampp" startapache
echo "Website: http://localhost:$XAMPP_PORT/"
echo "Configure SMTP: $DEST/backend/config.local.php"
if command -v curl >/dev/null; then
  curl --retry 5 --retry-connrefused --retry-delay 1 --fail --silent --show-error "http://localhost:$XAMPP_PORT/api/health"
fi
