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

namespace Pimcore\Migrations;

use Doctrine\DBAL\Migrations\Version as DoctrineVersion;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Extension\Bundle\Installer\MigrationInstallerInterface;
use Pimcore\Migrations\Configuration\Configuration;
use Pimcore\Migrations\Configuration\ConfigurationFactory;
use Pimcore\Migrations\Configuration\InstallConfiguration;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class MigrationManager
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var ConfigurationFactory
     */
    private $configurationFactory;

    public function __construct(
        ConnectionInterface $connection,
        ConfigurationFactory $configurationFactory
    ) {
        $this->connection = $connection;
        $this->configurationFactory = $configurationFactory;
    }

    /**
     * Resolves a configuration instance for a configured migration set
     *
     * @param string $migrationSet
     *
     * @return Configuration
     */
    public function getConfiguration(string $migrationSet): Configuration
    {
        return $this->configurationFactory->getForSet($migrationSet, $this->connection);
    }

    /**
     * Loads a specific version for a migration set
     *
     * @param string $migrationSet
     * @param string $versionId
     *
     * @return DoctrineVersion
     */
    public function getVersion(string $migrationSet, string $versionId): DoctrineVersion
    {
        return $this->getConfiguration($migrationSet)->getVersion($versionId);
    }

    /**
     * Resolves a configuration instance for a migration aware bundle
     *
     * @param BundleInterface $bundle
     *
     * @return Configuration
     */
    public function getBundleConfiguration(BundleInterface $bundle): Configuration
    {
        return $this->configurationFactory->getForBundle($bundle, $this->connection);
    }

    /**
     * Loads a specific version for a bundle migration set
     *
     * @param BundleInterface $bundle
     *
     * @return DoctrineVersion
     */
    public function getBundleVersion(BundleInterface $bundle, string $versionId): DoctrineVersion
    {
        return $this->getBundleConfiguration($bundle)->getVersion($versionId);
    }

    /**
     * Resolves an install configuration handling only a single install version
     *
     * @param Configuration $configuration
     * @param MigrationInstallerInterface $installer
     *
     * @return InstallConfiguration
     */
    public function getInstallConfiguration(Configuration $configuration, MigrationInstallerInterface $installer): InstallConfiguration
    {
        return $this->configurationFactory->getInstallConfiguration($configuration, $installer);
    }

    /**
     * Executes a migration
     *
     * @param DoctrineVersion $version
     * @param bool $up
     * @param bool $dryRun
     *
     * @return array
     */
    public function executeVersion(DoctrineVersion $version, bool $up = true, bool $dryRun = false): array
    {
        $direction = $up ? DoctrineVersion::DIRECTION_UP : DoctrineVersion::DIRECTION_DOWN;

        return $version->execute($direction, $dryRun);
    }

    /**
     * Marks version as migrated
     *
     * @param DoctrineVersion $version
     * @param bool $includePrevious
     */
    public function markVersionAsMigrated(DoctrineVersion $version, bool $includePrevious = true)
    {
        $configuration = $version->getConfiguration();

        if ($includePrevious) {
            $migrations = $configuration->getMigrations();
            foreach ($migrations as $migration) {
                if ($migration->getVersion() < $version->getVersion()) {
                    if (!$configuration->hasVersionMigrated($migration)) {
                        $configuration->getOutputWriter()->write(sprintf(
                            '  <info>--</info> Marking version <comment>%s</comment> as migrated',
                            $migration->getVersion()
                        ));

                        $migration->markMigrated();
                    }
                }
            }
        }

        if (!$configuration->hasVersionMigrated($version)) {
            $configuration->getOutputWriter()->write(sprintf(
                '  <info>--</info> Marking version <comment>%s</comment> as migrated',
                $version->getVersion()
            ));

            $version->markMigrated();
        }
    }

    /**
     * Marks version as not migrated
     *
     * @param DoctrineVersion $version
     */
    public function markVersionAsNotMigrated(DoctrineVersion $version)
    {
        $configuration = $version->getConfiguration();

        if ($configuration->hasVersionMigrated($version)) {
            $configuration->getOutputWriter()->write(sprintf(
                '  <info>--</info> Marking version <comment>%s</comment> as not migrated',
                $version->getVersion()
            ));

            $version->markNotMigrated();
        }
    }
}
