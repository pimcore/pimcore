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
 * @package    Version
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

/**
 * @method \Pimcore\Model\Version\Dao getDao()
 */
class GridConfigFavourite extends AbstractModel
{
    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var int
     */
    public $classId;

    /**
     * @var int
     */
    public $objectId;

    /**
     * @var int
     */
    public $gridConfigId;

    /**
     * @var string
     */
    public $searchType;

    /**
     * @param $ownerId
     * @param $classId
     * @param null $searchType
     *
     * @return GridConfigFavourite
     */
    public static function getByOwnerAndClassAndObjectId($ownerId, $classId, $objectId = null, $searchType = '')
    {
        $favourite = new self();
        $favourite->getDao()->getByOwnerAndClassAndObjectId($ownerId, $classId, $objectId, $searchType);

        return $favourite;
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
     * @return int
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * @param int $classId
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
}
