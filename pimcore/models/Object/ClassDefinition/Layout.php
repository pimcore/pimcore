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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\Element;

class Layout
{
    use Model\Object\ClassDefinition\Helper\VarExport, Element\ChildsCompatibilityTrait;

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
    public $childs = [];

    /**
     * @var boolean
     */
    public $locked;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return boolean
     */
    public function getCollapsible()
    {
        return $this->collapsible;
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
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
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $region
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param integer $width
     * @return $this
     */
    public function setWidth($width)
    {
        if (!empty($width) && is_numeric($width)) {
            $this->width = intval($width);
        } else {
            $this->width = $width;
        }

        return $this;
    }

    /**
     * @param integer $height
     * @return $this
     */
    public function setHeight($height)
    {
        if (!empty($height) && is_numeric($height)) {
            $this->height = intval($height);
        } else {
            $this->height = $height;
        }

        return $this;
    }

    /**
     * @param boolean $collapsible
     * @return $this
     */
    public function setCollapsible($collapsible)
    {
        $this->collapsible = (bool) $collapsible;

        $this->filterCollapsibleValue();

        return $this;
    }

    /**
     * @param array $permissions
     * @return $this
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->childs;
    }

    /**
     * @param array $children
     * @return $this
     */
    public function setChildren($children)
    {
        $this->childs = $children;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasChildren()
    {
        if (is_array($this->childs) && count($this->childs) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param mixed $child
     */
    public function addChild($child)
    {
        $this->childs[] = $child;
    }

    /**
     * @param mixed $data
     * @param array $blockedKeys
     * @return $this
     */
    public function setValues($data = [], $blockedKeys = [])
    {
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
    public function getDatatype()
    {
        return $this->datatype;
    }

    /**
     * @param mixed $datatype
     * @return $this
     */
    public function setDatatype($datatype)
    {
        $this->datatype = $datatype;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * @param $locked
     * @return $this
     */
    public function setLocked($locked)
    {
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

        $this->filterCollapsibleValue();

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

    /**
     * @return Layout
     */
    protected function filterCollapsibleValue()
    {
        //if class definition set as collapsed the code below forces collapsible, issue: #778
        $this->collapsible = $this->getCollapsed() || $this->getCollapsible();

        return $this;
    }

    /** Override point for Enriching the layout definition before the layout is returned to the admin interface.
     * @param $object Model\Object\Concrete
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
    }
}
