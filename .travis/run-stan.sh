#!/bin/bash

set -e

if [ $SYMFONY_VERSION = "^3.4" ]
then
    config=".travis/phpstan.travis.neon"
else
    config=".travis/phpstan.s4.travis.neon"
fi

cmd="vendor/bin/phpstan analyse -c $config bundles/ lib/ models/ -l $PHPSTAN_LEVEL --memory-limit=-1"

if [ $PHPSTAN_BASELINE_GENERATE == 1 ]; then cmd+=" --generate-baseline"; fi

echo $cmd
eval $cmd
