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

use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\Exception\InvalidConfigException;

/**
 * Shared by the two key/group relation DAOs, whose load paths both have to hand the model the
 * decoded structure of the per assignment JSON columns rather than the stored text.
 *
 * @internal
 */
trait PerAssignmentDaoTrait
{
    protected const COLUMN_METADATA = 'metadata';

    protected const COLUMN_DEFINITION_OVERRIDE = 'definitionOverride';

    /**
     * Sets the two nullable per assignment columns on the model and returns the row without them.
     * They are set here rather than left to assignVariablesToModel(), which skips a NULL: a row whose
     * column was cleared would otherwise leave the previously loaded value in memory, and the next
     * save would write it back.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     *
     * @throws InvalidConfigException
     */
    protected function hydratePerAssignmentColumns(KeyGroupRelation $relation, array $row): array
    {
        $relation->setMetadata($this->decodeJsonColumn($row[self::COLUMN_METADATA] ?? null));
        $relation->setDefinitionOverride($this->decodeJsonColumn($row[self::COLUMN_DEFINITION_OVERRIDE] ?? null));

        unset(
            $row[self::COLUMN_METADATA],
            $row[self::COLUMN_DEFINITION_OVERRIDE]
        );

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonColumn(mixed $value): ?array
    {
        if (!is_string($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
