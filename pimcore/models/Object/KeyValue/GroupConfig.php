<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\KeyValue;

use Pimcore\Model;

class GroupConfig extends Model\AbstractModel {

    /** Group id.
     * @var integer
     */
    public $id;

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
     * @return Model\Object\KeyValue\GroupConfig
     */
    public static function getById($id) {
        try {

            $config = new self();
            $config->setId(intval($id));
            $config->getResource()->getById();

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
            $config->getResource()->getByName();

            return $config;
        } catch (\Exception $e) {

        }
    }

    /**
     * @return Model\Object\KeyValue\GroupConfig
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
     * @return Model\Object\KeyValue\GroupConfig
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete() {
        \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.preDelete", $this);
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.postDelete", $this);
    }

    /**
     * Saves the group config
     */
    public function save() {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.preAdd", $this);
        }

        $model = parent::save();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.postAdd", $this);
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
}
