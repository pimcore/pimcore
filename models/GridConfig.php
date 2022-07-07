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
 * @method \Pimcore\Model\GridConfig\Dao getDao()
 *
 * @internal
 */
class GridConfig extends AbstractModel
{
    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var int|null
     */
    protected $ownerId;

    /**
     * @var string
     */
    protected $classId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $searchType;

    /**
     * @var string
     */
    protected $config;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var int|null
     */
    protected $creationDate;

    /**
     * @var int|null
     */
    protected $modificationDate;

    /**
     * @var bool
     */
    protected $shareGlobally;

    /**
     * @var bool
     */
    protected $setAsFavourite;

    /**
     * @var string
     */
    protected $type = 'object';

    /**
     * @param int $id
     *
     * @return GridConfig|null
     */
    public static function getById($id)
    {
        if (!$id) {
            return null;
        }

        try {
            $config = new self();
            $config->getDao()->getById($id);

            return $config;
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->getId()) {
            $this->setCreationDate(time());
        }

        $this->setModificationDate(time());

        $this->getDao()->save();
    }

    /**
     * Delete this GridConfig
     */
    public function delete()
    {
        $this->getDao()->delete();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * @return int|null
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSearchType()
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
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return int|null
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return bool
     */
    public function isShareGlobally()
    {
        return $this->shareGlobally;
    }

    /**
     * @param bool $shareGlobally
     */
    public function setShareGlobally($shareGlobally)
    {
        $this->shareGlobally = $shareGlobally;
    }

    /**
     * @return bool
     */
    public function isSetAsFavourite()
    {
        return $this->setAsFavourite;
    }

    /**
     * @param bool $setAsFavourite
     */
    public function setSetAsFavourite($setAsFavourite)
    {
        $this->setAsFavourite = $setAsFavourite;
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
