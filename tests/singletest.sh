#!/bin/bash

mkdir -p output

./phpunit --verbose --bootstrap bootstrap.php --log-json output/log.xml SingleTest

