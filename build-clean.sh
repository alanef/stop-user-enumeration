#!/bin/bash

# Clean build script for Stop User Enumeration plugin
# This script creates a clean distribution package suitable for WordPress.org

PLUGIN_SLUG="stop-user-enumeration"
VERSION=$(grep "Version:" $PLUGIN_SLUG/$PLUGIN_SLUG.php | awk '{print $2}')

echo "======================================"
echo "Building $PLUGIN_SLUG version $VERSION"
echo "======================================"

# Clean up previous builds
echo "Cleaning up previous builds..."
rm -rf build
rm -rf zipped

# Create build directory
echo "Creating build directory..."
mkdir -p build/$PLUGIN_SLUG

# Copy plugin files
echo "Copying plugin files..."
cp -r $PLUGIN_SLUG/* build/$PLUGIN_SLUG/

# Go to build directory
cd build/$PLUGIN_SLUG

# Install production dependencies only
echo "Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Remove development files
echo "Removing development files..."

# Remove composer files
find . -name "composer.json" -delete
find . -name "composer.lock" -delete

# Remove documentation files (except readme.txt)
find . -name "*.md" -not -name "readme.txt" -delete
find . -name "README" -delete
find . -name "CHANGELOG" -not -name "changelog.txt" -delete
find . -name "CONTRIBUTING*" -delete
find . -name "CODE_OF_CONDUCT*" -delete

# Remove git and development config files
find . -name ".git*" -delete
find . -name ".editorconfig" -delete
find . -name ".eslint*" -delete
find . -name ".jshint*" -delete
find . -name ".stylelint*" -delete
find . -name "*.xml.dist" -delete
find . -name "phpunit.xml*" -delete
find . -name "phpcs.xml*" -delete
find . -name ".phpcs.xml*" -delete
find . -name "psalm.xml*" -delete
find . -name ".travis.yml" -delete
find . -name ".scrutinizer.yml" -delete

# Remove test directories
find . -type d -name "tests" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name "Tests" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name "test" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name "Test" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name ".github" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name "bin" -exec rm -rf {} + 2>/dev/null || true

# Remove example and demo files
find . -name "*example*" -delete
find . -name "*demo*" -delete
find . -name "*sample*" -delete

# Remove build files
find . -name "Makefile" -delete
find . -name "Gruntfile.js" -delete
find . -name "gulpfile.js" -delete
find . -name "webpack.config.js" -delete
find . -name "rollup.config.js" -delete

# Remove package management files
find . -name "package.json" -delete
find . -name "package-lock.json" -delete
find . -name "yarn.lock" -delete
find . -name "bower.json" -delete

# Remove IDE files
find . -name ".idea" -type d -exec rm -rf {} + 2>/dev/null || true
find . -name ".vscode" -type d -exec rm -rf {} + 2>/dev/null || true
find . -name "*.iml" -delete
find . -name ".project" -delete

# Remove other unnecessary files
find . -name "*.log" -delete
find . -name "*.cache" -delete
find . -name ".DS_Store" -delete
find . -name "Thumbs.db" -delete
find . -name "desktop.ini" -delete

# Remove composer/installers (not needed in production)
echo "Removing unnecessary vendor packages..."
rm -rf includes/vendor/composer/installers

# Clean up vendor directory further
find includes/vendor -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true
find includes/vendor -name "docs" -type d -exec rm -rf {} + 2>/dev/null || true
find includes/vendor -name "doc" -type d -exec rm -rf {} + 2>/dev/null || true
find includes/vendor -name "examples" -type d -exec rm -rf {} + 2>/dev/null || true

# Remove empty directories
find . -type d -empty -delete

# Return to build directory
cd ..

# Create distribution directory
mkdir -p ../zipped

# Create the zip file
echo "Creating zip file..."
zip -r ../zipped/$PLUGIN_SLUG-$VERSION.zip $PLUGIN_SLUG -x "*.git*" "*/\.DS_Store"

# Get zip file size
cd ../zipped
FILESIZE=$(du -h "$PLUGIN_SLUG-$VERSION.zip" | cut -f1)

# Final report
echo ""
echo "======================================"
echo "Build complete!"
echo "======================================"
echo "Plugin: $PLUGIN_SLUG"
echo "Version: $VERSION"
echo "Package: zipped/$PLUGIN_SLUG-$VERSION.zip"
echo "Size: $FILESIZE"
echo ""

# Verify no development files remain
echo "Verifying package contents..."
cd ../build/$PLUGIN_SLUG
DEV_FILES=$(find . -name "composer.json" -o -name "*.md" -o -name ".git*" -o -name "test*" -o -name "Test*" | wc -l)
if [ $DEV_FILES -gt 0 ]; then
    echo "⚠️  Warning: Found $DEV_FILES potential development files remaining"
    find . -name "composer.json" -o -name "*.md" -o -name ".git*" -o -name "test*" -o -name "Test*"
else
    echo "✓ No development files found in distribution"
fi

echo ""
echo "Next steps:"
echo "1. Test the package in a clean WordPress installation"
echo "2. Run the Plugin Check plugin on the packaged version"
echo "3. Submit to WordPress.org repository"