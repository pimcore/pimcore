<?php

if (!function_exists('phing_phpunit_test_autoload')) {
    function phing_phpunit_test_autoload($className) {
        $className = (string) str_replace('_', DIRECTORY_SEPARATOR, $className);
        require_once(dirname(__FILE__) . '/src/' . $className . '.php');
    }
}

spl_autoload_register('phing_phpunit_test_autoload');
