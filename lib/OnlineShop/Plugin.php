<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop;

class Plugin extends \Pimcore\API\Plugin\AbstractPlugin implements \Pimcore\API\Plugin\PluginInterface {

    public static $configFile = "/OnlineShop/config/plugin_config.xml";

    public function init() {
        parent::init();

        LegacyClassMappingTool::loadMapping();
    }

    public static function getConfig($readonly = true) {
        if(!$readonly) {
            $config = new \Zend_Config_Xml(PIMCORE_PLUGINS_PATH . self::$configFile,
                null,
                array('skipExtends'        => true,
                    'allowModifications' => true));
        } else {
            $config = new \Zend_Config_Xml(PIMCORE_PLUGINS_PATH . self::$configFile);
        }
        return $config;
    }

    public static function setConfig($onlineshopConfigFile) {
        $config = self::getConfig(false);
        $config->onlineshop_config_file = $onlineshopConfigFile;

        // Write the config file
        $writer = new \Zend_Config_Writer_Xml(array('config'   => $config,
            'filename' => PIMCORE_PLUGINS_PATH . self::$configFile));
        $writer->write();
    }



    /**
     *  install function
     * @return string $message statusmessage to display in frontend
     */
    public static function install() {
        //Cart
        \Pimcore\Db::get()->query(
            "CREATE TABLE `plugin_onlineshop_cart` (
              `id` int(20) NOT NULL AUTO_INCREMENT,
              `userid` int(20) NOT NULL,
              `name` varchar(250) COLLATE utf8_bin DEFAULT NULL,
              `creationDateTimestamp` int(10) NOT NULL,
              `modificationDateTimestamp` int(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

        //CartCheckoutData
        \Pimcore\Db::get()->query(
            "CREATE TABLE `plugin_onlineshop_cartcheckoutdata` (
              `cartId` int(20) NOT NULL,
              `key` varchar(150) COLLATE utf8_bin NOT NULL,
              `data` longtext,
              PRIMARY KEY (`cartId`,`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

        //CartItem
        \Pimcore\Db::get()->query(
            "CREATE TABLE `plugin_onlineshop_cartitem` (
              `productId` int(20) NOT NULL,
              `cartId` int(20) NOT NULL,
              `count` int(20) NOT NULL,
              `itemKey` varchar(100) COLLATE utf8_bin NOT NULL,
              `parentItemKey` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT '0',
              `comment` LONGTEXT ASCII,
              `addedDateTimestamp` int(10) NOT NULL,
              `sortIndex` INT(10) UNSIGNED NULL DEFAULT '0',
              PRIMARY KEY (`itemKey`,`cartId`,`parentItemKey`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

        // Voucher Service

        // Statistics
        \Pimcore\Db::get()->query(
            "CREATE TABLE `plugins_onlineshop_vouchertoolkit_statistics` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `voucherSeriesId` BIGINT(20) NOT NULL,
                `date` DATE NOT NULL,
                PRIMARY KEY (`id`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB ;"
        );


        // Tokens
        \Pimcore\Db::get()->query(
            "CREATE TABLE `plugins_onlineshop_vouchertoolkit_tokens` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `voucherSeriesId` BIGINT(20) NULL DEFAULT NULL,
                `token` VARCHAR(250) NULL DEFAULT NULL COLLATE 'latin1_bin',
                `length` INT(11) NULL DEFAULT NULL,
                `type` VARCHAR(50) NULL DEFAULT NULL,
                `usages` BIGINT(20) NULL DEFAULT '0',
                `timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `token` (`token`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB;"
        );

        // Reservations
        \Pimcore\Db::get()->query(
            "CREATE TABLE `plugins_onlineshop_vouchertoolkit_reservations` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `token` VARCHAR(250) NOT NULL,
                `cart_id` VARCHAR(250) NOT NULL,
                `timestamp` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `token` (`token`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB
            ;"
        );

        self::createFieldCollections();
        self::createClasses();
        self::createObjectBricks();


        //copy config file
        if(!is_file(PIMCORE_WEBSITE_PATH . "/var/plugins/OnlineShop/OnlineShopConfig.xml")) {
            mkdir(PIMCORE_WEBSITE_PATH . "/var/plugins/OnlineShop", 0777, true);
            copy(PIMCORE_PLUGINS_PATH . "/OnlineShop/config/OnlineShopConfig_sample.xml", PIMCORE_WEBSITE_PATH . "/var/plugins/OnlineShop/OnlineShopConfig.xml");
            copy(PIMCORE_PLUGINS_PATH . "/OnlineShop/config/.htaccess", PIMCORE_WEBSITE_PATH . "/var/plugins/OnlineShop/.htaccess");
        }
        self::setConfig("/website/var/plugins/OnlineShop/OnlineShopConfig.xml");



        // execute installations from subsystems
        $reflection = new \ReflectionClass( __CLASS__ );
        $methods = $reflection->getMethods( \ReflectionMethod::IS_STATIC );
        foreach($methods as $method)
        {
            /* @var \ReflectionMethod $method */
            if(preg_match('#^install[A-Z]#', $method->name))
            {
                $func = $method->name;
                $success = self::$func();
            }
        }


        // import admin-translations
        \Pimcore\Model\Translation\Admin::importTranslationsFromFile(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/admin-translations/init.csv', true);


        // create status message
        if(self::isInstalled()){
            $statusMessage = "installed"; // $translate->_("plugin_objectassetfolderrelation_installed_successfully");
        } else {
            $statusMessage = "not installed"; // $translate->_("plugin_objectassetfolderrelation_could_not_install");
        }
        return $statusMessage;

    }

    private static function createClass($classname, $filepath) {
        $class = \Pimcore\Model\Object\ClassDefinition::getByName($classname);
        if(!$class) {
            $class = new \Pimcore\Model\Object\ClassDefinition();
            $class->setName($classname);
        }
        $json = file_get_contents($filepath);

        $success = \Pimcore\Model\Object\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        if(!$success){
            \Logger::err("Could not import $classname Class.");
        }
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
            $result = \Pimcore\Db::get()->describeTable("plugin_onlineshop_cartitem");
        } catch(\Exception $e){}
        return !empty($result);
    }

    /**
     * uninstall function
     * @return string $messaget status message to display in frontend
     */
    public static function uninstall() {

        \Pimcore\Db::get()->query("DROP TABLE IF EXISTS `plugin_onlineshop_cart`");
        \Pimcore\Db::get()->query("DROP TABLE IF EXISTS `plugin_onlineshop_cartcheckoutdata`");
        \Pimcore\Db::get()->query("DROP TABLE IF EXISTS `plugin_onlineshop_cartitem`");
        \Pimcore\Db::get()->query("DROP TABLE IF EXISTS `plugin_customerdb_event_orderEvent`");
        \Pimcore\Db::get()->query("DROP TABLE IF EXISTS `plugins_onlineshop_vouchertoolkit_reservations`");
        \Pimcore\Db::get()->query("DROP TABLE IF EXISTS `plugins_onlineshop_vouchertoolkit_tokens`");
        \Pimcore\Db::get()->query("DROP TABLE IF EXISTS `plugins_onlineshop_vouchertoolkit_statistics`");


        // execute uninstallation from subsystems
        $reflection = new \ReflectionClass( __CLASS__ );
        $methods = $reflection->getMethods( \ReflectionMethod::IS_STATIC );
        foreach($methods as $method)
        {
            /* @var \ReflectionMethod $method */
            if(preg_match('#^uninstall[A-Z]#', $method->name))
            {
                $func = $method->name;
                $success = self::$func();
            }
        }


        // create status message
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
     * @param \Pimcore\Model\Object\AbstractObject $object
     * @return void
     */
    public function postAddObject(\Pimcore\Model\Object\AbstractObject $object) {
        if ($object instanceof \OnlineShop\Framework\Model\IIndexable) {
            $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
            $indexService->updateIndex($object);
        }
    }

    /**
     * @param \Pimcore\Model\Object\AbstractObject $object
     * @return void
     */
    public function postUpdateObject(\Pimcore\Model\Object\AbstractObject $object) {
        if ($object instanceof \OnlineShop\Framework\Model\IIndexable) {
            $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
            $indexService->updateIndex($object);
        }
    }

    public function preDeleteObject(\Pimcore\Model\Object\AbstractObject $object) {
        if ($object instanceof \OnlineShop\Framework\Model\IIndexable) {
            $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
            $indexService->deleteFromIndex($object);
        }

        // Delete tokens when a a configuration object gets removed.
        if($object instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries){
            $voucherService = \OnlineShop\Framework\Factory::getInstance()->getVoucherService();
            $voucherService->cleanUpVoucherSeries($object);
        }
    }

    /**
     * @var \Zend_Log
     */
    private static $sqlLogger = null;

    /**
     * @return \Zend_Log
     */
    public static function getSQLLogger() {
        if(!self::$sqlLogger) {


            // check for big logfile, empty it if it's bigger than about 200M
            $logfilename = PIMCORE_WEBSITE_PATH . '/var/log/online-shop-sql.log';
            if (filesize($logfilename) > 200000000) {
                file_put_contents($logfilename, "");
            }

            $prioMapping = array(
                "debug" => \Zend_Log::DEBUG,
                "info" => \Zend_Log::INFO,
                "notice" => \Zend_Log::NOTICE,
                "warning" => \Zend_Log::WARN,
                "error" => \Zend_Log::ERR,
                "critical" => \Zend_Log::CRIT,
                "alert" => \Zend_Log::ALERT,
                "emergency" => \Zend_Log::EMERG
            );

            $prios = array();
            $conf = \Pimcore\Config::getSystemConfig();
            if($conf && $conf->general->debugloglevel) {
                $prioMapping = array_reverse($prioMapping);
                foreach ($prioMapping as $level => $state) {
                    $prios[] = $prioMapping[$level];
                    if($level == $conf->general->debugloglevel) {
                        break;
                    }
                }
            }
            else {
                // log everything if config isn't loaded (eg. at the installer)
                foreach ($prioMapping as $p) {
                    $prios[] = $p;
                }
            }

            $logger = new \Zend_Log();
            $logger->addWriter(new \Zend_Log_Writer_Stream($logfilename));

            foreach($prioMapping as $key => $mapping) {
                if(!array_key_exists($mapping, $prios)) {
                    $logger->addFilter(new \Zend_Log_Filter_Priority($mapping, "!="));
                }
            }

            self::$sqlLogger = $logger;
        }
        return self::$sqlLogger;
    }


    /**
     * installs all field collections
     */
    private static function createFieldCollections() {
        // Add FieldCollections
        $sourceFiles = scandir(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/fieldcollection_sources');
        foreach ($sourceFiles as $filename) {
            if (!is_dir($filename)) {

                preg_match('/_(.*)_/', $filename, $matches);
                $key = $matches[1];

                try {
                    $fieldCollection = \Pimcore\Model\Object\Fieldcollection\Definition::getByKey($key);
                } catch(\Exception $e) {
                    $fieldCollection = new \Pimcore\Model\Object\Fieldcollection\Definition();
                    $fieldCollection->setKey($key);
                }

                $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/fieldcollection_sources/' . $filename);
                $success = \Pimcore\Model\Object\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, $data);
                if(!$success){
                    \Logger::err("Could not import $key FieldCollection.");
                }
            }
        }
    }

    private static function createClasses() {
        // Add classes
        self::createClass("FilterDefinition", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_FilterDefinition_export.json');
        self::createClass("OnlineShopOrderItem", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_OnlineShopOrderItem_export.json');
        self::createClass("OnlineShopVoucherSeries", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_OnlineShopVoucherSeries_export.json');
        self::createClass("OnlineShopVoucherToken", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_OnlineShopVoucherToken_export.json');
        self::createClass("OnlineShopOrder", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_OnlineShopOrder_export.json');
        self::createClass("OfferToolOfferItem", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_OfferToolOfferItem_export.json');
        self::createClass("OfferToolOffer", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_OfferToolOffer_export.json');
        self::createClass("OfferToolCustomProduct", PIMCORE_PLUGINS_PATH . '/OnlineShop/install/class_source/class_OfferToolCustomProduct_export.json');
    }

    /**
     * installs all object bricks
     */
    private static function createObjectBricks()
    {
        $sourceFiles = scandir(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/objectbrick_sources');
        foreach ($sourceFiles as $filename) {
            if (!is_dir($filename)) {

                preg_match('/_(.*)_/', $filename, $matches);
                $key = $matches[1];

                try {
                    $brick = \Pimcore\Model\Object\Objectbrick\Definition::getByKey($key);
                } catch(\Exception $e) {
                    $brick = new \Pimcore\Model\Object\Objectbrick\Definition();
                    $brick->setKey($key);
                }

                $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/OnlineShop/install/objectbrick_sources/' . $filename);
                $success = \Pimcore\Model\Object\ClassDefinition\Service::importObjectBrickFromJson($brick, $data);
                if(!$success){
                    \Logger::err("Could not import $key ObjectBrick.");
                }
            }
        }
    }


    /**
     * install pricing rule system
     *
     * @return bool
     */
    private static function installPricingRules()
    {
        // PricingRules
        \Pimcore\Db::get()->query("
            CREATE TABLE IF NOT EXISTS `plugin_onlineshop_pricing_rule` (
            `id` INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) NULL DEFAULT NULL,
            `label` TEXT NULL,
            `description` TEXT NULL,
            `behavior` ENUM('additiv','stopExecute') NULL DEFAULT NULL,
            `active` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
            `prio` TINYINT(3) UNSIGNED NOT NULL,
            `condition` TEXT NOT NULL COMMENT 'configuration der condition',
            `actions` TEXT NOT NULL COMMENT 'configuration der action',
            PRIMARY KEY (`id`),
            UNIQUE INDEX `name` (`name`),
            INDEX `active` (`active`)
        )
        ENGINE=InnoDB
        AUTO_INCREMENT=0;
        ");

        // create permission key
        $key = 'plugin_onlineshop_pricing_rules';
        $permission = new \Pimcore\Model\User\Permission\Definition();
        $permission->setKey( $key );

        $res = new \Pimcore\Model\User\Permission\Definition\Dao();
        $res->configure( \Pimcore\Db::get() );
        $res->setModel( $permission );
        $res->save();

        return true;
    }


    /**
     * remove pricing rule system
     *
     * @return bool
     */
    private static function uninstallPricingRules()
    {
        $db = \Pimcore\Db::get();
        // remove tables
        $db->query("DROP TABLE IF EXISTS `plugin_onlineshop_pricing_rule`");

        // remove permissions
        $key = 'plugin_onlineshop_pricing_rules';

        $db->delete('users_permission_definitions', '`key` = ' . $db->quote($key) );

        return true;
    }



    public function maintenance() {
        $checkoutManager = \OnlineShop\Framework\Factory::getInstance()->getCheckoutManager(new \OnlineShop\Framework\CartManager\Cart());
        $checkoutManager->cleanUpPendingOrders();

        \OnlineShop\Framework\Factory::getInstance()->getVoucherService()->cleanUpReservations();
        \OnlineShop\Framework\Factory::getInstance()->getVoucherService()->cleanUpStatistics();
    }
}
