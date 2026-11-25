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

namespace Pimcore\Model\DataObject\Classificationstore;

/**
 * One stored descriptor whose target no longer resolves, as the sweep reports it.
 *
 * @internal
 */
final class DanglingRelation
{
    public function __construct(
        private readonly int $objectId,
        private readonly string $fieldname,
        private readonly int $groupId,
        private readonly int $keyId,
        private readonly string $elementType,
        private readonly int $elementId
    ) {
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function describe(): string
    {
        return sprintf(
            'object %d, field "%s", group %d, key %d: %s %d',
            $this->objectId,
            $this->fieldname,
            $this->groupId,
            $this->keyId,
            $this->elementType,
            $this->elementId
        );
    }
}
