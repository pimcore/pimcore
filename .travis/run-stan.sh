#!/bin/bash

if [ $SYMFONY_VERSION = "^3.4" ]
then
    vendor/bin/phpstan analyse -c .travis/phpstan.travis.neon bundles/ lib/ models/ -l 0 --memory-limit=-1;
else
    vendor/bin/phpstan analyse -c .travis/phpstan.s4.travis.neon bundles/ lib/ models/ -l 0 --memory-limit=-1;
fi
