<?php

if (version_compare(PHP_VERSION, '5.5', "<")) {
    echo "<b>Pimcore requires at least PHP 5.5.<br />Your version is" . PHP_VERSION . ".<br />Please upgrade your PHP installation before resuming the pimcore update!</b>";
    exit;
}

// delete custom autoload-classmap.php
$autoloadClassMap = PIMCORE_CONFIGURATION_DIRECTORY . "/autoload-classmap.php";
if(file_exists($autoloadClassMap)) {
    echo "Your custom autoload-classmap.php was deleted, please regenerate it after the upgrade has finished.";
    @unlink($autoloadClassMap);
}
