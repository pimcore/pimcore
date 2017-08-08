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

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;

        $this->buildDefaultMigrationSets();
    }

    public function createForSet(
        string $set,
        Connection $connection,
        OutputWriter $outputWriter = null
    )
    {
        if (!isset($this->migrationSets[$set])) {
            throw new \InvalidArgumentException(sprintf('Migration set "%s" is not registered', $set));
        }

        $migrationSet = $this->migrationSets[$set];

        return $this->createConfiguration($migrationSet, $connection, $outputWriter);
    }

    public function createForBundle(
        BundleInterface $bundle,
        Connection $connection,
        OutputWriter $outputWriter = null
    ): Configuration
    {
        $migrationSet = $this->getMigrationSetForBundle($bundle);

        return $this->createConfiguration($migrationSet, $connection, $outputWriter);
    }

    protected function createConfiguration(
        MigrationSetConfiguration $migrationSet,
        Connection $connection,
        OutputWriter $outputWriter = null
    ): Configuration
    {
        $configuration = new Configuration(
            $migrationSet->getIdentifier(),
            $connection,
            $outputWriter
        );

        $configuration->setName($migrationSet->getName());
        $configuration->setMigrationsNamespace($migrationSet->getNamespace());
        $configuration->setMigrationsDirectory($migrationSet->getDirectory());

        return $configuration;
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
            $bundle->getPath() . '/Resources/migrations'
        );
    }

    private function registerMigrationSet(MigrationSetConfiguration $migrationSet)
    {
        if (isset($this->migrationSets[$migrationSet->getIdentifier()])) {
            throw new \RuntimeException(sprintf('Migration set "%s" is already registered', $migrationSet->getIdentifier()));
        }

        $this->migrationSets[$migrationSet->getIdentifier()] = $migrationSet;
    }
}
