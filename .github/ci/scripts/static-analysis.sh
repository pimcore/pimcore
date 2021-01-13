#!/bin/bash

set -e

if [ $SYMFONY_VERSION = "^3.4" ];
then
    config=".github/ci/files/phpstan.actions.neon"
else
    config=".github/ci/files/phpstan.s4.actions.neon"
fi

cmd="vendor/bin/phpstan analyse -c $config bundles/ lib/ models/ -l 3 --memory-limit=-1"

echo $cmd
eval $cmd
