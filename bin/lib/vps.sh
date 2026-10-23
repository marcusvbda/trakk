#!/usr/bin/env bash
# Shared by bin/bootstrap-vps and bin/deploy-secrets. Reads only the two deploy lines from .env, never the rest.

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

read_env_value() {
    local key="$1" value
    value="$(grep -E "^${key}=" "$PROJECT_DIR/.env" | tail -n 1 | cut -d= -f2- | tr -d '\r')"
    value="${value%\"}"
    value="${value#\"}"
    printf '%s' "$value"
}

load_vps_target() {
    [ -f "$PROJECT_DIR/.env" ] || { echo "Missing .env in $PROJECT_DIR" >&2; exit 1; }

    VPS_HOST="$(read_env_value DEPLOY_VPS_HOST)"
    VPS_USER="$(read_env_value DEPLOY_VPS_USER)"
    VPS_PORT="${DEPLOY_VPS_PORT:-22}"

    if [ -z "$VPS_HOST" ] || [ -z "$VPS_USER" ] || [ "$VPS_HOST" = "****" ] || [ "$VPS_USER" = "****" ]; then
        echo "Set DEPLOY_VPS_HOST and DEPLOY_VPS_USER in .env first." >&2
        exit 1
    fi

    VPS_SSH=(ssh -p "$VPS_PORT" "$VPS_USER@$VPS_HOST")
}
