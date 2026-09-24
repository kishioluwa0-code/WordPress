#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENVIRONMENT="${EDUTECH_ENVIRONMENT:-${1:-}}"
DEPLOY_MODE="${DEPLOY_MODE:-artifact}"
PACKAGE_PATH="${ROOT_DIR}/release/edutech-v1.0.0.zip"
CHECKSUM_PATH="${PACKAGE_PATH}.sha256"
WORK_DIR="$(mktemp -d)"
cleanup() { rm -rf "$WORK_DIR"; }
trap cleanup EXIT

case "$ENVIRONMENT" in
  development|dev)
    ENVIRONMENT="development"
    FIREBASE_PROJECT_ID="edutech-dev-95323"
    ;;
  staging)
    FIREBASE_PROJECT_ID="edutech-staging-150ec"
    ;;
  production|prod)
    ENVIRONMENT="production"
    FIREBASE_PROJECT_ID="edutech-staging-20d90"
    ;;
  *)
    echo "Usage: EDUTECH_ENVIRONMENT=development|staging|production $0" >&2
    exit 2
    ;;
esac

export EDUTECH_ENVIRONMENT="$ENVIRONMENT"
export EDUTECH_FIREBASE_PROJECT_ID="$FIREBASE_PROJECT_ID"

command -v unzip >/dev/null 2>&1 || { echo "unzip is required" >&2; exit 1; }
command -v sha256sum >/dev/null 2>&1 || { echo "sha256sum is required" >&2; exit 1; }

printf 'Deploying Edutech v1.0 to %s (%s)\n' "$ENVIRONMENT" "$FIREBASE_PROJECT_ID"

# Build the exact package consumed by the deployment step.
"$ROOT_DIR/build-edutech-plugin.sh" >/dev/null
sha256sum -c "$CHECKSUM_PATH"
unzip -q "$PACKAGE_PATH" -d "$WORK_DIR"
PACKAGE_DIR="$WORK_DIR/edutech-v1.0.0"

# Keep environment metadata outside PHP source and credentials.
cat > "$WORK_DIR/EDUTECH-DEPLOYMENT.json" <<EOF
{
  "environment": "$ENVIRONMENT",
  "firebase_project_id": "$FIREBASE_PROJECT_ID",
  "package": "edutech-v1.0.0",
  "commit": "${GITHUB_SHA:-local}",
  "deployed_at_utc": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
EOF

if [[ "$DEPLOY_MODE" == "artifact" ]]; then
  printf 'Artifact ready: %s\n' "$PACKAGE_PATH"
  exit 0
fi

if [[ "$DEPLOY_MODE" != "ssh" ]]; then
  echo "DEPLOY_MODE must be artifact or ssh" >&2
  exit 2
fi

: "${WP_DEPLOY_HOST:?WP_DEPLOY_HOST is required for SSH deployment}"
: "${WP_DEPLOY_USER:?WP_DEPLOY_USER is required for SSH deployment}"
: "${WP_DEPLOY_PATH:?WP_DEPLOY_PATH is required for SSH deployment}"
: "${SSH_KEY_FILE:?SSH_KEY_FILE is required for SSH deployment}"
test -s "$SSH_KEY_FILE"

REMOTE="${WP_DEPLOY_USER}@${WP_DEPLOY_HOST}"
SSH_OPTS=( -i "$SSH_KEY_FILE" -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes )
RELEASE_ID="${GITHUB_SHA:-$(date -u +%Y%m%d%H%M%S)}"
REMOTE_RELEASE="${WP_DEPLOY_PATH%/}/.releases/${RELEASE_ID}"

ssh "${SSH_OPTS[@]}" "$REMOTE" "umask 077 && mkdir -p '$REMOTE_RELEASE'"
rsync -az --delete -e "ssh ${SSH_OPTS[*]}" "$PACKAGE_DIR/" "$REMOTE:$REMOTE_RELEASE/"
scp "${SSH_OPTS[@]}" "$WORK_DIR/EDUTECH-DEPLOYMENT.json" "$REMOTE:$REMOTE_RELEASE/EDUTECH-DEPLOYMENT.json"
ssh "${SSH_OPTS[@]}" "$REMOTE" "ln -sfn '$REMOTE_RELEASE' '${WP_DEPLOY_PATH%/}/current'"

printf 'Deployment complete: %s/current\n' "${WP_DEPLOY_PATH%/}"
