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

namespace Pimcore\Tests\Model\Element;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DeadlockException;
use Exception;
use PDO;
use Pimcore\Db;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * AbstractElement::retryableTransaction() (and the deprecated retryableFunction()) inside a transaction that was
 * opened by the caller.
 *
 * When the database server rolls that transaction back by itself (deadlock victim, MariaDB >= 11.6.2 snapshot
 * conflict with error 1020), the savepoint of the nested call is gone: ROLLBACK TO SAVEPOINT fails, DBAL's nesting
 * level stays too high, and a retry would run in autocommit mode, writing data outside of any transaction that the
 * caller can no longer roll back. The server side loss is simulated with a plain ROLLBACK on the native connection,
 * which works on every supported database.
 *
 * schedule_tasks rows with a cid that does not belong to any element are used as scratch data.
 *
 * @group model.element.retryable
 */
final class RetryableFunctionTest extends ModelTestCase
{
    private const SCRATCH_CID = 2147000001;

    private ?Connection $observer = null;

    private AbstractElement $element;

    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        $this->resetConnection();
        $this->deleteScratchRows();
        // created before any test opens a transaction of its own
        $this->element = TestHelper::createEmptyDocumentPage();
    }

    protected function tearDown(): void
    {
        // a failed test may leave DBAL's nesting level or the server transaction in an unknown state
        $this->resetConnection();
        $this->deleteScratchRows();
        $this->observer?->close();

        parent::tearDown();
    }

    public function testLostTransactionInsideCallersTransactionIsNotRetried(): void
    {
        $db = Db::get();
        $db->beginTransaction();
        $calls = 0;

        try {
            $this->retryable([
                'retryableFunc' => function () use (&$calls): void {
                    $calls++;
                    $this->insertScratchRow(Db::get());
                    $this->loseServerSideTransaction();

                    throw $this->deadlock();
                },
            ]);
            $this->fail('The original exception has to be thrown');
        } catch (DeadlockException) {
            // expected: the owner of the transaction decides what to do
        }

        $this->assertSame(1, $calls, 'The function must not run again outside of the lost transaction');
        $this->assertSame(1, $db->getTransactionNestingLevel(), 'DBAL must be back at the level of the caller');

        // used to fail with "SAVEPOINT ... does not exist" and a stuck nesting level
        $this->rollBackTheCallersTransaction($db);
        $this->assertSame(0, $this->committedScratchRows(), 'Nothing may be written outside of the transaction');
    }

    public function testFailedCommitOfTheSavepointInsideCallersTransactionIsNotRetried(): void
    {
        $db = Db::get();
        $db->beginTransaction();
        $calls = 0;

        try {
            $this->retryable([
                'retryableFunc' => function () use (&$calls): void {
                    $calls++;
                    $this->insertScratchRow(Db::get());
                    // the savepoint is gone when the nested call tries to release it
                    $this->loseServerSideTransaction();
                },
            ]);
            $this->fail('Releasing a savepoint of a lost transaction has to fail');
        } catch (Exception) {
            // expected
        }

        $this->assertSame(1, $calls);
        $this->assertSame(1, $db->getTransactionNestingLevel());

        $this->rollBackTheCallersTransaction($db);
        $this->assertSame(0, $this->committedScratchRows());
    }

    public function testFailureWithAliveTransactionRollsBackToTheSavepointOnly(): void
    {
        $db = Db::get();
        $db->beginTransaction();
        $this->insertScratchRow($db); // written by the caller before

        try {
            $this->retryable([
                'retryableFunc' => function (): void {
                    $this->insertScratchRow(Db::get());

                    throw new RuntimeException('not retryable');
                },
            ]);
            $this->fail('The exception has to be thrown');
        } catch (RuntimeException) {
        }

        $this->assertSame(1, $db->getTransactionNestingLevel());
        $this->assertSame(1, $this->scratchRows($db), 'Only the write of the failed nested call is rolled back');

        $db->commit();
        $this->assertSame(1, $this->committedScratchRows(), 'The earlier write of the caller is kept');
    }

    public function testRetryableFailureWithAliveTransactionIsStillRetried(): void
    {
        $db = Db::get();
        $db->beginTransaction();
        $calls = 0;

        $this->retryable([
            'retryableFunc' => function () use (&$calls): void {
                $calls++;
                $this->insertScratchRow(Db::get());

                // the transaction is alive (e.g. only the statement was rolled back): rolling back to the savepoint works
                if ($calls === 1) {
                    throw $this->deadlock();
                }
            },
        ]);

        $this->assertSame(2, $calls);
        $this->assertSame(1, $db->getTransactionNestingLevel());

        $db->commit();
        $this->assertSame(1, $this->committedScratchRows(), 'The write of the first run was rolled back to the savepoint');
    }

    public function testRetryableFailureOfTheOutermostTransactionIsRetried(): void
    {
        $calls = 0;

        $this->retryable([
            'retryableFunc' => function () use (&$calls): void {
                $calls++;
                $this->insertScratchRow(Db::get());

                if ($calls === 1) {
                    throw $this->deadlock();
                }
            },
        ]);

        $this->assertSame(2, $calls);
        $this->assertSame(0, Db::get()->getTransactionNestingLevel());
        $this->assertSame(1, $this->committedScratchRows());
    }

    public function testDeprecatedRetryableFunctionStillRetriesALostTransaction(): void
    {
        $db = Db::get();
        $db->beginTransaction();
        $calls = 0;

        $this->collectDeprecations(function () use (&$calls): void {
            try {
                $this->retryable([
                    'retryableFunc' => function () use (&$calls): void {
                        $calls++;
                        $this->loseServerSideTransaction();

                        throw $this->deadlock();
                    },
                    'maxRetries' => 2,
                ], 'retryableFunction');
            } catch (Throwable) {
                // the retry runs outside of the lost transaction and its commit fails
            }
        });

        $this->assertGreaterThan(1, $calls, 'The deprecated method keeps the previous behaviour');
    }

    public function testRetryableFunctionIsDeprecated(): void
    {
        $deprecations = $this->collectDeprecations(function (): void {
            $this->retryable(['retryableFunc' => function (): void {
                $this->insertScratchRow(Db::get());
            }], 'retryableFunction');
        });

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('retryableTransaction()', $deprecations[0]);
        $this->assertSame(1, $this->committedScratchRows(), 'The deprecated method still works');
    }

    public function testRetryableTransactionIsNotDeprecated(): void
    {
        $deprecations = $this->collectDeprecations(function (): void {
            $this->retryable(['retryableFunc' => function (): void {
                $this->insertScratchRow(Db::get());
            }]);
        });

        $this->assertSame([], $deprecations);
    }

    /**
     * The real thing on MariaDB >= 11.6.2: error 1020 of a locking read after a concurrent commit.
     */
    public function testSnapshotConflictInsideCallersTransaction(): void
    {
        $db = Db::get();
        $variable = $db->fetchAssociative("SHOW SESSION VARIABLES LIKE 'innodb\_snapshot\_isolation'");

        if ($variable === false || strtolower((string) $variable['Value']) !== 'on') {
            // MySQL, old MariaDB versions, or a session that does not enforce snapshot isolation
            $this->addToAssertionCount(1);

            return;
        }

        $db->beginTransaction();
        $db->fetchOne('SELECT COUNT(*) FROM schedule_tasks WHERE cid = ?', [self::SCRATCH_CID]); // the snapshot of the caller
        $this->insertScratchRow($this->observer()); // committed by another connection afterwards
        $calls = 0;

        try {
            $this->retryable([
                'retryableFunc' => function () use (&$calls): void {
                    $calls++;
                    Db::get()->fetchFirstColumn('SELECT id FROM schedule_tasks WHERE cid = ? FOR UPDATE', [self::SCRATCH_CID]);
                },
            ]);
            $this->fail('The locking read has to be rejected');
        } catch (Exception $e) {
            $this->assertStringContainsString('1020', $e->getMessage());
        }

        $this->assertSame(1, $calls);
        $this->assertSame(1, $db->getTransactionNestingLevel());
        $this->rollBackTheCallersTransaction($db);
    }

    /**
     * @param array<string, mixed> $arguments named arguments of the method
     */
    private function retryable(array $arguments, string $methodName = 'retryableTransaction'): void
    {
        $method = new ReflectionMethod(AbstractElement::class, $methodName);
        $method->invokeArgs($this->element, $arguments);
    }

    /**
     * With PHP >= 8.4 PDO knows that the server has already rolled the transaction back and reports "There is no active
     * transaction". DBAL's own state is clean in any case: it resets its nesting level before it asks PDO.
     */
    private function rollBackTheCallersTransaction(Connection $db): void
    {
        try {
            $db->rollBack();
        } catch (Exception) {
        }

        $this->assertSame(0, $db->getTransactionNestingLevel());
    }

    /**
     * @return string[] the messages of the deprecations that were triggered while $callback ran
     */
    private function collectDeprecations(callable $callback): array
    {
        $deprecations = [];

        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            if ($level === E_USER_DEPRECATED) {
                $deprecations[] = $message;

                return true;
            }

            return false;
        });

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    private function deadlock(): DeadlockException
    {
        $driverException = new class('Deadlock found when trying to get lock; try restarting transaction', 1213) extends Exception implements DriverException {
            public function getSQLState(): ?string
            {
                return '40001';
            }
        };

        return new DeadlockException($driverException, null);
    }

    /**
     * The server rolls the transaction back by itself, DBAL is not told (as with a deadlock victim or error 1020).
     */
    private function loseServerSideTransaction(): void
    {
        $native = Db::get()->getNativeConnection();
        $this->assertInstanceOf(PDO::class, $native);
        $native->exec('ROLLBACK');
    }

    private function insertScratchRow(Connection $connection): void
    {
        $connection->insert('schedule_tasks', [
            'cid' => self::SCRATCH_CID,
            'ctype' => 'document',
            'date' => time() + 3600,
            'action' => 'delete',
            'active' => 1,
        ]);
    }

    private function scratchRows(Connection $connection): int
    {
        return (int) $connection->fetchOne('SELECT COUNT(*) FROM schedule_tasks WHERE cid = ?', [self::SCRATCH_CID]);
    }

    /**
     * What is committed, as seen by another connection.
     */
    private function committedScratchRows(): int
    {
        return $this->scratchRows($this->observer());
    }

    private function observer(): Connection
    {
        return $this->observer ??= DriverManager::getConnection(Db::get()->getParams());
    }

    private function resetConnection(): void
    {
        Db::get()->close(); // forgets the nesting level and drops a transaction that is still open
    }

    private function deleteScratchRows(): void
    {
        Db::get()->executeStatement('DELETE FROM schedule_tasks WHERE cid = ?', [self::SCRATCH_CID]);
    }
}
