<?php

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
    protected $fieldname;

    /** @var string */
    protected $ownerType = 'object';

    /** @var string|null */
    protected $ownerName;

    /** @var int|string|null */
    protected $index;

    /** @var string|null */
    protected $position;

    /** @var int|null */
    protected $groupId;

    /** @var int|null */
    protected $keyId;

    /**
     * @var mixed
     */
    protected $keyDefinition;

    /**
     * CalculatedValue constructor.
     *
     * @param string $fieldname
     */
    public function __construct($fieldname)
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();
    }

    /**
     * @internal
     *
     * @param string $ownerType
     * @param string|null $ownerName
     * @param int|string|null $index
     * @param string|null $position
     * @param int|null $groupId
     * @param int|null $keyId
     * @param mixed $keyDefinition
     */
    public function setContextualData($ownerType, $ownerName, $index, $position, $groupId = null, $keyId = null, $keyDefinition = null)
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
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @return int|string|null
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string|null
     */
    public function getOwnerName()
    {
        return $this->ownerName;
    }

    /**
     * @return string
     */
    public function getOwnerType()
    {
        return $this->ownerType;
    }

    /**
     * @return string|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int|null
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @return mixed
     */
    public function getKeyDefinition()
    {
        return $this->keyDefinition;
    }

    /**
     * @return int|null
     */
    public function getKeyId()
    {
        return $this->keyId;
    }
}
