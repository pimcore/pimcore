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

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;

class CollectionGroupRelation extends Model\AbstractModel {

    /**
     * @var integer
     */
    public $colId;

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


    /**
     * @return Model\Object\Classificationstore\CollectionGroupRelation
     */
    public static function create() {
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


}