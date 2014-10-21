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
 * @package    Element
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

abstract class AbstractElement extends Model\AbstractModel implements ElementInterface {


    /**
     * Get specific property data or the property object itself ($asContainer=true) by its name, if the
     * property doesn't exists return null
     * @param string $name
     * @param bool $asContainer
     * @return mixed
     */
    public function getProperty($name, $asContainer = false) {
        $properties = $this->getProperties();
        if ($this->hasProperty($name)) {
            if($asContainer) {
                return $properties[$name];
            } else {
                return $properties[$name]->getData();
            }
        }
        return null;
    }

    /**
     * @param  $name
     * @return bool
     */
    public function hasProperty ($name) {
        $properties = $this->getProperties();
        return array_key_exists($name, $properties);
    }

    /**
     * @param  $name
     */
    public function removeProperty ($name) {
        $properties = $this->getProperties();
        unset($properties[$name]);
        $this->setProperties($properties);
    }

    /**
     * get the cache tag for the element
     *
     * @return Dependency
     */
    public function getCacheTag() {
        $elementType = Service::getElementType($this);
        return $elementType . "_" . $this->getId();
    }

    /**
     * Get the cache tags for the element, resolve all dependencies to tag the cache entries
     * This is necessary to update the cache if there is a change in an depended object
     *
     * @param array $tags
     * @return array
     */
    public function getCacheTags($tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        $tags[$this->getCacheTag()] = $this->getCacheTag();
        return $tags;
    }

    /**
     * Resolves the dependencies of the element and returns an array of them - Used by update()
     *
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();

        // check for properties
        if (method_exists($this, "getProperties")) {
            $properties = $this->getProperties();
            foreach ($properties as $property) {
                $dependencies = array_merge($dependencies, $property->resolveDependencies());
            }
        }

        return $dependencies;
    }

    /**
     * Returns true if the element is locked
     * @return bool
     */
    public function isLocked(){
        if($this->getLocked()) {
            return true;
        }

        // check for inherited
        return $this->getResource()->isLocked();
    }

    /**
     * @return array
     */
    public function getUserPermissions () {

        $elementType = Service::getElementType($this);
        $vars = get_class_vars("\\Pimcore\\Model\\User\\Workspace\\" . ucfirst($elementType));
        $ignored = array("userId","cid","cpath","resource");
        $permissions = array();

        foreach ($vars as $name => $defaultValue) {
            if(!in_array($name, $ignored)) {
                $permissions[$name] = $this->isAllowed($name);
            }
        }

        return $permissions;
    }

    /**
     * This is used for user-permissions, pass a permission type (eg. list, view, save) an you know if the current user is allowed to perform the requested action
     *
     * @param string $type
     * @return boolean
     */
    public function isAllowed($type) {

        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();
        //everything is allowed for admin
        if ($currentUser->isAdmin()) {
            return true;
        }

        return $this->getResource()->isAllowed($type, $currentUser);
    }

    /**
     *
     */
    public function unlockPropagate() {
        $type = Service::getType($this);
        $ids = $this->getResource()->unlockPropagate();

        // invalidate cache items
        foreach ($ids as $id) {
            $element = Service::getElementById($type, $id);
            if($element) {
                $element->clearDependentCache();
            }
        }
    }

    /**
     * Inverted hasChilds()
     *
     * @return boolean
     */
    public function hasNoChilds() {
        return !$this->hasChilds();
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->getFullPath();
    }
}
