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

namespace Pimcore\Migrations;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Version\ExecutionResult;

/**
 * @internal
 */
final class FilteredTableMetadataStorage implements MetadataStorage
{
    /**
     * @var TableMetadataStorage
     */
    private $storage;

    /**
     * @var null|string
     */
    private $prefix;

    /**
     * @param DependencyFactory $dependencyFactory
     * @return $this
     */
    public function __invoke(DependencyFactory $dependencyFactory)
    {
        $storage = new TableMetadataStorage(
            $dependencyFactory->getConnection(),
            $dependencyFactory->getVersionComparator(),
            $dependencyFactory->getConfiguration()->getMetadataStorageConfiguration(),
            $dependencyFactory->getMigrationRepository()
        );

        $this->setStorage($storage);

        return $this;
    }

    /**
     * @param TableMetadataStorage $storage
     */
    public function setStorage(TableMetadataStorage $storage): void
    {
        $this->storage = $storage;
    }

    /**
     * @param string|null $prefix
     */
    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function ensureInitialized(): void
    {
        $this->storage->ensureInitialized();
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutedMigrations(): ExecutedMigrationsList
    {
        $migrations = $this->storage->getExecutedMigrations();
        if (!$this->prefix) {
            return $migrations;
        }

        $filteredMigrations = [];
        $items = $migrations->getItems();
        foreach ($items as $migration) {
            if (strpos($migration->getVersion(), $this->prefix) === 0) {
                $filteredMigrations[] = $migration;
            }
        }

        return new ExecutedMigrationsList($filteredMigrations);
    }

    /**
     * {@inheritdoc}
     */
    public function complete(ExecutionResult $migration): void
    {
        $this->storage->complete($migration);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->storage->reset();
    }
}
