#!/usr/bin/env bash
# kaslo-test.sh — Kaslo TRMNL X test suite
#
# Levels:
#   1. PHP syntax check on all PHP files (run before every upload)
#   2. Non-ASCII character scan (catches parse errors from box-drawing, em-dash, etc.)
#   3. Remote schema smoke test (run after every deploy + flush)
#
# Usage:
#   ./kaslo-test.sh            # all levels
#   ./kaslo-test.sh syntax     # Level 1+2 only (no network)
#   ./kaslo-test.sh remote     # Level 3 only
#
# Prerequisites: php, curl, jq

set -euo pipefail

KEY="kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea"
BASE_URL="https://knotwork.ca/kaslo-api.php?key=${KEY}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PASS=0; FAIL=0

green() { printf '\033[0;32m%s\033[0m\n' "$*"; }
red()   { printf '\033[0;31m%s\033[0m\n' "$*"; }
bold()  { printf '\033[1m%s\033[0m\n' "$*"; }

pass() { green "  PASS  $1"; ((PASS++)); }
fail() { red   "  FAIL  $1"; ((FAIL++)); }

# --------------------------------------------------------------------------
# Level 1: PHP syntax
# --------------------------------------------------------------------------
level_syntax() {
    bold ""
    bold "Level 1 — PHP syntax check"

    PHP_FILES=(
        "kaslo-api.php"
        "kaslo-flush.php"
    )

    for f in "${PHP_FILES[@]}"; do
        fp="${SCRIPT_DIR}/${f}"
        if [[ ! -f "$fp" ]]; then
            fail "$f  (file not found)"
            continue
        fi
        result=$(php -l "$fp" 2>&1)
        if echo "$result" | grep -q "No syntax errors"; then
            pass "$f"
        else
            fail "$f"
            echo "    $result"
        fi
    done
}

# --------------------------------------------------------------------------
# Level 2: Non-ASCII scan
# --------------------------------------------------------------------------
level_ascii() {
    bold ""
    bold "Level 2 — Non-ASCII character scan"

    PHP_FILES=(
        "kaslo-api.php"
        "kaslo-flush.php"
    )

    for f in "${PHP_FILES[@]}"; do
        fp="${SCRIPT_DIR}/${f}"
        if [[ ! -f "$fp" ]]; then
            continue
        fi
        # grep -P '[^\x00-\x7F]' exits 0 if match found (bad), 1 if no match (good)
        if grep -qP '[^\x00-\x7F]' "$fp" 2>/dev/null; then
            fail "$f  — non-ASCII chars found:"
            grep -nP '[^\x00-\x7F]' "$fp" | head -5 | while read -r line; do
                echo "    $line"
            done
        else
            pass "$f  (clean)"
        fi
    done
}

# --------------------------------------------------------------------------
# Level 3: Remote smoke test
# --------------------------------------------------------------------------
level_remote() {
    bold ""
    bold "Level 3 — Remote schema smoke test"

    if ! command -v jq &>/dev/null; then
        red "  SKIP  jq not installed — install with: brew install jq"
        return
    fi

    echo "  Fetching ${BASE_URL}&game=0 ..."
    response=$(curl -sf --max-time 15 "${BASE_URL}&game=0" 2>/dev/null) || {
        fail "HTTP request failed (server down or key wrong)"
        return
    }

    # Check each data group
    checks=(
        "Weather .temp"
        "Condition .condition"
        "Forecast-today .f0_dow"
        "Forecast-6 .f6_dow"
        "Moon .moon_phase"
        "Sunrise .sunrise"
        "Calendar-day0 .cal_d0_dow"
        "Calendar-day13 .cal_d13_dow"
        "Todo-u0 .todo_u0_text"
        "Go-opponent .go_opponent"
        "Go-turns .go_my_turn_count"
        "Pressure .fc_pressure_now"
    )

    for item in "${checks[@]}"; do
        label="${item%% *}"
        key="${item##* }"
        val=$(echo "$response" | jq -r "$key // empty" 2>/dev/null)
        if [[ -n "$val" ]]; then
            pass "$label  ($key = \"$val\")"
        else
            fail "$label  ($key missing or null)"
        fi
    done

    # Key count
    key_count=$(echo "$response" | jq 'keys | length' 2>/dev/null || echo 0)
    if [[ "$key_count" -ge 150 ]]; then
        pass "Key count  ($key_count keys)"
    else
        fail "Key count  ($key_count keys — expected ≥150)"
    fi

    # Check other game slots
    bold ""
    bold "  Game slots"
    for g in 0 1 2; do
        slot_response=$(curl -sf --max-time 15 "${BASE_URL}&game=${g}" 2>/dev/null) || {
            fail "game=${g}  request failed"
            continue
        }
        opp=$(echo "$slot_response" | jq -r '.go_opponent // "—"' 2>/dev/null)
        col=$(echo "$slot_response" | jq -r '.go_color // "—"' 2>/dev/null)
        pass "game=${g}  (opponent=\"$opp\", color=\"$col\")"
    done
}

# --------------------------------------------------------------------------
# Main
# --------------------------------------------------------------------------
mode="${1:-all}"

case "$mode" in
    syntax)
        level_syntax
        level_ascii
        ;;
    remote)
        level_remote
        ;;
    all|*)
        level_syntax
        level_ascii
        level_remote
        ;;
esac

bold ""
bold "Results: ${PASS} passed, ${FAIL} failed"
echo ""

if [[ "$FAIL" -gt 0 ]]; then
    exit 1
else
    exit 0
fi
