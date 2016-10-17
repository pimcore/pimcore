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

namespace Pimcore\Model\Object\KeyValue;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\KeyValue\GroupConfig\Dao getDao()
 */
class GroupConfig extends Model\AbstractModel
{

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
    public static function getByName($name)
    {
        try {
            $config = new self();
            $config->setName($name);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @return Model\Object\KeyValue\GroupConfig
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
     * @param string $name
     * @return $this
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
     * @return Model\Object\KeyValue\GroupConfig
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
        \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.preDelete", $this);
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.keyValue.groupConfig.postDelete", $this);
    }

    /**
     * Saves the group config
     */
    public function save()
    {
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
