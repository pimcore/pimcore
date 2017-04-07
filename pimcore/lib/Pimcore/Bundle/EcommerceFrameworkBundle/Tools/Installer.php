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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tools;

use Pimcore\Config;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Logger;

class Installer extends AbstractInstaller
{

    /**
     * @var array - contains all tables that need to be created
     */
    private static $tables = [
        "ecommerceframework_cart" =>
            "CREATE TABLE `ecommerceframework_cart` (
              `id` int(20) NOT NULL AUTO_INCREMENT,
              `userid` int(20) NOT NULL,
              `name` varchar(250) COLLATE utf8_bin DEFAULT NULL,
              `creationDateTimestamp` int(10) NOT NULL,
              `modificationDateTimestamp` int(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
        "ecommerceframework_cartcheckoutdata" =>
            "CREATE TABLE `ecommerceframework_cartcheckoutdata` (
              `cartId` int(20) NOT NULL,
              `key` varchar(150) COLLATE utf8_bin NOT NULL,
              `data` longtext,
              PRIMARY KEY (`cartId`,`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
        "ecommerceframework_cartitem" =>
            "CREATE TABLE `ecommerceframework_cartitem` (
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
        "ecommerceframework_vouchertoolkit_statistics" =>
            "CREATE TABLE `ecommerceframework_vouchertoolkit_statistics` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `voucherSeriesId` BIGINT(20) NOT NULL,
                `date` DATE NOT NULL,
                PRIMARY KEY (`id`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB ;",
        "ecommerceframework_vouchertoolkit_tokens" =>
            "CREATE TABLE `ecommerceframework_vouchertoolkit_tokens` (
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
        "ecommerceframework_vouchertoolkit_reservations" =>
            "CREATE TABLE `ecommerceframework_vouchertoolkit_reservations` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `token` VARCHAR(250) NOT NULL,
                `cart_id` VARCHAR(250) NOT NULL,
                `timestamp` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `token` (`token`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB;",
        "ecommerceframework_pricing_rule" =>
            "CREATE TABLE IF NOT EXISTS `ecommerceframework_pricing_rule` (
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
        "FilterDefinition" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_FilterDefinition_export.json',
        "OnlineShopOrderItem" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OnlineShopOrderItem_export.json',
        "OnlineShopVoucherSeries" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OnlineShopVoucherSeries_export.json',
        "OnlineShopVoucherToken" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OnlineShopVoucherToken_export.json',
        "OnlineShopOrder" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OnlineShopOrder_export.json',
        "OfferToolCustomProduct" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OfferToolCustomProduct_export.json',
        "OfferToolOfferItem" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OfferToolOfferItem_export.json',
        "OfferToolOffer" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OfferToolOffer_export.json',
        "OnlineShopTaxClass" => PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/class_source/class_OnlineShopTaxClass_export.json'
    ];

    /**
     * @return array - contains all fieldcollections that need to be created
     */
    private static function getFieldCollections()
    {
        $fieldCollections = [];

        $sourceFiles = scandir(PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/fieldcollection_sources');
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
    private static function getObjectBricks()
    {
        $objectBricks = [];

        $sourceFiles = scandir(PIMCORE_PATH . '/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/objectbrick_sources');
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
    public function install()
    {
        $this->checkCanBeInstalled();

        $this->copyConfigFile();

        $this->createFieldCollections();
        $this->createClasses();
        $this->createObjectBricks();

        $this->createTables();

        $this->importTranslations();

        $this->addPermissions();

        return true;
    }

    /**
     * @return bool
     */
    public function canBeInstalled()
    {
        try {
            $this->checkCanBeInstalled();

            return true;
        } catch (\Exception $e) {
            Logger::warn("Ecommerce Framework cannot be installed because: " . $e);
        }

        return false;
    }


    /**
     * checks, if install is possible. otherwise throws exception
     *
     * @throws \Exception
     */
    protected function checkCanBeInstalled()
    {

        //check tables
        $db = \Pimcore\Db::get();
        $existingTables = [];
        foreach (self::$tables as $name => $statement) {
            try {
                $result = $db->query("DESCRIBE TABLE " . $db->quoteIdentifier($name))->fetchAll;
            } catch (\Exception $e) {
                //nothing to do
            }
            if (!empty($result)) {
                $existingTables[] = $name;
            }
        }
        if (!empty($existingTables)) {
            throw new \Exception("Table(s) " . implode(", ", $existingTables) . " already exist. Please remove them first.");
        }

        //check classes
        $existingClasses = [];
        foreach (self::$classes as $name => $file) {
            $class = \Pimcore\Model\Object\ClassDefinition::getByName($name);
            if (!empty($class)) {
                $existingClasses[] = $name;
            }
        }
        if (!empty($existingClasses)) {
            throw new \Exception("Class(es) " . implode(", ", $existingClasses) . " already exist. Please remove them first.");
        }

        //check fieldcollections
        $existingFieldCollections = [];
        foreach (self::getFieldCollections() as $key => $file) {
            try {
                $fieldCollection = \Pimcore\Model\Object\Fieldcollection\Definition::getByKey($key);
            } catch (\Exception $e) {
                //nothing to do
            }

            if (!empty($fieldCollection)) {
                $existingFieldCollections[] = $key;
            }
        }
        if (!empty($existingFieldCollections)) {
            throw new \Exception("Fieldcollection(s) " . implode(", ", $existingFieldCollections) . " already exist. Please remove them first.");
        }

        //check object bricks
        $existingObjectBricks = [];
        foreach (self::getObjectBricks() as $key => $file) {
            try {
                $brick = \Pimcore\Model\Object\Objectbrick\Definition::getByKey($key);
            } catch (\Exception $e) {
                //nothing to do
            }

            if (!empty($brick)) {
                $existingObjectBricks[] = $key;
            }
        }
        if (!empty($existingObjectBricks)) {
            throw new \Exception("Fieldcollection(s) " . implode(", ", $existingObjectBricks) . " already exist. Please remove them first.");
        }
    }


    /**
     * installs all field collections
     */
    private function createFieldCollections()
    {
        $fieldCollections = self::getFieldCollections();
        foreach ($fieldCollections as $key => $filename) {
            try {
                $fieldCollection = \Pimcore\Model\Object\Fieldcollection\Definition::getByKey($key);
                if ($fieldCollection) {
                    throw new \Exception("Fieldcollection $key already exists");
                }
            } catch (\Exception $e) {
                $fieldCollection = new \Pimcore\Model\Object\Fieldcollection\Definition();
                $fieldCollection->setKey($key);
            }

            $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/fieldcollection_sources/' . $filename);
            $success = \Pimcore\Model\Object\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, $data);
            if (!$success) {
                Logger::err("Could not import $key FieldCollection.");
            }
        }
    }

    /**
     * creates classes
     * @throws \Exception
     */
    private function createClasses()
    {
        foreach (self::$classes as $classname => $filepath) {
            $class = \Pimcore\Model\Object\ClassDefinition::getByName($classname);
            if (!$class) {
                $class = new \Pimcore\Model\Object\ClassDefinition();
                $class->setName($classname);
            } else {
                throw new \Exception("Class $classname already exists, ");
            }
            $json = file_get_contents($filepath);

            $success = \Pimcore\Model\Object\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
            if (!$success) {
                Logger::err("Could not import $classname Class.");
            }
        }
    }

    /**
     * installs all object bricks
     */
    private function createObjectBricks()
    {
        $bricks = self::getObjectBricks();
        foreach ($bricks as $key => $filename) {
            preg_match('/_(.*)_/', $filename, $matches);
            $key = $matches[1];

            try {
                $brick = \Pimcore\Model\Object\Objectbrick\Definition::getByKey($key);
                if ($brick) {
                    throw new \Exception("Brick $key already exists");
                }
            } catch (\Exception $e) {
                $brick = new \Pimcore\Model\Object\Objectbrick\Definition();
                $brick->setKey($key);
            }

            $data = file_get_contents(PIMCORE_PLUGINS_PATH . '/EcommerceFramework/install/objectbrick_sources/' . $filename);
            $success = \Pimcore\Model\Object\ClassDefinition\Service::importObjectBrickFromJson($brick, $data);
            if (!$success) {
                Logger::err("Could not import $key ObjectBrick.");
            }
        }
    }

    /**
     * creates tables
     */
    private function createTables()
    {
        $db = \Pimcore\Db::get();
        foreach (self::$tables as $name => $statement) {
            $db->query($statement);
        }
    }

    /**
     * copy sample config file - if not exists.
     */
    private function copyConfigFile()
    {
        //copy config file
        if (!is_file(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/EcommerceFrameworkConfig.php")) {
            copy(__DIR__ . "/../install/EcommerceFrameworkConfig_sample.php", PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/EcommerceFrameworkConfig.php");
        }
    }

    /**
     * imports admin-translations
     * @throws \Exception
     */
    private function importTranslations()
    {
        \Pimcore\Model\Translation\Admin::importTranslationsFromFile(__DIR__ . '/../install/admin-translations/init.csv', true);
    }

    /**
     * adds admin permissions
     */
    private function addPermissions()
    {
        $keys = [
            'bundle_ecommerce_pricing_rules',
            'bundle_ecommerce_back-office_order',
        ];

        foreach ($keys as $key) {
            $permission = new \Pimcore\Model\User\Permission\Definition();
            $permission->setKey($key);

            $res = new \Pimcore\Model\User\Permission\Definition\Dao();
            $res->configure(\Pimcore\Db::get());
            $res->setModel($permission);
            $res->save();
        }
    }


    public function canBeUninstalled()
    {
        return true;
    }

    /**
     * uninstalls e-commerce framework
     */
    public function uninstall()
    {
        $db = \Pimcore\Db::get();
        $db->query("DROP TABLE IF EXISTS `ecommerceframework_cart`");
        $db->query("DROP TABLE IF EXISTS `ecommerceframework_cartcheckoutdata`");
        $db->query("DROP TABLE IF EXISTS `ecommerceframework_cartitem`");
        $db->query("DROP TABLE IF EXISTS `ecommerceframework_vouchertoolkit_reservations`");
        $db->query("DROP TABLE IF EXISTS `ecommerceframework_vouchertoolkit_tokens`");
        $db->query("DROP TABLE IF EXISTS `ecommerceframework_vouchertoolkit_statistics`");
        $db->query("DROP TABLE IF EXISTS `ecommerceframework_pricing_rule`");

        //remove permissions
        $key = 'bundle_ecommerce_pricing_rules';
        $db->deleteWhere('users_permission_definitions', '`key` = ' . $db->quote($key));

        $key = 'bundle_ecommerce_back-office_order';
        $db->deleteWhere('users_permission_definitions', '`key` = ' . $db->quote($key));

        return true;
    }

    /**
     *
     * @return boolean
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }

    /**
     *  indicates wether this plugins is currently installed
     * @return boolean
     */
    public function isInstalled()
    {
        $result = null;
        try {
            if (Config::getSystemConfig()) {
                $result = \Pimcore\Db::get()->query("DESCRIBE ecommerceframework_cartitem")->fetchAll();
            }
        } catch (\Exception $e) {
        }

        return !empty($result);
    }
}
