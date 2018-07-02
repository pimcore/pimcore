<?php

if (version_compare(PHP_VERSION, '7.1', '<')) {
    echo '<b>Pimcore 5.3 requires PHP >= 7.1, your current version is: ' . PHP_VERSION . '</b><br />';
    exit;
}
