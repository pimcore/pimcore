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
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    protected function decodeJsonColumns(array $row): array
    {
        foreach ([self::COLUMN_METADATA, self::COLUMN_DEFINITION_OVERRIDE] as $column) {
            if (is_string($row[$column] ?? null)) {
                $row[$column] = json_decode($row[$column], true);
            }
        }

        return $row;
    }
}
