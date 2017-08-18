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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Pimcore\Migrations\InstallVersion;
use Pimcore\Migrations\Version;

class Configuration extends \Doctrine\DBAL\Migrations\Configuration\Configuration
{
    /**
     * @var string
     */
    private $migrationSet;

    /**
     * The column name to track the migration set
     *
     * @var string
     */
    private $migrationSetColumnName = 'migration_set';

    /**
     * The column name to track migration date
     *
     * @var string
     */
    private $migrationDateColumnName = 'migrated_at';

    /**
     * Flag for whether or not the migration table has been created
     *
     * @var boolean
     */
    private $migrationTableCreated = false;

    /**
     * Connection instance to use for migrations
     *
     * @var Connection
     */
    private $connection;

    /**
     * @inheritDoc
     */
    public function __construct(
        string $migrationSet,
        Connection $connection,
        OutputWriter $outputWriter = null,
        MigrationFinderInterface $finder = null
    )
    {
        $this->migrationSet = $migrationSet;
        $this->connection   = $connection;

        parent::setMigrationsTableName('pimcore_migrations');
        parent::__construct($connection, $outputWriter, $finder);
    }

    public function getMigrationSet(): string
    {
        return $this->migrationSet;
    }

    public function getMigrationSetColumnName(): string
    {
        return $this->migrationSetColumnName;
    }

    public function setMigrationSetColumnName(string $migrationSetColumnName)
    {
        $this->migrationSetColumnName = $migrationSetColumnName;
    }

    public function getMigrationDateColumnName(): string
    {
        return $this->migrationDateColumnName;
    }

    public function setMigrationDateColumnName(string $migrationDateColumnName)
    {
        $this->migrationDateColumnName = $migrationDateColumnName;
    }

    public function setMigrationsTableName($tableName)
    {
        // noop - do not allow to set table name from outside
        // if this is omitted, the migration bundle commands will
        // override our custom table name which is reserved to plain
        // doctrine migrations if using the migration bundle with the ORM
    }

    /**
     * @inheritdoc
     */
    public function registerMigration($version, $class)
    {
        $this->ensureMigrationClassExists($class);

        $migrations = $this->getMigrations();

        $version = (string) $version;
        $class = (string) $class;
        if (isset($migrations[$version])) {
            throw MigrationException::duplicateMigrationVersion($version, get_class($migrations[$version]));
        }
        $version = new Version($this, $version, $class);
        $migrations[$version->getVersion()] = $version;
        ksort($migrations, SORT_STRING);

        $this->setMigrations($migrations);

        return $version;
    }

    /**
     * This is really hacky, but allows us to change the Version to an overridden version without having
     * to override the whole Configuration class as the Configuration uses the same migration lazy loading
     * snippet in a lot of methods which we'd need to override. This seemed as the less painful way regarding
     * maintainability of our custom Configuration. Could be omitted if doctrine moved the register migrations
     * if empty step into a method or at least provided a way to add Versions without having to override
     * registerMigration()
     *
     * @param array $migrations
     */
    protected function setMigrations(array $migrations)
    {
        static $migrationsProperty;

        if (null === $migrationsProperty) {
            $reflector = new \ReflectionClass(\Doctrine\DBAL\Migrations\Configuration\Configuration::class);
            $migrationsProperty = $reflector->getProperty('migrations');
        }

        $migrationsProperty->setAccessible(true);
        $migrationsProperty->setValue($this, $migrations);
        $migrationsProperty->setAccessible(false);
    }

    /**
     * Create the migration table to track migrations with.
     *
     * @return boolean Whether or not the table was created.
     */
    public function createMigrationTable()
    {
        $this->validate();

        if ($this->migrationTableCreated) {
            return false;
        }

        $connection = $this->getConnection();
        $this->connect();

        if ($connection->getSchemaManager()->tablesExist([$this->getMigrationsTableName()])) {
            $this->migrationTableCreated = true;

            return false;
        }

        $setColumn           = $this->migrationSetColumnName;
        $versionColumn       = $this->getMigrationsColumnName();
        $migrationDateColumn = $this->migrationDateColumnName;

        $columns = [
            $setColumn => new Column(
                $setColumn,
                Type::getType('string'), [
                    'length' => 255
                ]
            ),
            $versionColumn => new Column(
                $versionColumn,
                Type::getType('string'), [
                    'length' => 255
                ]
            ),
            $migrationDateColumn => new Column(
                $migrationDateColumn,
                Type::getType('datetime')
            ),
        ];

        $table = new Table($this->getMigrationsTableName(), $columns);
        $table->setPrimaryKey([$setColumn, $versionColumn]);

        $this->connection->getSchemaManager()->createTable($table);

        $this->migrationTableCreated = true;

        return true;
    }

    /**
     * Check if a version has been migrated or not yet
     *
     * @param Version $version
     *
     * @return boolean
     */
    public function hasVersionMigrated(\Doctrine\DBAL\Migrations\Version $version)
    {
        $this->connect();
        $this->createMigrationTable();

        $version = $this->connection->fetchColumn(
            $this->formatQuery('SELECT {version} FROM {table} WHERE {migration_set} = ? AND {version} = ?'),
            [
                $this->migrationSet,
                $version->getVersion()
            ]
        );

        return $version !== false;
    }

    /**
     * Returns all migrated versions from the versions table, in an array.
     *
     * @return Version[]
     */
    public function getMigratedVersions()
    {
        $this->connect();
        $this->createMigrationTable();

        $ret = $this->connection->fetchAll($this->formatQuery('SELECT {version} FROM {table} WHERE {migration_set} = ?'), [
            $this->migrationSet
        ]);

        return array_map('current', $ret);
    }

    /**
     * Returns the current migrated version from the versions table.
     *
     * @return string
     */
    public function getCurrentVersion()
    {
        $this->connect();
        $this->createMigrationTable();

        if (empty($this->getMigrations())) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        $where = null;
        if (!empty($this->getMigrations())) {
            $migratedVersions = [];
            foreach ($this->getMigrations() as $migration) {
                $migratedVersions[] = sprintf("'%s'", $migration->getVersion());
            }
            $where = " WHERE {migration_set} = ? AND {version} IN (" . implode(', ', $migratedVersions) . ")";
        }

        $sql = $this->formatQuery(sprintf(
            "SELECT {version} FROM {table}%s ORDER BY {version} DESC",
            $where
        ));

        $sql = $this->connection->getDatabasePlatform()->modifyLimitQuery($sql, 1);
        $result = $this->connection->fetchColumn($sql, [
            $this->migrationSet
        ]);

        return $result !== false ? (string) $result : '0';
    }

    /**
     * Returns the total number of executed migration versions
     *
     * @return integer
     */
    public function getNumberOfExecutedMigrations()
    {
        $this->connect();
        $this->createMigrationTable();

        $result = $this->connection->fetchColumn(
            $this->formatQuery('SELECT COUNT({version}) FROM {table} WHERE {migration_set} = ?'),
            [$this->migrationSet]
        );

        return $result !== false ? $result : 0;
    }

    /**
     * Clears migration table records.
     */
    public function clearMigratedVersions()
    {
        $this->connection->executeQuery($this->formatQuery('DELETE FROM {table} WHERE {migration_set} = ?'), [
                $this->migrationSet
            ]
        );
    }

    /**
     * Returns the number of new (not migrated) migrations
     *
     * @return int
     */
    public function getNumberOfNewMigrations(): int
    {
        $availableMigrations = $this->getAvailableVersions();
        $executedMigrations  = $this->getMigratedVersions();

        $newMigrations = count(array_diff(
            $availableMigrations,
            $executedMigrations
        ));

        return $newMigrations;
    }

    /**
     * Handles simple placeholder handling in query. Makes queries more readable as we need to replace the configurable
     * columns in every query.
     *
     * @param string $query
     *
     * @return string
     */
    public function formatQuery(string $query): string
    {
        $replacements = [
            'table'         => $this->getMigrationsTableName(),
            'migration_set' => $this->migrationSetColumnName,
            'version'       => $this->getMigrationsColumnName(),
            'migrated_at'   => $this->migrationDateColumnName
        ];

        $from = array_map(function ($key) {
            return '{' . $key . '}';
        }, array_keys($replacements));

        $to = array_values($replacements);

        return str_replace($from, $to, $query);
    }

    private function ensureMigrationClassExists($class)
    {
        if (!class_exists($class)) {
            throw MigrationException::migrationClassNotFound($class, $this->getMigrationsNamespace());
        }
    }
}
