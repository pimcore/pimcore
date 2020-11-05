#!/bin/bash

set -e

if [ $PHPSTAN_BASELINE == 0 ]; then sed -e "s?- phpstan-baseline.neon?#- phpstan-baseline.neon?g" -i phpstan.neon; fi

cmd="vendor/bin/phpstan analyse -c .travis/phpstan.travis.neon bundles/ lib/ models/ -l $PHPSTAN_LEVEL --memory-limit=-1"

if [ $PHPSTAN_BASELINE_GENERATE == 1 ]; then cmd+=" --generate-baseline"; fi

echo $cmd
eval $cmd
