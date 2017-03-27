<?php
/**
 * Update Onlineshop Website specific xml config files to php config files.
 *
 * Execute after updating E-Commerce Plugin to 0.10.0!
 *
 */

$workingDirectory = getcwd();
chdir(__DIR__);
include_once("../../../pimcore/cli/startup.php");
chdir($workingDirectory);



function setFileExtension($path, $extension = "php")
{
    $pathinfo = pathinfo($path);

    return $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.' . $extension;
}

/**
 * String ends with
 */
function endsWith($haystack, $needle)
{
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

/**
 * Create a new php config file in the same directory for the given file.
 *
 * @param $filePath
 */
function createPhpConfigFileFor($filePath)
{
    $newPath = PIMCORE_DOCUMENT_ROOT . setFileExtension($filePath);

    try {
        $config = new \Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . $filePath);

        $config = $config->toArray();

        refactorProductIndexColumns($config);
        \Pimcore\File::putPhpFile($newPath, to_php_data_file_format($config));

        print "created file " . $newPath . "\n";
    } catch (Exception $e) {
        print $e->getMessage();
        print "\ncould not create " . $newPath . "\n";
    }
}

/**
 * Parse config array and get all sub config files, replace its extensions with .php and
 * create the new php config files.
 *
 * @param $config - modified on call
 */
function searchAndRefactorSubConfigs(&$config)
{
    if (!is_array($config)) {
        return;
    }

    foreach ($config as $prop => $val) {
        if ($prop == "file" && !is_array($val) && endsWith($val, ".xml")) {
            $config[$prop] = setFileExtension($val);
            createPhpConfigFileFor($val);
        } else {
            searchAndRefactorSubConfigs($val);
            $config[$prop] = $val;
        }
    }
}

/**
 * Remove "column" key in product index config array.
 * @param $config
 */
function refactorProductIndexColumns(&$config)
{
    $config['onlineshop']['productindex']['generalSearchColumns'] = $config['onlineshop']['productindex']['generalSearchColumns']['column'];
    $config['onlineshop']['productindex']['columns'] = $config['onlineshop']['productindex']['columns']['column'];

    if ($config['tenant']['generalSearchColumns']['column']) {
        $config['tenant']['generalSearchColumns'] = $config['tenant']['generalSearchColumns']['column'];
    }
    if ($config['tenant']['columns']['column']) {
        $config['tenant']['columns'] = $config['tenant']['columns']['column'];
    }
}

$config = \OnlineShop\Plugin::getConfig(true);
$onlineshopConfigPath = PIMCORE_DOCUMENT_ROOT . setFileExtension($config->onlineshop_config_file, 'xml');

if (file_exists($onlineshopConfigPath)) {
    $onlineshopConfig = new \Zend_Config_Xml($onlineshopConfigPath, null, ["allowModifications" => true]);

    $onlineshopConfigArray = $onlineshopConfig->toArray();
    
    searchAndRefactorSubConfigs($onlineshopConfigArray);
    refactorProductIndexColumns($onlineshopConfigArray);
    
    \Pimcore\File::putPhpFile(setFileExtension($onlineshopConfigPath), to_php_data_file_format($onlineshopConfigArray));
}




/* clear all cache for loading new configs on further requests */
Pimcore\Cache::clearTag("ecommerceconfig");
