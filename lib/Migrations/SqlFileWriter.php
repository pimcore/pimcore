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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Pimcore\Migrations\Configuration\Configuration;

class SqlFileWriter
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var string
     */
    private $destPath;

    /**
     * @var null|OutputWriter
     */
    private $outputWriter;

    /**
     * @param Configuration $configuration
     * @param string $destPath
     * @param OutputWriter|null $outputWriter
     */
    public function __construct(Configuration $configuration, $destPath, OutputWriter $outputWriter = null)
    {
        if (empty($destPath)) {
            $this->throwInvalidArgumentException('Destination file must be specified.');
        }

        $this->configuration = $configuration;
        $this->destPath = $destPath;
        $this->outputWriter = $outputWriter;
    }

    /**
     * @param array $queriesByVersion array Keys are versions and values are arrays of SQL queries (they must be castable to string)
     * @param string $direction
     *
     * @return int|bool
     */
    public function write(array $queriesByVersion, $direction)
    {
        $path = $this->buildMigrationFilePath();
        $string = $this->buildMigrationFile($queriesByVersion, $direction);

        if ($this->outputWriter) {
            $this->outputWriter->write("\n" . sprintf('Writing migration file to "<info>%s</info>"', $path));
        }

        return file_put_contents($path, $string);
    }

    private function buildMigrationFile(array $queriesByVersion, $direction)
    {
        $string = sprintf("-- Migration File Generated on %s\n", date('Y-m-d H:i:s'));

        foreach ($queriesByVersion as $version => $queries) {
            $string .= "\n-- Version " . $version . "\n";
            foreach ($queries as $query) {
                $string .= $query . ";\n";
            }

            $string .= $this->getVersionUpdateQuery($version, $direction);
        }

        return $string;
    }

    private function getVersionUpdateQuery($version, $direction)
    {
        if ($direction == Version::DIRECTION_DOWN) {
            $query = sprintf(
                $this->configuration->formatQuery("DELETE FROM {table} WHERE {migration_set} = '%s' AND {version} = '%s';\n"),
                $this->configuration->getMigrationSet(),
                $version
            );
        } else {
            $query = sprintf(
                $this->configuration->formatQuery("INSERT INTO {table} ({migration_set}, {version}) VALUES ('%s', '%s');\n"),
                $this->configuration->getMigrationSet(),
                $version
            );
        }

        return $query;
    }

    private function buildMigrationFilePath()
    {
        $path = $this->destPath;
        if (is_dir($path)) {
            $path = realpath($path);
            $path = $path . '/' . $this->configuration->getMigrationSet() . '_migration_' . date('YmdHis') . '.sql';
        }

        return $path;
    }

    /**
     * This only exists for backwards-compatibiliy with DBAL 2.4
     */
    protected function throwInvalidArgumentException($message)
    {
        if (class_exists('Doctrine\DBAL\Exception\InvalidArgumentException')) {
            throw new InvalidArgumentException($message);
        } else {
            throw new DBALException($message);
        }
    }
}
