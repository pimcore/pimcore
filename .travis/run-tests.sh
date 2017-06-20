#!/bin/bash

set -e

CMD="vendor/bin/codecept run -c pimcore"

# add suite if configured
if [[ -n "$PIMCORE_TEST_SUITE" ]]; then
    CMD="$CMD $PIMCORE_TEST_SUITE"
fi

# add env if configured
if [[ -n "$PIMCORE_TEST_ENV" ]]; then
    CMD="$CMD --env $PIMCORE_TEST_ENV"
fi

# skip file tests unless configured otherwise
if [[ -z "$PIMCORE_TEST_CACHE_FILE" ]] || [[ "$PIMCORE_TEST_CACHE_FILE" -ne 1 ]]; then
    CMD="$CMD --skip-group cache.core.file"
fi

# generate json result file
CMD="$CMD --json"

echo $CMD
eval $CMD
