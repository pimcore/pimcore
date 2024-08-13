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

namespace Pimcore\Migrations;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Version\Version;

/**
 * @internal
 */
final class FilteredMigrationsRepository implements \Doctrine\Migrations\MigrationsRepository
{
    private FilesystemMigrationsRepository $filesystemRepo;

    private ?string $prefix = null;

    public function __invoke(DependencyFactory $dependencyFactory): static
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

    private function setFileSystemRepo(FilesystemMigrationsRepository $repository): void
    {
        $this->filesystemRepo = $repository;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function hasMigration(string $version): bool
    {
        return $this->filesystemRepo->hasMigration($version);
    }

    public function getMigration(Version $version): AvailableMigration
    {
        return $this->filesystemRepo->getMigration($version);
    }

    public function getMigrations(): AvailableMigrationsSet
    {
        $migrations = $this->filesystemRepo->getMigrations();
        if (!$this->prefix) {
            return $migrations;
        }

        $filteredMigrations = [];
        foreach ($migrations->getItems() as $migration) {
            if (str_starts_with(get_class($migration->getMigration()), $this->prefix)) {
                $filteredMigrations[] = $migration;
            }
        }

        return new AvailableMigrationsSet($filteredMigrations);
    }
}
