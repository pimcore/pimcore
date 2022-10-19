<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class CalculatedValue implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /** @var string */
    protected string $fieldname;

    /** @var string */
    protected string $ownerType = 'object';

    /** @var string|null */
    protected ?string $ownerName;

    /** @var int|string|null */
    protected string|int|null $index;

    /** @var string|null */
    protected ?string $position;

    /** @var int|null */
    protected ?int $groupId;

    /** @var int|null */
    protected ?int $keyId;

    /**
     * @var mixed
     */
    protected mixed $keyDefinition;

    /**
     * CalculatedValue constructor.
     *
     * @param string $fieldname
     */
    public function __construct(string $fieldname)
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();
    }

    /**
     * @param string $ownerType
     * @param string|null $ownerName
     * @param int|string|null $index
     * @param string|null $position
     * @param int|null $groupId
     * @param int|null $keyId
     * @param mixed|null $keyDefinition
          * @internal
     *
     */
    public function setContextualData(string $ownerType, ?string $ownerName, int|string|null $index, ?string $position, int $groupId = null, int $keyId = null, mixed $keyDefinition = null)
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

    /**
     * @return string
     */
    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    /**
     * @return int|string|null
     */
    public function getIndex(): int|string|null
    {
        return $this->index;
    }

    /**
     * @return string|null
     */
    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    /**
     * @return string
     */
    public function getOwnerType(): string
    {
        return $this->ownerType;
    }

    /**
     * @return string|null
     */
    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * @return int|null
     */
    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    /**
     * @return mixed
     */
    public function getKeyDefinition(): mixed
    {
        return $this->keyDefinition;
    }

    /**
     * @return int|null
     */
    public function getKeyId(): ?int
    {
        return $this->keyId;
    }
}
