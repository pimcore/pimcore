<?php

// get db connection
$db = Pimcore_Resource::get();

try{
    $db->query("ALTER TABLE `users` ADD COLUMN `apiKey` varchar(255) NULL DEFAULT NULL;");
} catch (\Exception $e) {

}

$conf = Pimcore_Config::getSystemConfig();

if($conf->webservice->enabled) {
    echo "<b>Warning</b>: You're using the webservice API, this update <b>resets all API keys!</b> <br />Please open the user management to set new API keys.";
}
