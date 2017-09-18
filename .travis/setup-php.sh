#!/bin/bash

echo "Setting up PHP..."

phpenv config-add .travis/php.ini

if [[ "$PIMCORE_TEST_SETUP_SKIP_PHP_REDIS_EXTENSION" != "true" ]]
then
    echo "Enabling PHP Redis extension..."
    phpenv config-add .travis/php-redis.ini
fi
