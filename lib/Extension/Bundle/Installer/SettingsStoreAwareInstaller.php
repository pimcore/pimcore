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

namespace Pimcore\Extension\Bundle\Installer;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Pimcore\Migrations\FilteredMigrationsRepository;
use Pimcore\Migrations\FilteredTableMetadataStorage;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class SettingsStoreAwareInstaller extends AbstractInstaller
{
    protected BundleInterface $bundle;

    protected FilteredMigrationsRepository $migrationRepository;

    protected FilteredTableMetadataStorage $tableMetadataStorage;

    protected DependencyFactory $dependencyFactory;

    public function __construct(BundleInterface $bundle)
    {
        parent::__construct();
        $this->bundle = $bundle;
    }

    #[Required]
    public function setMigrationRepository(FilteredMigrationsRepository $migrationRepository): void
    {
        $this->migrationRepository = $migrationRepository;
    }

    #[Required]
    public function setTableMetadataStorage(FilteredTableMetadataStorage $tableMetadataStorage): void
    {
        $this->tableMetadataStorage = $tableMetadataStorage;
    }

    #[Required]
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

    protected function markInstalled(): void
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

        SettingsStore::set($this->getSettingsStoreInstallationId(), true, SettingsStore::TYPE_BOOLEAN, 'pimcore');
    }

    protected function markUninstalled(): void
    {
        SettingsStore::set($this->getSettingsStoreInstallationId(), false, SettingsStore::TYPE_BOOLEAN, 'pimcore');

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

    public function install(): void
    {
        parent::install();
        $this->markInstalled();
    }

    public function uninstall(): void
    {
        parent::uninstall();
        $this->markUninstalled();
    }

    public function isInstalled(): bool
    {
        $installSetting = SettingsStore::get($this->getSettingsStoreInstallationId(), 'pimcore');

        return (bool) ($installSetting ? $installSetting->getData() : false);
    }

    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    public function canBeUninstalled(): bool
    {
        return $this->isInstalled();
    }
}
