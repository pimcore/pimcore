<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\Translation;
use Pimcore\Model\User\Permission;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @internal
 */
class Installer extends AbstractInstaller
{
    private string $installSourcesPath;

    private array $tablesToInstall = [
        'ecommerceframework_cart' =>
            'CREATE TABLE IF NOT EXISTS `ecommerceframework_cart` (
              `id` int(20) NOT NULL AUTO_INCREMENT,
              `userid` int(20) NOT NULL,
              `name` varchar(250) COLLATE utf8_bin DEFAULT NULL,
              `creationDateTimestamp` int(10) NOT NULL,
              `modificationDateTimestamp` int(10) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `ecommerceframework_cart_userid_index` (`userid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;',
        'ecommerceframework_cartcheckoutdata' =>
            'CREATE TABLE IF NOT EXISTS `ecommerceframework_cartcheckoutdata` (
              `cartId` int(20) NOT NULL,
              `key` varchar(150) COLLATE utf8_bin NOT NULL,
              `data` longtext,
              PRIMARY KEY (`cartId`,`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;',
        'ecommerceframework_cartitem' =>
            "CREATE TABLE IF NOT EXISTS `ecommerceframework_cartitem` (
              `productId` int(20) NOT NULL,
              `cartId` int(20) NOT NULL,
              `count` int(20) NOT NULL,
              `itemKey` varchar(100) COLLATE utf8_bin NOT NULL,
              `parentItemKey` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT '0',
              `comment` LONGTEXT ASCII,
              `addedDateTimestamp` bigint NOT NULL,
              `sortIndex` INT(10) UNSIGNED NULL DEFAULT '0',
              PRIMARY KEY (`itemKey`,`cartId`,`parentItemKey`),
              KEY `cartId_parentItemKey` (`cartId`,`parentItemKey`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
        'ecommerceframework_vouchertoolkit_statistics' =>
            "CREATE TABLE IF NOT EXISTS `ecommerceframework_vouchertoolkit_statistics` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `voucherSeriesId` BIGINT(20) NOT NULL,
                `date` DATE NOT NULL,
                PRIMARY KEY (`id`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB;",
        'ecommerceframework_vouchertoolkit_tokens' =>
            "CREATE TABLE IF NOT EXISTS `ecommerceframework_vouchertoolkit_tokens` (
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
            "CREATE TABLE IF NOT EXISTS `ecommerceframework_vouchertoolkit_reservations` (
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
            ) ENGINE=InnoDB AUTO_INCREMENT=0;",
    ];

    private array $classesToInstall = [
            'FilterDefinition' => 'EF_FD',
            'OfferToolCustomProduct' => 'EF_OTCP',
            'OfferToolOffer' => 'EF_OTO',
            'OfferToolOfferItem' => 'EF_OTOI',
            'OnlineShopOrder' => 'EF_OSO',
            'OnlineShopOrderItem' => 'EF_OSOI',
            'OnlineShopTaxClass' => 'EF_OSTC',
            'OnlineShopVoucherSeries' => 'EF_OSVS',
            'OnlineShopVoucherToken' => 'EF_OSVT',
    ];

    private array $permissionsToInstall = [
        'bundle_ecommerce_pricing_rules',
        'bundle_ecommerce_back-office_order',
    ];

    protected BundleInterface $bundle;

    protected Connection $db;

    protected ?Schema $schema = null;

    public function __construct(
        BundleInterface $bundle,
        Connection $connection
    ) {
        $this->installSourcesPath = __DIR__ . '/../../install';
        $this->bundle = $bundle;
        $this->db = $connection;
        parent::__construct();
    }

    public function install()
    {
        $this->installFieldCollections();
        $this->installClasses();
        $this->installTables();
        $this->installTranslations();
        $this->installPermissions();
    }

    public function uninstall()
    {
        $this->uninstallPermissions();
        $this->uninstallTables();
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool
    {
        $installed = false;

        try {
            // check if if first permission is installed
            $installed = $this->db->fetchOne('SELECT `key` FROM users_permission_definitions WHERE `key` = :key', [
                'key' => $this->permissionsToInstall[0],
            ]);
        } catch (\Exception $e) {
            // nothing to do
        }

        return (bool) $installed;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled(): bool
    {
        return $this->isInstalled();
    }

    private function getClassesToInstall(): array
    {
        $result = [];
        foreach (array_keys($this->classesToInstall) as $className) {
            $filename = sprintf('class_%s_export.json', $className);
            $path = $this->installSourcesPath . '/class_sources/' . $filename;
            $path = realpath($path);

            if (false === $path || !is_file($path)) {
                throw new InstallationException(sprintf(
                    'Class export for class "%s" was expected in "%s" but file does not exist',
                    $className,
                    $path
                ));
            }

            $result[$className] = $path;
        }

        return $result;
    }

    private function installClasses(): void
    {
        $classes = $this->getClassesToInstall();

        $mapping = $this->classesToInstall;

        foreach ($classes as $key => $path) {
            $class = ClassDefinition::getByName($key);

            if ($class) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping class "%s" as it already exists',
                    $key
                ));

                continue;
            }

            $class = new ClassDefinition();

            $classId = $mapping[$key];

            $class->setName($key);
            $class->setId($classId);

            $data = file_get_contents($path);
            $success = Service::importClassDefinitionFromJson($class, $data, false, true);

            if (!$success) {
                throw new InstallationException(sprintf(
                    'Failed to create class "%s"',
                    $key
                ));
            }
        }
    }

    private function installFieldCollections(): void
    {
        $fieldCollections = $this->findInstallFiles(
            $this->installSourcesPath . '/fieldcollection_sources',
            '/^fieldcollection_(.*)_export\.json$/'
        );

        foreach ($fieldCollections as $key => $path) {
            if ($fieldCollection = Fieldcollection\Definition::getByKey($key)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping field collection "%s" as it already exists',
                    $key
                ));

                continue;
            }

            $fieldCollection = new Fieldcollection\Definition();
            $fieldCollection->setKey($key);

            $data = file_get_contents($path);
            $success = Service::importFieldCollectionFromJson($fieldCollection, $data);

            if (!$success) {
                throw new InstallationException(sprintf(
                    'Failed to create field collection "%s"',
                    $key
                ));
            }
        }
    }

    private function installPermissions(): void
    {
        foreach ($this->permissionsToInstall as $permission) {
            $definition = Permission\Definition::getByKey($permission);

            if ($definition) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping permission "%s" as it already exists',
                    $permission
                ));

                continue;
            }

            try {
                Permission\Definition::create($permission);
            } catch (\Throwable $e) {
                throw new InstallationException(sprintf(
                    'Failed to create permission "%s": %s',
                    $permission, $e->getMessage()
                ));
            }
        }
    }

    private function uninstallPermissions(): void
    {
        foreach ($this->permissionsToInstall as $permission) {
            $this->db->executeQuery('DELETE FROM users_permission_definitions WHERE `key` = :key', [
                'key' => $permission,
            ]);
        }
    }

    private function installTables(): void
    {
        foreach ($this->tablesToInstall as $name => $statement) {
            if ($this->getSchema()->hasTable($name)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping table "%s" as it already exists',
                    $name
                ));

                continue;
            }

            $this->db->executeQuery($statement);
        }
    }

    private function uninstallTables(): void
    {
        foreach (array_keys($this->tablesToInstall) as $table) {
            if (!$this->getSchema()->hasTable($table)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Not dropping table "%s" as it doesn\'t exist',
                    $table
                ));

                continue;
            }

            $this->getSchema()->dropTable($table);
        }
    }

    private function installTranslations(): void
    {
        Translation::importTranslationsFromFile($this->installSourcesPath . '/admin-translations/init.csv', Translation::DOMAIN_ADMIN);
    }

    /**
     * Finds objectbrick/fieldcollection sources by path returns a result list
     * indexed by element name.
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
                $key = $matches[1];
                $results[$key] = $file->getRealPath();
            }
        }

        return $results;
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    protected function getSchema(): Schema
    {
        return $this->schema ??= $this->db->getSchemaManager()->createSchema();
    }
}
