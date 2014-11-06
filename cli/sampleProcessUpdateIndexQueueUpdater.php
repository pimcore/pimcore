<?php

$workingDirectory = getcwd();
chdir(__DIR__);
include_once("../../../pimcore/cli/startup.php");
chdir($workingDirectory);


OnlineShop_Framework_IndexService_Tool_IndexUpdater::processUpdateIndexQueue();
 