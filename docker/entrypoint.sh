#!/bin/bash
set -e

WP_TESTS_DIR=/tmp/wordpress-tests-lib

# Only run the installer if the test lib isn't already set up
# (the Docker volume caches this between runs)

if [ ! -f "$WP_TESTS_DIR/includes/functions.php" ]; then
    echo ">>> Installing WordPress test suite..."
    if [ ! -f "/app/tests/bin/install-wp-tests.sh" ]; then
        echo ">>> Downloading install-wp-tests.sh file..."
        curl -o /app/tests/bin/install-wp-tests.sh https://raw.githubusercontent.com/wp-cli/scaffold-command/main/templates/install-wp-tests.sh
    fi
    bash /app/tests/bin/install-wp-tests.sh \
        "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$WP_VERSION"
else
    echo ">>> WordPress test suite already installed, skipping..."
fi


cd /app/tests

if [ ! -f "/app/tests/vendor/autoload.php" ]; then
    echo ">>> Installing compose dependencies..."
    composer install
    composer dump-autoload
fi

export PATH="$PATH:/app/tests/vendor/bin"

echo ">>> Ready to run PHPUnit!"

# composer require --dev "yoast/phpunit-polyfills"
# phpunit -c /app/tests/phpunit.xml.dist

# Open bash
bash