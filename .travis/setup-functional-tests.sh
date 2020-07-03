#!/bin/bash

# this was added to an extra script because travis had problems when the .travis.yml contained
# the string "sudo"
if [[ "$TRAVIS_SUDO" == "true" ]]
then
    echo "Setting up environment for functional tests (install webserver)"
    .travis/setup-sudo.sh
fi
