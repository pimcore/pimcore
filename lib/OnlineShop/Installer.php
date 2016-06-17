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
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop;

class Installer {

    /**
     * @var array - contains all tables that need to be created
     */
    private static $tables = [
        "plugin_onlineshop_cart" =>
            "CREATE TABLE `plugin_onlineshop_cart` (
              `id` int(20) NOT NULL AUTO_INCREMENT,
              `userid` int(20) NOT NULL,
              `name` varchar(250) COLLATE utf8_bin DEFAULT NULL,
              `creationDateTimestamp` int(10) NOT NULL,
              `modificationDateTimestamp` int(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
        "plugin_onlineshop_cartcheckoutdata" =>
            "CREATE TABLE `plugin_onlineshop_cartcheckoutdata` (
              `cartId` int(20) NOT NULL,
              `key` varchar(150) COLLATE utf8_bin NOT NULL,
              `data` longtext,
              PRIMARY KEY (`cartId`,`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
        "plugin_onlineshop_cartitem" =>
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
        "plugins_onlineshop_vouchertoolkit_statistics" =>
            "CREATE TABLE `plugins_onlineshop_vouchertoolkit_statistics` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `voucherSeriesId` BIGINT(20) NOT NULL,
                `date` DATE NOT NULL,
                PRIMARY KEY (`id`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB ;",
        "plugins_onlineshop_vouchertoolkit_tokens" =>
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
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB;",
        "plugins_onlineshop_vouchertoolkit_reservations" =>
            "CREATE TABLE `plugins_onlineshop_vouchertoolkit_reservations` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `token` VARCHAR(250) NOT NULL,
                `cart_id` VARCHAR(250) NOT NULL,
                `timestamp` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `token` (`token`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB;",
        "plugin_onlineshop_pricing_rule" =>
            "CREATE TABLE IF NOT EXISTS `plugin_onlineshop_pricing_rule` (
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
            ) ENGINE=InnoDB AUTO_INCREMENT=0; "

    ];

    /**
     * @var array - contains all classes that need to be created
     */
    private static $classes = [
        "FilterDefinition" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_FilterDefinition_export.json',
        "OnlineShopOrderItem" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_OnlineShopOrderItem_export.json',
        "OnlineShopVoucherSeries" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_OnlineShopVoucherSeries_export.json',
        "OnlineShopVoucherToken" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_OnlineShopVoucherToken_export.json',
        "OnlineShopOrder" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_OnlineShopOrder_export.json',
        "OfferToolCustomProduct" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_OfferToolCustomProduct_export.json',
        "OfferToolOfferItem" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_OfferToolOfferItem_export.json',
        "OfferToolOffer" => PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/class_source/class_OfferToolOffer_export.json'
    ];

    /**
     * @return array - contains all fieldcollections that need to be created
     */
    private static function getFieldCollections() {
        $fieldCollections = [];

        $sourceFiles = scandir(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/fieldcollection_sources');
        foreach ($sourceFiles as $filename) {
            if (!is_dir($filename)) {
                preg_match('/_(.*)_/', $filename, $matches);
                $key = $matches[1];
                $fieldCollections[$key] = $filename;
            }
        }
        return $fieldCollections;
    }

    /**
     * @return array - contains all objectbricks that need to be created
     */
    private static function getObjectBricks() {
        $objectBricks = [];

        $sourceFiles = scandir(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/objectbrick_sources');
        foreach ($sourceFiles as $filename) {
            if (!is_dir($filename)) {
                preg_match('/_(.*)_/', $filename, $matches);
                $key = $matches[1];
                $fieldCollections[$key] = $filename;
            }
        }
        return $objectBricks;
    }

    /**
     * installs e-commerce framework
     *
     * @throws \Exception
     */
    public static function install() {

        self::checkInstallPossible();

        self::createFieldCollections();
        self::createClasses();
        self::createObjectBricks();

        self::createTables();

        self::copyConfigFile();
        self::importTranslations();
        
        self::addPermissions();
        
    }

    /**
     * checks, if install is possible. otherwise throws exception
     */
    private static function checkInstallPossible() {

        //check tables
        $db = \Pimcore\Db::get();
        $existingTables = [];
        foreach(self::$tables as $name => $statement) {
            try {
                $result = $db->describeTable($name);
            } catch (\Exception $e) {
                //nothing to do
            }
            if(!empty($result)) {
                $existingTables[] = $name;
            }

        }
        if(!empty($existingTables)) {
            throw new \Exception("Table(s) " . implode(", ", $existingTables) . " already exist. Please remove them first.");
        }

        //check classes
        $existingClasses = [];
        foreach(self::$classes as $name => $file) {
            $class = \Pimcore\Model\Object\ClassDefinition::getByName($name);
            if(!empty($class)) {
                $existingClasses[] = $name;
            }
        }
        if(!empty($existingClasses)) {
            throw new \Exception("Class(es) " . implode(", ", $existingClasses) . " already exist. Please remove them first.");
        }

        //check fieldcollections
        $existingFieldCollections = [];
        foreach(self::getFieldCollections() as $key => $file) {
            try {
                $fieldCollection = \Pimcore\Model\Object\Fieldcollection\Definition::getByKey($key);
            } catch (\Exception $e) {
                //nothing to do
            }

            if(!empty($fieldCollection)) {
                $existingFieldCollections[] = $key;
            }
        }
        if(!empty($existingFieldCollections)) {
            throw new \Exception("Fieldcollection(s) " . implode(", ", $existingFieldCollections) . " already exist. Please remove them first.");
        }

        //check object bricks
        $existingObjectBricks = [];
        foreach(self::getObjectBricks() as $key => $file) {
            try {
                $brick = \Pimcore\Model\Object\Objectbrick\Definition::getByKey($key);
            } catch (\Exception $e) {
                //nothing to do
            }

            if(!empty($brick)) {
                $existingObjectBricks[] = $key;
            }
        }
        if(!empty($existingObjectBricks)) {
            throw new \Exception("Fieldcollection(s) " . implode(", ", $existingObjectBricks) . " already exist. Please remove them first.");
        }

    }


    /**
     * installs all field collections
     */
    private static function createFieldCollections() {

        $fieldCollections = self::getFieldCollections();
        foreach($fieldCollections as $key => $filename) {
            try {
                $fieldCollection = \Pimcore\Model\Object\Fieldcollection\Definition::getByKey($key);
                if($fieldCollection) {
                    throw new \Exception("Fieldcollection $key already exists");
                }
            } catch(\Exception $e) {
                $fieldCollection = new \Pimcore\Model\Object\Fieldcollection\Definition();
                $fieldCollection->setKey($key);
            }

            $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/fieldcollection_sources/' . $filename);
            $success = \Pimcore\Model\Object\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, $data);
            if(!$success){
                \Logger::err("Could not import $key FieldCollection.");
            }
        }
    }

    /**
     * creates classes
     * @throws \Exception
     */
    private static function createClasses() {
        foreach(self::$classes as $classname => $filepath) {
            $class = \Pimcore\Model\Object\ClassDefinition::getByName($classname);
            if(!$class) {
                $class = new \Pimcore\Model\Object\ClassDefinition();
                $class->setName($classname);
            } else {
                throw new \Exception("Class $classname already exists, ");
            }
            $json = file_get_contents($filepath);

            $success = \Pimcore\Model\Object\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
            if(!$success){
                \Logger::err("Could not import $classname Class.");
            }
        }
    }

    /**
     * installs all object bricks
     */
    private static function createObjectBricks()
    {

        $bricks = self::getObjectBricks();
        foreach($bricks as $key => $filename) {
            preg_match('/_(.*)_/', $filename, $matches);
            $key = $matches[1];

            try {
                $brick = \Pimcore\Model\Object\Objectbrick\Definition::getByKey($key);
                if($brick) {
                    throw new \Exception("Brick $key already exists");
                }
            } catch(\Exception $e) {
                $brick = new \Pimcore\Model\Object\Objectbrick\Definition();
                $brick->setKey($key);
            }

            $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/objectbrick_sources/' . $filename);
            $success = \Pimcore\Model\Object\ClassDefinition\Service::importObjectBrickFromJson($brick, $data);
            if(!$success){
                \Logger::err("Could not import $key ObjectBrick.");
            }
        }
    }

    /**
     * creates tables
     */
    private static function createTables() {

        $db = \Pimcore\Db::get();
        foreach(self::$tables as $name => $statement) {
            $db->query($statement);
        }

    }

    /**
     * copy sample config file - if not exists.
     */
    private static function copyConfigFile() {
        //copy config file
        if(!is_file(PIMCORE_WEBSITE_PATH . "/var/plugins/EcommerceFramework/OnlineShopConfig.xml")) {
            mkdir(PIMCORE_WEBSITE_PATH . "/var/plugins/EcommerceFramework", 0777, true);
            copy(PIMCORE_PLUGINS_PATH . "/EcommerceFramework/config/OnlineShopConfig_sample.xml", PIMCORE_WEBSITE_PATH . "/var/plugins/EcommerceFramework/OnlineShopConfig.xml");
            copy(PIMCORE_PLUGINS_PATH . "/EcommerceFramework/config/.htaccess", PIMCORE_WEBSITE_PATH . "/var/plugins/EcommerceFramework/.htaccess");
        }
        Plugin::setConfig("/website/var/plugins/EcommerceFramework/OnlineShopConfig.xml");
        
    }

    /**
     * imports admin-translations
     * @throws \Exception
     */
    private static function importTranslations() {
        \Pimcore\Model\Translation\Admin::importTranslationsFromFile(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/admin-translations/init.csv', true);
    }

    /**
     * adds admin permissions
     */
    private static function addPermissions() {

        $keys = [
            'plugin_onlineshop_pricing_rules',
            'plugin_onlineshop_back-office_order',
        ];

        foreach($keys as $key) {
            $permission = new \Pimcore\Model\User\Permission\Definition();
            $permission->setKey( $key );

            $res = new \Pimcore\Model\User\Permission\Definition\Dao();
            $res->configure( \Pimcore\Db::get() );
            $res->setModel( $permission );
            $res->save();

        }

    }

}