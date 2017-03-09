#!/bin/bash

set -e

# switch interpreter
if [[ "$TRAVIS_PHP_VERSION" == *"hhvm"* ]]; then CMD="hhvm"; else CMD="php"; fi

CMD="$CMD vendor/bin/codecept run -c pimcore"

# add suite if configured
if [[ -n "$PIMCORE_TEST_SUITE" ]]; then CMD="$CMD $PIMCORE_TEST_SUITE"; fi

# skip array tests unless configured otherwise
if [[ -z "$PIMCORE_TEST_CACHE_ARRAY" ]] || [[ "$PIMCORE_TEST_CACHE_ARRAY" -ne 1 ]]; then
    CMD="$CMD --skip-group cache.core.array"
fi

# skip file tests unless configured otherwise
if [[ -z "$PIMCORE_TEST_CACHE_FILE" ]] || [[ "$PIMCORE_TEST_CACHE_FILE" -ne 1 ]]; then
    CMD="$CMD --skip-group cache.core.file"
fi

# generate json result file
CMD="$CMD --json"

echo $CMD
eval $CMD
