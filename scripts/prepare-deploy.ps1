# Build frontend assets and stage files for commit (cPanel Git deploy).
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)

Write-Host "Running npm run build..."
npm run build

Write-Host "Staging frontend and related files..."
git add resources/css resources/js resources/views public/build

Write-Host ""
Write-Host "Done. Review with: git status"
Write-Host "Then commit and push. Checklist:"
Write-Host "  - npm run build: done"
Write-Host "  - public/build committed: (after you commit)"
Write-Host "  - pushed to GitHub: (after git push)"
