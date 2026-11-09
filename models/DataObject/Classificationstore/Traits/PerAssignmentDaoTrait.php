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

    protected const COLUMN_SUB_GROUP = 'subGroup';

    /**
     * Sets the three nullable per assignment columns on the model and returns the row without them.
     * They are set here rather than left to assignVariablesToModel(), which skips a NULL: a row whose
     * column was cleared would otherwise leave the previously loaded value in memory, and the next
     * save would write it back.
     *
     * A column the row does not carry at all - a load against a schema the migration has not reached
     * yet - is left alone: setting NULL would mark the field initialized, and a save after a schema
     * upgrade would then write that NULL over a value stored in between.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     *
     * @throws InvalidConfigException
     */
    protected function hydratePerAssignmentColumns(KeyGroupRelation $relation, array $row): array
    {
        if (array_key_exists(self::COLUMN_METADATA, $row)) {
            $relation->setMetadata($this->decodeJsonColumn($row[self::COLUMN_METADATA]));
        }

        if (array_key_exists(self::COLUMN_DEFINITION_OVERRIDE, $row)) {
            $relation->setDefinitionOverride($this->decodeJsonColumn($row[self::COLUMN_DEFINITION_OVERRIDE]));
        }

        if (array_key_exists(self::COLUMN_SUB_GROUP, $row)) {
            $subGroup = $row[self::COLUMN_SUB_GROUP];
            $relation->setSubGroup(is_string($subGroup) ? $subGroup : null);
        }

        unset(
            $row[self::COLUMN_METADATA],
            $row[self::COLUMN_DEFINITION_OVERRIDE],
            $row[self::COLUMN_SUB_GROUP]
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
