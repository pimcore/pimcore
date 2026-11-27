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

namespace Pimcore\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Event\MigrationsVersionEventArgs;
use PDO;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * MySQL and MariaDB commit the open transaction implicitly on every DDL statement (CREATE, ALTER, DROP ...). For a
 * transactional migration that runs DDL, doctrine/migrations then skips its commit because PDO has no transaction
 * anymore, but DBAL is not told: its transaction nesting level stays above 0 for the rest of the process. Every later
 * DBAL user sees a transaction the server does not have, e.g. Symfony's DoctrineDbalAdapter wraps each cache save
 * in a savepoint then, which fails with "SAVEPOINT ... does not exist" (so does messenger:stop-workers, which
 * stores its restart signal in the cache and is run by every config save).
 *
 * After each executed migration this resets DBAL's transaction state when the server has no transaction anymore.
 *
 * @internal
 */
final class ImplicitCommitListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function onMigrationsVersionExecuted(MigrationsVersionEventArgs $args): void
    {
        $connection = $args->getConnection();
        if (!$connection->isTransactionActive()) {
            return;
        }

        $nativeConnection = $connection->getNativeConnection();
        if (!$nativeConnection instanceof PDO || !$this->hasTransactionTheServerDoesNotHave($connection, $nativeConnection)) {
            return;
        }

        $this->logger->info(
            'Migration {version} committed its transaction implicitly (DDL on MySQL/MariaDB), resetting the transaction state of the connection. The migration should return false from isTransactional().',
            ['version' => (string) $args->getPlan()->getVersion()]
        );

        $this->resetTransactionNestingLevel($connection, $nativeConnection);
    }

    /**
     * There is nothing left to commit or roll back on the server, only DBAL's bookkeeping has to be brought back to 0.
     * For a nested level commit() releases the (lost) savepoint and decrements the level in a finally block also when
     * that fails; rollBack() of a nested level would only decrement after its statement succeeded. For level 1
     * rollBack() sets the level to 0 before it calls the driver, whose "no active transaction" error is expected.
     *
     * This only runs while DBAL reports a transaction the server does not have: with DBAL's auto-commit disabled
     * rollBack() of level 1 opens the next (real) transaction, as DBAL always keeps one open in that mode.
     */
    private function resetTransactionNestingLevel(Connection $connection, PDO $nativeConnection): void
    {
        while ($this->hasTransactionTheServerDoesNotHave($connection, $nativeConnection)) {
            $level = $connection->getTransactionNestingLevel();

            try {
                if ($level === 1) {
                    $connection->rollBack();
                } else {
                    $connection->commit();
                }
            } catch (Throwable) {
                // expected, the server has no transaction and no savepoint anymore
            }

            if ($this->hasTransactionTheServerDoesNotHave($connection, $nativeConnection)
                && $connection->getTransactionNestingLevel() >= $level
            ) {
                // e.g. a nested level that is marked rollback-only: commit() throws before it decrements
                $this->logger->warning(
                    'The transaction nesting level of the connection could not be reset ({level}) after an implicit commit.',
                    ['level' => $connection->getTransactionNestingLevel()]
                );

                return;
            }
        }
    }

    /**
     * The same check doctrine/migrations uses to decide that a transaction was committed implicitly: DBAL reports a
     * transaction, PDO (which asks the server) has none.
     */
    private function hasTransactionTheServerDoesNotHave(Connection $connection, PDO $nativeConnection): bool
    {
        return $connection->isTransactionActive() && !$nativeConnection->inTransaction();
    }
}
