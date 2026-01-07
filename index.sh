#!/bin/bash

# Default to current directory if no path is provided
MODULE_PATH=${1:-$(pwd)}
echo "Adding index.php files to subdirectories in: $MODULE_PATH"

# Check if index.php exists in the root directory
ROOT_INDEX="$MODULE_PATH/index.php"
if [ ! -f "$ROOT_INDEX" ]; then
    echo "Creating index.php in root directory..."
    cat > "$ROOT_INDEX" << 'EOL'
<?php
/**
 * 2010-2025 2win.agency
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
 * @copyright 2010-2025 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 */
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

header('Location: ../');
exit;
EOL
    echo "Created index.php in root directory."
fi

# Function to recursively copy index.php to all subdirectories
copy_index_file() {
    local dir="$1"
    
    # Find all subdirectories
    find "$dir" -type d -not -path "*/\.*" | while read subdir; do
        # Skip if it's the root directory
        if [ "$subdir" != "$dir" ]; then
            # Only copy if the subdirectory doesn't already have an index.php
            if [ ! -f "$subdir/index.php" ]; then
                cp "$ROOT_INDEX" "$subdir/"
                echo "Added index.php to: $subdir"
            else
                echo "index.php already exists in: $subdir"
            fi
        fi
    done
}

# Start copying
copy_index_file "$MODULE_PATH"
echo "Completed adding index.php files"