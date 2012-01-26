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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Layout {

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $region;

    /**
     * @var string
     */
    public $title;

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * @var boolean
     */
    public $collapsible;

    /**
     * @var bool
     */
    public $collapsed;

    /**
     * @var string
     */
    public $bodyStyle;

    /**
     * @var string
     */
    public $datatype = "layout";

    /**
     * @var array
     */
    public $permissions;

    /**
     * @var array
     */
    public $childs = array();

    /**
     * @var boolean
     */
    public $locked;

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getRegion() {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return integer
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @return integer
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @return boolean
     */
    public function getCollapsible() {
        return $this->collapsible;
    }

    /**
     * @return array
     */
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @param string $region
     * @return void
     */
    public function setRegion($region) {
        $this->region = $region;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width) {
        if (!empty($width) && is_numeric($width)) {
            $this->width = intval($width);
        } else {
            $this->width = $width;
        }
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        if (!empty($height) && is_numeric($height)) {
            $this->height = intval($height);
        } else {
            $this->height = $height;
        }
    }

    /**
     * @param boolean $collapsible
     * @return void
     */
    public function setCollapsible($collapsible) {
        $this->collapsible = (bool) $collapsible;
    }

    /**
     * @param array $permissions
     * @return void
     */
    public function setPermissions($permissions) {
        $this->permissions = $permissions;
    }

    /**
     * @return array
     */
    public function getChilds() {
        return $this->childs;
    }

    /**
     * @param array $childs
     * @return void
     */
    public function setChilds($childs) {
        $this->childs = $childs;
    }

    /**
     * @return boolean
     */
    public function hasChilds() {
        if (is_array($this->childs) && count($this->childs) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $child
     * @return void
     */
    public function addChild($child) {
        $this->childs[] = $child;
    }

    /**
     * @param mixed $data
     * @param array $blockedKeys
     * @return void
     */
    public function setValues($data = array(), $blockedKeys = array()) {
        foreach ($data as $key => $value) {
            if (!in_array($key, $blockedKeys)) {
                $method = "set" . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getDatatype() {
        return $this->datatype;
    }

    /**
     * @param mixed $datatype
     * @return void
     */
    public function setDatatype($datatype) {
        $this->datatype = $datatype;
    }

    /**
     *
     * @return boolean
     */
    public function getLocked() {
        return $this->locked;
    }

    /**
     * @param boolean $locked
     */
    public function setLocked($locked) {
        $this->locked = (bool) $locked;
    }

    /**
     * @param boolean $collapsed
     */
    public function setCollapsed($collapsed)
    {
        $this->collapsed = $collapsed;
    }

    /**
     * @return boolean
     */
    public function getCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * @param string $bodyStyle
     */
    public function setBodyStyle($bodyStyle)
    {
        $this->bodyStyle = $bodyStyle;
    }

    /**
     * @return string
     */
    public function getBodyStyle()
    {
        return $this->bodyStyle;
    }

}
