<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bernhard
 * Date: 28.08.11
 * Time: 22:44
 * To change this template use File | Settings | File Templates.
 */
 

$workingDirectory = getcwd();
include("../../../cli/startup.php");
chdir($workingDirectory);

// only for logged in users
$user = Pimcore_Tool_Authentication::authenticateSession();
if(!$user instanceof User) {
    die("Authentication failed!");
}


include ("dbtuner.mysqltuner.php");

echo dbtuner_mysqltuner_page();