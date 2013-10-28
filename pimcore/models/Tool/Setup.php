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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Setup extends Pimcore_Model_Abstract {

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
				$configTemplate = new Zend_Config_Xml($configTemplatePath);
				if($configTemplate->general) { // check if the template contains a valid configuration
					$settings = $configTemplate->toArray();
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
					"validLanguages" => "en,de",
					"debug" => "1",
					"loglevel" => array(
						"debug" => "1",
						"info" => "1",
						"notice" => "1",
						"warning" => "1",
						"error" => "1",
						"critical" => "1",
						"alert" => "1",
						"emergency" => "1"
					),
					"custom_php_logfile" => "1"
				),
				"database" => array(
					"adapter" => "Mysqli",
					"params" => array(
						"host" => "localhost",
						"username" => "root",
						"password" => "",
						"dbname" => "",
						"port" => "3306",
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
            Pimcore_File::mkdir(PIMCORE_WEBSITE_VAR . "/" . $folder);
        }
		
        $config = new Zend_Config($settings, true);		
        $writer = new Zend_Config_Writer_Xml(array(
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
		
		if($user = User::getByName($settings["username"])) {
			$user->delete();
		}
		
		$user = User::create(array(
            "parentId" => 0,
            "username" => $settings["username"],
            "password" => Pimcore_Tool_Authentication::getPasswordHash($settings["username"], $settings["password"]),
            "active" => true
        ));
        $user->setAdmin(true);
        $user->save();
    }

}