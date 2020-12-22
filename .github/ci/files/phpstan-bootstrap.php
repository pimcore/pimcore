<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../../../vendor/autoload.php')) {
    include __DIR__ . '/../../../../../../vendor/autoload.php';
} elseif (getenv('PIMCORE_PROJECT_ROOT') != '' && file_exists(getenv('PIMCORE_PROJECT_ROOT') . '/vendor/autoload.php')) {
    include getenv('PIMCORE_PROJECT_ROOT') . '/vendor/autoload.php';
} elseif (getenv('PIMCORE_PROJECT_ROOT') != '') {
    throw new \Exception('Invalid Pimcore project root "' . getenv('PIMCORE_PROJECT_ROOT') . '"');
} else {
    throw new \Exception('Unknown configuration! Pimcore project root not found, please set env variable PIMCORE_PROJECT_ROOT.');
}

\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

if (!defined('PIMCORE_TEST')) {
    define('PIMCORE_TEST', true);
}
