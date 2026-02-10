#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
K6_SCRIPT="${SCRIPT_DIR}/stability.k6.js"

BASE_URL="${1:-http://127.0.0.1:8000}"
PROFILE="${2:-baseline}"

if [[ $# -ge 1 ]]; then
    shift
fi

if [[ $# -ge 1 ]]; then
    shift
fi

k6 run "${K6_SCRIPT}" \
    -e BASE_URL="${BASE_URL}" \
    -e K6_PROFILE="${PROFILE}" \
    "$@"
