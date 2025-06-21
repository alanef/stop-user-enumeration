#!/bin/bash

# Script to run PHPUnit tests in wp-env environment

echo "======================================"
echo "Running PHPUnit Tests in wp-env"
echo "======================================"
echo ""

# Check if wp-env is running
if ! wp-env run cli wp core version > /dev/null 2>&1; then
    echo "Starting wp-env..."
    npm run start
    echo "Waiting for WordPress to be ready..."
    sleep 10
fi

# Copy vendor directory to the plugin in tests environment
echo "Copying vendor directory..."
docker cp vendor/. $(docker ps -qf "name=tests-wordpress"):/var/www/html/wp-content/plugins/stop-user-enumeration/vendor/ 2>/dev/null || true
docker cp tests/. $(docker ps -qf "name=tests-wordpress"):/var/www/html/wp-content/plugins/stop-user-enumeration/tests/ 2>/dev/null || true
docker cp phpunit.xml.dist $(docker ps -qf "name=tests-wordpress"):/var/www/html/wp-content/plugins/stop-user-enumeration/phpunit.xml.dist 2>/dev/null || true

# Run PHPUnit directly
echo ""
echo "Running tests..."
echo ""

# Use the WordPress PHPUnit bootstrap that's already in wp-env
wp-env run tests-cli --env-cwd=/var/www/html/wp-content/plugins/stop-user-enumeration bash -c "
    export WP_TESTS_DIR=/wordpress-phpunit && \
    export WP_TESTS_PHPUNIT_POLYFILLS_PATH=/var/www/html/wp-content/plugins/stop-user-enumeration/vendor/yoast/phpunit-polyfills && \
    php ./vendor/bin/phpunit --configuration phpunit.xml.dist
"