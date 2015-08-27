#!/usr/bin/env php
<?php

define('PIMCORE_CONSOLE', true);

require_once 'startup.php';
// chdir(PIMCORE_DOCUMENT_ROOT);

$application = new Pimcore\Console\Application();
$application->run();