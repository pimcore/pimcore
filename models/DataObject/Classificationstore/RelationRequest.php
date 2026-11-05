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

use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * One stored value handed to {@see RelationResolver::resolveAll()}: where it sits in the store, the
 * descriptor it holds and the field definition that denormalizes it.
 *
 * @internal
 */
final class RelationRequest
{
    public function __construct(
        private readonly int $groupId,
        private readonly int $keyId,
        private readonly string $language,
        private readonly Data $fieldDefinition,
        private readonly mixed $normalizedValue
    ) {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getFieldDefinition(): Data
    {
        return $this->fieldDefinition;
    }

    public function getNormalizedValue(): mixed
    {
        return $this->normalizedValue;
    }
}
