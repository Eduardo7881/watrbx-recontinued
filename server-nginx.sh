#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PREFIX="${PREFIX:-}"
IS_TERMUX=0
[[ -n "${TERMUX_VERSION:-}" || "${PREFIX}" == *com.termux* ]] && IS_TERMUX=1
die() { printf 'Error: %s\n' "$*" >&2; exit 1; }
have() { command -v "$1" >/dev/null 2>&1; }

[[ "$(uname -s)" == Linux ]] || die "This script requires Linux or Termux."
have nginx || die "Nginx is not installed. Install it first."
if [[ "$IS_TERMUX" -eq 1 ]]; then
  SITE="${PREFIX}/etc/nginx/conf.d/watrbx.conf"
  FPM_CONFIG="${PREFIX}/etc/php-fpm.d/www.conf"
  SOCKET="${PREFIX}/var/run/watrbx-php-fpm.sock"
  [[ -f "$SITE" ]] || die "WATRBX Nginx configuration not found. Run ./setup-nginx.sh first."
  command -v php-fpm >/dev/null 2>&1 || die "php-fpm was not found. Run: pkg install php"
  mkdir -p "${PREFIX}/var/run" "${PREFIX}/var/log"
  nginx -t
  # Stop prior instances owned by this Termux user, then start PHP-FPM and Nginx.
  pkill -x php-fpm 2>/dev/null || true
  pkill -x nginx 2>/dev/null || true
  php-fpm --fpm-config "$FPM_CONFIG"
  for _ in {1..30}; do [[ -S "$SOCKET" ]] && break; sleep 0.2; done
  [[ -S "$SOCKET" ]] || die "PHP-FPM did not create the expected socket: $SOCKET. Check $FPM_CONFIG and the PHP-FPM log."
  nginx
  printf 'WATRBX is running with Nginx + PHP-FPM.\n'
  printf 'Open http://127.0.0.1:8080/ if your Nginx config listens on port 8080.\n'
  printf 'Stop services with: pkill -x nginx; pkill -x php-fpm\n'
else
  have sudo || [[ $EUID -eq 0 ]] || die "Install sudo or run as root."
  run_root() { if [[ $EUID -eq 0 ]]; then "$@"; else sudo "$@"; fi; }
  SITE="/etc/nginx/sites-available/watrbx"
  [[ -f "$SITE" ]] || die "WATRBX site is not configured. Run ./setup-nginx.sh first."
  nginx -t
  have systemctl || die "systemd is unavailable. Start Nginx and PHP-FPM using your distribution's service manager."
  FPM_SERVICE=""
  for svc in php-fpm php8.5-fpm php8.4-fpm php8.3-fpm php8.2-fpm php8.1-fpm php8.0-fpm php8.0-fpm; do
    if systemctl list-unit-files "${svc}.service" --no-legend 2>/dev/null | grep -q "${svc}.service"; then FPM_SERVICE="$svc"; break; fi
  done
  [[ -n "$FPM_SERVICE" ]] || die "Could not find a PHP-FPM systemd service. Check: systemctl list-unit-files '*fpm*'"
  run_root systemctl enable --now "$FPM_SERVICE"
  run_root systemctl enable --now nginx
  run_root systemctl restart "$FPM_SERVICE"
  run_root systemctl reload nginx
  printf 'Nginx and %s are running.\n' "$FPM_SERVICE"
  printf 'Open http://localhost/ or this server’s IP/domain.\n'
fi
