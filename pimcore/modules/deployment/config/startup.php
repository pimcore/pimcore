<?php
$includePaths = array(
    PIMCORE_PATH . "/modules/deployment/models", //needs to be defined here - otherwise resourceclasses won't be loaded
);
set_include_path(get_include_path() . implode(PATH_SEPARATOR, $includePaths));

if (!defined("PIMCORE_DEPLOYMENT_DIRECTORY"))  define("PIMCORE_DEPLOYMENT_DIRECTORY", PIMCORE_WEBSITE_VAR . "/deployment");
if (!defined("PIMCORE_DEPLOYMENT_CONFIG_FILE"))  define("PIMCORE_DEPLOYMENT_CONFIG_FILE", PIMCORE_CONFIGURATION_DIRECTORY . "/deployment/config.xml");
if (!defined("PIMCORE_DEPLOYMENT_PACKAGES_DIRECTORY"))  define("PIMCORE_DEPLOYMENT_PACKAGES_DIRECTORY", PIMCORE_DEPLOYMENT_DIRECTORY . "/packages");