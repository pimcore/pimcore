<?php

// move PIMCORE_LOG_FILEOBJECT_DIRECTORY to private var

$oldPath = PIMCORE_LOG_DIRECTORY . '/fileobjects';
$newPath = PIMCORE_PRIVATE_VAR . '/application-logger';

if (is_dir($oldPath) && !is_dir($newPath)) {
    rename($oldPath, $newPath);
}
