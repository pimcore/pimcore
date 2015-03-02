#!/bin/bash

phpenv config-add build/travis/php.ini

if [[ "$TRAVIS_PHP_VERSION" == "7" ]]; then sudo ln -s /home/travis/.phpenv/versions/7 /home/travis/.phpenv/versions/nightly; fi

cat /home/travis/.phpenv/versions/nightly/etc/php-fpm.d/*.conf

sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

sudo cp -f build/travis/apache-fpm.conf /etc/apache2/sites-available/default
