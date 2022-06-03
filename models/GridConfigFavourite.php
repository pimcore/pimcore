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

namespace Pimcore\Model;

use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\GridConfigFavourite\Dao getDao()
 *
 * @internal
 */
class GridConfigFavourite extends AbstractModel
{
    /**
     * @var int
     */
    protected $ownerId;

    /**
     * @var string
     */
    protected $classId;

    /**
     * @var int
     */
    protected $objectId;

    /**
     * @var int
     */
    protected $gridConfigId;

    /**
     * @var string
     */
    protected $searchType;

    /**
     * enum('asset','object')
     *
     * @var string
     */
    protected $type;

    /**
     * @param int $ownerId
     * @param string $classId
     * @param int|null $objectId
     * @param string|null $searchType
     *
     * @return GridConfigFavourite|null
     */
    public static function getByOwnerAndClassAndObjectId($ownerId, $classId, $objectId = null, $searchType = '')
    {
        try {
            $favourite = new self();
            $favourite->getDao()->getByOwnerAndClassAndObjectId($ownerId, $classId, $objectId, $searchType);

            return $favourite;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $this->getDao()->save();
    }

    /**
     * Delete this favourite
     */
    public function delete()
    {
        $this->getDao()->delete();
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param int $ownerId
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @return string
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * @param string $classId
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
    }

    /**
     * @return int
     */
    public function getGridConfigId()
    {
        return $this->gridConfigId;
    }

    /**
     * @param int $gridConfigId
     */
    public function setGridConfigId($gridConfigId)
    {
        $this->gridConfigId = $gridConfigId;
    }

    /**
     * @return string
     */
    public function getSearchType(): string
    {
        return $this->searchType;
    }

    /**
     * @param string $searchType
     */
    public function setSearchType($searchType)
    {
        $this->searchType = $searchType;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param int $objectId
     */
    public function setObjectId(int $objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * enum('asset','object')
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * enum('asset','object')
     *
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }
}
