#!/bin/bash

set -ev

SEARCHPATH=$TRAVIS_BUILD_DIR
if [ -z "$SEARCHPATH" ]; then
    SEARCHPATH=$(dirname $0)/..
fi

echo "Downloading ack 2 to /tmp/ack"
curl -L https://beyondgrep.com/ack-2.14-single-file > /tmp/ack && chmod 0755 /tmp/ack
/tmp/ack --version

echo "Analyzing ZF1 usage in $SEARCHPATH"
/tmp/ack -oh 'Zend_[a-zA-Z0-9_\x7f-\xff]+' $SEARCHPATH \
    --ignore-dir docs \
    --ignore-dir vendor \
    --ignore-dir website \
    --ignore-file=match:maintenance.html \
    --nosql | sort | uniq -c | sort -bgr
