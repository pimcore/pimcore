<?php

// simple bootstrap for plain unit tests (no db initalization, ...)
require_once __DIR__ . '/../pimcore/cli/startup.php';

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->add('', __DIR__ . '/');
$loader->add('', __DIR__ . '/lib/');
