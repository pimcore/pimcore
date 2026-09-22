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

namespace Pimcore\Bundle\InstallBundle\Profile\DataSource;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Imports SQL dump files from a directory.
 *
 * Supports both plain `.sql` and gzip-compressed `.sql.gz` files.
 * Files are processed in alphabetical order (sorted by filename).
 * Uses a marker table to track whether the data source has already been applied,
 * preventing duplicate imports on manual re-runs.
 */
final readonly class SqlDumpDataSource implements DataSourceInterface
{
    public function __construct(
        private string $dumpDirectory,
        private string $markerTable = '_pimcore_installer_data_source_applied',
    ) {
    }

    public function getLabel(): string
    {
        return sprintf('SQL dumps from %s', basename($this->dumpDirectory));
    }

    public function apply(Connection $connection, OutputInterface $output): void
    {
        if (!is_dir($this->dumpDirectory)) {
            throw new RuntimeException(sprintf(
                'Dump directory not found: %s',
                $this->dumpDirectory,
            ));
        }

        $finder = new Finder();
        $finder->files()
            ->in($this->dumpDirectory)
            ->name(['*.sql', '*.sql.gz'])
            ->sortByName();

        $isMysql = $connection->getDatabasePlatform() instanceof AbstractMySQLPlatform;

        if ($isMysql) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($finder as $file) {
            $output->writeln(sprintf('  Importing %s...', $file->getFilename()));
            $connection->executeStatement($this->readFileContents($file));
        }

        if ($isMysql) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Mark as applied
        $connection->executeStatement(sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` (applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)',
            $this->markerTable,
        ));
        $connection->executeStatement(sprintf(
            'INSERT INTO `%s` (applied_at) VALUES (CURRENT_TIMESTAMP)',
            $this->markerTable,
        ));
    }

    private function readFileContents(SplFileInfo $file): string
    {
        if (str_ends_with($file->getFilename(), '.gz')) {
            return file_get_contents('compress.zlib://' . $file->getPathname());
        }

        return $file->getContents();
    }

    public function isApplied(Connection $connection): bool
    {
        try {
            $result = $connection->executeQuery(sprintf(
                'SELECT COUNT(*) FROM `%s`',
                $this->markerTable,
            ));

            return (int) $result->fetchOne() > 0;
        } catch (Exception) {
            return false;
        }
    }
}
