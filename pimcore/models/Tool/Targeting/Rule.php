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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Targeting;

use Pimcore\Model;
use Pimcore\Model\Tool;

class Rule extends Model\AbstractModel {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description = "";

    /**
     * @var string
     */
    public $scope = "hit";

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var array
     */
    public $conditions = array();

    /**
     * @var Model\Tool\Targeting\Rule\Actions
     */
    public $actions;

    /**
     * @param $target
     * @return bool
     */
    public static function inTarget($target) {
        if($target instanceof Model\Tool\Targeting\Rule) {
            $targetId = $target->getId();
        } else if (is_string($target)) {
            $target = self::getByName($target);
            if(!$target) {
                return false;
            } else {
                $targetId = $target->getId();
            }
        } else {
            $targetId = (int) $target;
        }

        if(array_key_exists("_ptc", $_GET) && intval($targetId) == intval($_GET["_ptc"])) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     */
    public static function fireEvent ($key, $value = null) {
        if($value === null) {
            $value = true;
        }

        $front = \Zend_Controller_Front::getInstance();
        $plugin = $front->getPlugin("Pimcore\\Controller\\Plugin\\Targeting");
        if($plugin instanceof \Pimcore\Controller\Plugin\Targeting) {
            $plugin->addEvent($key, $value);
        }
    }

    /**
     * Static helper to retrieve an instance of Tool\Targeting\Rule by the given ID
     *
     * @param integer $id
     * @return Tool\Targeting\Rule
     */
    public static function getById($id) {
        try {
            $target = new self();
            $target->setId(intval($id));
            $target->getResource()->getById();
            return $target;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $name
     * @return null|Rule
     */
    public static function getByName($name) {
        try {
            $target = new self();
            $target->setName($name);
            $target->getResource()->getByName();
            return $target;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $name
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

    /**
     * @param $actions
     * @return $this
     */
    public function setActions($actions)
    {
        if(!$actions) {
            $actions = new Tool\Targeting\Rule\Actions();
        }
        $this->actions = $actions;
        return $this;
    }

    /**
     * @return Tool\Targeting\Rule\Actions
     */
    public function getActions()
    {
        // this is to be backward compatible (was Tool\Targeting\Actions)
        if($this->actions instanceof Tool\Targeting\Rule\Actions) {
            return $this->actions;
        }

        return new Tool\Targeting\Rule\Actions();
    }

    /**
     * @param $conditions
     * @return $this
     */
    public function setConditions($conditions)
    {
        if(!$conditions) {
            $conditions = array();
        }
        $this->conditions = $conditions;
        return $this;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        if(!empty($scope)) {
            $this->scope = $scope;
        }
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }
}
