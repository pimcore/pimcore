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
 * @method \Pimcore\Model\Object\Classificationstore\KeyGroupRelation\Dao getDao()
 */
class KeyGroupRelation extends Model\AbstractModel
{

    /**
     * @var integer
     */
    public $keyId;

    /**
     * @var integer
     */
    public $groupId;


    /** The key
     * @var string
     */
    public $name;

    /** The key description.
     * @var
     */
    public $description;

    /** Field definition
     * @var
     */
    public $definition;

    /** Field type
     * @var
     */
    public $type;

    /** @var  int */
    public $sorter;

    /** The group name
     * @var string
     */
    public $groupName;

    /** @var  bool */
    public $mandatory;

    /** @var  bool */
    public $enabled;

    /**
     * @return Model\Object\Classificationstore\KeyGroupRelation
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return int
     */
    public function getKeyId()
    {
        return $this->keyId;
    }

    /**
     * @param int $keyId
     */
    public function setKeyId($keyId)
    {
        $this->keyId = $keyId;
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
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param mixed $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getSorter()
    {
        return $this->sorter;
    }

    /**
     * @param int $sorter
     */
    public function setSorter($sorter)
    {
        $this->sorter = $sorter;
    }

    /**
     * @return boolean
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param boolean $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = intval($mandatory);
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }



    /**
     * @param $groupId
     * @param $keyId
     * @return KeyGroupRelation
     */
    public static function getByGroupAndKeyId($groupId, $keyId)
    {
        $relation = new KeyGroupRelation\Listing();
        $relation->setCondition("groupId = " . $relation->quote($groupId) . " and keyId = " . $relation->quote($keyId));
        $relation->setLimit(1);
        $relation = $relation->load();
        if ($relation) {
            return $relation[0];
        }
    }
}
