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
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\ClassDefinition\Service;
use Pimcore\Model\Object\Fieldcollection;
use Pimcore\Model\Object\Objectbrick;
use Pimcore\Model\Translation\Admin;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class Installer extends AbstractInstaller
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $installSourcesPath;

    /**
     * @var array - contains all tables that need to be created
     */
    private $tables = [
        'ecommerceframework_cart' =>
            'CREATE TABLE `ecommerceframework_cart` (
              `id` int(20) NOT NULL AUTO_INCREMENT,
              `userid` int(20) NOT NULL,
              `name` varchar(250) COLLATE utf8_bin DEFAULT NULL,
              `creationDateTimestamp` int(10) NOT NULL,
              `modificationDateTimestamp` int(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;',
        'ecommerceframework_cartcheckoutdata' =>
            'CREATE TABLE `ecommerceframework_cartcheckoutdata` (
              `cartId` int(20) NOT NULL,
              `key` varchar(150) COLLATE utf8_bin NOT NULL,
              `data` longtext,
              PRIMARY KEY (`cartId`,`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;',
        'ecommerceframework_cartitem' =>
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
        'ecommerceframework_vouchertoolkit_statistics' =>
            "CREATE TABLE `ecommerceframework_vouchertoolkit_statistics` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `voucherSeriesId` BIGINT(20) NOT NULL,
                `date` DATE NOT NULL,
                PRIMARY KEY (`id`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB ;",
        'ecommerceframework_vouchertoolkit_tokens' =>
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
        'ecommerceframework_vouchertoolkit_reservations' =>
            "CREATE TABLE `ecommerceframework_vouchertoolkit_reservations` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `token` VARCHAR(250) NOT NULL,
                `cart_id` VARCHAR(250) NOT NULL,
                `timestamp` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `token` (`token`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB;",
        'ecommerceframework_pricing_rule' =>
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
    private $classes = [
        'FilterDefinition',
        'OnlineShopOrderItem',
        'OnlineShopVoucherSeries',
        'OnlineShopVoucherToken',
        'OnlineShopOrder',
        'OfferToolCustomProduct',
        'OfferToolOfferItem',
        'OfferToolOffer',
        'OnlineShopTaxClass',
    ];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger             = $logger;
        $this->installSourcesPath = __DIR__ . '/../Resources/install';
    }

    /**
     * installs e-commerce framework
     *
     * @throws \Exception
     */
    public function install()
    {
        $this->checkCanBeInstalled();

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
            $this->logger->error('Ecommerce Framework cannot be installed because: {exception}', [
                'exception' => $e
            ]);
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
        foreach ($this->tables as $name => $statement) {
            try {
                $result = $db->fetchAll('SHOW TABLES LIKE ?', [$name]);
            } catch (\Exception $e) {
                //nothing to do
            }
            if (!empty($result)) {
                $existingTables[] = $name;
            }
        }
        if (!empty($existingTables)) {
            throw new \Exception('Table(s) ' . implode(', ', $existingTables) . ' already exist. Please remove them first.');
        }

        //check classes
        $existingClasses = [];
        foreach ($this->getClasses() as $name => $file) {
            $class = ClassDefinition::getByName($name);
            if (!empty($class)) {
                $existingClasses[] = $name;
            }
        }
        if (!empty($existingClasses)) {
            throw new \Exception('Class(es) ' . implode(', ', $existingClasses) . ' already exist. Please remove them first.');
        }

        //check fieldcollections
        $existingFieldCollections = [];
        foreach ($this->getFieldCollections() as $key => $file) {
            try {
                $fieldCollection = Fieldcollection\Definition::getByKey($key);
            } catch (\Exception $e) {
                //nothing to do
            }

            if (!empty($fieldCollection)) {
                $existingFieldCollections[] = $key;
            }
        }
        if (!empty($existingFieldCollections)) {
            throw new \Exception('Fieldcollection(s) ' . implode(', ', $existingFieldCollections) . ' already exist. Please remove them first.');
        }

        //check object bricks
        $existingObjectBricks = [];
        foreach ($this->getObjectBricks() as $key => $file) {
            try {
                $brick = Objectbrick\Definition::getByKey($key);
            } catch (\Exception $e) {
                //nothing to do
            }

            if (!empty($brick)) {
                $existingObjectBricks[] = $key;
            }
        }
        if (!empty($existingObjectBricks)) {
            throw new \Exception('Fieldcollection(s) ' . implode(', ', $existingObjectBricks) . ' already exist. Please remove them first.');
        }
    }

    /**
     * Returns a list of all class exports indexed by class name
     *
     * @return array
     */
    private function getClasses(): array
    {
        $result = [];
        foreach ($this->classes as $className) {
            $filename = sprintf('class_%s_export.json', $className);
            $path     = $this->installSourcesPath . '/class_sources/' . $filename;
            $path     = realpath($path);

            if (false === $path || !is_file($path)) {
                throw new \RuntimeException(sprintf(
                    'Class export for class "%s" was expected in "%s" but file does not exist',
                    $className,
                    $path
                ));
            }

            $result[$className] = $path;
        }

        return $result;
    }

    /**
     * @return array - contains all fieldcollections that need to be created
     */
    private function getFieldCollections(): array
    {
        return $this->findInstallFiles(
            $this->installSourcesPath . '/fieldcollection_sources',
            '/^fieldcollection_(.*)_export\.json$/'
        );
    }

    /**
     * @return array - contains all objectbricks that need to be created
     */
    private function getObjectBricks(): array
    {
        return $this->findInstallFiles(
            $this->installSourcesPath . '/objectbrick_sources',
            '/^objectbrick_(.*)_export\.json$/'
        );
    }

    /**
     * Finds objectbrick/fieldcollection sources by path returns a result list
     * indexed by element name.
     *
     * @param string $directory
     * @param string $pattern
     *
     * @return array
     */
    private function findInstallFiles(string $directory, string $pattern): array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name($pattern);

        $results = [];
        foreach ($finder as $file) {
            if (preg_match($pattern, $file->getFilename(), $matches)) {
                $key           = $matches[1];
                $results[$key] = $file->getRealPath();
            }
        }

        return $results;
    }

    /**
     * installs all field collections
     */
    private function createFieldCollections()
    {
        $fieldCollections = $this->getFieldCollections();
        foreach ($fieldCollections as $key => $path) {
            try {
                $fieldCollection = Fieldcollection\Definition::getByKey($key);
                if ($fieldCollection) {
                    throw new \Exception("Fieldcollection $key already exists");
                }
            } catch (\Exception $e) {
                $fieldCollection = new Fieldcollection\Definition();
                $fieldCollection->setKey($key);
            }

            $data    = file_get_contents($path);
            $success = Service::importFieldCollectionFromJson($fieldCollection, $data);

            if (!$success) {
                $this->logger->error('Could not import FieldCollection "{name}".', [
                    'name' => $key
                ]);
            }
        }
    }

    /**
     * creates classes
     *
     * @throws \Exception
     */
    private function createClasses()
    {
        foreach ($this->getClasses() as $classname => $path) {
            $class = ClassDefinition::getByName($classname);
            if (!$class) {
                $class = new ClassDefinition();
                $class->setName($classname);
            } else {
                throw new \Exception("Class $classname already exists, ");
            }

            $data    = file_get_contents($path);
            $success = Service::importClassDefinitionFromJson($class, $data);

            if (!$success) {
                $this->logger->error('Could not import Class "{name}".', [
                    'name' => $classname
                ]);
            }
        }
    }

    /**
     * installs all object bricks
     */
    private function createObjectBricks()
    {
        $bricks = $this->getObjectBricks();
        foreach ($bricks as $key => $path) {
            try {
                $brick = Objectbrick\Definition::getByKey($key);
                if ($brick) {
                    throw new \Exception("Brick $key already exists");
                }
            } catch (\Exception $e) {
                $brick = new Objectbrick\Definition();
                $brick->setKey($key);
            }

            $data    = file_get_contents($path);
            $success = Service::importObjectBrickFromJson($brick, $data);

            if (!$success) {
                $this->logger->error('Could not import ObjectBrick "{name}".', [
                    'name' => $key
                ]);
            }
        }
    }

    /**
     * creates tables
     */
    private function createTables()
    {
        $db = \Pimcore\Db::get();
        foreach ($this->tables as $name => $statement) {
            $db->query($statement);
        }
    }

    /**
     * imports admin-translations
     *
     * @throws \Exception
     */
    private function importTranslations()
    {
        Admin::importTranslationsFromFile($this->installSourcesPath . '/admin-translations/init.csv', true);
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
        $db->query('DROP TABLE IF EXISTS `ecommerceframework_cart`');
        $db->query('DROP TABLE IF EXISTS `ecommerceframework_cartcheckoutdata`');
        $db->query('DROP TABLE IF EXISTS `ecommerceframework_cartitem`');
        $db->query('DROP TABLE IF EXISTS `ecommerceframework_vouchertoolkit_reservations`');
        $db->query('DROP TABLE IF EXISTS `ecommerceframework_vouchertoolkit_tokens`');
        $db->query('DROP TABLE IF EXISTS `ecommerceframework_vouchertoolkit_statistics`');
        $db->query('DROP TABLE IF EXISTS `ecommerceframework_pricing_rule`');

        //remove permissions
        $key = 'bundle_ecommerce_pricing_rules';
        $db->deleteWhere('users_permission_definitions', '`key` = ' . $db->quote($key));

        $key = 'bundle_ecommerce_back-office_order';
        $db->deleteWhere('users_permission_definitions', '`key` = ' . $db->quote($key));

        return true;
    }

    /**
     *
     * @return bool
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }

    /**
     *  indicates if this bundle is currently installed
     *
     * @return bool
     */
    public function isInstalled()
    {
        $result = null;
        try {
            if (Config::getSystemConfig()) {
                $result = \Pimcore\Db::get()->fetchAll('SHOW TABLES LIKE "ecommerceframework_cartitem"');
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return !empty($result);
    }
}
