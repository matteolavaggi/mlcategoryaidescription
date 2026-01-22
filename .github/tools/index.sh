#!/bin/bash
# =============================================================================
# PrestaShop Module - Add index.php to all folders (security requirement)
# Location: .github/tools/index.sh
# Run from: .github/tools/ folder (in WSL, Git Bash, or Linux)
# Usage: ./index.sh
# =============================================================================

set -e

# Get the module root directory (two levels up from .github/tools)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "============================================"
echo "  PrestaShop Index.php Generator"
echo "============================================"
echo "Module root: $MODULE_ROOT"
echo "============================================"

# Create a temp file with proper index.php content
# MUST include _PS_VERSION_ check for PrestaShop validator
TMP_INDEX=$(mktemp)
cat > "$TMP_INDEX" << 'INDEXEOF'
<?php
/**
 * 2010-2026 2win.agency
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author    2win.agency
 * @copyright 2010-2026 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
INDEXEOF

trap "rm -f $TMP_INDEX" EXIT

ADDED=0
UPDATED=0

# Find all directories (excluding hidden folders)
while IFS= read -r dir; do
    INDEX_FILE="$dir/index.php"
    REL_PATH="${dir#$MODULE_ROOT/}"
    [ "$REL_PATH" = "$dir" ] && REL_PATH="."
    
    if [ ! -f "$INDEX_FILE" ]; then
        cp "$TMP_INDEX" "$INDEX_FILE"
        echo "✅ Added: $REL_PATH/index.php"
        ((ADDED++)) || true
    elif ! grep -q "_PS_VERSION_" "$INDEX_FILE" 2>/dev/null; then
        # File exists but missing _PS_VERSION_ check - update it
        cp "$TMP_INDEX" "$INDEX_FILE"
        echo "🔄 Updated: $REL_PATH/index.php (added _PS_VERSION_ check)"
        ((UPDATED++)) || true
    fi
done < <(find "$MODULE_ROOT" -type d \
    -not -path "*/.git*" \
    -not -path "*/.github*" \
    -not -path "*/.vscode*" \
    -not -path "*/node_modules*")

echo "============================================"
echo "✅ Completed!"
echo "============================================"
