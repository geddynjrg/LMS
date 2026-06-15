#!/bin/sh
# Remove Vite dev-server marker so Laravel serves built assets over HTTPS.
set -e
cd "$(dirname "$0")/.."
if [ -f public/hot ]; then
  rm -f public/hot
  echo "Removed public/hot (Vite dev mode disabled)."
fi
