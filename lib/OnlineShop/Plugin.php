<?php

class OnlineShop_Plugin extends Pimcore_API_Plugin_Abstract implements Pimcore_API_Plugin_Interface {

    public static $configFile = "/OnlineShop/config/plugin_config.xml";

    public static function getConfig($readonly = true) {
        if(!$readonly) {
            $config = new Zend_Config_Xml(PIMCORE_PLUGINS_PATH . OnlineShop_Plugin::$configFile,
			                              null,
			                              array('skipExtends'        => true,
		    	                                'allowModifications' => true));
        } else {
            $config = new Zend_Config_Xml(PIMCORE_PLUGINS_PATH . OnlineShop_Plugin::$configFile);
        }
        return $config;
    }

    public static function setConfig($onlineshopConfigFile) {
        $config = self::getConfig(false);
        $config->onlineshop_config_file = $onlineshopConfigFile;

        // Write the config file
        $writer = new Zend_Config_Writer_Xml(array('config'   => $config,
                                                   'filename' => PIMCORE_PLUGINS_PATH . OnlineShop_Plugin::$configFile));
        $writer->write();
    }



    /**
     *  install function
     * @return string $message statusmessage to display in frontend
     */
    public static function install() {
        //Cart
        Pimcore_API_Plugin_Abstract::getDb()->query(
            "CREATE TABLE `plugin_onlineshop_cart` (
              `id` int(20) NOT NULL AUTO_INCREMENT,
              `userid` int(20) NOT NULL,
              `name` varchar(250) COLLATE utf8_bin DEFAULT NULL,
              `creationDateTimestamp` int(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

        //CartCheckoutData
        Pimcore_API_Plugin_Abstract::getDb()->query(
            "CREATE TABLE `plugin_onlineshop_cartcheckoutdata` (
              `cartId` int(20) NOT NULL,
              `key` varchar(150) COLLATE utf8_bin NOT NULL,
              `data` longtext CHARACTER SET latin1,
              PRIMARY KEY (`cartId`,`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

        //CartItem
        Pimcore_API_Plugin_Abstract::getDb()->query(
            "CREATE TABLE `plugin_onlineshop_cartitem` (
              `productId` int(20) NOT NULL,
              `cartId` int(20) NOT NULL,
              `count` int(20) NOT NULL,
              `itemKey` varchar(100) COLLATE utf8_bin NOT NULL,
              `parentItemKey` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT '0',
              `comment` LONGTEXT ASCII,
              `addedDateTimestamp` int(10) NOT NULL,
              PRIMARY KEY (`itemKey`,`cartId`,`parentItemKey`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

        //OrderEvent
        Pimcore_API_Plugin_Abstract::getDb()->query("
            CREATE TABLE `plugin_customerdb_event_orderEvent` (
              `eventid` int(11) NOT NULL DEFAULT '0',
              `orderObject__id` int(11) DEFAULT NULL,
              `orderObject__type` enum('document','asset','object') DEFAULT NULL,
              PRIMARY KEY (`eventid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        // Add FieldCollections
        //chdir(__DIR__);
        $sourceFiles = scandir(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/fieldcollection_sources');
        foreach ($sourceFiles as $filename) {
            if (!is_dir($filename)) {
                $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/fieldcollection_sources/' . $filename);
                $conf = new Zend_Config_Xml($data);
                $importData = $conf->toArray();

                $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);

                preg_match('/_(.*)_/', $filename, $matches);
                $key = $matches[1];

                try {
                    $fieldCollection = Object_Fieldcollection_Definition::getByKey($key);
                } catch(Exception $e) {
                    $fieldCollection = new Object_Fieldcollection_Definition();
                    $fieldCollection->setKey($key);
                    $fieldCollection->setParentClass($importData['parentClass']);
                    $fieldCollection->setLayoutDefinitions($layout);
                    $fieldCollection->save();
                }
            }
        }

        // Add class
        $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_FilterDefinition_export.xml');
        $conf = new Zend_Config_Xml($data);
        $importData = $conf->toArray();

        $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);

        $classname = "FilterDefinition";
        $class = Object_Class::getByName($classname);
        if(!$class) {
            $class = new Object_Class();
            $class->setLayoutDefinitions($layout);
            $class->setModificationDate(time());
            $class->setIcon($importData["icon"]);
            $class->setAllowInherit($importData["allowInherit"]);
            $class->setAllowVariants($importData["allowVariants"]);
            $class->setParentClass($importData["parentClass"]);
            $class->setPreviewUrl($importData["previewUrl"]);
            $class->setPropertyVisibility($importData["propertyVisibility"]);
            $class->setName($classname);

            $class->save();
        }

        //copy config file
        if(!is_file(PIMCORE_WEBSITE_PATH . "/var/plugins/OnlineShopConfig.xml")) {
            copy(PIMCORE_PLUGINS_PATH . "/OnlineShop/config/OnlineShopConfig_sample.xml", PIMCORE_WEBSITE_PATH . "/var/plugins/OnlineShopConfig.xml");
        }
        self::setConfig("/website/var/plugins/OnlineShopConfig.xml");


        if(self::isInstalled()){
			$statusMessage = "installed"; // $translate->_("plugin_objectassetfolderrelation_installed_successfully");
		} else {
			$statusMessage = "not installed"; // $translate->_("plugin_objectassetfolderrelation_could_not_install");
		}
		return $statusMessage;

    }

    /**
     *
     * @return boolean
     */
    public static function needsReloadAfterInstall() {
        return true;
    }

    /**
     *  indicates wether this plugins is currently installed
     * @return boolean
     */
    public static function isInstalled() {
        $result = null;
		try{
			$result = Pimcore_API_Plugin_Abstract::getDb()->describeTable("plugin_onlineshop_cartitem");
		} catch(Exception $e){}
		return !empty($result);
    }

    /**
     * uninstall function
     * @return string $messaget status message to display in frontend
     */
    public static function uninstall() {

        Pimcore_API_Plugin_Abstract::getDb()->query("DROP TABLE IF EXISTS `plugin_onlineshop_cart`");
        Pimcore_API_Plugin_Abstract::getDb()->query("DROP TABLE IF EXISTS `plugin_onlineshop_cartcheckoutdata`");
        Pimcore_API_Plugin_Abstract::getDb()->query("DROP TABLE IF EXISTS `plugin_onlineshop_cartitem`");
        Pimcore_API_Plugin_Abstract::getDb()->query("DROP TABLE IF EXISTS `plugin_customerdb_event_orderEvent`");

		if(!self::isInstalled()){
			$statusMessage = "uninstalled successfully"; //  $translate->_("plugin_objectassetfolderrelation_uninstalled_successfully");
		} else {
			$statusMessage = "did not uninstall"; // $translate->_("plugin_objectassetfolderrelation_could_not_uninstall");
		}
		return $statusMessage;

    }


    /**
     * @return string $jsClassName
     */
    public static function getJsClassName() {
    }

    /**
     *
     * @param string $language
     * @return string path to the translation file relative to plugin direcory
     */
    public static function getTranslationFile($language) {
        if ($language == "de") {
            return "/OnlineShop/texts/de.csv";
        } else if ($language == "en") {
            return "/OnlineShop/texts/en.csv";
        } else {
            return null;
        }
    }

    /**
     * @param Object_Abstract $object
     * @return void
     */
    public function postUpdateObject(Object_Abstract $object) {
        if ($object instanceof OnlineShop_Framework_AbstractProduct) {
            $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
            $indexService->updateIndex($object);
        }
    }

    public function preDeleteObject(Object_Abstract $object) {
        if ($object instanceof OnlineShop_Framework_AbstractProduct) {
            $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
            $indexService->deleteFromIndex($object);
        }
        parent::preDeleteObject($object);
    }


}
