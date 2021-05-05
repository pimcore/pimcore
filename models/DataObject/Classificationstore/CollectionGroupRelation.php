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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class CollectionGroupRelation extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected $colId;

    /**
     * @var int
     */
    protected $groupId;

    /** The key
     * @var string
     */
    protected $name;

    /**
     * The key description.
     *
     * @var string
     */
    protected $description;

    /** @var int */
    protected $sorter;

    /**
     * @return Model\DataObject\Classificationstore\CollectionGroupRelation
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
     * @return int
     */
    public function getColId()
    {
        return $this->colId;
    }

    /**
     * @param int $colId
     */
    public function setColId($colId)
    {
        $this->colId = $colId;
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
}
