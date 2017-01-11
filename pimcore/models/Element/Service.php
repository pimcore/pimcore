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
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;
use Pimcore\Model\Dependency;
use Pimcore\File;
use Pimcore\Tool;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Element\Dao getDao()
 */
class Service extends Model\AbstractModel
{

    /**
     * @static
     * @param  $element
     * @return string
     */
    public static function getIdPath($element)
    {
        $path = "";

        if ($element instanceof ElementInterface) {
            $elementType = self::getElementType($element);
            $nid = $element->getParentId();
            $ne = self::getElementById($elementType, $nid);
        }

        if ($ne) {
            $path = self::getIdPath($ne, $path);
        }

        if ($element) {
            $path = $path . "/" . $element->getId();
        }

        return $path;
    }

    /**
     * @static
     * @param  $element
     * @return string
     */
    public static function getTypePath($element)
    {
        $path = "";

        if ($element instanceof ElementInterface) {
            $elementType = self::getElementType($element);
            $nid = $element->getParentId();
            $ne = self::getElementById($elementType, $nid);
        }

        if ($ne) {
            $path = self::getTypePath($ne, $path);
        }

        if ($element) {
            $type = $element->getType();
            if ($type != "folder") {
                if ($element instanceof Document) {
                    $type = "document";
                } elseif ($element instanceof Object\AbstractObject) {
                    $type = "object";
                } elseif ($element instanceof Asset) {
                    $type = "asset";
                } else {
                    throw new \Exception("unknown type");
                }
            }
            $path = $path . "/" . $type;
        }

        return $path;
    }



    /**
     * @static
     * @param  $list array | \Pimcore\Model\Listing\AbstractListing
     * @return array
     */
    public static function getIdList($list, $idGetter = 'getId')
    {
        $ids = [];
        if (is_array($list)) {
            foreach ($list as $entry) {
                if (is_object($entry) && method_exists($entry, $idGetter)) {
                    $ids[] = $entry->$idGetter();
                } elseif (is_scalar($entry)) {
                    $ids[] = $entry;
                }
            }
        }

        if ($list instanceof \Pimcore\Model\Listing\AbstractListing) {
            $ids = $list->loadIdList();
        }
        $ids = array_unique($ids);

        return $ids;
    }

    /**
     * @param Dependency $d
     * @return array
     */
    public static function getRequiredByDependenciesForFrontend(Dependency $d)
    {
        $dependencies["hasHidden"] = false;
        $dependencies["requiredBy"] = [];

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
        $dependencies["hasHidden"] = false;
        $dependencies["requires"] = [];

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
     * @param Document|Asset|Object\AbstractObject $element
     * @return array
     */
    public static function getDependencyForFrontend($element)
    {
        if ($element instanceof ElementInterface) {
            return [
                "id" => $element->getId(),
                "path" => $element->getRealFullPath(),
                "type" => self::getElementType($element),
                "subtype" => $element->getType()
            ];
        }
    }

    /**
     * @param array $config
     * @return Object\AbstractObject|Document|Asset
     */
    public static function getDependedElement($config)
    {
        if ($config["type"] == "object") {
            return Object::getById($config["id"]);
        } elseif ($config["type"] == "asset") {
            return Asset::getById($config["id"]);
        } elseif ($config["type"] == "document") {
            return Document::getById($config["id"]);
        }

        return false;
    }


    /**
     * determines whether an element is published
     *
     * @static
     * @param  ElementInterface $element
     * @return bool
     */
    public static function isPublished($element = null)
    {
        if ($element instanceof ElementInterface) {
            if (method_exists($element, "isPublished")) {
                return $element->isPublished();
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @static
     * @param  string $type
     * @param  string $path
     * @return ElementInterface
     */
    public static function getElementByPath($type, $path)
    {
        if ($type == "asset") {
            $element = Asset::getByPath($path);
        } elseif ($type == "object") {
            $element = Object::getByPath($path);
        } elseif ($type == "document") {
            $element = Document::getByPath($path);
        }

        return $element;
    }


    /**
     * Returns a uniqe key for the element in the $target-Path (recursive)
     * @static
     * @return ElementInterface|string
     * @param string $type
     * @param string $sourceKey
     * @param ElementInterface $target
     */
    public static function getSaveCopyName($type, $sourceKey, $target)
    {
        if (self::pathExists($target->getRealFullPath() . "/" . $sourceKey, $type)) {
            // only for assets: add the prefix _copy before the file extension (if exist) not after to that source.jpg will be source_copy.jpg and not source.jpg_copy
            if ($type == "asset" && $fileExtension = File::getFileExtension($sourceKey)) {
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
    public static function pathExists($path, $type = null)
    {
        if ($type == "asset") {
            return Asset\Service::pathExists($path);
        } elseif ($type == "document") {
            return Document\Service::pathExists($path);
        } elseif ($type == "object") {
            return Object\Service::pathExists($path);
        }

        return;
    }


    /**
     * @static
     * @param  string $type
     * @param  int $id
     * @return ElementInterface
     */
    public static function getElementById($type, $id)
    {
        $element = null;
        if ($type == "asset") {
            $element = Asset::getById($id);
        } elseif ($type == "object") {
            $element = Object::getById($id);
        } elseif ($type == "document") {
            $element = Document::getById($id);
        }

        return $element;
    }

    /**
     * @static
     * @param  ElementInterface $element $element
     * @return string
     */
    public static function getElementType($element)
    {
        $type = null;
        if ($element instanceof Object\AbstractObject) {
            $type = "object";
        } elseif ($element instanceof Document) {
            $type = "document";
        } elseif ($element instanceof Asset) {
            $type = "asset";
        }

        return $type;
    }

    /**
     * determines the type of an element (object,asset,document)
     *
     * @static
     * @param  ElementInterface $element
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
     * @param  ElementInterface $element
     * @return void
     */
    public static function scheduleForSanityCheck($element)
    {
        $type = self::getElementType($element);
        $sanityCheck = new Sanitycheck($element->getId(), $type);
        $sanityCheck->save();
    }

    /**
     *
     */
    public static function runSanityCheck()
    {
        $sanityCheck = Sanitycheck::getNext();
        $count = 0;
        while ($sanityCheck) {
            $count++;
            if ($count % 10 == 0) {
                \Pimcore::collectGarbage();
            }

            $element = self::getElementById($sanityCheck->getType(), $sanityCheck->getId());
            if ($element) {
                try {
                    self::performSanityCheck($element);
                } catch (\Exception $e) {
                    Logger::error("Element\\Service: sanity check for element with id [ " . $element->getId() . " ] and type [ " . self::getType($element) . " ] failed");
                }
                $sanityCheck->delete();
            } else {
                $sanityCheck->delete();
            }
            $sanityCheck = Sanitycheck::getNext();

            // reduce load on server
            Logger::debug("Now timeout for 3 seconds");
            sleep(3);
        }
    }

    /**
     * @static
     * @param  ElementInterface $element
     * @return void
     */
    protected static function performSanityCheck($element)
    {
        if ($latestVersion = $element->getLatestVersion()) {
            if ($latestVersion->getDate() > $element->getModificationDate()) {
                return;
            }
        }

        $element->setUserModification(0);
        $element->save();

        if ($version = $element->getLatestVersion(true)) {
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
        $properties = [];
        foreach ($props as $key => $p) {

            //$p = object2array($p);
            $allowedProperties = [
                "key",
                "o_key",
                "filename",
                "path",
                "o_path",
                "id",
                "o_id",
                "o_type",
                "type"
            ];

            if ($p->getData() instanceof Document || $p->getData() instanceof Asset || $p->getData() instanceof Object\AbstractObject) {
                $pa = [];

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
            } else {
                $properties[$key] = object2array($p);
            }

            // add config from predefined properties
            if ($p->getName() && $p->getType()) {
                $predefined = Model\Property\Predefined::getByKey($p->getName());

                if ($predefined && $predefined->getType() == $p->getType()) {
                    $properties[$key]["config"] = $predefined->getConfig();
                    $properties[$key]["description"] = $predefined->getDescription();
                }
            }
        }

        return $properties;
    }


    /**
     * @param  ElementInterface $target the parent element
     * @param  ElementInterface $new the newly inserted child
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
                $target->setChilds(array_merge($target->getChilds(), [$new]));
            }
        } else {
            $target->setChilds([$new]);
        }
    }

    /**
     * @param  ElementInterface $element
     * @return array
     */
    public static function gridElementData(ElementInterface $element)
    {
        $data = [
            "id" => $element->getId(),
            "fullpath" => $element->getRealFullPath(),
            "type" => self::getType($element),
            "subtype" => $element->getType(),
            "filename" => self::getFilename($element),
            "creationDate" => $element->getCreationDate(),
            "modificationDate" => $element->getModificationDate()
        ];

        if (method_exists($element, "isPublished")) {
            $data["published"] = $element->isPublished();
        } else {
            $data["published"] = true;
        }

        return $data;
    }


    /**
     * @param ElementInterface $element
     * @return string
     */
    public static function getFilename(ElementInterface $element)
    {
        if ($element instanceof Document || $element instanceof Object\AbstractObject) {
            return $element->getKey();
        } elseif ($element instanceof Asset) {
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
        if ($user->isAdmin()) {
            return [];
        }

        // get workspaces
        $workspaces = $user->{"getWorkspaces".ucfirst($type)}();
        foreach ($user->getRoles() as $roleId) {
            $role = Model\User\Role::getById($roleId);
            $workspaces = array_merge($workspaces, $role->{"getWorkspaces".ucfirst($type)}());
        }

        $forbidden = [];
        if (count($workspaces) > 0) {
            foreach ($workspaces as $workspace) {
                if (!$workspace->getList()) {
                    $forbidden[] = $workspace->getCpath();
                }
            }
        } else {
            $forbidden[] = "/";
        }

        return $forbidden;
    }

    /**
     * renews all references, for example after unserializing an ElementInterface
     * @param  Document|Asset|Object\AbstractObject $data
     * @return mixed
     */
    public static function renewReferences($data, $initial = true)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = self::renewReferences($value, false);
            }

            return $data;
        } elseif (is_object($data)) {
            if ($data instanceof ElementInterface && !$initial) {
                return self::getElementById(self::getElementType($data), $data->getId());
            } else {

                // if this is the initial element set the correct path and key
                if ($data instanceof ElementInterface && $initial) {
                    $originalElement = self::getElementById(self::getElementType($data), $data->getId());

                    if ($originalElement) {
                        if ($data instanceof Asset) {
                            $data->setFilename($originalElement->getFilename());
                        } elseif ($data instanceof Document) {
                            $data->setKey($originalElement->getKey());
                        } elseif ($data instanceof Object\AbstractObject) {
                            $data->setKey($originalElement->getKey());
                        }

                        if (!Object\AbstractObject::doNotRestoreKeyAndPath()) {
                            $data->setPath($originalElement->getRealPath());
                        }
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
    public static function correctPath($path)
    {
        // remove trailing slash
        if ($path != "/") {
            $path = rtrim($path, "/ ");
        }

        // correct wrong path (root-node problem)
        $path = str_replace("//", "/", $path);

        if (strpos($path, "%") !== false) {
            $path = rawurldecode($path);
        }

        return $path;
    }

    /**
     * @static
     * @param ElementInterface $element
     * @return ElementInterface
     */
    public static function loadAllFields(ElementInterface $element)
    {
        if ($element instanceof Document) {
            Document\Service::loadAllDocumentFields($element);
        } elseif ($element instanceof Object\Concrete) {
            Object\Service::loadAllObjectFields($element);
        } elseif ($element instanceof Asset) {
            Asset\Service::loadAllFields($element);
        }

        return $element;
    }

    /**
     * clean up broken views which were generated by localized fields, ....
     * when removing a language the view isn't valid anymore
     */
    public function cleanupBrokenViews()
    {
        $this->getDao()->cleanupBrokenViews();
    }

    /** Callback for array_filter function.
     * @param $var value
     * @return bool true if value is accepted
     */
    private static function filterNullValues($var)
    {
        return strlen($var) > 0;
    }

    /**
     * @param $path
     * @param array $options
     * @return Asset\Folder|Document\Folder|Object\Folder
     * @throws \Exception
     */
    public static function createFolderByPath($path, $options = [])
    {
        $calledClass = get_called_class();
        if ($calledClass == __CLASS__) {
            throw new \Exception("This method must be called from a extended class. e.g Asset\\Service, Object\\Service, Document\\Service");
        }

        $type = str_replace('\Service', '', $calledClass);
        $type = "\\" . ltrim($type, "\\");
        $folderType = $type . '\Folder';

        $lastFolder = null;
        $pathsArray = [];
        $parts = explode('/', $path);
        $parts = array_filter($parts, "\\Pimcore\\Model\\Element\\Service::filterNullValues");

        $sanitizedPath = "/";
        foreach ($parts as $part) {
            $sanitizedPath = $sanitizedPath . self::getValidKey($part, $type) . "/";
        }

        if (!($foundElement = $type::getByPath($sanitizedPath))) {
            foreach ($parts as $part) {
                $pathsArray[] = $pathsArray[count($pathsArray) - 1] . '/' . self::getValidKey($part, $type);
            }

            for ($i = 0; $i < count($pathsArray); $i++) {
                $currentPath = $pathsArray[$i];
                if (!($type::getByPath($currentPath) instanceof $type)) {
                    $parentFolderPath = ($i ==0) ? '/' : $pathsArray[$i - 1];

                    $parentFolder = $type::getByPath($parentFolderPath);

                    $folder = new $folderType();
                    $folder->setParent($parentFolder);
                    if ($parentFolder) {
                        $folder->setParentId($parentFolder->getId());
                    } else {
                        $folder->setParentId(1);
                    }

                    $key = substr($currentPath, strrpos($currentPath, '/') + 1, strlen($currentPath));

                    if (method_exists($folder, 'setKey')) {
                        $folder->setKey($key);
                    }

                    if (method_exists($folder, 'setFilename')) {
                        $folder->setFilename($key);
                    }

                    if (method_exists($folder, 'setType')) {
                        $folder->setType('folder');
                    }

                    $folder->setPath($currentPath);
                    $folder->setUserModification(0);
                    $folder->setUserOwner(1);
                    $folder->setCreationDate(time());
                    $folder->setModificationDate(time());
                    $folder->setValues($options);
                    $folder->save();
                    $lastFolder = $folder;
                }
            }
        } else {
            return $foundElement;
        }

        return $lastFolder;
    }

    /** Changes the query according to the custom view config
     * @param $cv array
     * @param $childsList
     */
    public static function addTreeFilterJoins($cv, $childsList)
    {
        if ($cv) {
            $childsList->onCreateQuery(function (\Zend_Db_Select $select) use ($cv, $childsList) {
                $where = $cv["where"];
                if ($where) {
                    $select->where($where);
                }

                $customViewJoins = $cv["joins"];
                if ($customViewJoins) {
                    foreach ($customViewJoins as $joinConfig) {
                        $type = $joinConfig["type"];
                        $method = $type == "left" || $type == "right" ? $method = "join" . ucfirst($type) : "join";
                        $name = $joinConfig["name"];
                        $condition = $joinConfig["condition"];
                        $columns = $joinConfig["columns"];
                        $select->$method($name, $condition, $columns);
                    }
                }

                if ($cv["having"]) {
                    $select->having($cv["having"]);
                };
            });
        }
    }

    public static function getCustomViewById($id)
    {
        $customViews = Tool::getCustomViewConfig();
        if ($customViews) {
            foreach ($customViews as $customView) {
                if ($customView["id"] == $id) {
                    return $customView;
                }
            }
        }
    }

    /**
     * @param $key
     * @param null $type
     * @return mixed|string
     */
    public static function getValidKey($key, $type)
    {
        $results = \Pimcore::getEventManager()->trigger("system.service.preGetValidKey", null, [
            "key" => $key,
            "type" => $type
        ]);

        if ($results->count()) {
            $key = $results->last();
        }

        $key = \Pimcore\Tool\Transliteration::toASCII($key);

        if ($type == "document") {
            // no spaces for documents / clean URLs
            $key = preg_replace('/[^a-zA-Z0-9\-\.~_]+/', '-', $key);
        } else {
            // assets & objects including spaces
            $key = preg_replace('/[^a-zA-Z0-9\-\.~_ ]+/', '-', $key);
        }

        if ($type == "asset") {
            // keys shouldn't start with a "." (=hidden file) *nix operating systems
            // keys shouldn't end with a "." - Windows issue: filesystem API trims automatically . at the end of a folder name (no warning ... et al)
            $key = trim($key, ". ");
        } else {
            $key = trim($key);
            $key = ltrim($key, ".");
        }

        return $key;
    }

    /**
     * @param $key
     * @param $type
     * @return bool
     */
    public static function isValidKey($key, $type)
    {
        return (self::getValidKey($key, $type) == $key);
    }

    /**
     * returns a unique key for an element
     *
     * @param $element
     * @return string
     */
    public static function getUniqueKey($element)
    {
        if ($element instanceof Object\AbstractObject) {
            return Object\Service::getUniqueKey($element);
        } elseif ($element instanceof Document) {
            return Document\Service::getUniqueKey($element);
        } elseif ($element instanceof Asset) {
            return Asset\Service::getUniqueKey($element);
        }
    }

    public static function fixAllowedTypes($data, $type)
    {
        // this is the new method with Ext.form.MultiSelect
        if ((is_string($data) && !empty($data)) || (\Pimcore\Tool\Admin::isExtJS6() && is_array($data) && count($data))) {
            if (!\Pimcore\Tool\Admin::isExtJS6()) {
                $parts = explode(",", $data);
                $data = [];
                foreach ($parts as $elementType) {
                    $data[] = [$type => $elementType];
                }
            } else {
                $first = reset($data);
                if (!is_array($first)) {
                    $parts = $data;
                    $data = [];
                    foreach ($parts as $elementType) {
                        $data[] = [$type => $elementType];
                    }
                } else {
                    $newList = [];
                    foreach ($data as $key => $item) {
                        if ($item) {
                            if (is_array($item)) {
                                foreach ($item as $itemKey => $itemValue) {
                                    if ($itemValue) {
                                        $newList[$key][$itemKey] = $itemValue;
                                    }
                                }
                            } elseif ($item) {
                                $newList[$key] = $item;
                            }
                        }
                    }

                    $data = $newList;
                }
            }
        }

        return $data ? $data : [];
    }

    public static function getSafeVersionInfo($versions)
    {
        if (is_array($versions)) {
            $versions = json_decode(json_encode($versions), true);
            $result = [];
            foreach ($versions as $version) {
                $name = $version["user"]["name"];
                $id = $version["user"]["id"];
                unset($version["user"]);
                $version["user"]["name"] = $name;
                $version["user"]["id"] = $id;


                $result[] = $version;
            }

            return $result;
        }
    }
}
