#!/usr/bin/env php
<?php
array_shift($argv);
file_put_contents('php://stderr', implode(' ', $argv) . "\n");