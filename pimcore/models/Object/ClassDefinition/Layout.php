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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Model;

class Layout {

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
        return $this;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $region
     * @return void
     */
    public function setRegion($region) {
        $this->region = $region;
        return $this;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
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
        return $this;
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
        return $this;
    }

    /**
     * @param boolean $collapsible
     * @return void
     */
    public function setCollapsible($collapsible) {
        $this->collapsible = (bool) $collapsible;
        return $this;
    }

    /**
     * @param array $permissions
     * @return void
     */
    public function setPermissions($permissions) {
        $this->permissions = $permissions;
        return $this;
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
        return $this;
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
        return $this;
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
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function getLocked() {
        return $this->locked;
    }

    /**
     * @param $locked
     * @return $this
     */
    public function setLocked($locked) {
        $this->locked = (bool) $locked;
        return $this;
    }

    /**
     * @param $collapsed
     * @return $this
     */
    public function setCollapsed($collapsed)
    {
        $this->collapsed = $collapsed;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * @param $bodyStyle
     * @return $this
     */
    public function setBodyStyle($bodyStyle)
    {
        $this->bodyStyle = $bodyStyle;
        return $this;
    }

    /**
     * @return string
     */
    public function getBodyStyle()
    {
        return $this->bodyStyle;
    }

}
