<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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

    /** @var string */
    protected $ownerName;

    /** @var int */
    protected $index;

    /** @var string */
    protected $position;

    /** @var int */
    protected $groupId;

    /** @var int */
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
     * Sets contextual information.
     *
     * @param string $ownerType
     * @param string $ownerName
     * @param int $index
     * @param string $position
     * @param int $groupId
     * @param int $keyId
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
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
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
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int
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
     * @return int
     */
    public function getKeyId()
    {
        return $this->keyId;
    }
}
