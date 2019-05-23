#!/bin/bash

vendor/bin/phpstan analyse -c phpstan.travis.neon bundles/ lib/ models/ -l 0
