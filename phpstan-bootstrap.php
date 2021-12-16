<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    define('PIMCORE_PROJECT_ROOT', __DIR__);
} elseif (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    define('PIMCORE_PROJECT_ROOT', __DIR__ . '/../../..');
} elseif (getenv('PIMCORE_PROJECT_ROOT')) {
    define('PIMCORE_PROJECT_ROOT', getenv('PIMCORE_PROJECT_ROOT'));
} else {
    throw new \Exception('Unknown configuration! Pimcore project root not found, please set env variable PIMCORE_PROJECT_ROOT.');
}

include PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();

if (!defined('PIMCORE_TEST')) {
    define('PIMCORE_TEST', true);
}
