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

# Run PHPUnit directly
echo ""
echo "Running tests..."
echo ""

# Use the WordPress PHPUnit bootstrap that's already in wp-env
# Tests are mapped to /var/www/html/tests by wp-env
wp-env run tests-cli bash -c "
    export WP_TESTS_DIR=/wordpress-phpunit && \
    export WP_TESTS_PHPUNIT_POLYFILLS_PATH=/var/www/html/vendor/yoast/phpunit-polyfills && \
    cd /var/www/html && \
    php ./vendor/bin/phpunit --configuration phpunit.xml.dist --colors=always --testdox
"

# Check exit status and display result
if [ $? -eq 0 ]; then
    echo ""
    echo -e "\033[32m✅ All tests passed!\033[0m"
    echo ""
else
    echo ""
    echo -e "\033[31m❌ Some tests failed!\033[0m"
    echo ""
    exit 1
fi