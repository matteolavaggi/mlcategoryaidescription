#!/bin/bash

# Set the module name (must be lowercase alphanumeric and underscores only)
MODULE_NAME="mlcategoryaidescription"

# Get the absolute path of the parent directory
PARENT_DIR=$(dirname "$(pwd)")

# Define the zip file path
ZIP_FILE="$PARENT_DIR/${MODULE_NAME}.zip"

# Remove the old zip file if it exists
if [ -f "$ZIP_FILE" ]; then
    echo "Removing old zip file: $ZIP_FILE"
    rm -f "$ZIP_FILE"
fi

# Create a temporary directory
TEMP_DIR=$(mktemp -d)

# Copy all files to the temporary directory, maintaining the module name as subfolder
cp -r . "$TEMP_DIR/$MODULE_NAME"

# Move to the temporary directory
cd "$TEMP_DIR"

# Create the zip file in the parent directory using absolute path
# Note: Using the module name without version number as requested
zip -r "$ZIP_FILE" "$MODULE_NAME" \
    -x "*.git*" \
    -x "*.cursor*" \
    -x "*.sh" \
    -x "*.code-workspace" \
    -x "*.zip" \
    -x "*.ps1"

# Clean up
cd - > /dev/null
rm -rf "$TEMP_DIR"

echo "Module has been zipped as ${MODULE_NAME}.zip in the parent directory: $PARENT_DIR"
