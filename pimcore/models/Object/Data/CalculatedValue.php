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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Data;

class CalculatedValue
{
    /** @var  string */
    public $fieldname;

    /** @var  string */
    public $ownerType = "object";

    /** @var  string */
    public $ownerName;

    /** @var int */
    public $index;

    /** @var  string */
    public $position;

    /** @var int */
    public $groupId;

    /** @var int */
    public $keyId;

    /**
     * @var mixed
     */
    public $keyDefinition;

    /**
     * CalculatedValue constructor.
     *
     * @param $fieldname
     */
    public function __construct($fieldname)
    {
        $this->fieldname = $fieldname;
    }

    /** Sets contextual information.
     * @param $ownerType
     * @param $ownerName
     * @param $index
     * @param $position
     * @param null $groupId
     * @param null $keyId
     * @param null $keyDefinition
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
