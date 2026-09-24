#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$ROOT_DIR/project/school-management-pro"
API_FILE="$PLUGIN_DIR/public/api/WLSM_Api.php"

command -v rg >/dev/null 2>&1 || { echo "rg is required" >&2; exit 1; }

printf '%s\n' '== Secret scan =='
if rg -n --hidden --glob '!.git/**' --glob '!**/includes/vendor/**' --glob '!tests/security-scan.sh' --glob '!*.zip' \
  '(sk_(live|test)_[A-Za-z0-9]{16,}|AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{20,}|-----BEGIN .*PRIVATE KEY-----)' \
  "$ROOT_DIR"; then
  echo 'Potential credential literal found.' >&2
  exit 1
fi

test ! -e "$PLUGIN_DIR/includes/vendor/stripe/stripe-php/README.md"

printf '%s\n' '== Direct access guards =='
php_files=$(find "$PLUGIN_DIR" -type f -name '*.php' -not -path '*/includes/vendor/*' | wc -l)
guarded_files=0
while IFS= read -r -d '' file; do
  if rg -q "defined\s*\(\s*['\"]ABSPATH['\"]\s*\)" "$file"; then
    guarded_files=$((guarded_files + 1))
  fi
done < <(find "$PLUGIN_DIR" -type f -name '*.php' -not -path '*/includes/vendor/*' -print0)
printf 'guarded=%s total=%s\n' "$guarded_files" "$php_files"
expected_guarded_files=541
if [ "$guarded_files" -ne "$expected_guarded_files" ]; then
  echo "Unexpected direct-access guard count; expected $expected_guarded_files." >&2
  exit 1
fi

printf '%s\n' '== REST permission coverage =='
rest_routes=$(rg -c 'register_rest_route\(' "$API_FILE")
centralized=$(rg -c "permission_callback'\s*=>\s*array\('WLSM_Api', 'permission_logged_in'\)" "$API_FILE")
public_callbacks=$(rg -c "permission_callback'\s*=>\s*'__return_true'" "$API_FILE" || echo 0)
closures=$(rg -c "permission_callback'\s*=>\s*function" "$API_FILE" || echo 0)
printf 'routes=%s centralized=%s public=%s closures=%s\n' "$rest_routes" "$centralized" "$public_callbacks" "$closures"
[ "$centralized" -eq $((rest_routes - 1)) ]
[ "$public_callbacks" -eq 1 ]
[ "$closures" -eq 0 ]

printf '%s\n' '== Dangerous execution scan =='
if rg -n --glob '!**/includes/vendor/**' '(^|[^A-Za-z0-9_])(eval|shell_exec|passthru|system)\s*\(|assert\s*\(\s*\$' "$PLUGIN_DIR"; then
  echo 'Dangerous execution pattern found.' >&2
  exit 1
fi

printf '%s\n' 'Static security scan passed.'
