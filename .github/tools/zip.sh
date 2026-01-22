#!/bin/bash
# =============================================================================
# PrestaShop Module ZIP Creator
# Location: .github/tools/zip.sh
# Run from: .github/tools/ folder (in WSL, Git Bash, or Linux)
# Usage: ./zip.sh [version]
# =============================================================================

set -e

# Get the module root directory (two levels up from .github/tools)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Auto-detect module name from main PHP file (file matching folder name)
MODULE_FOLDER=$(basename "$MODULE_ROOT")
MAIN_PHP_FILE="$MODULE_ROOT/${MODULE_FOLDER}.php"

if [ ! -f "$MAIN_PHP_FILE" ]; then
    # Fallback: find any PHP file with Module class
    MAIN_PHP_FILE=$(find "$MODULE_ROOT" -maxdepth 1 -name "*.php" -exec grep -l "extends Module" {} \; | head -1)
    if [ -z "$MAIN_PHP_FILE" ]; then
        echo "❌ Error: Cannot find main module PHP file"
        exit 1
    fi
fi

MODULE_NAME=$(basename "$MAIN_PHP_FILE" .php)

# Extract version from main PHP file
VERSION=$(grep -oP "\\\$this->version\s*=\s*['\"]\\K[^'\"]*" "$MAIN_PHP_FILE" 2>/dev/null | head -1)

# Allow version override from command line
if [ -n "$1" ]; then
    VERSION="$1"
fi

echo "============================================"
echo "  PrestaShop Module ZIP Creator"
echo "============================================"
echo "Module:  $MODULE_NAME"
echo "Version: $VERSION"
echo "Root:    $MODULE_ROOT"
echo "============================================"

# Check zip is available
if ! command -v zip &> /dev/null; then
    echo "❌ Error: 'zip' command not found"
    echo "   Install with: sudo apt install zip (WSL/Linux)"
    echo "   Or: pacman -S zip (Git Bash/MSYS2)"
    exit 1
fi

# Get the parent directory for output
PARENT_DIR=$(dirname "$MODULE_ROOT")

# Define the zip file path (with version)
if [ -n "$VERSION" ]; then
    ZIP_FILE="$PARENT_DIR/${MODULE_NAME}-${VERSION}.zip"
else
    ZIP_FILE="$PARENT_DIR/${MODULE_NAME}.zip"
fi

# Remove the old zip file if it exists
if [ -f "$ZIP_FILE" ]; then
    echo "🗑️  Removing old zip: $(basename "$ZIP_FILE")"
    rm -f "$ZIP_FILE"
fi

# Create a temporary directory
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

# Copy module files to temp (excluding dev files)
echo "📦 Copying module files..."
mkdir -p "$TEMP_DIR/$MODULE_NAME"

# Use rsync if available, otherwise cp with manual cleanup
if command -v rsync &> /dev/null; then
    rsync -a \
        --exclude='.git' \
        --exclude='.github' \
        --exclude='.vscode' \
        --exclude='node_modules' \
        --exclude='*.sh' \
        --exclude='*.log' \
        --exclude='.DS_Store' \
        --exclude='Thumbs.db' \
        --exclude='*.code-workspace' \
        --exclude='PROJECT.md' \
        --exclude='README_DEV.md' \
        "$MODULE_ROOT/" "$TEMP_DIR/$MODULE_NAME/"
else
    cp -r "$MODULE_ROOT/." "$TEMP_DIR/$MODULE_NAME/"
    # Manual cleanup
    rm -rf "$TEMP_DIR/$MODULE_NAME/.git"
    rm -rf "$TEMP_DIR/$MODULE_NAME/.github"
    rm -rf "$TEMP_DIR/$MODULE_NAME/.vscode"
    rm -rf "$TEMP_DIR/$MODULE_NAME/node_modules"
    find "$TEMP_DIR/$MODULE_NAME" -name "*.sh" -delete
    find "$TEMP_DIR/$MODULE_NAME" -name "*.log" -delete
    find "$TEMP_DIR/$MODULE_NAME" -name ".DS_Store" -delete
    find "$TEMP_DIR/$MODULE_NAME" -name "Thumbs.db" -delete
    find "$TEMP_DIR/$MODULE_NAME" -name "*.code-workspace" -delete
    rm -f "$TEMP_DIR/$MODULE_NAME/PROJECT.md"
    rm -f "$TEMP_DIR/$MODULE_NAME/README_DEV.md"
fi

# Create ZIP
echo "🔒 Creating ZIP..."
cd "$TEMP_DIR"
zip -rq "$ZIP_FILE" "$MODULE_NAME"

# Show result
ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
echo "============================================"
echo "✅ ZIP created successfully!"
echo "   File: $(basename "$ZIP_FILE")"
echo "   Size: $ZIP_SIZE"
echo "   Path: $ZIP_FILE"
echo "============================================"
echo ""
echo "Next steps:"
echo "  1. Upload to https://validator.prestashop.com/"
echo "  2. Fix any reported errors"
echo "  3. Submit to PrestaShop Addons"
