<?php
declare(strict_types=1);

namespace Pimcore\Tests\Unit\InstallBundle\Database;

use Pimcore\Bundle\InstallBundle\Database\DatabaseSetup;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class DatabaseSetupTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $setup = new DatabaseSetup();
        $this->assertInstanceOf(DatabaseSetup::class, $setup);
    }

    public function testInstallSqlFileExists(): void
    {
        $installSqlPath = __DIR__ . '/../../../../bundles/InstallBundle/dump/install.sql';
        $this->assertFileExists($installSqlPath, 'install.sql must exist for DatabaseSetup to work');
    }

    public function testSplitSqlStatementsHandlesSemicolonsInStrings(): void
    {
        $setup = new DatabaseSetup();

        $method = new \ReflectionMethod(DatabaseSetup::class, 'splitSqlStatements');

        $sql = <<<'SQL'
CREATE TABLE test (id INT);
INSERT INTO test VALUES (1);
INSERT INTO config (key, value) VALUES ('delimiter', 'a;b;c');
INSERT INTO test VALUES (2);
SQL;

        $statements = $method->invoke($setup, $sql);

        $this->assertCount(4, $statements);
        $this->assertSame('CREATE TABLE test (id INT)', trim($statements[0]));
        $this->assertSame('INSERT INTO test VALUES (1)', trim($statements[1]));
        $this->assertStringContainsString("'a;b;c'", $statements[2]);
        $this->assertSame('INSERT INTO test VALUES (2)', trim($statements[3]));
    }

    public function testSplitSqlStatementsHandlesEmptyInput(): void
    {
        $setup = new DatabaseSetup();
        $method = new \ReflectionMethod(DatabaseSetup::class, 'splitSqlStatements');

        $statements = $method->invoke($setup, '');

        $this->assertSame([], $statements);
    }

    public function testSplitSqlStatementsHandlesSingleLineComments(): void
    {
        $setup = new DatabaseSetup();
        $method = new \ReflectionMethod(DatabaseSetup::class, 'splitSqlStatements');

        $sql = <<<'SQL'
CREATE TABLE test (id INT); -- this is a comment; with semicolons
INSERT INTO test VALUES (1);
SQL;

        $statements = $method->invoke($setup, $sql);

        $this->assertCount(2, $statements);
    }
}
