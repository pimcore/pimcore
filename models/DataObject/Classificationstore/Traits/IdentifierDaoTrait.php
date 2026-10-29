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
    abstract public function create(): void;

    abstract public function update(): void;

    abstract public function delete(): void;

    /**
     * First half of the write-once guard, to be called before the rest of the row is written: a stale
     * identifier - one belonging to an instance loaded before another process assigned a different one - is
     * refused here, so that a refused save leaves no partial write behind.
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
            $this->rejectStaleIdentifier($this->readIdentifier($table, $id), $identifier);
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
        $this->create();

        try {
            $this->update();
        } catch (Throwable $e) {
            $this->delete();

            throw $e;
        }
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
