#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$ROOT_DIR/project/school-management-pro"
COMPOSER_DIR="$PLUGIN_DIR/includes"

command -v php >/dev/null 2>&1 || { echo "php is required" >&2; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "composer is required" >&2; exit 1; }
command -v unzip >/dev/null 2>&1 || { echo "unzip is required" >&2; exit 1; }

printf '%s\n' '== Composer manifest =='
composer validate --working-dir="$COMPOSER_DIR"
composer check-platform-reqs --working-dir="$COMPOSER_DIR" --no-dev

test -s "$COMPOSER_DIR/vendor/autoload.php"
php "$ROOT_DIR/tests/jwt-compat.php"
php "$ROOT_DIR/tests/feature-flags.php"
php "$ROOT_DIR/tests/portal-wrapper.php"
php "$ROOT_DIR/tests/auth.php"
php "$ROOT_DIR/tests/identity.php"
php "$ROOT_DIR/tests/policy.php"
test -f "$PLUGIN_DIR/school-management.php"
test -f "$PLUGIN_DIR/includes/helpers/WLSM_Brand.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-environment.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-migrations.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-modules.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-features.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-portal.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-portal-pages.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-auth.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-identity.php"
test -f "$PLUGIN_DIR/includes/core/class-edutech-policy.php"
test -f "$PLUGIN_DIR/assets/css/edutech-design-system.css"

grep -q 'Plugin Name: Edutech v1.0' "$PLUGIN_DIR/school-management.php"
grep -q "define( 'EDUTECH_VERSION', '1.0.0'" "$PLUGIN_DIR/school-management.php"
grep -q "define( 'EDUTECH_DB_VERSION', '1.0.0'" "$PLUGIN_DIR/school-management.php"
grep -q "'name'        => 'Edutech v1.0'" "$PLUGIN_DIR/includes/helpers/WLSM_Brand.php"
grep -q 'class Edutech_Migrations' "$PLUGIN_DIR/includes/core/class-edutech-migrations.php"
grep -q 'class Edutech_Modules' "$PLUGIN_DIR/includes/core/class-edutech-modules.php"
grep -q 'class Edutech_Features' "$PLUGIN_DIR/includes/core/class-edutech-features.php"
grep -q 'class Edutech_Portal' "$PLUGIN_DIR/includes/core/class-edutech-portal.php"
grep -q 'class Edutech_Portal_Pages' "$PLUGIN_DIR/includes/core/class-edutech-portal-pages.php"
grep -q 'class Edutech_Auth' "$PLUGIN_DIR/includes/core/class-edutech-auth.php"
grep -q 'class Edutech_Identity' "$PLUGIN_DIR/includes/core/class-edutech-identity.php"
grep -q 'class Edutech_Policy' "$PLUGIN_DIR/includes/core/class-edutech-policy.php"
grep -q 'edutechIdentity' "$PLUGIN_DIR/public/inc/WLSM_Shortcode.php"
grep -q 'edutech-role-' "$PLUGIN_DIR/public/inc/WLSM_Shortcode.php"
grep -q 'Edutech_Auth::login_form_args' "$PLUGIN_DIR/public/inc/account/index.php"
grep -q 'safe_redirect' "$PLUGIN_DIR/includes/helpers/WLSM_Login.php"
grep -q 'edutech-auth-card' "$PLUGIN_DIR/assets/css/edutech-design-system.css"
grep -q 'edutech-portal-frame--standalone' "$PLUGIN_DIR/assets/css/edutech-design-system.css"
grep -q 'Edutech_Portal::wrap' "$PLUGIN_DIR/public/inc/WLSM_Shortcode.php"
grep -q '^\.edutech-portal {' "$PLUGIN_DIR/assets/css/edutech-design-system.css"
grep -q 'prefers-reduced-motion' "$PLUGIN_DIR/assets/css/edutech-design-system.css"
grep -q 'focus-visible' "$PLUGIN_DIR/assets/css/edutech-design-system.css"
grep -q "add_filter('body_class'" "$PLUGIN_DIR/public/public.php"
grep -q "'edutech-design-system'" "$PLUGIN_DIR/public/inc/WLSM_Shortcode.php"

printf '%s\n' '== First-party PHP syntax =='
files=0
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
  files=$((files + 1))
done < <(find "$PLUGIN_DIR" -type f -name '*.php' -not -path '*/includes/vendor/*' -print0)
printf 'Validated %s first-party PHP files.\n' "$files"

printf '%s\n' '== Single-package build =='
"$ROOT_DIR/build-edutech-plugin.sh" >/tmp/edutech-build.log
cat /tmp/edutech-build.log

test -s "$ROOT_DIR/release/edutech-v1.0.0.zip"
test -s "$ROOT_DIR/release/edutech-v1.0.0.zip.sha256"
unzip -t "$ROOT_DIR/release/edutech-v1.0.0.zip" >/dev/null
unzip -p "$ROOT_DIR/release/edutech-v1.0.0.zip" '*/EDUTECH-MANIFEST.json' | grep -q '"database_version": "1.0.0"'
unzip -p "$ROOT_DIR/release/edutech-v1.0.0.zip" '*/assets/css/edutech-design-system.css' | grep -q '^\.edutech-portal {'
unzip -p "$ROOT_DIR/release/edutech-v1.0.0.zip" '*/includes/core/class-edutech-portal-pages.php' | grep -q 'edutech-portal'
unzip -p "$ROOT_DIR/release/edutech-v1.0.0.zip" '*/includes/core/class-edutech-auth.php' | grep -q 'MAX_ATTEMPTS'
unzip -p "$ROOT_DIR/release/edutech-v1.0.0.zip" '*/includes/core/class-edutech-identity.php' | grep -q 'SCHOOL_ADMIN'

printf '%s\n' 'Smoke tests passed.'
