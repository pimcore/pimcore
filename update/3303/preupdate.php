<?php

// flush cache
\Pimcore\Model\Cache::clearAll();

// delete the autoload class map
$file = PIMCORE_CONFIGURATION_DIRECTORY . "/autoload-classmap.php";
if(file_exists($file)) {
    unlink($file);
}
