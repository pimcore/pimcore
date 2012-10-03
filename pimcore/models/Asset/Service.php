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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_Service extends Element_Service {

    /**
     * @var User
     */
    protected $_user;
    /**
     * @var array
     */
    protected $_copyRecursiveIds;

    /**
     * @param  User $user
     * @return void
     */
    public function __construct($user = null) {
        $this->_user = $user;
    }

    /**
     * @param  Asset $target
     * @param  Asset $source
     * @return Asset copied asset
     */
    public function copyRecursive($target, $source) {

        // avoid recursion
        if (!$this->_copyRecursiveIds) {
            $this->_copyRecursiveIds = array();
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return;
        }

        $source->getProperties();
        if (!$source instanceof Asset_Folder) {
            $source->getData();
        }


        $new = clone $source;
        $new->id = null;
        if($new instanceof Asset_Folder){
            $new->setChilds(null);
        }

        $new->setFilename(Element_Service::getSaveCopyName("asset", $new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChilds() as $child) {
            $this->copyRecursive($new, $child);
        }

        if($target instanceof Asset_Folder){
            $this->updateChilds($target,$new);
        }


        return $new;
    }

    /**
     * @param  Asset $target
     * @param  Asset $source
     * @return Asset copied asset
     */
    public function copyAsChild($target, $source) {

        $source->getProperties();
        if (!$source instanceof Asset_Folder) {
            $source->getData();
        }

        $new = clone $source;
        $new->id = null;

        if($new instanceof Asset_Folder){
            $new->setChilds(null);
        }
        $new->setFilename(Element_Service::getSaveCopyName("asset", $new->getFilename(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->save();

        if($target instanceof Asset_Folder){
            $this->updateChilds($target,$new);
        }

        return $new;
    }

    /**
     * @param  Asset $target
     * @param  Asset $source
     * @return Asset the modified asset
     */
    public function copyContents($target, $source) {

        // check if the type is the same
        if (get_class($source) != get_class($target)) {
            throw new Exception("Source and target have to be the same type");
        }

        if (!$source instanceof Asset_Folder) {
            $target->setData($source->getData());
            $target->setCustomSettings($source->getCustomSettings());
        }

        $target->setUserModification($this->_user->getId());
        $target->setProperties($source->getProperties());
        $target->save();

        return $target;
    }


    /**
     * @param  Asset $asset
     * @return void
     */
    public static function gridAssetData($asset) {

        $data = Element_Service::gridElementData($asset);

        return $data;
    }

    /**
     * @static
     * @param $path
     * @return bool
     */
    public static function pathExists ($path, $type = null) {

        $path = Element_Service::correctPath($path);

        try {
            $asset = new Asset();

            if (Pimcore_Tool::isValidPath($path)) {
                $asset->getResource()->getByPath($path);
                return true;
            }
        }
        catch (Exception $e) {

        }

        return false;
    }

    /**
     * @static
     * @param Element_Interface $element
     * @return Element_Interface
     */
    public static function loadAllFields (Element_Interface $element) {
        if($element instanceof Asset && method_exists($element, "getData")) {
            $element->setData(null);
            $element->getData();
        }

        return $element;
    }
}