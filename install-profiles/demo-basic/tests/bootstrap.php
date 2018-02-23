<?php
define(
    'PIMCORE_PROJECT_ROOT',
    realpath(getenv('PIMCORE_PROJECT_ROOT'))
);

require_once PIMCORE_PROJECT_ROOT . '/pimcore/config/bootstrap.php';
