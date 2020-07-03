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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao getDao()
 * @method void save()
 * @method void delete()
 */
class KeyGroupRelation extends Model\AbstractModel
{
    /**
     * @var int
     */
    public $keyId;

    /**
     * @var int
     */
    public $groupId;

    /** The key
     * @var string
     */
    public $name;

    /**
     * The key description.
     *
     * @var string
     */
    public $description;

    /**
     * Field definition
     *
     * @var string
     */
    public $definition;

    /**
     * Field type
     *
     * @var string
     */
    public $type;

    /** @var int */
    public $sorter;

    /** The group name
     * @var string
     */
    public $groupName;

    /** @var bool */
    public $mandatory;

    /** @var bool */
    public $enabled;

    /**
     * @return Model\DataObject\Classificationstore\KeyGroupRelation
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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param string $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
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
        $this->sorter = (int) $sorter;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param bool $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = (bool)$mandatory;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param int $groupId
     * @param int $keyId
     *
     * @return KeyGroupRelation|null
     */
    public static function getByGroupAndKeyId($groupId, $keyId)
    {
        $relation = new KeyGroupRelation\Listing();
        $relation->setCondition('groupId = ' . $relation->quote($groupId) . ' and keyId = ' . $relation->quote($keyId));
        $relation->setLimit(1);
        $relation = $relation->load();
        if ($relation) {
            return $relation[0];
        }

        return null;
    }
}
