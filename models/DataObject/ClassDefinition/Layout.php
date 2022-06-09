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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\Element;

class Layout implements Model\DataObject\ClassDefinition\Data\VarExporterInterface
{
    use Model\DataObject\ClassDefinition\Helper\VarExport {
        __set_state as private _VarExport__set_state;
    }
    use Element\ChildsCompatibilityTrait;

    /**
     * @internal
     *
     * @var string
     */
    public $name;

    /**
     * @internal
     *
     * @var string
     */
    public $type;

    /**
     * @internal
     *
     * @var string
     */
    public $region;

    /**
     * @internal
     *
     * @var string
     */
    public $title;

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * @internal
     *
     * @var bool
     */
    public $collapsible = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $collapsed = false;

    /**
     * @internal
     *
     * @var string
     */
    public $bodyStyle;

    /**
     * @internal
     *
     * @var string
     */
    public $datatype = 'layout';

    /**
     * @internal
     *
     * @var array
     */
    public $permissions;

    /**
     * @internal
     *
     * @var array
     */
    public $children = [];

    /**
     * @internal
     *
     * @var bool
     */
    public $locked = false;

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
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return bool
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
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $region
     *
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @param bool $collapsible
     *
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
     *
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
        return $this->children;
    }

    /**
     * @internal
     *
     * @return array
     */
    public function &getChildrenByRef()
    {
        return $this->children;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        if (is_array($this->children) && count($this->children) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param Data|Layout $child
     */
    public function addChild($child)
    {
        $this->children[] = $child;
    }

    /**
     * @param array $data
     * @param array $blockedKeys
     *
     * @return $this
     */
    public function setValues($data = [], $blockedKeys = [])
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $blockedKeys)) {
                $method = 'set' . ucfirst($key);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDatatype()
    {
        return $this->datatype;
    }

    /**
     * @param string $datatype
     *
     * @return $this
     */
    public function setDatatype($datatype)
    {
        $this->datatype = $datatype;

        return $this;
    }

    /**
     *
     * @return bool
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     *
     * @return $this
     */
    public function setLocked($locked)
    {
        $this->locked = (bool) $locked;

        return $this;
    }

    /**
     * @param bool $collapsed
     *
     * @return $this
     */
    public function setCollapsed($collapsed)
    {
        $this->collapsed = (bool) $collapsed;

        $this->filterCollapsibleValue();

        return $this;
    }

    /**
     * @return bool
     */
    public function getCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * @param string $bodyStyle
     *
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

    /**
     * @return array
     */
    public function getBlockedVarsForExport(): array
    {
        return ['blockedVarsForExport', 'childs'];
    }

    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        foreach ($this->getBlockedVarsForExport() as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    public static function __set_state($data)
    {
        $obj = new static();
        $obj->setValues($data);

        $obj->childs = $obj->children;  // @phpstan-ignore-line

        return $obj;
    }
}
