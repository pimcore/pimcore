<?php

namespace Pimcore\Migrations;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Version\ExecutionResult;

class FilteredTableMetadataStorage implements MetadataStorage
{
    /**
     * @var TableMetadataStorage
     */
    private $storage;

    /**
     * @var null|string
     */
    private $prefix;

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

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function ensureInitialized() : void
    {
        $this->storage->ensureInitialized();;
    }

    public function getExecutedMigrations() : ExecutedMigrationsList
    {
        $migrations = $this->storage->getExecutedMigrations();
        if(!$this->prefix) {
            return $migrations;
        }

        $filteredMigrations = [];
        $items = $migrations->getItems();
        foreach($items as $migration) {
            if(strpos($migration->getVersion(), $this->prefix) === 0) {
                $filteredMigrations[] = $migration;
            }
        }

        return new ExecutedMigrationsList($filteredMigrations);
    }

    public function complete(ExecutionResult $migration) : void
    {
        $this->storage->complete($migration);
    }

    public function reset() : void
    {
        $this->storage->reset();
    }
}
