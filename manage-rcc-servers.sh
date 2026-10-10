#!/data/data/com.termux/files/usr/bin/bash
# WatrBX RCCService server manager for Termux/Linux.
set -Eeuo pipefail

if [[ -z "${BASH_VERSION:-}" ]]; then exec bash "$0" "$@"; fi

PROJECT_DIR="$(cd "${1:-.}" 2>/dev/null && pwd)" || { echo "[ERROR] Project directory not found: ${1:-.}" >&2; exit 1; }
ENV_FILE="$PROJECT_DIR/.env"

say() { printf '%s\n' "$*"; }
fail() { say "[ERROR] $*" >&2; exit 1; }

[[ -f "$ENV_FILE" ]] || fail "Missing $ENV_FILE. Run this from the WatrBX project directory or pass its path."
command -v mariadb >/dev/null 2>&1 || fail "MariaDB client not found. Install it with: pkg install mariadb"

# Read the simple KEY=value format used by WatrBX .env without sourcing executable content.
env_value() {
    local key="$1" line value
    line="$(grep -E "^[[:space:]]*${key}=" "$ENV_FILE" | tail -n 1 || true)"
    [[ -n "$line" ]] || { printf ''; return; }
    value="${line#*=}"
    value="${value%$'\r'}"
    value="${value#\"}"; value="${value%\"}"
    value="${value#\'}"; value="${value%\'}"
    printf '%s' "$value"
}

DB_HOST="$(env_value DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_value DB_PORT)"; DB_PORT="${DB_PORT:-3306}"
DB_NAME="$(env_value DB_NAME)"
DB_USER="$(env_value DB_USER)"
DB_PASS="$(env_value DB_PASS)"
[[ -n "$DB_NAME" && -n "$DB_USER" ]] || fail "DB_NAME or DB_USER is missing in .env."
[[ "$DB_NAME" =~ ^[a-zA-Z0-9_]+$ ]] || fail "Unsafe database name in .env: $DB_NAME"

DB=(mariadb --protocol=tcp -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME")
if [[ -n "$DB_PASS" ]]; then DB+=("-p$DB_PASS"); fi
sql() { "${DB[@]}" --batch --raw "$@"; }

if ! sql -e 'SELECT 1' >/dev/null 2>&1; then
    fail "Cannot connect to database '$DB_NAME' at $DB_HOST:$DB_PORT using DB_USER from .env. Check .env and MariaDB."
fi

if ! sql -N -e "SHOW TABLES LIKE 'servers'" | grep -qx 'servers'; then
    fail "Table 'servers' does not exist in '$DB_NAME'. Import structure.sql first."
fi

# Terminal presentation: colors are disabled automatically when output is redirected.
if [[ -t 1 && -z "${NO_COLOR:-}" ]]; then
    C_RESET=$'\033[0m'; C_BOLD=$'\033[1m'; C_DIM=$'\033[2m'
    C_RED=$'\033[38;5;203m'; C_GREEN=$'\033[38;5;82m'; C_YELLOW=$'\033[38;5;220m'
    C_BLUE=$'\033[38;5;75m'; C_CYAN=$'\033[38;5;51m'; C_MAGENTA=$'\033[38;5;177m'
    C_ORANGE=$'\033[38;5;214m'; C_WHITE=$'\033[38;5;255m'; C_PANEL=$'\033[38;5;240m'
    C_BG=$'\033[48;5;235m'
    C_RESET=$'\033[0m'; C_BOLD=$'\033[1m'; C_DIM=$'\033[2m'
else
    C_RESET=''; C_BOLD=''; C_DIM=''; C_RED=''; C_GREEN=''; C_YELLOW=''; C_BLUE=''; C_CYAN=''; C_MAGENTA=''; C_ORANGE=''; C_WHITE=''; C_PANEL=''; C_BG=''
fi

term_width() {
    local w
    w="$(tput cols 2>/dev/null || printf '80')"
    [[ "$w" =~ ^[0-9]+$ ]] || w=80
    (( w > 100 )) && w=100
    (( w < 60 )) && w=60
    printf '%s' "$w"
}

line() {
    local width
    width="$(term_width)"
    printf '%s' "$C_PANEL"
    printf '%*s' "$width" '' | tr ' ' '─'
    printf '%s\n' "$C_RESET"
}

ok() { printf '  %s●%s  %s\n' "$C_GREEN" "$C_RESET" "$*"; }
warn() { printf '  %s▲%s  %s\n' "$C_YELLOW" "$C_RESET" "$*"; }
err() { printf '  %s✖%s  %s\n' "$C_RED" "$C_RESET" "$*" >&2; }

brand() {
    printf '\n%s' "$C_CYAN"
    cat <<'LOGO'
██╗    ██╗ █████╗ ████████╗██████╗ ██████╗ ██╗  ██╗
██║    ██║██╔══██╗╚══██╔══╝██╔══██╗██╔══██╗╚██╗██╔╝
██║ █╗ ██║███████║   ██║   ██████╔╝██████╔╝ ╚███╔╝
██║███╗██║██╔══██║   ██║   ██╔══██╗██╔══██╗ ██╔██╗
╚███╔███╔╝██║  ██║   ██║   ██████╔╝██║  ██║██╔╝ ██╗
 ╚══╝╚══╝ ╚═╝  ╚═╝   ╚═╝   ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝
LOGO
    printf '%s' "$C_RESET"
    printf ' %sR C C S E R V I C E   /   C O N T R O L   C E N T E R%s\n' "$C_BOLD$C_WHITE" "$C_RESET"
    printf ' %sInstance inventory • Manager registry • Database tools%s\n' "$C_DIM" "$C_RESET"
}

header() {
    printf '\n%s╭─ %s%s%s ─%s\n' "$C_CYAN" "$C_BOLD$C_WHITE" "$1" "$C_RESET" "$C_CYAN"
    line
}

section() {
    printf '\n %s▰%s %s%s%s\n' "$C_MAGENTA" "$C_RESET" "$C_BOLD" "$1" "$C_RESET"
}

menu_item() {
    printf '  %s[%s%s%s]%s  %-34s %s\n' "$C_CYAN" "$C_BOLD" "$1" "$C_RESET" "$C_RESET" "$2" "${3:-}"
}

pause() { printf '\n'; read -r -p 'Pressione Enter para continuar...' _ || true; }

show_dashboard() {
    local server_count instance_count idle_count busy_count db_status
    server_count="$(sql -N -e 'SELECT COUNT(*) FROM servers' 2>/dev/null || echo '?')"
    if sql -N -e "SHOW TABLES LIKE 'rccinstances'" 2>/dev/null | grep -qx 'rccinstances'; then
        instance_count="$(sql -N -e 'SELECT COUNT(*) FROM rccinstances' 2>/dev/null || echo '?')"
        idle_count="$(sql -N -e 'SELECT COUNT(*) FROM rccinstances WHERE is_idle=1' 2>/dev/null || echo '?')"
        busy_count="$(sql -N -e 'SELECT COUNT(*) FROM rccinstances WHERE is_idle=0' 2>/dev/null || echo '?')"
    else instance_count='N/A'; idle_count='N/A'; busy_count='N/A'; fi
    db_status="$(sql -N -e 'SELECT VERSION()' 2>/dev/null | head -n1 || printf 'unknown')"
    brand
    line
    printf ' %s● DATABASE%s  %s%s%s   %s│%s   %s● CONNECTION%s  %sONLINE%s\n' "$C_MAGENTA" "$C_RESET" "$C_WHITE" "$DB_NAME" "$C_RESET" "$C_PANEL" "$C_RESET" "$C_GREEN" "$C_RESET" "$C_GREEN" "$C_RESET"
    printf ' %sMariaDB %s%s\n' "$C_DIM" "$db_status" "$C_RESET"
    printf ' %sProject:%s %s\n %sEndpoint:%s %s:%s\n' "$C_DIM" "$C_RESET" "$PROJECT_DIR" "$C_DIM" "$C_RESET" "$DB_HOST" "$DB_PORT"
    line
    printf ' %s┌──────────────────────┬──────────────────────┐%s\n' "$C_PANEL" "$C_RESET"
    printf ' %s│%s  %sMANAGER RECORDS%s     %s│%s  %sRCC INSTANCES%s       %s│%s\n' "$C_PANEL" "$C_RESET" "$C_CYAN" "$C_RESET" "$C_PANEL" "$C_RESET" "$C_MAGENTA" "$C_RESET" "$C_PANEL" "$C_RESET"
    printf ' %s│%s       %s%-12s%s   %s│%s       %s%-12s%s   %s│%s\n' "$C_PANEL" "$C_RESET" "$C_BOLD$C_WHITE" "$server_count" "$C_RESET" "$C_PANEL" "$C_RESET" "$C_BOLD$C_WHITE" "$instance_count" "$C_RESET" "$C_PANEL" "$C_RESET"
    printf ' %s├──────────────────────┼──────────────────────┤%s\n' "$C_PANEL" "$C_RESET"
    printf ' %s│%s  %sIDLE%s                 %s│%s  %sBUSY%s                 %s│%s\n' "$C_PANEL" "$C_RESET" "$C_GREEN" "$C_RESET" "$C_PANEL" "$C_RESET" "$C_ORANGE" "$C_RESET" "$C_PANEL" "$C_RESET"
    printf ' %s│%s       %s%-12s%s   %s│%s       %s%-12s%s   %s│%s\n' "$C_PANEL" "$C_RESET" "$C_GREEN" "$idle_count" "$C_RESET" "$C_PANEL" "$C_RESET" "$C_ORANGE" "$busy_count" "$C_RESET" "$C_PANEL" "$C_RESET"
    printf ' %s└──────────────────────┴──────────────────────┘%s\n' "$C_PANEL" "$C_RESET"
    printf ' %sHint:%s use %s[?]%s for help, %s[q]%s to quit. Changes apply to the selected database.\n' "$C_DIM" "$C_RESET" "$C_CYAN" "$C_RESET" "$C_CYAN" "$C_RESET"
}

show_servers() {
    header 'REGISTERED SERVER MANAGERS'
    sql -e 'SELECT id AS row_id, server_id, ip AS manager_host, port, CONCAT(LEFT(access_key, 6), "…") AS key_prefix FROM servers ORDER BY id' || true
    printf '\n%sNote:%s servers.ip:port is the RCCService manager/API endpoint, not automatically the public game endpoint.\n' "$C_YELLOW" "$C_RESET"
    if sql -N -e "SHOW TABLES LIKE 'rccinstances'" | grep -qx 'rccinstances'; then
        header 'REGISTERED RCC INSTANCES'
        sql -e 'SELECT id AS row_id, guid, serverid, type, port, is_idle AS idle, placeid FROM rccinstances ORDER BY id' || true
    else warn "Table rccinstances was not found."; fi
}

search_records() {
    local term escaped
    header 'SEARCH SERVER RECORDS'
    read -r -p 'Search server_id or manager host: ' term
    [[ -n "$term" ]] || { warn 'Empty search.'; return 0; }
    # Restrict search input to avoid SQL syntax injection in this small CLI.
    [[ "$term" =~ ^[a-zA-Z0-9._:%-]{1,255}$ ]] || { err 'Use only letters, numbers, dots, underscores, colon, percent and hyphen.'; return 1; }
    escaped="${term//\'/\'\'}"
    sql -e "SELECT id, server_id, ip, port, CONCAT(LEFT(access_key,6),'…') AS key_prefix FROM servers WHERE server_id LIKE '%$escaped%' OR ip LIKE '%$escaped%' ORDER BY id" || true
}

show_instances() {
    header 'RCC INSTANCE INVENTORY'
    if ! sql -N -e "SHOW TABLES LIKE 'rccinstances'" | grep -qx 'rccinstances'; then warn 'Table rccinstances does not exist.'; return 0; fi
    printf 'Filter: [a]ll, [i]dle, [b]usy, [p]lace ID\n'
    read -r -p 'Filter [a]: ' filter
    case "${filter:-a}" in
      i) sql -e 'SELECT id,guid,serverid,type,port,is_idle,placeid,timestarted FROM rccinstances WHERE is_idle=1 ORDER BY id' ;;
      b) sql -e 'SELECT id,guid,serverid,type,port,is_idle,placeid,timestarted FROM rccinstances WHERE is_idle=0 ORDER BY id' ;;
      p) local place; read -r -p 'Place ID: ' place; [[ "$place" =~ ^[0-9]+$ ]] || { err 'Place ID must be numeric.'; return 1; }; sql -e "SELECT id,guid,serverid,type,port,is_idle,placeid,timestarted FROM rccinstances WHERE placeid=$place ORDER BY id" ;;
      a|'') sql -e 'SELECT id,guid,serverid,type,port,is_idle,placeid,timestarted FROM rccinstances ORDER BY id' ;;
      *) err 'Unknown filter.'; return 1;;
    esac
}

check_database() {
    header 'DIAGNOSTICS'
    printf 'Project .env: %s\n' "$ENV_FILE"
    [[ -r "$ENV_FILE" ]] && ok '.env readable' || warn '.env is not readable'
    printf 'MariaDB client: '; command -v mariadb || true
    printf 'OpenSSL: '; command -v openssl || true
    if sql -e 'SELECT VERSION() AS mariadb_version, DATABASE() AS selected_database, CURRENT_USER() AS authenticated_user' 2>&1; then ok 'Database connection succeeded'; else err 'Database connection failed'; return 1; fi
    local table
    for table in servers rccinstances; do
        if sql -N -e "SHOW TABLES LIKE '$table'" 2>/dev/null | grep -qx "$table"; then ok "Table $table exists"; else warn "Table $table is missing"; fi
    done
    printf '\n%sImportant:%s this check validates SQL connectivity only; it does not prove the RCCService API is reachable.\n' "$C_YELLOW" "$C_RESET"
}

export_records() {
    local out
    read -r -p 'Output file [watrbx-servers-export.tsv]: ' out
    out="${out:-watrbx-servers-export.tsv}"
    [[ "$out" != */* ]] || { err 'Choose a filename in the current directory, without a path.'; return 1; }
    [[ ! -e "$out" ]] || { read -r -p 'File exists. Overwrite? Type YES: ' confirm; [[ "${confirm:-}" == YES ]] || { warn 'Export cancelled.'; return 0; }; }
    sql --batch -e 'SELECT id,server_id,ip,port,access_key FROM servers ORDER BY id' > "$out" && ok "Export written to $PROJECT_DIR/$out" || { err 'Export failed.'; return 1; }
    warn 'The export contains secret access keys. Keep it private and do not commit it to Git.'
}

add_server() {
    local server_id ip port access_key exists
    say ""
    say "Add a WatrBX server record"
    say "This record points to the RCCService manager/API, not an individual game instance."
    read -r -p "Server ID (example: rcc-primary): " server_id
    [[ "$server_id" =~ ^[a-zA-Z0-9._-]{1,255}$ ]] || { say "[ERROR] Use 1-255 letters, numbers, dots, underscores or hyphens."; return 1; }
    read -r -p "RCCService manager IP/host (example: 127.0.0.1): " ip
    [[ "$ip" =~ ^[a-zA-Z0-9.:-]+$ ]] || { say "[ERROR] Invalid host/IP. Use an IP address or hostname without a scheme/path."; return 1; }
    read -r -p "RCCService manager/API port: " port
    [[ "$port" =~ ^[0-9]+$ ]] && (( port >= 1 && port <= 65535 )) || { say "[ERROR] Port must be between 1 and 65535."; return 1; }
    exists="$(sql -N -e "SELECT COUNT(*) FROM servers WHERE server_id='${server_id//\'/\'\'}'")"
    [[ "$exists" == "0" ]] || { say "[ERROR] Server ID '$server_id' already exists."; return 1; }
    access_key="$(openssl rand -hex 32 2>/dev/null)" || fail "OpenSSL failed to generate an access key."
    [[ ${#access_key} -ge 32 ]] || fail "Generated access key is unexpectedly short."
    # Validate values above; escape SQL string literals before insertion.
    server_id="${server_id//\'/\'\'}"
    ip="${ip//\'/\'\'}"
    access_key="${access_key//\'/\'\'}"
    if sql -e "INSERT INTO servers (server_id, ip, port, access_key) VALUES ('$server_id', '$ip', $port, '$access_key')"; then
        say ""
        say "[OK] Server record created."
        say "Server ID: $server_id"
        say "Manager endpoint: http://$ip:$port"
        say "Access key (copy it now; it is shown only once):"
        say "$access_key"
        say ""
        say "Configure the RCCService manager to use this access key for Authorization on WatrBX API requests."
        say "Do not publish or commit this key."
    else
        say "[ERROR] Database insert failed. No server was added."
        return 1
    fi
}

edit_server() {
    local id ip port
    read -r -p "Database row ID to edit: " id
    [[ "$id" =~ ^[0-9]+$ ]] || { say "[ERROR] ID must be numeric."; return 1; }
    if [[ "$(sql -N -e "SELECT COUNT(*) FROM servers WHERE id=$id")" != "1" ]]; then say "[ERROR] No server row with id $id."; return 1; fi
    read -r -p "New manager IP/host: " ip
    [[ "$ip" =~ ^[a-zA-Z0-9.:-]+$ ]] || { say "[ERROR] Invalid host/IP. Use an IP address or hostname without a scheme/path."; return 1; }
    read -r -p "New manager/API port: " port
    [[ "$port" =~ ^[0-9]+$ ]] && (( port >= 1 && port <= 65535 )) || { say "[ERROR] Port must be between 1 and 65535."; return 1; }
    ip="${ip//\'/\'\'}"
    sql -e "UPDATE servers SET ip='$ip', port=$port WHERE id=$id" && say "[OK] Server endpoint updated."
}

rotate_key() {
    local id key
    read -r -p "Database row ID to rotate access key: " id
    [[ "$id" =~ ^[0-9]+$ ]] || { say "[ERROR] ID must be numeric."; return 1; }
    if [[ "$(sql -N -e "SELECT COUNT(*) FROM servers WHERE id=$id")" != "1" ]]; then say "[ERROR] No server row with id $id."; return 1; fi
    key="$(openssl rand -hex 32)"
    sql -e "UPDATE servers SET access_key='$key' WHERE id=$id" || return 1
    say "[OK] Access key rotated. Update the RCCService configuration immediately:"
    say "$key"
    say "The old key stops working as soon as this change is committed."
}

delete_server() {
    local id confirm
    read -r -p "Database row ID to delete: " id
    [[ "$id" =~ ^[0-9]+$ ]] || { say "[ERROR] ID must be numeric."; return 1; }
    if [[ "$(sql -N -e "SELECT COUNT(*) FROM servers WHERE id=$id")" != "1" ]]; then say "[ERROR] No server row with id $id."; return 1; fi
    say "WARNING: RCC instances referencing this server_id will be left in the database."
    read -r -p "Type DELETE to confirm: " confirm
    [[ "$confirm" == "DELETE" ]] || { say "Cancelled."; return 0; }
    sql -e "DELETE FROM servers WHERE id=$id" && say "[OK] Server record deleted."
}

while true; do
    show_dashboard
    section 'SERVER MANAGERS'
    menu_item 1 'List managers and registered instances' 'VIEW'
    menu_item 2 'Register a new manager endpoint' 'CREATE'
    menu_item 3 'Edit manager host / API port' 'EDIT'
    menu_item 4 'Rotate manager access key' 'SECURITY'
    menu_item 5 'Delete manager record' 'DANGER'
    section 'RCC INSTANCE INVENTORY'
    menu_item 6 'Browse and filter instances' 'READ ONLY'
    menu_item 7 'Search manager records' 'FIND'
    section 'ADMINISTRATION TOOLS'
    menu_item 8 'Run database diagnostics' 'CHECK'
    menu_item 9 'Export manager records to TSV' 'EXPORT'
    menu_item '?' 'Show help and safety notes' 'HELP'
    menu_item 0 'Exit control center' 'QUIT'
    line
    printf ' %sWATRBX%s %s›%s ' "$C_BOLD$C_CYAN" "$C_RESET" "$C_MAGENTA" "$C_RESET"
    read -r -p 'command: ' choice || exit 0
    case "$choice" in
        1) show_servers; pause ;;
        2) add_server || true; pause ;;
        3) edit_server || true; pause ;;
        4) rotate_key || true; pause ;;
        5) delete_server || true; pause ;;
        6) show_instances || true; pause ;;
        7) search_records || true; pause ;;
        8) check_database || true; pause ;;
        9) export_records || true; pause ;;
        '?'|h|help) header 'CONTROL CENTER HELP'; printf '  • Manager records identify the RCCService API endpoint used by WatrBX.\n  • Instance records should be registered by RCCService through the WatrBX API.\n  • Access keys are secrets. Never paste them into public logs or commit them.\n  • Deleting a manager does not automatically stop a running RCCService process.\n'; pause ;;
        0|q|quit|exit) printf '\n %sSession closed. Keep the RCC running.%s\n' "$C_DIM" "$C_RESET"; exit 0 ;;
        *) warn 'Unknown command. Choose an item from the menu.'; sleep 1 ;;
    esac
done
