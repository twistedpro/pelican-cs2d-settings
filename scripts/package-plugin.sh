#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_ID="$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["id"] ?? "";' "${ROOT_DIR}/plugin.json" 2>/dev/null || true)"

if [ -z "${PLUGIN_ID}" ]; then
    if command -v jq >/dev/null 2>&1; then
        PLUGIN_ID="$(jq -r '.id // empty' "${ROOT_DIR}/plugin.json")"
    fi
fi

if [ -z "${PLUGIN_ID}" ]; then
    PLUGIN_ID="$(sed -n 's/^[[:space:]]*"id"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' "${ROOT_DIR}/plugin.json" | head -n 1)"
fi

if [ -z "${PLUGIN_ID}" ]; then
    echo "Could not read plugin id from plugin.json." >&2
    exit 1
fi

DIST_DIR="${ROOT_DIR}/dist"
BUILD_DIR="${ROOT_DIR}/build/package"
PACKAGE_DIR="${BUILD_DIR}/${PLUGIN_ID}"
ARCHIVE_PATH="${DIST_DIR}/${PLUGIN_ID}.zip"

rm -rf "${BUILD_DIR}"
mkdir -p "${PACKAGE_DIR}" "${DIST_DIR}"

cp "${ROOT_DIR}/plugin.json" "${PACKAGE_DIR}/"
cp -R "${ROOT_DIR}/config" "${ROOT_DIR}/resources" "${ROOT_DIR}/src" "${PACKAGE_DIR}/"

if [ -f "${ROOT_DIR}/README.md" ]; then
    cp "${ROOT_DIR}/README.md" "${PACKAGE_DIR}/"
fi

if [ -f "${ROOT_DIR}/CHANGELOG.md" ]; then
    cp "${ROOT_DIR}/CHANGELOG.md" "${PACKAGE_DIR}/"
fi

find "${PACKAGE_DIR}" -name ".DS_Store" -delete

(
    cd "${BUILD_DIR}"
    zip -qr "${ARCHIVE_PATH}" "${PLUGIN_ID}"
)

if unzip -l "${ARCHIVE_PATH}" | grep -E '(^|/)\.DS_Store$' >/dev/null; then
    echo "Archive contains .DS_Store files" >&2
    exit 1
fi

echo "Packaged ${ARCHIVE_PATH}"
