#!/bin/bash
# ===================================================================
# ClassPortal — Safe Deployment Script
# ===================================================================
# Handles local modifications before pulling updates to prevent
# merge conflicts with runtime data files (brain_db.json, .env, etc.)
# ===================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "=============================================="
echo "  ClassPortal — Deploying Updates..."
echo "  $(date)"
echo "=============================================="

# --- Step 1: Check git status ---
echo ""
echo "[1/5] Checking repository status..."
if ! git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
    echo "ERROR: Not a git repository. Aborting."
    exit 1
fi

# --- Step 2: Stash local changes ---
echo ""
echo "[2/5] Stashing local modifications..."
STASH_NAME="deploy-stash-$(date +%Y%m%d%H%M%S)"
if [[ -n $(git status --porcelain) ]]; then
    git stash push --include-untracked --message "$STASH_NAME"
    HAD_CHANGES=true
    echo "  -> Local changes stashed as: $STASH_NAME"
else
    HAD_CHANGES=false
    echo "  -> No local changes found. Skipping stash."
fi

# --- Step 3: Pull latest code ---
echo ""
echo "[3/5] Pulling latest updates from origin..."
git pull origin main 2>&1 || {
    PULL_FAILED=true
    echo ""
    echo "WARNING: Pull failed!"
    if [[ "$HAD_CHANGES" == true ]]; then
        echo "  Restoring stashed changes..."
        git stash pop
    fi
    echo "ERROR: Deployment aborted. Fix conflicts manually and retry."
    exit 1
}
echo "  -> Pull succeeded."

# --- Step 4: Restore stashed changes ---
echo ""
echo "[4/5] Restoring local modifications..."
if [[ "$HAD_CHANGES" == true ]]; then
    STASH_LIST=$(git stash list)
    if echo "$STASH_LIST" | grep -q "$STASH_NAME"; then
        if git stash pop; then
            echo "  -> Local changes restored successfully."
        else
            echo "  -> CONFLICT during stash pop!"
            echo "  -> Resolve conflicts manually, then run: git stash drop"
            echo "  -> Then continue with step 5."
        fi
    else
        echo "  -> Stash already applied or dropped. Skipping."
    fi
fi

# --- Step 5: Post-deploy tasks ---
echo ""
echo "[5/5] Running post-deploy tasks..."
if [ -f artisan ]; then
    echo "  -> Running migrations..."
    php artisan migrate --force 2>&1 || echo "  -> Migration skipped or failed (non-fatal)."

    echo "  -> Clearing cache..."
    php artisan optimize:clear 2>&1 || echo "  -> Cache clear skipped or failed (non-fatal)."

    echo "  -> Generating optimized cache..."
    php artisan optimize 2>&1 || echo "  -> Optimize skipped or failed (non-fatal)."
else
    echo "  -> No artisan found. Skipping Laravel post-deploy tasks."
fi

echo ""
echo "=============================================="
echo "  Deployment complete!"
echo "  $(date)"
echo "=============================================="
