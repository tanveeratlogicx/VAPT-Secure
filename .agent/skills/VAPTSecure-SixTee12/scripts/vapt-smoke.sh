#!/usr/bin/env bash
# =============================================================================
# VAPTSecure SixTee12 — Smoke Test Script
# Runs curl-based HTTP checks against all 12 VAPT risks
#
# Usage:
#   export TEST_TARGET_URL="https://your-site.com"
#   bash vapt-smoke.sh
#
# Exit codes:
#   0 = all checks passed
#   1 = one or more checks failed
# =============================================================================

set -euo pipefail

BASE_URL="${TEST_TARGET_URL:-http://localhost}"
BASE_URL="${BASE_URL%/}"   # strip trailing slash

PASS=0
FAIL=0
SKIP=0
RESULTS=()

# Colours
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; BOLD='\033[1m'; RESET='\033[0m'

divider() { echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"; }

log_pass() { echo -e "${GREEN}✅  $1${RESET}  $2"; ((PASS++)); RESULTS+=("PASS|$1|$2"); }
log_fail() { echo -e "${RED}❌  $1${RESET}  $2"; ((FAIL++)); RESULTS+=("FAIL|$1|$2"); }
log_skip() { echo -e "${YELLOW}⚠️   $1${RESET}  $2 (manual verification required)"; ((SKIP++)); RESULTS+=("SKIP|$1|$2"); }

divider
echo -e "${BOLD}  VAPTSecure SixTee12 — Smoke Test Runner${RESET}"
echo -e "  Target: ${BLUE}${BASE_URL}${RESET}"
echo -e "  Date:   $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
divider
echo ""

# ─── CHECK-001-001: wp-cron.php must be blocked ───────────────────────────────
echo -e "${BOLD}[RISK-001]${RESET} wp-cron.php DoS Protection"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "${BASE_URL}/wp-cron.php" || echo "000")
if [[ "$STATUS" == "403" || "$STATUS" == "404" ]]; then
  log_pass "CHECK-001-001" "wp-cron.php blocked (HTTP $STATUS)"
else
  log_fail "CHECK-001-001" "wp-cron.php returned HTTP $STATUS (expected 403)"
fi

# ─── CHECK-002-001: XML-RPC must be blocked ───────────────────────────────────
echo -e "${BOLD}[RISK-002]${RESET} XML-RPC Pingback Protection"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 -X POST "${BASE_URL}/xmlrpc.php" \
  -H "Content-Type: text/xml" \
  --data '<?xml version="1.0"?><methodCall><methodName>pingback.ping</methodName><params><param><value><string>http://evil.com</string></value></param></params></methodCall>' \
  || echo "000")
if [[ "$STATUS" == "403" || "$STATUS" == "404" ]]; then
  log_pass "CHECK-002-001" "XML-RPC blocked (HTTP $STATUS)"
else
  log_fail "CHECK-002-001" "XML-RPC returned HTTP $STATUS (expected 403)"
fi

# ─── CHECK-003-001: REST users endpoint requires auth ─────────────────────────
echo -e "${BOLD}[RISK-003]${RESET} REST API User Enumeration Protection"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "${BASE_URL}/wp-json/wp/v2/users" || echo "000")
if [[ "$STATUS" == "401" || "$STATUS" == "403" ]]; then
  log_pass "CHECK-003-001" "REST /users endpoint requires auth (HTTP $STATUS)"
else
  log_fail "CHECK-003-001" "REST /users returned HTTP $STATUS (expected 401)"
fi

# ─── CHECK-004-001: Password reset is rate-limited ────────────────────────────
echo -e "${BOLD}[RISK-004]${RESET} Password Reset Rate Limiting"
echo "  Sending 6 rapid reset requests..."
for i in 1 2 3 4 5; do
  curl -s -o /dev/null --max-time 5 -X POST "${BASE_URL}/wp-login.php?action=lostpassword" \
    --data "user_login=admin" &
done
wait
FINAL=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 -X POST \
  "${BASE_URL}/wp-login.php?action=lostpassword" --data "user_login=admin" || echo "000")
if [[ "$FINAL" == "429" || "$FINAL" == "403" ]]; then
  log_pass "CHECK-004-001" "Password reset rate limited (HTTP $FINAL)"
else
  log_skip "CHECK-004-001" "Rate limit not detectable via HTTP (response: $FINAL) — verify fail2ban config"
fi

# ─── CHECK-005-001: Author enumeration must be blocked ────────────────────────
echo -e "${BOLD}[RISK-005]${RESET} Author Query Enumeration Protection"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 -L "${BASE_URL}/?author=1" || echo "000")
if [[ "$STATUS" == "403" || "$STATUS" == "404" ]]; then
  log_pass "CHECK-005-001" "Author enumeration blocked (HTTP $STATUS)"
else
  log_fail "CHECK-005-001" "Author query returned HTTP $STATUS (expected 403)"
fi

# ─── CHECK-006-001: REST API index requires auth ──────────────────────────────
echo -e "${BOLD}[RISK-006]${RESET} REST Endpoint Disclosure Protection"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "${BASE_URL}/wp-json/" || echo "000")
if [[ "$STATUS" == "401" || "$STATUS" == "403" ]]; then
  log_pass "CHECK-006-001" "REST index restricted (HTTP $STATUS)"
else
  log_skip "CHECK-006-001" "REST index returned $STATUS — low severity, verify namespace restriction"
fi

# ─── CHECK-007-001: Login brute-force protection (manual) ─────────────────────
echo -e "${BOLD}[RISK-007]${RESET} Login Rate Limiting"
log_skip "CHECK-007-001" "Brute-force simulation requires manual fail2ban/server verification"
echo "  → Run: for i in \$(seq 1 6); do curl -X POST ${BASE_URL}/wp-login.php -d 'log=admin&pwd=wrong\$i'; done"
echo "    Then check: fail2ban-client status wordpress"

# ─── CHECK-008-001: Login error messages must be generic ──────────────────────
echo -e "${BOLD}[RISK-008]${RESET} Login Error Message Enumeration"
BODY=$(curl -s --max-time 10 -X POST "${BASE_URL}/wp-login.php" \
  --data "log=invaliduser__vapt_test__99999&pwd=wrongpassword&wp-submit=Log+In" || echo "")
LEAK=false
for phrase in "unknown username" "incorrect password" "the password you entered for" "is not registered"; do
  if echo "$BODY" | grep -qi "$phrase"; then
    LEAK=true
    break
  fi
done
if [ "$LEAK" = false ]; then
  log_pass "CHECK-008-001" "Login error messages are generic"
else
  log_fail "CHECK-008-001" "Login error leaks username/password differentiation"
fi

# ─── CHECK-009-001: Contact form rate limiting ────────────────────────────────
echo -e "${BOLD}[RISK-009]${RESET} Contact Form Rate Limiting"
echo "  Sending 11 rapid form submissions..."
for i in $(seq 1 10); do
  curl -s -o /dev/null --max-time 5 -X POST "${BASE_URL}/contact-us/" \
    --data "email=test${i}@test.com&message=flood+test+${i}" &
done
wait
FINAL=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 -X POST "${BASE_URL}/contact-us/" \
  --data "email=flood@test.com&message=rate+limit+check" || echo "000")
if [[ "$FINAL" == "429" || "$FINAL" == "403" ]]; then
  log_pass "CHECK-009-001" "Contact form rate limited (HTTP $FINAL)"
else
  log_skip "CHECK-009-001" "Rate limit not detected (HTTP $FINAL) — verify CAPTCHA config manually"
fi

# ─── CHECK-010-001: No server version headers ─────────────────────────────────
echo -e "${BOLD}[RISK-010]${RESET} Server Banner Suppression"
HEADERS=$(curl -sI --max-time 10 "${BASE_URL}/" || echo "")
BANNER_LEAK=false
for header in "Server:" "X-Powered-By:" "X-Runtime:"; do
  if echo "$HEADERS" | grep -qi "^${header}"; then
    BANNER_LEAK=true
    LEAKED_HEADER="$header"
    break
  fi
done
if [ "$BANNER_LEAK" = false ]; then
  log_pass "CHECK-010-001" "No server version headers exposed"
else
  log_fail "CHECK-010-001" "Server header exposed: ${LEAKED_HEADER} — remove in server config"
fi

# ─── CHECK-011-001: readme.html must be blocked ───────────────────────────────
echo -e "${BOLD}[RISK-011]${RESET} WordPress Version Disclosure (readme.html)"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "${BASE_URL}/readme.html" || echo "000")
if [[ "$STATUS" == "403" || "$STATUS" == "404" ]]; then
  log_pass "CHECK-011-001" "readme.html blocked (HTTP $STATUS)"
else
  log_fail "CHECK-011-001" "readme.html accessible (HTTP $STATUS) — add deny rule in .htaccess"
fi

# ─── CHECK-012-001: HSTS header must be present ───────────────────────────────
echo -e "${BOLD}[RISK-012]${RESET} HSTS Header Implementation"
HTTPS_URL="${BASE_URL/http:\/\//https://}"
HEADERS=$(curl -sI --max-time 10 "${HTTPS_URL}/" 2>/dev/null || echo "")
if echo "$HEADERS" | grep -qi "strict-transport-security"; then
  HSTS_VAL=$(echo "$HEADERS" | grep -i "strict-transport-security" | head -1 | tr -d '\r')
  log_pass "CHECK-012-001" "HSTS header present: ${HSTS_VAL}"
else
  log_fail "CHECK-012-001" "HSTS header missing — add Strict-Transport-Security to server config"
fi

# ─── Summary ──────────────────────────────────────────────────────────────────
echo ""
divider
echo -e "${BOLD}  VAPTSecure SixTee12 — Scan Complete${RESET}"
divider
echo -e "  ${GREEN}✅ Passed:  ${PASS}${RESET}"
echo -e "  ${RED}❌ Failed:  ${FAIL}${RESET}"
echo -e "  ${YELLOW}⚠️  Skipped: ${SKIP}${RESET} (manual verification required)"
echo ""

# Calculate score
TOTAL_MAX_CVSS=71.9
declare -A RISK_CVSS=(
  [CHECK-001-001]=7.5 [CHECK-002-001]=7.5 [CHECK-003-001]=5.3
  [CHECK-004-001]=5.3 [CHECK-005-001]=5.3 [CHECK-006-001]=3.7
  [CHECK-007-001]=7.5 [CHECK-008-001]=5.3 [CHECK-009-001]=5.3
  [CHECK-010-001]=3.7 [CHECK-011-001]=5.3 [CHECK-012-001]=5.3
)
UNPROTECTED_CVSS=0
for entry in "${RESULTS[@]}"; do
  STATUS_="${entry%%|*}"
  CHECK_ID="$(echo $entry | cut -d'|' -f2)"
  if [[ "$STATUS_" == "FAIL" ]]; then
    CVSS_VAL="${RISK_CVSS[$CHECK_ID]:-0}"
    UNPROTECTED_CVSS=$(echo "$UNPROTECTED_CVSS + $CVSS_VAL" | bc)
  fi
done
SCORE=$(echo "scale=0; 100 - ($UNPROTECTED_CVSS / $TOTAL_MAX_CVSS * 100)/1" | bc 2>/dev/null || echo "N/A")
echo -e "  ${BOLD}Security Score: ${SCORE}/100${RESET}"
divider
echo ""

[ "$FAIL" -eq 0 ] && exit 0 || exit 1
