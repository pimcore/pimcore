<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;

class Setup extends Model\AbstractModel {

    /**
     * @param array $config
     */
    public function config ($config = array()) {
	
		$settings = null;
	
		// check for an initial configuration template
		// used eg. by the demo installer
		$configTemplatePath = PIMCORE_CONFIGURATION_DIRECTORY . "/system.xml.template";
		if(file_exists($configTemplatePath)) {
			try {
				$configTemplate = new \Zend_Config_Xml($configTemplatePath);
				if($configTemplate->general) { // check if the template contains a valid configuration
					$settings = $configTemplate->toArray();

					// unset database configuration
					unset($settings["database"]["params"]["host"]);
					unset($settings["database"]["params"]["port"]);
				}
			} catch (\Exception $e) {
				
			}
		}
		
		// set default configuration if no template is present
		if(!$settings) {
			// write configuration file
			$settings = array(
				"general" => array(
					"timezone" => "Europe/Berlin",
					"language" => "en",
					"validLanguages" => "en",
					"debug" => "1",
					"debugloglevel" => "debug",
					"custom_php_logfile" => "1"
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
        foreach($varFolders as $folder) {
            \Pimcore\File::mkdir(PIMCORE_WEBSITE_VAR . "/" . $folder);
        }
		
        $config = new \Zend_Config($settings, true);
        $writer = new \Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => PIMCORE_CONFIGURATION_SYSTEM
        ));
        $writer->write();
    }

    /**
     *
     */
    public function contents($config = array()) {
        $this->getResource()->contents();
		$this->createOrUpdateUser($config);
    }
	
	/**
     * @param array $config
     */
    public function createOrUpdateUser ($config = array()) {
		
		$defaultConfig = array(
            "username" => "admin",
            "password" => md5(microtime())
        );
		
		$settings = array_replace_recursive($defaultConfig, $config);
		
		if($user = Model\User::getByName($settings["username"])) {
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