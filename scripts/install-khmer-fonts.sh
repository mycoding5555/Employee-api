#!/usr/bin/env bash
set -euo pipefail

# install-khmer-fonts.sh
# Usage:
#   ./scripts/install-khmer-fonts.sh [siemreap_url] [muol_url]
# If URLs are omitted the script will print instructions and exit.

FONTS_DIR="$(cd "$(dirname "$0")/../public/fonts" && pwd)"
mkdir -p "$FONTS_DIR"

SIEMREAP_URL="${1:-}"
MUOL_URL="${2:-}"

if [[ -z "$SIEMREAP_URL" || -z "$MUOL_URL" ]]; then
  echo "Provide two download URLs (Siemreap then Muol Light)."
  echo "Example: ./scripts/install-khmer-fonts.sh \"
  echo "  https://example.com/KhmerOSSiemreap-Regular.ttf \"
  echo "  https://example.com/KhmerOSMuolLight.ttf\""
  echo
  echo "Notes:"
  echo " - The script will save files as KhmerOSSiemreap-Regular.ttf and KhmerOSMuolLight.ttf in public/fonts/."
  echo " - If you have local files, just copy them into public/fonts/ with those names."
  echo " - If you have ttf only and want woff2, install 'woff2' (https://github.com/google/woff2) to generate .woff2 files via woff2_compress."
  exit 1
fi

SIEMREAP_FILE="$FONTS_DIR/KhmerOSSiemreap-Regular.ttf"
MUOL_FILE="$FONTS_DIR/KhmerOSMuolLight.ttf"

echo "Downloading Siemreap font to $SIEMREAP_FILE..."
if command -v curl >/dev/null 2>&1; then
  curl -fsSL "$SIEMREAP_URL" -o "$SIEMREAP_FILE"
elif command -v wget >/dev/null 2>&1; then
  wget -qO "$SIEMREAP_FILE" "$SIEMREAP_URL"
else
  echo "Please install curl or wget." >&2
  exit 2
fi

echo "Downloading Muol Light font to $MUOL_FILE..."
if command -v curl >/dev/null 2>&1; then
  curl -fsSL "$MUOL_URL" -o "$MUOL_FILE"
else
  wget -qO "$MUOL_FILE" "$MUOL_URL"
fi

# Optionally generate woff/woff2 if tools present
if command -v woff2_compress >/dev/null 2>&1; then
  echo "Generating .woff2 files using woff2_compress..."
  woff2_compress "$SIEMREAP_FILE" || true
  woff2_compress "$MUOL_FILE" || true
fi

if command -v ttf2woff >/dev/null 2>&1; then
  echo "Generating .woff files using ttf2woff..."
  ttf2woff "$SIEMREAP_FILE" "${SIEMREAP_FILE%.ttf}.woff" || true
  ttf2woff "$MUOL_FILE" "${MUOL_FILE%.ttf}.woff" || true
fi

echo "Fonts installed to $FONTS_DIR."
echo "If you use Laravel Mix/Vite, rebuild assets: npm run dev or npm run build."
