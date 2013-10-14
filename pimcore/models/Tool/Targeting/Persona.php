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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Targeting_Persona extends Pimcore_Model_Abstract {

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
     * @var int
     */
    public $threshold = 1;

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var array
     */
    public $conditions = array();

    /**
     * Static helper to retrieve an instance of Tool_Targeting_Persona by the given ID
     *
     * @param integer $id
     * @return Tool_Targeting_Persona
     */
    public static function getById($id) {
        try {
            $persona = new self();
            $persona->setId(intval($id));
            $persona->getResource()->getById();
            return $persona;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * add the persona to the current user
     * @param $id
     */
    public static function fire ($id) {
        $front = Zend_Controller_Front::getInstance();
        $plugin = $front->getPlugin("Pimcore_Controller_Plugin_Targeting");
        if($plugin instanceof Pimcore_Controller_Plugin_Targeting) {
            $plugin->addPersona($id);
        }
    }

    /**
     * @param $id
     */
    public static function isIdActive($id) {
        $persona = Tool_Targeting_Persona::getById($id);
        if($persona) {
            return $persona->getActive();
        }
        return false;
    }

    /**
     * @param string $description
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
     * @param int $id
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
     * @param string $name
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
     * @param array $conditions
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
     * @param int $threshold
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
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
