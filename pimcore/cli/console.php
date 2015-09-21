#!/usr/bin/env php
<?php

define('PIMCORE_CONSOLE', true);

require_once 'startup.php';

$application = new Pimcore\Console\Application();
$application->run();
