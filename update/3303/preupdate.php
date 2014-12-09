<?php

// flush cache
\Pimcore_Model_Cache::clearAll();

// delete the autoload class map
$file = PIMCORE_CONFIGURATION_DIRECTORY . "/autoload-classmap.php";
if(file_exists($file)) {
    unlink($file);
}


// clear the opcache (as of PHP 5.5)
if(function_exists("opcache_reset")) {
    opcache_reset();
}

// clear the APC opcode cache (<= PHP 5.4)
if(function_exists("apc_clear_cache")) {
    apc_clear_cache();
}

// clear the Zend Optimizer cache (Zend Server <= PHP 5.4)
if (function_exists('accelerator_reset')) {
    return accelerator_reset();
}

