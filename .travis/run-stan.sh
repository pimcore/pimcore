#!/bin/bash

if [ $PHPSTAN_BASELINE == 0 ]; then sed -e "s?- phpstan-baseline.neon?#- phpstan-baseline.neon?g" -i phpstan.neon; fi

if [ $SYMFONY_VERSION = "^3.4" ]
then
    vendor/bin/phpstan analyse -c .travis/phpstan.travis.neon bundles/ lib/ models/ -l $PHPSTAN_LEVEL --memory-limit=-1;
else
    vendor/bin/phpstan analyse -c .travis/phpstan.s4.travis.neon bundles/ lib/ models/ -l $PHPSTAN_LEVEL --memory-limit=-1;
fi
