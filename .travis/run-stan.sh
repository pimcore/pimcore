#!/bin/bash


# install suggested packages first
composer require "heidelpay/heidelpay-php:^1.2.5.1" "klarna/checkout:^3.0.0" "elasticsearch/elasticsearch:2.0.0" "paypal/paypal-checkout-sdk:^1" "mpay24/mpay24-php:^4.2" "composer/composer:*"

if [ $SYMFONY_VERSION = "^3.4" ]
then
    vendor/bin/phpstan analyse -c .travis/phpstan.travis.neon bundles/ lib/ models/ -l $PHPSTAN_LEVEL --memory-limit=-1;
else
    vendor/bin/phpstan analyse -c .travis/phpstan.s4.travis.neon bundles/ lib/ models/ -l $PHPSTAN_LEVEL --memory-limit=-1;
fi
