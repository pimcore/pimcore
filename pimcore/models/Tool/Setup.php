<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool;

use Pimcore\File;
use Pimcore\Model;

class Setup extends Model\AbstractModel
{

    /**
     * @param array $config
     */
    public function config($config = array())
    {
        $settings = null;

        // check for an initial configuration template
        // used eg. by the demo installer
        $configTemplatePath = PIMCORE_CONFIGURATION_DIRECTORY . "/system.template.php";
        if (file_exists($configTemplatePath)) {
            try {
                $configTemplate = new \Zend_Config(include($configTemplatePath));
                if ($configTemplate->general) { // check if the template contains a valid configuration
                    $settings = $configTemplate->toArray();

                    // unset database configuration
                    unset($settings["database"]["params"]["host"]);
                    unset($settings["database"]["params"]["port"]);
                }
            } catch (\Exception $e) {
            }
        }

        // set default configuration if no template is present
        if (!$settings) {
            // write configuration file
            $settings = array(
                "general" => array(
                    "timezone" => "Europe/Berlin",
                    "language" => "en",
                    "validLanguages" => "en",
                    "debug" => "1",
                    "debugloglevel" => "debug",
                    "custom_php_logfile" => "1",
                    "extjs6" => "1",
                ),
                "database" => array(
                    "adapter" => "Mysqli",
                    "params" => array(
                        "username" => "root",
                        "password" => "",
                        "dbname" => "",
                    )
                ),
                "documents" => array(
                    "versions" => array(
                        "steps" => "10"
                    ),
                    "default_controller" => "default",
                    "default_action" => "default",
                    "error_pages" => array(
                        "default" => "/"
                    ),
                    "createredirectwhenmoved" => "",
                    "allowtrailingslash" => "no",
                    "allowcapitals" => "no",
                    "generatepreview" => "1"
                ),
                "objects" => array(
                    "versions" => array(
                        "steps" => "10"
                    )
                ),
                "assets" => array(
                    "versions" => array(
                        "steps" => "10"
                    )
                ),
                "services" => array(),
                "cache" => array(
                    "excludeCookie" => ""
                ),
                "httpclient" => array(
                    "adapter" => "Zend_Http_Client_Adapter_Socket"
                )
            );
        }

        $settings = array_replace_recursive($settings, $config);

        // create initial /website/var folder structure
        // @TODO: should use values out of startup.php (Constants)
        $varFolders = array("areas","assets","backup","cache","classes","config","email","log","plugins","recyclebin","search","system","tmp","versions","webdav");
        foreach ($varFolders as $folder) {
            \Pimcore\File::mkdir(PIMCORE_WEBSITE_VAR . "/" . $folder);
        }

        $configFile = \Pimcore\Config::locateConfigFile("system.php");
        File::putPhpFile($configFile, to_php_data_file_format($settings));
    }

    /**
     *
     */
    public function contents($config = array())
    {
        $this->getDao()->contents();
        $this->createOrUpdateUser($config);
    }

    /**
     * @param array $config
     */
    public function createOrUpdateUser($config = array())
    {
        $defaultConfig = array(
            "username" => "admin",
            "password" => md5(microtime())
        );

        $settings = array_replace_recursive($defaultConfig, $config);

        if ($user = Model\User::getByName($settings["username"])) {
            $user->delete();
        }

        $user = Model\User::create(array(
            "parentId" => 0,
            "username" => $settings["username"],
            "password" => \Pimcore\Tool\Authentication::getPasswordHash($settings["username"], $settings["password"]),
            "active" => true
        ));
        $user->setAdmin(true);
        $user->save();
    }
}
