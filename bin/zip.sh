#!/usr/bin/env bash
# Builds dist/mitos-checklist.zip: the plugin as WordPress installs it.
set -euo pipefail
cd "$(dirname "$0")/.."
rm -rf dist && mkdir -p dist/mitos-checklist
cp -R mitos-checklist.php uninstall.php includes assets LICENSE readme.txt dist/mitos-checklist/
(cd dist && zip -qr mitos-checklist.zip mitos-checklist && rm -rf mitos-checklist)
echo "dist/mitos-checklist.zip"
