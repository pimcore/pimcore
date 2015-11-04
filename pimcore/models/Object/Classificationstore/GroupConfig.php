<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;

class GroupConfig extends Model\AbstractModel {

    /** Group id.
     * @var integer
     */
    public $id;

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

    public $collId;


    /**
     * @param integer $id
     * @return Model\Object\Classificationstore\GroupConfig
     */
    public static function getById($id) {
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
    public static function getByName ($name) {
        try {
            $config = new self();
            $config->setName($name);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {

        }
    }

    public function hasChilds() {
        return $this->getDao()->hasChilds();
    }

    /**
     * @return Model\Object\Classificationstore\GroupConfig
     */
    public static function create() {
        $config = new self();
        $config->save();

        return $config;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return integer
     */
    public function getId() {
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
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /** Returns the description.
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /** Sets the description.
     * @param $description
     * @return Model\Object\Classificationstore\GroupConfig
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete() {
        \Pimcore::getEventManager()->trigger("object.Classificationstore.groupConfig.preDelete", $this);
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.Classificationstore.groupConfig.postDelete", $this);
    }

    /**
     * Saves the group config
     */
    public function save() {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.Classificationstore.groupConfig.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.groupConfig.preAdd", $this);
        }

        $model = parent::save();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.groupConfig.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.groupConfig.postAdd", $this);
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
    public function getRelations() {
        $list = new KeyGroupRelation\Listing();
        $list->setCondition("groupId = " . $this->id);
        $list = $list->load();
        return $list;
    }


}
