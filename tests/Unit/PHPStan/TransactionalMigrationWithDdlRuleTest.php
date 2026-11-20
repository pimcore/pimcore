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

namespace Pimcore\Tests\Unit\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pimcore\Tests\Support\PHPStan\MigrationDdlCollector;
use Pimcore\Tests\Support\PHPStan\NonTransactionalMigrationCollector;
use Pimcore\Tests\Support\PHPStan\TransactionalMigrationWithDdlRule;
use ReflectionClass;

/**
 * @extends RuleTestCase<TransactionalMigrationWithDdlRule>
 */
class TransactionalMigrationWithDdlRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TransactionalMigrationWithDdlRule(self::createReflectionProvider());
    }

    protected function getCollectors(): array
    {
        return [new MigrationDdlCollector(), new NonTransactionalMigrationCollector()];
    }

    public function testReportsSchemaApiCall(): void
    {
        $this->assertReported(Fixtures\SchemaApiMigration::class, 'Doctrine\DBAL\Schema\Table::addColumn()', 23);
    }

    public function testReportsAddSql(): void
    {
        $this->assertReported(Fixtures\AddSqlMigration::class, 'ALTER statement', 24);
    }

    public function testReportsSprintfStatement(): void
    {
        $this->assertReported(Fixtures\SprintfStatementMigration::class, 'DROP statement', 23);
    }

    public function testReportsDefinitionSaveInHelper(): void
    {
        $this->assertReported(Fixtures\DefinitionSaveInHelperMigration::class, 'Pimcore\Model\DataObject\ClassDefinition::save()', 30);
    }

    public function testReportsExplicitlyTransactionalMigration(): void
    {
        $this->assertReported(Fixtures\ExplicitlyTransactionalMigration::class, 'CREATE statement', 28);
    }

    public function testIgnoresNonTransactionalMigration(): void
    {
        $this->analyse([$this->fixtureFile(Fixtures\NonTransactionalMigration::class)], []);
    }

    public function testIgnoresInheritedNonTransactionalMigration(): void
    {
        $this->analyse([
            $this->fixtureFile(Fixtures\InheritedNonTransactionalMigration::class),
            $this->fixtureFile(Fixtures\NonTransactionalBaseMigration::class),
        ], []);
    }

    public function testIgnoresDataOnlyMigration(): void
    {
        $this->analyse([$this->fixtureFile(Fixtures\DataOnlyMigration::class)], []);
    }

    public function testIgnoresNonMigrationClass(): void
    {
        $this->analyse([$this->fixtureFile(Fixtures\NotAMigration::class)], []);
    }

    private function assertReported(string $className, string $reason, int $line): void
    {
        $this->analyse([$this->fixtureFile($className)], [
            [
                sprintf(
                    'Migration %s runs DDL (%s) but is transactional. DDL commits implicitly on MySQL/MariaDB, override isTransactional() to return false.',
                    $className,
                    $reason
                ),
                $line,
            ],
        ]);
    }

    private function fixtureFile(string $className): string
    {
        return __DIR__ . '/Fixtures/' . (new ReflectionClass($className))->getShortName() . '.php';
    }
}
