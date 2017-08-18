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

use Doctrine\DBAL\Migrations\OutputWriter;
use Pimcore\Db\Connection;
use Pimcore\Extension\Bundle\Installer\MigrationInstallerInterface;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ConfigurationFactory
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var MigrationSetConfiguration[]
     */
    private $migrationSets = [];

    /**
     * @var Configuration[]
     */
    private $configurations = [];

    /**
     * @var InstallConfiguration[]
     */
    private $installConfigurations = [];

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;

        $this->buildDefaultMigrationSets();
    }

    public function getForSet(
        string $set,
        Connection $connection,
        OutputWriter $outputWriter = null
    )
    {
        $migrationSet = $this->getMigrationSet($set);

        return $this->getConfiguration($migrationSet, $connection, $outputWriter);
    }

    public function getForBundle(
        BundleInterface $bundle,
        Connection $connection,
        OutputWriter $outputWriter = null
    ): Configuration
    {
        $migrationSet  = $this->getMigrationSetForBundle($bundle);
        $configuration = $this->getConfiguration($migrationSet, $connection, $outputWriter);

        if ($bundle instanceof PimcoreBundleInterface) {
            $installer = $bundle->getInstaller();

            if (null !== $installer && $installer instanceof MigrationInstallerInterface) {
                $configuration->setInstaller($installer);
            }
        }

        return $configuration;
    }

    protected function getConfiguration(
        MigrationSetConfiguration $migrationSet,
        Connection $connection,
        OutputWriter $outputWriter = null
    ): Configuration
    {
        if (isset($this->configurations[$migrationSet->getIdentifier()])) {
            return $this->configurations[$migrationSet->getIdentifier()];
        }

        $configuration = new Configuration(
            $migrationSet->getIdentifier(),
            $connection,
            $outputWriter
        );

        $this->configureConfiguration($configuration, $migrationSet);

        $this->configurations[$migrationSet->getIdentifier()] = $configuration;

        return $configuration;
    }

    /**
     * Creates a dedicated install configuration from an existing configuration
     *
     * @param Configuration $configuration
     * @param MigrationInstallerInterface $installer
     *
     * @return InstallConfiguration
     */
    public function getInstallConfiguration(
        Configuration $configuration,
        MigrationInstallerInterface $installer
    ): InstallConfiguration {
        $migrationSetId = $configuration->getMigrationSet();

        if (isset($this->installConfigurations[$migrationSetId])) {
            return $this->installConfigurations[$migrationSetId];
        }

        $migrationSet = $this->getMigrationSet($migrationSetId);

        // pipe messages to original config output writer
        $outputWriter = new OutputWriter(function($message) use ($configuration) {
            $configuration->getOutputWriter()->write($message);
        });

        $installConfiguration = new InstallConfiguration(
            $installer,
            $configuration->getMigrationSet(),
            $configuration->getConnection(),
            $outputWriter
        );

        $this->configureConfiguration($installConfiguration, $migrationSet);

        $this->installConfigurations[$migrationSetId] = $installConfiguration;

        return $installConfiguration;
    }

    /**
     * Applies migration set configuration to configuration instance
     *
     * @param Configuration $configuration
     * @param MigrationSetConfiguration $migrationSet
     */
    protected function configureConfiguration(Configuration $configuration, MigrationSetConfiguration $migrationSet)
    {
        $configuration->setName($migrationSet->getName());
        $configuration->setMigrationsNamespace($migrationSet->getNamespace());
        $configuration->setMigrationsDirectory($migrationSet->getDirectory());
    }

    protected function buildDefaultMigrationSets()
    {
        $this->registerMigrationSet(
            new MigrationSetConfiguration(
                'app',
                'Migrations',
                'App\\Migrations',
                $this->rootDir . '/Resources/migrations'
            )
        );
    }

    private function getMigrationSetForBundle(BundleInterface $bundle): MigrationSetConfiguration
    {
        if (!isset($this->migrationSets[$bundle->getName()])) {
            $this->registerMigrationSet($this->buildBundleMigrationSet($bundle));
        }

        return $this->migrationSets[$bundle->getName()];
    }

    protected function buildBundleMigrationSet(BundleInterface $bundle)
    {
        return new MigrationSetConfiguration(
            $bundle->getName(),
            $bundle->getName() . ' Migrations',
            $bundle->getNamespace() . '\\Migrations',
            $bundle->getPath() . '/Migrations'
        );
    }

    private function registerMigrationSet(MigrationSetConfiguration $migrationSet)
    {
        if (isset($this->migrationSets[$migrationSet->getIdentifier()])) {
            throw new \RuntimeException(sprintf('Migration set "%s" is already registered', $migrationSet->getIdentifier()));
        }

        $this->migrationSets[$migrationSet->getIdentifier()] = $migrationSet;
    }

    protected function getMigrationSet(string $set): MigrationSetConfiguration
    {
        if (!isset($this->migrationSets[$set])) {
            throw new \InvalidArgumentException(sprintf('Migration set "%s" is not registered', $set));
        }

        return $this->migrationSets[$set];
    }
}
