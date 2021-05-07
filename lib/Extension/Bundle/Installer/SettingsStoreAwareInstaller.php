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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Extension\Bundle\Installer;

use Pimcore\Migrations\MigrationManager;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

abstract class SettingsStoreAwareInstaller extends AbstractInstaller
{
    /**
     * @var BundleInterface
     */
    protected $bundle;

    /**
     * @var MigrationManager
     */
    protected $migrationManager;

    public function __construct(BundleInterface $bundle)
    {
        parent::__construct();
        $this->bundle = $bundle;
    }

    /**
     * @param MigrationManager $migrationManager
     * @required
     */
    public function setMigrationManager(MigrationManager $migrationManager): void
    {
        $this->migrationManager = $migrationManager;
    }

    protected function getSettingsStoreInstallationId(): string
    {
        return 'BUNDLE_INSTALLED__' . $this->bundle->getNamespace() . '\\' . $this->bundle->getName();
    }

    public function getLastMigrationVersionClassName(): ?string
    {
        return null;
    }

    private function getMigrationVersion(): ?string
    {
        $className = $this->getLastMigrationVersionClassName();

        if ($className) {
            preg_match('/\d+$/', $className, $matches);

            return end($matches);
        }

        return null;
    }

    protected function markInstalled()
    {
        $migrationVersion = $this->getMigrationVersion();
        if ($migrationVersion) {
            $version = $this->migrationManager->getBundleVersion(
                $this->bundle,
                $migrationVersion
            );
            $this->migrationManager->markVersionAsMigrated($version, true);
        }

        SettingsStore::set($this->getSettingsStoreInstallationId(), true, 'bool', 'pimcore');
    }

    protected function markUninstalled()
    {
        $configuration = $this->migrationManager->getBundleConfiguration($this->bundle);
        if ($configuration) {
            $configuration->clearMigratedVersions();
        }

        SettingsStore::set($this->getSettingsStoreInstallationId(), false, 'bool', 'pimcore');
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
