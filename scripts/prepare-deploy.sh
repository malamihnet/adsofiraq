#!/usr/bin/env bash
# Build frontend assets and stage files for commit (cPanel Git deploy).
set -euo pipefail
cd "$(dirname "$0")/.."

echo "Running npm run build..."
npm run build

echo "Staging frontend and related files..."
git add resources/css resources/js resources/views public/build

echo ""
echo "Done. Review with: git status"
echo "Then commit and push. Checklist:"
echo "  - npm run build: done"
echo "  - public/build committed: (after you commit)"
echo "  - pushed to GitHub: (after git push)"
