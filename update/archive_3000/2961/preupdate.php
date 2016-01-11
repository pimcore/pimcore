<?php

if (version_compare(PHP_VERSION, '5.4', "<")) {
    echo "<b>Pimcore requires at least PHP 5.4.<br />Your version is" . PHP_VERSION . ".<br />Please upgrade your PHP installation before resuming the pimcore update!</b>";
    exit;
}