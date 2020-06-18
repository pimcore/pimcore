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

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProvider;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Types\Type;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Migrations\Configuration\Configuration;
use Pimcore\Migrations\Migration\DryRunMigrationInterface;
use Pimcore\Migrations\Migration\PimcoreMigrationInterface;

class Version extends \Doctrine\DBAL\Migrations\Version
{
    /**
     * The Migrations Configuration instance for this migration
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * The version in timestamp format (YYYYMMDDHHMMSS)
     *
     * @var string
     */
    private $version;

    /**
     * The migration instance for this version
     *
     * @var AbstractMigration
     */
    private $migration;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $class;

    /** The array of collected SQL statements for this version */
    private $sql = [];

    /** The array of collected parameters for SQL statements for this version */
    private $params = [];

    /** The array of collected types for SQL statements for this version */
    private $types = [];

    /** The time in seconds that this migration version took to execute */
    private $time;

    /**
     * @var int
     */
    private $state = self::STATE_NONE;

    /** @var SchemaDiffProviderInterface */
    private $schemaProvider;

    public function __construct(Configuration $configuration, $version, $class, SchemaDiffProviderInterface $schemaProvider = null)
    {
        $this->configuration = $configuration;
        $this->outputWriter = $configuration->getOutputWriter();
        $this->connection = $configuration->getConnection();
        $this->version = $version;

        $this->class = $class;
        $this->migration = $this->createMigration();

        if (null !== $schemaProvider) {
            $this->schemaProvider = $schemaProvider;
        } else {
            $schemaProvider = new SchemaDiffProvider($this->connection->getSchemaManager(), $this->connection->getDatabasePlatform());
            $this->schemaProvider = LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration($schemaProvider);
        }
    }

    protected function createMigration()
    {
        $class = $this->class;

        return new $class($this);
    }

    /**
     * Returns the string version in the format YYYYMMDDHHMMSS
     *
     * @return string $version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns the Migrations Configuration object instance
     *
     * @return Configuration $configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Check if this version has been migrated or not.
     *
     * @return bool
     */
    public function isMigrated()
    {
        return $this->configuration->hasVersionMigrated($this);
    }

    public function markMigrated()
    {
        $this->markVersion('up');
    }

    public function markNotMigrated()
    {
        $this->markVersion('down');
    }

    private function markVersion($direction)
    {
        $action = $direction === 'up' ? 'insert' : 'delete';

        $setColumn = $this->configuration->getMigrationSetColumnName();
        $versionColumn = $this->configuration->getMigrationsColumnName();
        $migrationDateColumn = $this->configuration->getMigrationDateColumnName();

        $data = [
            $setColumn => $this->configuration->getMigrationSet(),
            $versionColumn => $this->getVersion(),
        ];

        $dataTypes = [
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
        ];

        if ('insert' === $action) {
            $data[$migrationDateColumn] = new \DateTime();
            $dataTypes[] = 'datetime';
        }

        // PIMCORE: add migration set to migrations table
        $this->configuration->createMigrationTable();
        $this->configuration->getConnection()->$action(
            $this->configuration->getMigrationsTableName(),
            $data,
            $dataTypes
        );
    }

    /**
     * Add some SQL queries to this versions migration
     *
     * @param array|string $sql
     * @param array        $params
     * @param array        $types
     */
    public function addSql($sql, array $params = [], array $types = [])
    {
        if (is_array($sql)) {
            foreach ($sql as $key => $query) {
                $this->sql[] = $query;
                if (!empty($params[$key])) {
                    $queryTypes = isset($types[$key]) ? $types[$key] : [];
                    $this->addQueryParams($params[$key], $queryTypes);
                }
            }
        } else {
            $this->sql[] = $sql;
            if (!empty($params)) {
                $this->addQueryParams($params, $types);
            }
        }
    }

    /**
     * @param mixed[] $params Array of prepared statement parameters
     * @param string[] $types Array of the types of each statement parameters
     */
    private function addQueryParams($params, $types)
    {
        $index = count($this->sql) - 1;
        $this->params[$index] = $params;
        $this->types[$index] = $types;
    }

    /**
     * Write a migration SQL file to the given path
     *
     * @param string $path      The path to write the migration SQL file.
     * @param string $direction The direction to execute.
     *
     * @return bool $written
     *
     * @throws MigrationException
     */
    public function writeSqlFile($path, $direction = self::DIRECTION_UP)
    {
        $queries = $this->execute($direction, true);

        if (!empty($this->params)) {
            throw MigrationException::migrationNotConvertibleToSql($this->class);
        }

        $this->outputWriter->write("\n-- Version " . $this->getVersion() . "\n");

        // PIMCORE: use pimcore SqlFileWriter
        $sqlQueries = [$this->getVersion() => $queries];
        $sqlWriter = new SqlFileWriter(
            $this->configuration,
            $path,
            $this->outputWriter
        );

        return $sqlWriter->write($sqlQueries, $direction);
    }

    /**
     * @return AbstractMigration
     */
    public function getMigration()
    {
        return $this->migration;
    }

    /**
     * Execute this migration version up or down and and return the SQL.
     * We are only allowing the addSql call and the schema modification to take effect in the up and down call.
     * This is necessary to ensure that the migration is revertable.
     * The schema is passed to the pre and post method only to be able to test the presence of some table, And the
     * connection that can get used trough it allow for the test of the presence of records.
     *
     * @param string  $direction      The direction to execute the migration.
     * @param bool $dryRun         Whether to not actually execute the migration SQL and just do a dry run.
     * @param bool $timeAllQueries Measuring or not the execution time of each SQL query.
     *
     * @return array $sql
     *
     * @throws \Exception when migration fails
     */
    public function execute($direction, $dryRun = false, $timeAllQueries = false)
    {
        $this->sql = [];

        // PIMCORE: add support for dry run aware migrations for migrations not migrating SQL queries
        if ($this->migration instanceof DryRunMigrationInterface) {
            $this->migration->setDryRun($dryRun);
        }

        $transaction = $this->migration->isTransactional();
        if ($transaction) {
            //only start transaction if in transactional mode
            $this->connection->beginTransaction();
        }

        try {
            $migrationStart = microtime(true);

            $this->state = self::STATE_PRE;
            $fromSchema = $this->schemaProvider->createFromSchema();

            $this->migration->{'pre' . ucfirst($direction)}($fromSchema);

            if ($direction === self::DIRECTION_UP) {
                $this->outputWriter->write("\n" . sprintf('  <info>++</info> migrating <comment>%s</comment>', $this->version) . "\n");
            } else {
                $this->outputWriter->write("\n" . sprintf('  <info>--</info> reverting <comment>%s</comment>', $this->version) . "\n");
            }

            $this->state = self::STATE_EXEC;

            $toSchema = $this->schemaProvider->createToSchema($fromSchema);
            $this->migration->$direction($toSchema);

            $this->addSql($this->schemaProvider->getSqlDiffToMigrate($fromSchema, $toSchema));

            $this->executeRegisteredSql($dryRun, $timeAllQueries);

            $this->state = self::STATE_POST;
            $this->migration->{'post' . ucfirst($direction)}($toSchema);

            if (! $dryRun) {
                if ($direction === self::DIRECTION_UP) {
                    $this->markMigrated();
                } else {
                    $this->markNotMigrated();
                }
            }

            $migrationEnd = microtime(true);
            $this->time = round($migrationEnd - $migrationStart, 2);
            if ($direction === self::DIRECTION_UP) {
                $this->outputWriter->write(sprintf("\n  <info>++</info> migrated (%ss)", $this->time));
            } else {
                $this->outputWriter->write(sprintf("\n  <info>--</info> reverted (%ss)", $this->time));
            }

            if ($transaction) {
                //commit only if running in transactional mode
                $this->connection->commit();
            }

            $this->state = self::STATE_NONE;

            return $this->sql;
        } catch (SkipMigrationException $e) {
            if ($transaction) {
                //only rollback transaction if in transactional mode
                $this->connection->rollBack();
            }

            if ($dryRun === false) {
                // now mark it as migrated
                if ($direction === self::DIRECTION_UP) {
                    $this->markMigrated();
                } else {
                    $this->markNotMigrated();
                }
            }

            $this->outputWriter->write(sprintf("\n  <info>SS</info> skipped (Reason: %s)", $e->getMessage()));

            $this->state = self::STATE_NONE;

            return [];
        } catch (\Exception $e) {
            $this->outputWriter->write(sprintf(
                '<error>Migration %s failed during %s. Error %s</error>',
                $this->version,
                $this->getExecutionState(),
                $e->getMessage()
            ));

            if ($transaction) {
                //only rollback transaction if in transactional mode
                $this->connection->rollBack();
            }

            $this->state = self::STATE_NONE;
            throw $e;
        }
    }

    public function getExecutionState()
    {
        switch ($this->state) {
            case self::STATE_PRE:
                return 'Pre-Checks';
            case self::STATE_POST:
                return 'Post-Checks';
            case self::STATE_EXEC:
                return 'Execution';
            default:
                return 'No State';
        }
    }

    private function outputQueryTime($queryStart, $timeAllQueries = false)
    {
        if ($timeAllQueries !== false) {
            $queryEnd = microtime(true);
            $queryTime = round($queryEnd - $queryStart, 4);

            $this->outputWriter->write(sprintf('  <info>%ss</info>', $queryTime));
        }
    }

    /**
     * Returns the time this migration version took to execute
     *
     * @return int $time The time this migration version took to execute
     */
    public function getTime()
    {
        return $this->time;
    }

    public function __toString()
    {
        return $this->version;
    }

    private function executeRegisteredSql($dryRun = false, $timeAllQueries = false)
    {
        if (! $dryRun) {
            if (!empty($this->sql)) {
                foreach ($this->sql as $key => $query) {
                    $queryStart = microtime(true);

                    if (! isset($this->params[$key])) {
                        $this->outputWriter->write('     <comment>-></comment> ' . $query);
                        $this->connection->executeQuery($query);
                    } else {
                        $this->outputWriter->write(sprintf('    <comment>-</comment> %s (with parameters)', $query));
                        $this->connection->executeQuery($query, $this->params[$key], $this->types[$key]);
                    }

                    $this->outputQueryTime($queryStart, $timeAllQueries);
                }
            } else {
                // PIMCORE: do not output warning if migration is a PimcoreMigrationInterface and states that it does not do SQL changes
                if (!($this->migration instanceof PimcoreMigrationInterface && !$this->migration->doesSqlMigrations())) {
                    $this->outputWriter->write(sprintf(
                        '<error>Migration %s was executed but did not result in any SQL statements.</error>',
                        $this->version
                    ));
                }
            }
        } else {
            foreach ($this->sql as $idx => $query) {
                $this->outputSqlQuery($idx, $query);
            }
        }
    }

    /**
     * Outputs a SQL query via the `OutputWriter`.
     *
     * @param int $idx The SQL query index. Used to look up params.
     * @param string $query the query to output
     *
     * @return void
     */
    private function outputSqlQuery($idx, $query)
    {
        $params = $this->formatParamsForOutput(
            isset($this->params[$idx]) ? $this->params[$idx] : [],
            isset($this->types[$idx]) ? $this->types[$idx] : []
        );

        $this->outputWriter->write(rtrim(sprintf(
            '     <comment>-></comment> %s %s',
            $query,
            $params
        )));
    }

    /**
     * Formats a set of sql parameters for output with dry run.
     *
     * @param array $params The query parameters
     * @param array $types The types of the query params. Default type is a string
     *
     * @return string|null a string of the parameters present.
     */
    private function formatParamsForOutput(array $params, array $types)
    {
        if (empty($params)) {
            return '';
        }

        $platform = $this->connection->getDatabasePlatform();
        $out = [];
        foreach ($params as $key => $value) {
            $type = isset($types[$key]) ? $types[$key] : 'string';
            $outval = Type::getType($type)->convertToDatabaseValue($value, $platform);
            $out[] = is_string($key) ? sprintf(':%s => %s', $key, $outval) : $outval;
        }

        return sprintf('with parameters (%s)', implode(', ', $out));
    }
}
