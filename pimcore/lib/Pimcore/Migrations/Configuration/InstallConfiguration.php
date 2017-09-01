<?php

declare(strict_types=1);

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

namespace Pimcore\Migrations\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\OutputWriter;
use Pimcore\Extension\Bundle\Installer\MigrationInstallerInterface;
use Pimcore\Migrations\InstallVersion;

/**
 * Configuration for bundle install/uninstall operations handling only a single migration which delegates
 * changes to the bundle installer.
 */
class InstallConfiguration extends Configuration
{
    /**
     * @var MigrationInstallerInterface
     */
    private $installer;

    /**
     * @var InstallVersion
     */
    private $installVersion;

    public function __construct(
        MigrationInstallerInterface $installer,
        string $migrationSet,
        Connection $connection,
        OutputWriter $outputWriter = null,
        MigrationFinderInterface $finder = null
    ) {
        $this->installer = $installer;

        parent::__construct($migrationSet, $connection, $outputWriter, $finder);

        $this->registerInstallVersion();
    }

    public function getInstaller(): MigrationInstallerInterface
    {
        return $this->installer;
    }

    public function getInstallVersion(): InstallVersion
    {
        if (null === $this->installVersion) {
            $this->installVersion = new InstallVersion($this->installer, $this);
        }

        return $this->installVersion;
    }

    public function hasInstallVersionMigrated(): bool
    {
        return $this->hasVersionMigrated($this->getInstallVersion());
    }

    protected function registerInstallVersion()
    {
        $installVersion = $this->getInstallVersion();

        $migrations = [];
        $migrations[$installVersion->getVersion()] = $installVersion;

        $this->setMigrations($migrations);
    }
}
