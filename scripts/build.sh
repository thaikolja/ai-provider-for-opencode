#!/usr/bin/env bash

#
# Copyright (C) 2026-2026 by Kolja Nolte
# https://www.kolja-nolte.com
# kolja.nolte@gmail.com
#
# All Rights Reserved.
#
# This code is proprietary software. You may not use, copy, modify,
# merge, publish, distribute, sublicense, and/or sell copies of
# the Software without express written permission from the author.
#
# @author Kolja Nolte <kolja.nolte@gmail.com>
# @date 2024-2026
#

# Exit on error
set -e

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_SLUG="nominal-ai-provider-for-opencode"
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

# 4. Clean up macOS metadata files that rsync may have missed
echo "Cleaning up .DS_Store files..."
find "$BUILD_DIR" -name ".DS_Store" -delete

# 5. Create the zip file (subshell, so cwd stays at plugin root)
echo "Creating zip archive..."
( cd "$BUILD_DIR" && zip -r "$ZIP_FILE" "$PLUGIN_SLUG" )

# 6. Clean up the build directory
echo "Cleaning up build directory..."
rm -rf "$BUILD_DIR"

# 7. Restore developer composer dependencies
echo "Restoring developer dependencies..."
composer install --optimize-autoloader --no-interaction

# 8. Yay, we're done!
echo "Build complete! Plugin zip created at: $ZIP_FILE"
