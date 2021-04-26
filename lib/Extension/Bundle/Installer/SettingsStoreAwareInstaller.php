<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Extension\Bundle\Installer;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Pimcore\Migrations\FilteredMigrationsRepository;
use Pimcore\Migrations\FilteredTableMetadataStorage;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

abstract class SettingsStoreAwareInstaller extends AbstractInstaller
{
    /**
     * @var BundleInterface
     */
    protected $bundle;

    /**
     * @var FilteredMigrationsRepository
     */
    protected $migrationRepository;

    /**
     * @var FilteredTableMetadataStorage
     */
    protected $tableMetadataStorage;

    /**
     * @var DependencyFactory
     */
    protected $dependencyFactory;

    public function __construct(BundleInterface $bundle)
    {
        parent::__construct();
        $this->bundle = $bundle;
    }

    /**
     * @param FilteredMigrationsRepository $migrationRepository
     * @required
     */
    public function setMigrationRepository(FilteredMigrationsRepository $migrationRepository): void
    {
        $this->migrationRepository = $migrationRepository;
    }

    /**
     * @param FilteredTableMetadataStorage $tableMetadataStorage
     * @required
     */
    public function setTableMetadataStorage(FilteredTableMetadataStorage $tableMetadataStorage): void
    {
        $this->tableMetadataStorage = $tableMetadataStorage;
    }

    /**
     * @param DependencyFactory $dependencyFactory
     * @required
     */
    public function setDependencyFactory(DependencyFactory $dependencyFactory): void
    {
        $this->dependencyFactory = $dependencyFactory;
    }

    protected function getSettingsStoreInstallationId(): string
    {
        return 'BUNDLE_INSTALLED__' . $this->bundle->getNamespace() . '\\' . $this->bundle->getName();
    }

    public function getLastMigrationVersionClassName(): ?string
    {
        return null;
    }

    protected function markInstalled()
    {
        $migrationVersion = $this->getLastMigrationVersionClassName();
        if ($migrationVersion) {
            $this->migrationRepository->setPrefix($this->bundle->getNamespace());
            $this->tableMetadataStorage->setPrefix($this->bundle->getNamespace());
            $migrations = $this->dependencyFactory->getMigrationRepository()->getMigrations();
            $executedMigrations = $this->dependencyFactory->getMetadataStorage()->getExecutedMigrations();

            foreach ($migrations->getItems() as $migration) {
                $version = $migration->getVersion();

                if (!$executedMigrations->hasMigration($version)) {
                    $migrationResult = new ExecutionResult($version, Direction::UP);
                    $this->dependencyFactory->getMetadataStorage()->complete($migrationResult);
                }

                if ((string)$version === $migrationVersion) {
                    break;
                }
            }
        }

        SettingsStore::set($this->getSettingsStoreInstallationId(), true, 'bool', 'pimcore');
    }

    protected function markUninstalled()
    {
        SettingsStore::set($this->getSettingsStoreInstallationId(), false, 'bool', 'pimcore');

        $migrationVersion = $this->getLastMigrationVersionClassName();
        if ($migrationVersion) {
            $this->tableMetadataStorage->setPrefix($this->bundle->getNamespace());
            $executedMigrations = $this->dependencyFactory->getMetadataStorage()->getExecutedMigrations();

            foreach ($executedMigrations->getItems() as $migration) {
                $migrationResult = new ExecutionResult($migration->getVersion(), Direction::DOWN);
                $this->dependencyFactory->getMetadataStorage()->complete($migrationResult);
            }
        }
    }

    public function install()
    {
        parent::install();
        $this->markInstalled();
    }

    public function uninstall()
    {
        parent::uninstall();
        $this->markUninstalled();
    }

    public function isInstalled()
    {
        $installSetting = SettingsStore::get($this->getSettingsStoreInstallationId(), 'pimcore');

        return $installSetting ? $installSetting->getData() : false;
    }

    public function canBeInstalled()
    {
        return !$this->isInstalled();
    }

    public function canBeUninstalled()
    {
        return $this->isInstalled();
    }
}
