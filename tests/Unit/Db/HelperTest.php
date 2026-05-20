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

namespace Pimcore\Tests\Unit\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use PHPUnit\Framework\TestCase;
use Pimcore\Db\Helper;
use RuntimeException;

/**
 * @internal
 */
class HelperTest extends TestCase
{
    public function testUpsertFallsBackToUpdateOnUniqueConstraintViolationException(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection->expects(self::once())
            ->method('insert')
            ->willThrowException(new UniqueConstraintViolationException(self::makeDriverException(1062, '23000'), null));

        $connection->expects(self::once())
            ->method('update')
            ->with(
                'tmp_store',
                self::callback(static fn (array $data): bool => $data === ['`id`' => 'maintenance.pid', '`data`' => 'foo']),
                ['`id`' => 'maintenance.pid'],
            )
            ->willReturn(1);

        self::assertNull(
            Helper::upsert($connection, 'tmp_store', ['id' => 'maintenance.pid', 'data' => 'foo'], ['id']),
        );
    }

    /**
     * Regression test: a generic DBAL exception (not converted to
     * UniqueConstraintViolationException by DBAL) carrying vendor code 1062
     * must still trigger the update fallback. This can happen e.g. when
     * custom middleware short-circuits DBAL's exception conversion.
     */
    public function testUpsertFallsBackOnGenericDbalDuplicateKeyException(): void
    {
        $dbalException = new class('duplicate', 1062) extends RuntimeException implements DBALException {};

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection->expects(self::once())
            ->method('insert')
            ->willThrowException($dbalException);

        $connection->expects(self::once())
            ->method('update')
            ->with(
                'tmp_store',
                self::anything(),
                ['`id`' => 'maintenance.pid'],
            )
            ->willReturn(1);

        self::assertNull(
            Helper::upsert($connection, 'tmp_store', ['id' => 'maintenance.pid', 'data' => 'foo'], ['id']),
        );
    }

    /**
     * Regression test: a wrapped DBAL exception whose previous is a 1062
     * driver exception must also trigger the update fallback.
     */
    public function testUpsertFallsBackWhenDuplicateKeyIsInPreviousException(): void
    {
        $driverException = self::makeDriverException(1062, '23000');
        $dbalException = new class('wrap', 0, $driverException) extends RuntimeException implements DBALException {};

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection->expects(self::once())
            ->method('insert')
            ->willThrowException($dbalException);

        $connection->expects(self::once())
            ->method('update')
            ->willReturn(1);

        self::assertNull(
            Helper::upsert($connection, 'tmp_store', ['id' => 'maintenance.pid', 'data' => 'foo'], ['id']),
        );
    }

    public function testUpsertRethrowsNonDuplicateKeyDbalExceptions(): void
    {
        $deadlock = new DeadlockException(self::makeDriverException(1213, '40001'), null);

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection->expects(self::once())
            ->method('insert')
            ->willThrowException($deadlock);

        $connection->expects(self::never())->method('update');

        $this->expectException(DeadlockException::class);

        Helper::upsert($connection, 'tmp_store', ['id' => 'maintenance.pid', 'data' => 'foo'], ['id']);
    }

    /**
     * Regression test: a non-duplicate-key exception that happens to share
     * SQLSTATE 23000 (e.g. a MySQL foreign-key violation, code 1452) must
     * NOT trigger the update fallback and must be re-thrown unchanged.
     */
    public function testUpsertRethrowsNonDuplicateIntegrityViolationsWithSqlState23000(): void
    {
        $fkViolation = new ForeignKeyConstraintViolationException(self::makeDriverException(1452, '23000'), null);

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection->expects(self::once())
            ->method('insert')
            ->willThrowException($fkViolation);

        $connection->expects(self::never())->method('update');

        $this->expectException(ForeignKeyConstraintViolationException::class);

        Helper::upsert($connection, 'tmp_store', ['id' => 'maintenance.pid', 'data' => 'foo'], ['id']);
    }

    private static function makeDriverException(int $code, string $sqlState): Exception|DriverExceptionInterface
    {
        return new class($code, $sqlState) extends Exception implements DriverExceptionInterface {
            public function __construct(int $code, private readonly string $sqlState)
            {
                parent::__construct('driver exception', $code);
            }

            public function getSQLState(): ?string
            {
                return $this->sqlState;
            }
        };
    }
}
