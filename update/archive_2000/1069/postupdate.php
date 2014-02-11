<?php

// plugins
$pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();
foreach ($pluginConfigs as $config) {
    Pimcore_ExtensionManager::enable("plugin", $config["plugin"]["pluginName"]);
}
