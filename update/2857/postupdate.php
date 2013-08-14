<?php

$dir = PIMCORE_DOCUMENT_ROOT . "/PIMCORE_DEPLOYMENT_DIRECTORY";
if(is_dir($dir)) {
    recursiveDelete($dir);
}
