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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class CalculatedValue implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected string $fieldname;

    protected string $ownerType = 'object';

    protected ?string $ownerName = null;

    protected string|int|null $index;

    protected ?string $position = null;

    protected ?int $groupId = null;

    protected ?int $keyId = null;

    protected mixed $keyDefinition = null;

    /**
     * CalculatedValue constructor.
     *
     */
    public function __construct(string $fieldname)
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();
    }

    /**
     *
     * @internal
     *
     */
    public function setContextualData(string $ownerType, ?string $ownerName, int|string|null $index, ?string $position, ?int $groupId = null, ?int $keyId = null, mixed $keyDefinition = null): void
    {
        $this->ownerType = $ownerType;
        $this->ownerName = $ownerName;
        $this->index = $index;
        $this->position = $position;
        $this->groupId = $groupId;
        $this->keyId = $keyId;
        $this->keyDefinition = $keyDefinition;
        $this->markMeDirty();
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function getIndex(): int|string|null
    {
        return $this->index;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function getOwnerType(): string
    {
        return $this->ownerType;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function getKeyDefinition(): mixed
    {
        return $this->keyDefinition;
    }

    public function getKeyId(): ?int
    {
        return $this->keyId;
    }
}
