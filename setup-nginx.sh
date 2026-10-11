#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SITE_NAME="watrbx"
TEMPLATE="${PROJECT_DIR}/nginx/watrbx.conf.template"
PREFIX="${PREFIX:-}"
IS_TERMUX=0
[[ -n "${TERMUX_VERSION:-}" || "${PREFIX}" == *com.termux* ]] && IS_TERMUX=1

die() { printf 'Error: %s\n' "$*" >&2; exit 1; }
have() { command -v "$1" >/dev/null 2>&1; }
ask() { local prompt="$1" default="$2" value; read -r -p "${prompt} [${default}]: " value; printf '%s' "${value:-$default}"; }

[[ "$(uname -s)" == Linux ]] || die "This script requires Linux or Termux."
have nginx || die "Nginx was not found. Install it first (Termux: pkg install nginx php)."
[[ -f "$TEMPLATE" ]] || die "Nginx template not found: $TEMPLATE"

if [[ "$IS_TERMUX" -eq 1 ]]; then
  NGINX_PREFIX="${PREFIX}"
  CONF_DIR="${NGINX_PREFIX}/etc/nginx/conf.d"
  TARGET="${CONF_DIR}/watrbx.conf"
  PHP_SOCKET=""
  FPM_CONFIG="${NGINX_PREFIX}/etc/php-fpm.d/www.conf"
  DEFAULT_ROOT="${PROJECT_DIR}/public"
  [[ -f "${DEFAULT_ROOT}/index.php" ]] || DEFAULT_ROOT="${PROJECT_DIR}"
  mkdir -p "$CONF_DIR"
  ROOT_PRIVILEGES=0
else
  have sudo || [[ $EUID -eq 0 ]] || die "Install sudo or run as root."
  run_root() { if [[ $EUID -eq 0 ]]; then "$@"; else sudo "$@"; fi; }
  CONF_DIR="/etc/nginx/sites-available"
  ENABLED_DIR="/etc/nginx/sites-enabled"
  TARGET="${CONF_DIR}/${SITE_NAME}"
  PHP_SOCKET=""
  DEFAULT_ROOT="${PROJECT_DIR}/public"
  [[ -f "${DEFAULT_ROOT}/index.php" ]] || DEFAULT_ROOT="${PROJECT_DIR}"
  ROOT_PRIVILEGES=1
fi

DOCROOT="$(ask 'Public document root' "$DEFAULT_ROOT")"
DOCROOT="$(cd -- "$DOCROOT" 2>/dev/null && pwd)" || die "Invalid document root."
if [[ ! -f "${DOCROOT}/index.php" ]]; then
  printf 'Warning: %s/index.php does not exist.\n' "$DOCROOT"
  read -r -p "Continue anyway? [y/N] " confirm
  [[ "$confirm" =~ ^[Yy]$ ]] || exit 1
fi
SERVER_NAME="$(ask 'server_name' 'localhost')"

if [[ "$IS_TERMUX" -eq 1 ]]; then
  PHP_BIN="$(command -v php-fpm || true)"
  [[ -n "$PHP_BIN" ]] || PHP_BIN="${PREFIX}/bin/php-fpm"
  [[ -x "$PHP_BIN" ]] || die "php-fpm executable not found. Install PHP in Termux with: pkg install php"
  FPM_CONFIG="${PREFIX}/etc/php-fpm.d/www.conf"
  mkdir -p "$(dirname "$FPM_CONFIG")"
  if [[ ! -f "$FPM_CONFIG" ]]; then
    cat > "$FPM_CONFIG" <<EOF
[global]
daemonize = yes
error_log = ${PREFIX}/var/log/php-fpm.log

[www]
user = $(id -un)
group = $(id -gn)
listen = ${PREFIX}/var/run/watrbx-php-fpm.sock
listen.owner = $(id -un)
listen.group = $(id -gn)
listen.mode = 0660
pm = dynamic
pm.max_children = 8
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
catch_workers_output = yes
EOF
    printf 'Created PHP-FPM config: %s\n' "$FPM_CONFIG"
  else
    printf 'Using existing PHP-FPM config: %s\n' "$FPM_CONFIG"
    printf 'Make sure its [www] listen setting uses a Unix socket, for example:\n  listen = %s/var/run/watrbx-php-fpm.sock\n' "$PREFIX"
    read -r -p "Use the WATRBX socket path shown above? [Y/n] " socket_confirm
    if [[ ! "$socket_confirm" =~ ^[Nn]$ ]]; then
      if grep -qE '^[[:space:]]*listen[[:space:]]*=' "$FPM_CONFIG"; then
        sed -i -E "s|^[[:space:]]*listen[[:space:]]*=.*|listen = ${PREFIX}/var/run/watrbx-php-fpm.sock|" "$FPM_CONFIG"
      fi
    fi
  fi
  PHP_SOCKET="${PREFIX}/var/run/watrbx-php-fpm.sock"
  mkdir -p "${PREFIX}/var/run" "${PREFIX}/var/log"
else
  for candidate in /run/php/php*-fpm.sock /run/php-fpm/www.sock /var/run/php/php*-fpm.sock; do
    if [[ -S "$candidate" ]]; then PHP_SOCKET="$candidate"; break; fi
  done
  if [[ -z "$PHP_SOCKET" ]]; then
    printf 'No active PHP-FPM socket was detected.\n'
    PHP_SOCKET="$(ask 'PHP-FPM Unix socket path' '/run/php/php-fpm.sock')"
  fi
fi

TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
sed \
  -e "s|@@DOCROOT@@|${DOCROOT//|/\\|}|g" \
  -e "s|@@SERVER_NAME@@|${SERVER_NAME//|/\\|}|g" \
  -e "s|@@PHP_SOCKET@@|${PHP_SOCKET//|/\\|}|g" \
  "$TEMPLATE" > "$TMP"

if [[ "$IS_TERMUX" -eq 1 ]]; then
  [[ -e "$TARGET" ]] && cp -a "$TARGET" "${TARGET}.bak.$(date +%Y%m%d%H%M%S)"
  install -m 0644 "$TMP" "$TARGET"
  if ! grep -qE '^[[:space:]]*include[[:space:]]+.*conf\.d/\*\.conf' "${PREFIX}/etc/nginx/nginx.conf"; then
    printf 'Warning: check that %s/nginx.conf includes conf.d/*.conf.\n' "$PREFIX/etc/nginx"
  fi
  nginx -t
  printf '\nConfiguration installed: %s\n' "$TARGET"
  printf 'PHP-FPM config: %s\n' "$FPM_CONFIG"
  printf 'Next run: ./server-nginx.sh\n'
else
  if [[ -e "$TARGET" ]]; then
    BACKUP="${TARGET}.bak.$(date +%Y%m%d%H%M%S)"
    printf 'Backing up existing configuration to %s\n' "$BACKUP"
    run_root cp -a "$TARGET" "$BACKUP"
  fi
  run_root install -d -m 0755 "$CONF_DIR" "$ENABLED_DIR"
  run_root install -m 0644 "$TMP" "$TARGET"
  run_root ln -sfn "$TARGET" "${ENABLED_DIR}/${SITE_NAME}"
  run_root nginx -t || die "Nginx configuration test failed. Fix the generated configuration before starting Nginx."
  if have systemctl; then
    run_root systemctl reload nginx 2>/dev/null || run_root systemctl start nginx
  else
    printf 'Configuration validated. Start or reload Nginx with your system service manager.\n'
  fi
  printf '\nConfiguration complete: %s\n' "$TARGET"
fi
printf 'Document root: %s\nPHP-FPM socket: %s\nLocal URL: http://127.0.0.1:8080/ (Termux default may vary)\n' "$DOCROOT" "$PHP_SOCKET"

