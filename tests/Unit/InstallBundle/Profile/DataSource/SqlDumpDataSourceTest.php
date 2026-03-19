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

namespace Pimcore\Tests\Unit\InstallBundle\Profile\DataSource;

use Doctrine\DBAL\DriverManager;
use Pimcore\Bundle\InstallBundle\Profile\DataSource\SqlDumpDataSource;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
final class SqlDumpDataSourceTest extends TestCase
{
    private string $tempDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/pimcore_sql_dump_test_' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);

        parent::tearDown();
    }

    public function testGetLabelReturnsDirectoryBasename(): void
    {
        $dataSource = new SqlDumpDataSource('/path/to/some/dump-dir');

        $this->assertSame('SQL dumps from dump-dir', $dataSource->getLabel());
    }

    public function testGetLabelWithDifferentPath(): void
    {
        $dataSource = new SqlDumpDataSource('/var/data/pimcore-install');

        $this->assertSame(
            'SQL dumps from pimcore-install',
            $dataSource->getLabel(),
        );
    }

    public function testConstructorWithCustomMarkerTable(): void
    {
        $dataSource = new SqlDumpDataSource(
            '/path/to/dumps',
            '_custom_marker_table',
        );

        $this->assertSame('SQL dumps from dumps', $dataSource->getLabel());
    }

    public function testApplyImportsPlainSqlFiles(): void
    {
        file_put_contents(
            $this->tempDir . '/001-create.sql',
            'CREATE TABLE test_plain (id INTEGER PRIMARY KEY, name TEXT)',
        );
        file_put_contents(
            $this->tempDir . '/002-insert.sql',
            "INSERT INTO test_plain (id, name) VALUES (1, 'hello')",
        );

        $connection = $this->createSqliteConnection();
        $dataSource = new SqlDumpDataSource($this->tempDir);
        $dataSource->apply($connection, new BufferedOutput());

        $result = $connection->fetchOne('SELECT name FROM test_plain WHERE id = 1');
        $this->assertSame('hello', $result);
    }

    public function testApplyImportsGzippedSqlFiles(): void
    {
        $sql = 'CREATE TABLE test_gz (id INTEGER PRIMARY KEY, value TEXT)';
        file_put_contents(
            $this->tempDir . '/001-schema.sql.gz',
            gzencode($sql),
        );

        $insertSql = "INSERT INTO test_gz (id, value) VALUES (1, 'compressed')";
        file_put_contents(
            $this->tempDir . '/002-data.sql.gz',
            gzencode($insertSql),
        );

        $connection = $this->createSqliteConnection();
        $dataSource = new SqlDumpDataSource($this->tempDir);
        $dataSource->apply($connection, new BufferedOutput());

        $result = $connection->fetchOne('SELECT value FROM test_gz WHERE id = 1');
        $this->assertSame('compressed', $result);
    }

    public function testApplyImportsMixedSqlAndGzFiles(): void
    {
        file_put_contents(
            $this->tempDir . '/001-schema.sql',
            'CREATE TABLE test_mixed (id INTEGER PRIMARY KEY, source TEXT)',
        );

        $gzSql = "INSERT INTO test_mixed (id, source) VALUES (1, 'from-gz')";
        file_put_contents(
            $this->tempDir . '/002-data.sql.gz',
            gzencode($gzSql),
        );

        $connection = $this->createSqliteConnection();
        $dataSource = new SqlDumpDataSource($this->tempDir);
        $dataSource->apply($connection, new BufferedOutput());

        $result = $connection->fetchOne('SELECT source FROM test_mixed WHERE id = 1');
        $this->assertSame('from-gz', $result);
    }

    public function testApplyProcessesFilesInAlphabeticalOrder(): void
    {
        file_put_contents(
            $this->tempDir . '/002-insert.sql',
            "INSERT INTO test_order (id, seq) VALUES (1, 'second')",
        );
        file_put_contents(
            $this->tempDir . '/001-create.sql',
            'CREATE TABLE test_order (id INTEGER PRIMARY KEY, seq TEXT)',
        );

        $connection = $this->createSqliteConnection();
        $dataSource = new SqlDumpDataSource($this->tempDir);
        $dataSource->apply($connection, new BufferedOutput());

        $result = $connection->fetchOne('SELECT seq FROM test_order WHERE id = 1');
        $this->assertSame('second', $result);
    }

    public function testApplyOutputsFilenamesForBothTypes(): void
    {
        file_put_contents(
            $this->tempDir . '/001-plain.sql',
            'CREATE TABLE test_output1 (id INTEGER)',
        );
        file_put_contents(
            $this->tempDir . '/002-compressed.sql.gz',
            gzencode('CREATE TABLE test_output2 (id INTEGER)'),
        );

        $connection = $this->createSqliteConnection();
        $output = new BufferedOutput();
        $dataSource = new SqlDumpDataSource($this->tempDir);
        $dataSource->apply($connection, $output);

        $rendered = $output->fetch();
        $this->assertStringContainsString('001-plain.sql', $rendered);
        $this->assertStringContainsString('002-compressed.sql.gz', $rendered);
    }

    public function testApplyThrowsForNonexistentDirectory(): void
    {
        $dataSource = new SqlDumpDataSource('/nonexistent/path/abc123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dump directory not found');

        $dataSource->apply($this->createSqliteConnection(), new BufferedOutput());
    }

    public function testIsAppliedReturnsFalseBeforeApply(): void
    {
        $connection = $this->createSqliteConnection();
        $dataSource = new SqlDumpDataSource($this->tempDir);

        $this->assertFalse($dataSource->isApplied($connection));
    }

    public function testIsAppliedReturnsTrueAfterApply(): void
    {
        file_put_contents(
            $this->tempDir . '/001-schema.sql',
            'CREATE TABLE test_marker (id INTEGER)',
        );

        $connection = $this->createSqliteConnection();
        $dataSource = new SqlDumpDataSource($this->tempDir);
        $dataSource->apply($connection, new BufferedOutput());

        $this->assertTrue($dataSource->isApplied($connection));
    }

    private function createSqliteConnection(): \Doctrine\DBAL\Connection
    {
        return DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }
}
