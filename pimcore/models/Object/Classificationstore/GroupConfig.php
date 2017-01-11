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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Classificationstore\GroupConfig\Dao getDao()
 */
class GroupConfig extends Model\AbstractModel
{
    use Model\Element\ChildsCompatibilityTrait;

    /** Group id.
     * @var integer
     */
    public $id;

    /**
     * Store ID
     * @var integer
     */
    public $storeId = 1;

    /** Parent id
     * @var int
     */
    public $parentId;

    /** The group name.
     * @var string
     */
    public $name;

    /** The group description.
     * @var
     */
    public $description;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;


    /**
     * @param integer $id
     * @return Model\Object\Classificationstore\GroupConfig
     */
    public static function getById($id)
    {
        try {
            $config = new self();
            $config->setId(intval($id));
            $config->getDao()->getById();

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @param $name
     * @return GroupConfig
     */
    public static function getByName($name, $storeId = 1)
    {
        try {
            $config = new self();
            $config->setName($name);
            $config->setStoreId($storeId ? $storeId : 1);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {
        }
    }

    public function hasChildren()
    {
        return $this->getDao()->hasChilds();
    }

    /**
     * @return Model\Object\Classificationstore\GroupConfig
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }


    /**
     * @param string name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /** Returns the description.
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /** Sets the description.
     * @param $description
     * @return Model\Object\Classificationstore\GroupConfig
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete()
    {
        \Pimcore::getEventManager()->trigger("object.classificationstore.groupConfig.preDelete", $this);
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.classificationstore.groupConfig.postDelete", $this);
    }

    /**
     * Saves the group config
     */
    public function save()
    {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.classificationstore.groupConfig.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.classificationstore.groupConfig.preAdd", $this);
        }

        $model = parent::save();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("object.classificationstore.groupConfig.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.classificationstore.groupConfig.postAdd", $this);
        }

        return $model;
    }

    /**
     * @param $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /** Returns all keys belonging to this group
     * @return KeyGroupRelation
     */
    public function getRelations()
    {
        $list = new KeyGroupRelation\Listing();
        $list->setCondition("groupId = " . $this->id);
        $list = $list->load();

        return $list;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }
}
