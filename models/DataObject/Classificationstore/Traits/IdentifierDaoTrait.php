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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Model\Exception\InvalidConfigException;
use Throwable;

/**
 * Shared by the four classification store configuration DAOs, whose tables all carry the same
 * nullable, unique, write-once identifier column.
 *
 * @internal
 */
trait IdentifierDaoTrait
{
    /**
     * The write-once guard straddles the rest of the row: the stale check reads the stored identifier
     * before the ordinary fields are written, and the NULL to value transition happens after them. A
     * refusal in between - because another request assigned this row a different identifier, or claimed
     * the proposed one for a row of its own - would otherwise leave those fields behind, and with them
     * the group relations that disabling a key deletes on the way past. writeAtomically() is what makes
     * the two halves and the fields between them one unit.
     */
    use AtomicWriteTrait;

    abstract public function create(): void;

    abstract public function update(): void;

    abstract public function delete(): void;

    /**
     * First half of the write-once guard, to be called before the rest of the row is written: a stale
     * identifier - one belonging to an instance loaded before another process assigned a different one - is
     * refused here, before anything has been written at all.
     *
     * Returns whether writeIdentifier() still has anything to do.
     *
     * @throws InvalidConfigException
     */
    private function isIdentifierPending(string $table, ?int $id, ?string $identifier): bool
    {
        if ($identifier === null || $id === null) {
            // a NULL identifier is the absence of one, never an instruction to clear the stored value
            return false;
        }

        $stored = $this->readIdentifier($table, $id);
        if ($stored === $identifier) {
            return false;
        }

        $this->rejectStaleIdentifier($stored, $identifier);

        return true;
    }

    /**
     * Second half, to be called after the rest of the row is written - on creation the row has no storeId
     * before that, and the identifier is only unique within a store. `identifier IS NULL` leaves the NULL to
     * value transition to the database rather than to whichever instance saves last.
     *
     * @throws InvalidConfigException
     */
    private function writeIdentifier(string $table, int $id, string $identifier): void
    {
        try {
            $assigned = $this->db->executeStatement(
                'UPDATE `' . $table . '` SET `identifier` = ? WHERE `id` = ? AND `identifier` IS NULL',
                [$identifier, $id]
            ) > 0;
        } catch (UniqueConstraintViolationException $e) {
            throw new InvalidConfigException(
                sprintf('The identifier "%s" is already in use.', $identifier),
                previous: $e
            );
        }

        if (!$assigned) {
            // an identifier was assigned between the two halves, and it is immutable from now on
            $this->rejectStaleIdentifier($this->readIdentifierForUpdate($table, $id), $identifier);
        }
    }

    /**
     * Creation inserts a placeholder row and fills it with the following update, so a rejected identifier has
     * to take the placeholder with it instead of leaving an unreachable row behind. The instance is not
     * reusable afterwards - its id points at a row that no longer exists.
     *
     * @throws Throwable
     */
    private function createAndUpdate(): void
    {
        $this->writeAtomically(function (): void {
            $this->create();

            try {
                $this->update();
            } catch (Throwable $e) {
                // the compensation an enclosing transaction needs, which is not this method's to
                // roll back; an own transaction removes the placeholder itself a moment later
                $this->delete();

                throw $e;
            }
        });
    }

    /**
     * A row that is gone and a row whose identifier is still NULL are the same answer here: nothing to reject.
     */
    private function readIdentifier(string $table, int $id): ?string
    {
        $stored = $this->db->fetchOne('SELECT `identifier` FROM `' . $table . '` WHERE `id` = ?', [$id]);

        return is_string($stored) ? $stored : null;
    }

    /**
     * Read as of now rather than from the transaction's snapshot: the guarded UPDATE of writeIdentifier()
     * is a current read, so under REPEATABLE READ a plain read here could report the column as still NULL
     * and turn a refusal into a silent success. The row is held for the rest of the transaction, which
     * ends either way as soon as the refusal is raised.
     */
    private function readIdentifierForUpdate(string $table, int $id): ?string
    {
        $stored = $this->db->fetchOne(
            'SELECT `identifier` FROM `' . $table . '` WHERE `id` = ? FOR UPDATE',
            [$id]
        );

        return is_string($stored) ? $stored : null;
    }

    /**
     * @throws InvalidConfigException
     */
    private function rejectStaleIdentifier(?string $stored, string $identifier): void
    {
        if ($stored !== null && $stored !== $identifier) {
            throw new InvalidConfigException(
                sprintf('The identifier "%s" cannot be changed to "%s".', $stored, $identifier)
            );
        }
    }
}
