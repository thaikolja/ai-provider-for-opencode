#!/usr/bin/env bash

# Exit on error
set -e

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_SLUG="ai-provider-for-opencode"
BUILD_DIR="/tmp/$PLUGIN_SLUG-build"
ZIP_FILE="$PLUGIN_DIR/$PLUGIN_SLUG.zip"

# Always work from the plugin root.
cd "$PLUGIN_DIR"

echo "Building $PLUGIN_SLUG..."

# 1. Clean up old build artifacts
echo "Cleaning up old build artifacts..."
rm -rf "$BUILD_DIR"
rm -f "$ZIP_FILE"

# 2. Run composer install without dev dependencies
echo "Running composer install (no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Create a clean build directory and copy files
echo "Copying files to build directory..."
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

# Use rsync with .distignore rules
rsync -rc --exclude-from="$PLUGIN_DIR/.distignore" "$PLUGIN_DIR/" "$BUILD_DIR/$PLUGIN_SLUG/"

# 4. Create the zip file (subshell, so cwd stays at plugin root)
echo "Creating zip archive..."
( cd "$BUILD_DIR" && zip -r "$ZIP_FILE" "$PLUGIN_SLUG" )

# 5. Clean up the build directory
echo "Cleaning up build directory..."
rm -rf "$BUILD_DIR"

# 6. Restore developer composer dependencies
echo "Restoring developer dependencies..."
composer install --optimize-autoloader --no-interaction

echo "Build complete! Plugin zip created at: $ZIP_FILE"
