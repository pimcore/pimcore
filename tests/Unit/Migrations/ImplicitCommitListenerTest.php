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

namespace Pimcore\Tests\Unit\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use PDO;
use Pimcore;
use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Throwable;

/**
 * MySQL and MariaDB commit the open transaction implicitly on every DDL statement. doctrine/migrations then skips
 * the commit of a transactional migration (it only checks PDO), so DBAL's transaction nesting level stayed above 0
 * for the rest of the process: every later DBAL user saw a transaction that the server did not have. Symfony's
 * DoctrineDbalAdapter, for example, wraps each cache save in a savepoint then, which fails with
 * "SAVEPOINT ... does not exist" - a later migration that saves a config (messenger:stop-workers stores its
 * restart signal in the cache) aborted the whole migrate run.
 */
class ImplicitCommitListenerTest extends TestCase
{
    public const TABLE = 'test_migration_implicit_commit';

    private const CACHE_TABLE = 'test_migration_implicit_commit_cache';

    private Connection $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Db::get();
        $this->dropTables();
        $this->getDependencyFactory()->getMetadataStorage()->ensureInitialized();
        $this->deleteMetadataRows();
    }

    protected function tearDown(): void
    {
        // leave no (phantom) transaction behind for the following tests, whatever the outcome of this one
        while ($this->db->isTransactionActive()) {
            try {
                $this->db->rollBack();
            } catch (Throwable) {
                // the server has no transaction anymore, rollBack() of level 1 still resets DBAL
            }
        }

        $this->dropTables();
        $this->deleteMetadataRows();

        parent::tearDown();
    }

    public function testTransactionalMigrationWithDdlLeavesNoTransactionBehind(): void
    {
        $cache = new DoctrineDbalAdapter($this->db, 'implicit-commit-test', 0, ['db_table' => self::CACHE_TABLE]);
        $cache->createTable();

        $this->execute(TransactionalDdlMigration::class);

        $this->assertTrue($this->db->createSchemaManager()->tablesExist([self::TABLE]), 'the migration ran');

        // while DBAL reports a transaction, the adapter saves inside a savepoint, which the server does not have
        $this->assertTrue($cache->save($cache->getItem('key')->set('value')), 'a cache save after the migration works');
        $this->assertFalse($this->db->isTransactionActive(), 'DBAL does not report the implicitly committed transaction as active');
        $this->assertSame(0, $this->db->getTransactionNestingLevel());
    }

    public function testTransactionalMigrationWithoutDdlIsStillCommitted(): void
    {
        $this->db->executeStatement('CREATE TABLE `' . self::TABLE . '` (`id` INT NOT NULL PRIMARY KEY)');

        $this->execute(TransactionalDmlMigration::class);

        $this->assertSame(0, $this->db->getTransactionNestingLevel());
        $this->assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM `' . self::TABLE . '`'));
    }

    public function testLeavesALivingTransactionOfTheCallerUntouched(): void
    {
        $this->db->executeStatement('CREATE TABLE `' . self::TABLE . '` (`id` INT NOT NULL PRIMARY KEY)');

        $this->db->beginTransaction();
        $this->execute(TransactionalDmlMigration::class);

        $this->assertSame(1, $this->db->getTransactionNestingLevel(), 'the transaction of the caller is still open');
        $native = $this->db->getNativeConnection();
        $this->assertInstanceOf(PDO::class, $native);
        $this->assertTrue($native->inTransaction());

        $this->db->rollBack();

        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM `' . self::TABLE . '`'), 'the caller could still roll the migration back');
    }

    /**
     * @param class-string<AbstractMigration> $migrationClass
     */
    private function execute(string $migrationClass): void
    {
        $dependencyFactory = $this->getDependencyFactory();
        $migration = new $migrationClass($dependencyFactory->getConnection(), new NullLogger());

        // what doctrine:migrations:migrate runs
        $dependencyFactory->getMigrator()->migrate(
            new MigrationPlanList([new MigrationPlan(new Version($migrationClass), $migration, Direction::UP)], Direction::UP),
            new MigratorConfiguration()
        );
    }

    private function getDependencyFactory(): DependencyFactory
    {
        // the dependency factory is a private service
        $dependencyFactory = Pimcore::getContainer()->get('test.service_container')->get('doctrine.migrations.dependency_factory');
        $this->assertInstanceOf(DependencyFactory::class, $dependencyFactory);

        return $dependencyFactory;
    }

    private function dropTables(): void
    {
        $this->db->executeStatement('DROP TABLE IF EXISTS `' . self::TABLE . '`, `' . self::CACHE_TABLE . '`');
    }

    private function deleteMetadataRows(): void
    {
        $this->db->executeStatement(
            'DELETE FROM `migration_versions` WHERE `version` IN (?, ?)',
            [TransactionalDdlMigration::class, TransactionalDmlMigration::class]
        );
    }
}

final class TransactionalDdlMigration extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `' . ImplicitCommitListenerTest::TABLE . '` (`id` INT NOT NULL PRIMARY KEY)');
    }
}

final class TransactionalDmlMigration extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO `' . ImplicitCommitListenerTest::TABLE . '` (`id`) VALUES (1)');
    }
}
