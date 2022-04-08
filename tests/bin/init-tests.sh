#!/bin/bash

set -eu

.github/ci/scripts/setup-pimcore-environment.sh
composer install
