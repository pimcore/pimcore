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

namespace Pimcore\Model\DataObject\Classificationstore\Traits;

use Throwable;

/**
 * Shared by the classification store DAOs whose writes span more than one statement, and whose callers
 * may or may not already hold a transaction of their own.
 *
 * @internal
 */
trait AtomicWriteTrait
{
    /**
     * A write that is not already inside a transaction gets one of its own, which a failure takes with it.
     * Inside an enclosing transaction nothing is begun or rolled back here: the failure propagates, and
     * that transaction is the unit its owner rolls back.
     *
     * Row locks taken by the callable are held until that unit ends, which is what lets a validating read
     * and the write it authorises face a concurrent writer as one step.
     *
     * @param callable(): void $write
     *
     * @throws Throwable
     */
    private function writeAtomically(callable $write): void
    {
        $ownTransaction = !$this->db->isTransactionActive();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $write();
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->rollBack();
            }

            throw $e;
        }

        if ($ownTransaction) {
            $this->db->commit();
        }
    }
}
