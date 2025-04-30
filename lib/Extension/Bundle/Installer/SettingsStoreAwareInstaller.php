<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Extension\Bundle\Installer;

use Doctrine\DBAL\Exception\TableExistsException;
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
            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $this->migrationRepository->setPrefix($this->bundle->getNamespace());
            $this->tableMetadataStorage->setPrefix($this->bundle->getNamespace());
            $migrations = $this->dependencyFactory->getMigrationRepository()->getMigrations();
            $executedMigrations = $metadataStorage->getExecutedMigrations();

            foreach ($migrations->getItems() as $migration) {
                $version = $migration->getVersion();

                if (!$executedMigrations->hasMigration($version)) {
                    $migrationResult = new ExecutionResult($version, Direction::UP);

                    try {
                        $metadataStorage->ensureInitialized();
                    } catch (TableExistsException $exception) {
                    }
                    $metadataStorage->complete($migrationResult);
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
            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $this->tableMetadataStorage->setPrefix($this->bundle->getNamespace());
            $executedMigrations = $metadataStorage->getExecutedMigrations();

            foreach ($executedMigrations->getItems() as $migration) {
                $migrationResult = new ExecutionResult($migration->getVersion(), Direction::DOWN);
                $metadataStorage->ensureInitialized();
                $metadataStorage->complete($migrationResult);
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
