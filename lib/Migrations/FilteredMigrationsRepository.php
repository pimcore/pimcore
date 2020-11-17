<?php

namespace Pimcore\Migrations;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Version\Version;
use phpDocumentor\Reflection\Types\Self_;

class FilteredMigrationsRepository implements \Doctrine\Migrations\MigrationsRepository
{
    /**
     * @var FilesystemMigrationsRepository
     */
    private $filesystemRepo;

    /**
     * @var null|string
     */
    private $prefix;

    public function __invoke(DependencyFactory $dependencyFactory)
    {
        $filesystemRepo = new FilesystemMigrationsRepository(
            $dependencyFactory->getConfiguration()->getMigrationClasses(),
            $dependencyFactory->getConfiguration()->getMigrationDirectories(),
            $dependencyFactory->getMigrationsFinder(),
            $dependencyFactory->getMigrationFactory()
        );

        $this->setFileSystemRepo($filesystemRepo);
        return $this;
    }

    private function setFileSystemRepo(FilesystemMigrationsRepository $repository) {
        $this->filesystemRepo = $repository;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function hasMigration(string $version) : bool
    {
        return $this->filesystemRepo->hasMigration($version);
    }

    public function getMigration(Version $version) : AvailableMigration
    {
        return $this->filesystemRepo->getMigration($version);
    }

    public function getMigrations() : AvailableMigrationsSet
    {
        $migrations = $this->filesystemRepo->getMigrations();
        if(!$this->prefix) {
            return $migrations;
        }

        $filteredMigrations = [];
        $items = $migrations->getItems();
        foreach($items as $migration) {
            if(strpos(get_class($migration->getMigration()), $this->prefix) === 0) {
                $filteredMigrations[] = $migration;
            }
        }

        return new AvailableMigrationsSet($filteredMigrations);
    }
}
