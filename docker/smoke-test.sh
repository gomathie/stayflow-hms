#!/bin/bash
# Smoke test for a running stack. Run it from the project folder after any
# change to the Dockerfile, docker/, the compose files, PHP or MySQL versions,
# or a bundled library:
#
#     bash docker/smoke-test.sh                 # local stack on HTTP_PORT
#     bash docker/smoke-test.sh https://staging.example.com
#
# It checks that the main website and back office pages load, that none of
# them shows an error, that an administrator can sign in, and that the app
# log has no new PHP errors. It changes nothing: no booking is made, nothing
# is saved. Exit code 0 means every check passed.
#
# It is a first check, not a full test. After it passes, still try by hand
# whatever the change could affect (see AGENTS.md, rule 15).

set -u
cd "$(dirname "$0")/.." || exit 2

env_value() { grep -E "^$1=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r'; }
BASE="${1:-http://localhost:$(env_value HTTP_PORT)}"; BASE="${BASE%/}"
[ "$BASE" = "http://localhost:" ] && BASE="http://localhost:8080"
ADMIN_DIR="$(env_value ADMIN_DIR)"; ADMIN_DIR="${ADMIN_DIR:-admin-salisberg}"
ADMIN_EMAIL="$(env_value ADMIN_EMAIL)"
ADMIN_PASSWORD="$(env_value ADMIN_PASSWORD)"
ERRORS='Fatal error|Parse error|Uncaught |SmartyException|SQLSTATE\[|<b>Warning</b>|<b>Notice</b>|<b>Deprecated</b>'
JAR="$(mktemp)"; BODY="$(mktemp)"; trap 'rm -f "$JAR" "$BODY"' EXIT
STARTED="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
pass=0; fail=0

ok()  { pass=$((pass + 1)); printf '  ok    %s\n' "$1"; }
bad() { fail=$((fail + 1)); printf '  FAIL  %s\n' "$1"; }

# page <label> <path> [text that must be present]
page() {
    local code
    code=$(curl -s -L -m 60 -b "$JAR" -c "$JAR" -o "$BODY" -w '%{http_code}' "$BASE/$2")
    if [ "$code" != "200" ]; then bad "$1 (HTTP $code)"; return; fi
    if grep -Eq "$ERRORS" "$BODY"; then bad "$1 (error text on the page: $(grep -Eo "$ERRORS" "$BODY" | head -1))"; return; fi
    if [ -n "${3:-}" ] && ! grep -q "$3" "$BODY"; then bad "$1 (expected text missing: $3)"; return; fi
    ok "$1"
}

echo "Smoke test against $BASE"
echo "Website"
page "home"              "index.php" "salisberg.css"
page "search results"    "index.php?controller=category&id_category=$(curl -s -L -m 60 "$BASE/index.php" | grep -o 'data-hotel-cat-id="[0-9]*"' | head -1 | grep -o '[0-9]*')&date_from=$(date -u -d '+3 days' +%Y-%m-%d 2>/dev/null || date -u -v+3d +%Y-%m-%d)&date_to=$(date -u -d '+5 days' +%Y-%m-%d 2>/dev/null || date -u -v+5d +%Y-%m-%d)"
page "room page"         "$(curl -s -L -m 60 "$BASE/index.php" | grep -o 'index.php?id_product=[0-9]*[^"]*' | head -1 | sed 's/&amp;/\&/g')"
page "contact"           "index.php?controller=contact" "submitMessage"
page "sign in"           "index.php?controller=authentication" "SubmitLogin"
page "password reset"    "index.php?controller=password"
page "cart and checkout" "index.php?controller=order-opc"
page "our properties"    "index.php?controller=our-properties"
page "about us"          "index.php?id_cms=4&controller=cms"
page "stylesheet"        "themes/hotel-reservation-theme/css/salisberg.css" "sb-green"

echo "Files that must not be served"
for path in ".env" "docker-compose.yml" "docker/entrypoint.sh" "CHANGELOG.md" "config/settings.inc.php" "install/"; do
    code=$(curl -s -m 30 -o "$BODY" -w '%{http_code}' "$BASE/$path")
    # settings.inc.php is PHP: served as an empty 200 is fine, its source is not
    if [ "$code" = "200" ] && [ -s "$BODY" ] && grep -Eq 'DB_PASSWD|MYSQL_|services:|#!/bin' "$BODY"; then bad "/$path is readable"; else ok "/$path not readable ($code)"; fi
done

echo "Back office"
: > "$JAR"
page "sign-in page" "$ADMIN_DIR/index.php?controller=AdminLogin" "submitLogin"
if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    reply=$(curl -s -m 60 -b "$JAR" -c "$JAR" "$BASE/$ADMIN_DIR/index.php?controller=AdminLogin" \
        --data-urlencode "ajax=1" --data-urlencode "token=" --data-urlencode "controller=AdminLogin" --data-urlencode "submitLogin=1" \
        --data-urlencode "email=$ADMIN_EMAIL" --data-urlencode "passwd=$ADMIN_PASSWORD" --data-urlencode "redirect=AdminDashboard")
    target=$(printf '%s' "$reply" | grep -o '"redirect":"[^"]*"' | cut -d'"' -f4 | sed 's/\\\//\//g; s/&amp;/\&/g')
    if [ -z "$target" ]; then
        bad "administrator sign-in (no redirect returned; wrong ADMIN_EMAIL/ADMIN_PASSWORD in .env, or the sign-in limit was reached)"
    else
        ok "administrator sign-in"
        page "dashboard" "$ADMIN_DIR/$target" "Dashboard"
        # every other page is reached through the menu links on the dashboard, which carry their tokens
        for controller in AdminOrders AdminProducts AdminCustomers AdminHotelRoomsBooking AdminSalisbergReports AdminEmployees AdminModules AdminSalisbergStaffGuide AdminInformation; do
            link=$(grep -o "index.php?controller=$controller&amp;token=[a-f0-9]*" "$BODY.dash" 2>/dev/null | head -1)
            [ -z "$link" ] && { cp "$BODY" "$BODY.dash" 2>/dev/null; link=$(grep -o "index.php?controller=$controller&amp;token=[a-f0-9]*" "$BODY.dash" | head -1); }
            if [ -z "$link" ]; then bad "$controller (no menu link found)"; continue; fi
            page "$controller" "$ADMIN_DIR/$(printf '%s' "$link" | sed 's/&amp;/\&/g')"
        done
        rm -f "$BODY.dash"
    fi
else
    bad "administrator sign-in (ADMIN_EMAIL or ADMIN_PASSWORD missing from .env)"
fi

if [ "$BASE" != "${BASE#http://localhost}" ] && command -v docker >/dev/null 2>&1; then
    echo "App log since the test started"
    log=$(docker compose logs app --since "$STARTED" 2>/dev/null | grep -E 'PHP (Fatal|Parse|Warning|Notice|Deprecated)' || true)
    count=$(printf '%s' "$log" | grep -c . || true)
    if [ "$count" = "0" ]; then ok "no PHP errors or warnings logged"; else
        bad "$count PHP error/warning lines logged; the most frequent:"
        printf '%s\n' "$log" | sed -E 's/^[^|]*\| //; s/^\[[^]]*\] *//; s/\[pid [0-9]+\] *//; s/\[client [^]]*\] *//' | sed -E 's/, referer:.*//' | sort | uniq -c | sort -rn | head -8 | cut -c1-220
    fi
    printf '  PHP %s, MySQL %s\n' "$(docker compose exec -T app php -r 'echo PHP_VERSION;' 2>/dev/null)" "$(docker compose exec -T db mysqld --version 2>/dev/null | grep -o 'Ver [0-9.]*' | cut -d' ' -f2)"
fi

echo "Passed: $pass   Failed: $fail"
[ "$fail" = "0" ]
