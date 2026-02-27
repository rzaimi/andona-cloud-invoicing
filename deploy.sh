#!/bin/bash
# =============================================================================
# deploy.sh — Run this on the PRODUCTION SERVER after uploading files via SFTP
#
# Usage:
#   bash deploy.sh
#
# LOCAL MACHINE — do these steps before running this script on the server:
#   1. npm run build
#      → DELETE public/build/ on server first (removes stale hashed files)
#      → then upload the new public/build/ folder via SFTP
#   2. composer install --no-dev --optimize-autoloader
#      → upload vendor/ folder via SFTP  (only when dependencies changed)
#   3. git tag -a vX.Y.Z -m "Release vX.Y.Z"
#   4. git push origin main --tags
# =============================================================================

set -e  # Stop immediately on any error

echo ""
echo "=== Andona Deploy — $(date '+%Y-%m-%d %H:%M:%S') ==="
echo ""

# ── Step 1: Pull latest code ──────────────────────────────────────────────────
echo "→ [1/5] Pulling latest code..."
git pull origin main
echo "  ✓ Code up to date."
echo ""

# ── Step 2: PHP dependencies ──────────────────────────────────────────────────
# Skipped — composer is not available on this server.
# Run locally before deploying:
#   composer install --no-dev --optimize-autoloader
# Then upload the vendor/ folder to the server via SFTP.
echo "→ [2/5] PHP dependencies (skipped — upload vendor/ manually via SFTP)"
echo ""

# ── Step 3: Run database migrations ───────────────────────────────────────────
echo "→ [3/5] Running migrations..."
php artisan migrate --force
echo "  ✓ Migrations done."
echo ""

# ── Step 4: Clear all caches ──────────────────────────────────────────────────
echo "→ [4/5] Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "  ✓ Caches cleared."
echo ""

# ── Step 5: Rebuild caches for performance ────────────────────────────────────
echo "→ [5/5] Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "  ✓ Caches rebuilt."
echo ""

echo "=== Deploy complete ==="
echo ""
