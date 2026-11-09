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

use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;

/**
 * Shared by the DAOs of the four configuration entities, whose load paths all have to hand the model
 * the decoded pairs of the `customMetadata` column and whose update paths write it as JSON text.
 *
 * @internal
 */
trait CustomMetadataDaoTrait
{
    protected const COLUMN_CUSTOM_METADATA = 'customMetadata';

    /**
     * Sets the pairs on the model and returns the row without the column. They are set here rather
     * than left to assignVariablesToModel(), which skips a NULL: a row whose column was cleared would
     * otherwise leave the previously loaded pairs in memory, and the next save would write them back.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    protected static function hydrateCustomMetadata(
        CollectionConfig|GroupConfig|SubGroupConfig|KeyConfig $config,
        array $row
    ): array {
        $config->setCustomMetadata(static::decodeCustomMetadata($row[self::COLUMN_CUSTOM_METADATA] ?? null));

        unset($row[self::COLUMN_CUSTOM_METADATA]);

        return $row;
    }

    /**
     * @param array<string, mixed>|null $value
     */
    protected static function encodeCustomMetadata(?array $value): ?string
    {
        return $value === null || $value === [] ? null : json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function decodeCustomMetadata(mixed $value): ?array
    {
        if (!is_string($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }
}
