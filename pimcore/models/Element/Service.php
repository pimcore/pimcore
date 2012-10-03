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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Service extends Pimcore_Model_Abstract {

    /**
     * @param Dependency $d
     * @return array
     */
    public static function getRequiredByDependenciesForFrontend(Dependency $d)
    {

        $user = Zend_Registry::get("pimcore_user");
        $dependencies["hasHidden"] = false;
        $dependencies["requiredBy"] = array();

        // requiredBy
        foreach ($d->getRequiredBy() as $r) {
            if ($e = self::getDependedElement($r)) {
                if ($e->isAllowed("list")) {
                    $dependencies["requiredBy"][] = self::getDependencyForFrontend($e);
                } else {
                    $dependencies["hasHidden"] = true;
                }
            }
        }
        return $dependencies;
    }

    /**
     * @param Dependency $d
     * @return array
     */
    public static function getRequiresDependenciesForFrontend(Dependency $d)
    {

        $user = Zend_Registry::get("pimcore_user");
        $dependencies["hasHidden"] = false;
        $dependencies["requires"] = array();

        // requires
        foreach ($d->getRequires() as $r) {
            if ($e = self::getDependedElement($r)) {
                if ($e->isAllowed("list")) {
                    $dependencies["requires"][] = self::getDependencyForFrontend($e);
                } else {
                    $dependencies["hasHidden"] = true;
                }

            }
        }

        return $dependencies;
    }

    /**
     * @param Document|Asset|Object_Abstract $element
     * @return array
     */
    public static function getDependencyForFrontend($element)
    {
        if ($element instanceof Document) {
            return array(
                "id" => $element->getId(),
                "path" => $element->getFullPath(),
                "type" => "document",
                "subtype" => $element->getType()
            );
        }
        else if ($element instanceof Asset) {
            return array(
                "id" => $element->getId(),
                "path" => $element->getFullPath(),
                "type" => "asset",
                "subtype" => $element->getType()
            );
        }
        else if ($element instanceof Object_Abstract) {
            return array(
                "id" => $element->getId(),
                "path" => $element->getFullPath(),
                "type" => "object",
                "subtype" => $element->geto_Type()
            );
        }
    }

    /**
     * @param array $config
     * @return Object_Abstract|Document|Asset
     */
    public static function getDependedElement($config)
    {

        if ($config["type"] == "object") {
            return Object_Abstract::getById($config["id"]);
        }
        else if ($config["type"] == "asset") {
            return Asset::getById($config["id"]);
        }
        else if ($config["type"] == "document") {
            return Document::getById($config["id"]);
        }

        return false;
    }


    /**
     * determines whether an element is published
     *
     * @static
     * @param  Element_Interface $element
     * @return bool
     */
    public static function isPublished($element = null)
    {

        if ($element instanceof Element_Interface) {
            if (method_exists($element, "isPublished")) {
                return $element->isPublished();
            }
            else {
                return true;
            }
        }
        return false;
    }

    /**
     * @static
     * @param  string $type
     * @param  string $path
     * @return Element_Interface
     */
    public static function getElementByPath($type, $path)
    {
        if ($type == "asset") {
            $element = Asset::getByPath($path);
        } else if ($type == "object") {
            $element = Object_Abstract::getByPath($path);
        } else if ($type == "document") {
            $element = Document::getByPath($path);
        }
        return $element;
    }


    /**
     * Returns a uniqe key for the element in the $target-Path (recursive)
     * @static
     * @return Element_Interface|string
     * @param string $type
     * @param string $sourceKey
     * @param Element_Interface $target
     */
    public static function getSaveCopyName($type, $sourceKey, $target)
    {
        if (self::pathExists($target->getFullPath() . "/" . $sourceKey, $type)) {
            // only for assets: add the prefix _copy before the file extension (if exist) not after to that source.jpg will be source_copy.jpg and not source.jpg_copy
            if($type == "asset" && $fileExtension = Pimcore_File::getFileExtension($sourceKey)) {
                $sourceKey = str_replace("." . $fileExtension, "_copy." . $fileExtension, $sourceKey);
            } else {
                $sourceKey .= "_copy";
            }

            return self::getSaveCopyName($type, $sourceKey, $target);
        }
        return $sourceKey;
    }

    /**
     * @static
     * @param $type
     * @param $path
     * @return bool
     */
    public static function pathExists ($path, $type = null) {
        if($type == "asset") {
            return Asset_Service::pathExists($path);
        } else if ($type == "document") {
            return Document_Service::pathExists($path);
        } else if ($type == "object") {
            return Object_Service::pathExists($path);
        }

        return;
    }


    /**
     * @static
     * @param  string $type
     * @param  int $id
     * @return Element_Interface
     */
    public static function getElementById($type, $id)
    {
        $element = null;
        if ($type == "asset") {
            $element = Asset::getById($id);
        } else if ($type == "object") {
            $element = Object_Abstract::getById($id);
        } else if ($type == "document") {
            $element = Document::getById($id);
        }
        return $element;
    }

    /**
     * @static
     * @param  Element_Interface $element $element
     * @return string
     */
    public static function getElementType($element)
    {
        $type = null;
        if ($element instanceof Object_Abstract) {
            $type = "object";
        } else if ($element instanceof Document) {
            $type = "document";
        } else if ($element instanceof Asset) {
            $type = "asset";
        }
        return $type;
    }

    /**
     * determines the type of an element (object,asset,document)
     *
     * @static
     * @param  Element_Interface $element
     * @return string
     */
    public static function getType($element)
    {
        return self::getElementType($element);
    }

    /**
     * Schedules element with this id for sanity check to be cleaned of broken relations
     *
     * @static
     * @param  Element_Interface $element
     * @return void
     */
    public static function scheduleForSanityCheck($element)
    {

        $type = self::getElementType($element);
        $sanityCheck = new Element_Sanitycheck($element->getId(), $type);
        $sanityCheck->save();


    }

    public static function runSanityCheck() {

        $sanityCheck = Element_Sanitycheck::getNext();
        while ($sanityCheck) {

            $element = self::getElementById($sanityCheck->getType(), $sanityCheck->getId());
            if ($element) {
                try {
                    self::performSanityCheck($element);
                } catch (Exception $e) {
                    Logger::error("Element_Service: sanity check for element with id [ " . $element->getId() . " ] and type [ " . self::getType($element) . " ] failed");
                }
                $sanityCheck->delete();
            } else {
                $sanityCheck->delete();
            }
            $sanityCheck = Element_Sanitycheck::getNext();

            // reduce load on server
            Logger::debug("Now timeout for 3 seconds");
            sleep(3);
        }

    }

    /**
     * @static
     * @param  Element_Interface $element
     * @return void
     */
    protected static function performSanityCheck($element)
    {
        $element->setUserModification(0);
        $element->save();

        if($version = $element->getLatestVersion(true)) {
            $version->setNote("Sanitycheck");
            $version->save();
        }
    }


    /**
     * @static
     * @param  $props
     * @return array
     */
    public static function minimizePropertiesForEditmode($props)
    {

        $properties = array();
        foreach ($props as $key => $p) {

            //$p = object2array($p);
            $allowedProperties = array(
                "key",
                "o_key",
                "filename",
                "path",
                "o_path",
                "id",
                "o_id",
                "o_type",
                "type"
            );

            if ($p->getData() instanceof Document || $p->getData() instanceof Asset || $p->getData() instanceof Object_Abstract) {

                $pa = array();

                $vars = get_object_vars($p->getData());

                foreach ($vars as $k => $value) {
                    if (in_array($k, $allowedProperties)) {
                        $pa[$k] = $p->getData()->$k;
                    }
                }

                // clone it because of caching
                $tmp = clone $p;
                $tmp->setData($pa);
                $properties[$key] = object2array($tmp);
            }
            else {
                $properties[$key] = object2array($p);
            }
        }

        return $properties;
    }


    /** 
     * @param  Element_Interface $target the parent element
     * @param  Element_Interface $new the newly inserted child
     * @return void
     */
    protected function updateChilds($target, $new)
    {

        if (is_array($target->getChilds())) {
            //check in case of recursion
            $found = false;
            foreach ($target->getChilds() as $child) {
                if ($child->getId() == $new->getId()) {
                    $found = true;
                }
            }
            if (!$found) {
                $target->setChilds(array_merge($target->getChilds(), array($new)));
            }
        } else {
            $target->setChilds(array($new));
        }


    }

    /**
     * @param  Element_interface $element
     * @return array
     */
    public static function gridElementData(Element_Interface $element)
    {
        $data = array(
            "id" => $element->getId(),
            "fullpath" => $element->getFullPath(),
            "type" => Element_Service::getType($element),
            "subtype" => $element->getType(),
            "filename" => self::getFilename($element),
            "creationDate" => $element->getCreationDate(),
            "modificationDate" => $element->getModificationDate()
        );

        if (method_exists($element, "isPublished")) {
            $data["published"] = $element->isPublished();
        } else {
            $data["published"] = true;
        }
        return $data;
    }


    /**
     * @param Element_Interface $element
     * @return string
     */
    public static function getFilename(Element_Interface $element)
    {
        if ($element instanceof Document || $element instanceof Object_Abstract) {
            return $element->getKey();
        } else if ($element instanceof Asset) {
            return $element->getFilename();
        }
    }

    /**
     * find all elements which the user may not list and therefore may never be shown to the user
     * @param  string $type asset|object|document
     * @return array
     */
    public static function findForbiddenPaths($type, $user)
    {
        if($user->isAdmin()) {
            return array();
        }

        // get workspaces
        $workspaces = $user->{"getWorkspaces".ucfirst($type)}();
        foreach ($user->getRoles() as $roleId) {
            $role = User_Role::getById($roleId);
            $workspaces = array_merge($workspaces, $role->{"getWorkspaces".ucfirst($type)}());
        }

        $forbidden = array();
        if(count($workspaces) > 0) {
            foreach ($workspaces as $workspace) {
                if(!$workspace->getList()) {
                    $forbidden[] = $workspace->getCpath();
                }
            }
        } else {
            $forbidden[] = "/";
        }

        return $forbidden;
    }

    /**
     * renews all references, for example after unserializing an Element_Interface
     * @param  Document|Asset|Object_Abstract $data
     * @return mixed
     */
    public static function renewReferences($data, $initial = true)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = self::renewReferences($value, false);
            }
            return $data;
        } else if (is_object($data)) {
            if ($data instanceof Element_Interface && !$initial) {
                return Element_Service::getElementById(Element_Service::getElementType($data), $data->getId());
            } else {

                // if this is the initial element set the correct path and key
                if ($data instanceof Element_Interface && $initial) {

                    $originalElement = Element_Service::getElementById(Element_Service::getElementType($data), $data->getId());

                    if ($originalElement) {
                        if ($data instanceof Asset) {
                            $data->setFilename($originalElement->getFilename());
                        } else if ($data instanceof Document) {
                            $data->setKey($originalElement->getKey());
                        } else if ($data instanceof Object_Abstract) {
                            $data->setKey($originalElement->getKey());
                        }

                        $data->setPath($originalElement->getPath());
                    }
                }

                $properties = get_object_vars($data);
                foreach ($properties as $name => $value) {
                    $data->$name = self::renewReferences($value, false);
                }
                return $data;
            }
        }
        return $data;
    }

    /**
     * @static
     * @param string $path
     * @return string
     */
    public static function correctPath ($path) {
        // remove trailing slash
        if($path != "/") {
            $path = rtrim($path,"/ ");
        }

        // correct wrong path (root-node problem)
        $path = str_replace("//", "/", $path);

        return $path;
    }

    /**
     * @static
     * @param Element_Interface $element
     * @return Element_Interface
     */
    public static function loadAllFields (Element_Interface $element) {

        if($element instanceof Document) {
            Document_Service::loadAllDocumentFields($element);
        } else if ($element instanceof Object_Concrete) {
            Object_Service::loadAllObjectFields($element);
        } else if ($element instanceof Asset) {
            Asset_Service::loadAllFields($element);
        }

        return $element;
    }

    /**
     * clean up broken views which were generated by localized fields, ....
     * when removing a language the view isn't valid anymore
     */
    public function cleanupBrokenViews () {

        $this->getResource()->cleanupBrokenViews();
    }
}