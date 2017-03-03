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

namespace Pimcore\Model\Object\ClassDefinition\Data\Relations;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Element;
use Pimcore\Db;
use Pimcore\Tool\Admin;
use Pimcore\Logger;

abstract class AbstractRelations extends Model\Object\ClassDefinition\Data
{

    /**
     * @var bool
     */
    public static $remoteOwner = false;


    /**
     * @var boolean
     */
    public $lazyLoading;

    /**
     * Set of allowed classes
     *
     * @var array
     */
    public $classes;

    /** Optional path formatter class
     * @var null|string
     */
    public $pathFormatterClass;

    /**
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @param array $classes
     * @return $this
     */
    public function setClasses($classes)
    {
        $this->classes = Element\Service::fixAllowedTypes($classes, "classes");

        return $this;
    }


    /**
     * @return boolean
     */
    public function getLazyLoading()
    {
        return $this->lazyLoading;
    }

    /**
     * @param  $lazyLoading
     * @return $this
     */
    public function setLazyLoading($lazyLoading)
    {
        $this->lazyLoading = $lazyLoading;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRemoteOwner()
    {
        return self::$remoteOwner;
    }

    /**
     *
     * Checks if an object is an allowed relation
     * @param Model\Object\AbstractObject $object
     * @return boolean
     */
    protected function allowObjectRelation($object)
    {
        $allowedClasses = $this->getClasses();
        $allowed = true;
        if (!$this->getObjectsAllowed()) {
            $allowed = false;
        } elseif ($this->getObjectsAllowed() and is_array($allowedClasses) and count($allowedClasses) > 0) {
            //check for allowed classes
            if ($object instanceof Object\Concrete) {
                $classname = $object->getClassName();
                foreach ($allowedClasses as $c) {
                    $allowedClassnames[] = $c['classes'];
                }
                if (!in_array($classname, $allowedClassnames)) {
                    $allowed = false;
                }
            } else {
                $allowed = false;
            }
        } else {
            //don't check if no allowed classes set
        }

        if ($object instanceof Object\AbstractObject) {
            Logger::debug("checked object relation to target object [" . $object->getId() . "] in field [" . $this->getName() . "], allowed:" . $allowed);
        } else {
            Logger::debug("checked object relation to target in field [" . $this->getName() . "], not allowed, target ist not an object");
            Logger::debug($object);
        }

        return $allowed;
    }

    /**
     *
     * Checks if an asset is an allowed relation
     * @param Model\Asset $asset
     * @return boolean
     */
    protected function allowAssetRelation($asset)
    {
        $allowedAssetTypes = $this->getAssetTypes();
        $allowedTypes = [];
        $allowed = true;
        if (!$this->getAssetsAllowed()) {
            $allowed = false;
        } elseif ($this->getAssetsAllowed() and  is_array($allowedAssetTypes) and count($allowedAssetTypes) > 0) {
            //check for allowed asset types
            foreach ($allowedAssetTypes as $t) {
                if (is_array($t) && array_key_exists("assetTypes", $t)) {
                    $t = $t["assetTypes"];
                }

                if ($t) {
                    if (is_string($t)) {
                        $allowedTypes[] = $t;
                    } elseif (is_array($t) && count($t) > 0) {
                        if (isset($t["assetTypes"])) {
                            $allowedTypes []= $t["assetTypes"];
                        } else {
                            $allowedTypes[]= $t;
                        }
                    }
                }
            }
            if (!in_array($asset->getType(), $allowedTypes)) {
                $allowed = false;
            }
        } else {
            //don't check if no allowed asset types set
        }

        Logger::debug("checked object relation to target asset [" . $asset->getId() . "] in field [" . $this->getName() . "], allowed:" . $allowed);

        return $allowed;
    }

    /**
     *
     * Checks if an document is an allowed relation
     * @param Document $document
     * @return boolean
     */
    protected function allowDocumentRelation($document)
    {
        $allowedDocumentTypes = $this->getDocumentTypes();

        $allowed = true;
        if (!$this->getDocumentsAllowed()) {
            $allowed = false;
        } elseif ($this->getDocumentsAllowed() and  is_array($allowedDocumentTypes) and count($allowedDocumentTypes) > 0) {
            //check for allowed asset types
            $allowedTypes = [];
            foreach ($allowedDocumentTypes as $t) {
                if ($t['documentTypes']) {
                    $allowedTypes[] = $t['documentTypes'];
                }
            }

            if (!in_array($document->getType(), $allowedTypes) && count($allowedTypes)) {
                $allowed = false;
            }
        } else {
            //don't check if no allowed document types set
        }

        Logger::debug("checked object relation to target document [" . $document->getId() . "] in field [" . $this->getName() . "], allowed:" . $allowed);

        return $allowed;
    }

    /** Enrich relation with type-specific data.
     * @param $object
     * @param $params
     * @param $classId
     * @param array $relation
     */
    protected function enrichRelation($object, $params, &$classId, &$relation = [])
    {
        if (!$relation) {
            $relation = [];
        }

        if ($object instanceof Object\Concrete) {
            $relation["src_id"] = $object->getId();
            $relation["ownertype"] = "object";

            $classId = $object->getClassId();
        } elseif ($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $relation["src_id"] = $object->getObject()->getId(); // use the id from the object, not from the field collection
            $relation["ownertype"] = "fieldcollection";
            $relation["ownername"] = $object->getFieldname();
            $relation["position"] = $object->getIndex();

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof Object\Localizedfield) {
            $relation["src_id"] = $object->getObject()->getId();
            $relation["ownertype"] = "localizedfield";
            $relation["ownername"] = "localizedfield";
            $context = $object->getContext();
            if ($context && $context["containerType"] == "fieldcollection") {
                $fieldname = $context["fieldname"];
                $index = $context["index"];
                $relation["ownername"] = "/fieldcollection~" . $fieldname . "/" . $index . "/localizedfield~" . $relation["ownername"];
            }

            $relation["position"] = $params["language"];

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $relation["src_id"] = $object->getObject()->getId();
            $relation["ownertype"] = "objectbrick";
            $relation["ownername"] = $object->getFieldname();
            $relation["position"] = $object->getType();

            $classId = $object->getObject()->getClassId();
        }
    }

    /**
     * @param $object
     * @param array $params
     * @throws \Exception
     */
    public function save($object, $params = [])
    {
        $db = Db::get();

        $data = $this->getDataFromObjectParam($object, $params);
        $relations = $this->getDataForResource($data, $object, $params);

        if (is_array($relations) && !empty($relations)) {
            foreach ($relations as $relation) {
                $this->enrichRelation($object, $params, $classId, $relation);


                /*relation needs to be an array with src_id, dest_id, type, fieldname*/
                try {
                    $db->insert("object_relations_" . $classId, $relation);
                } catch (\Exception $e) {
                    Logger::error("It seems that the relation " . $relation["src_id"] . " => " . $relation["dest_id"]
                        . " (fieldname: " . $this->getName() . ") already exist -> please check immediately!");
                    Logger::error($e);

                    // try it again with an update if the insert fails, shouldn't be the case, but it seems that
                    // sometimes the insert throws an exception

                    throw $e;
                }
            }
        }
    }

    /**
     * @param $object
     * @param array $params
     * @return null
     */
    public function load($object, $params = [])
    {
        $db = Db::get();
        $data = null;

        if ($object instanceof Object\Concrete) {
            if (!method_exists($this, "getLazyLoading") or !$this->getLazyLoading() or (array_key_exists("force", $params) && $params["force"])) {
                $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'object'", [$object->getId(), $this->getName()]);
            } else {
                return null;
            }
        } elseif ($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'fieldcollection' AND ownername = ? AND position = ?", [$object->getObject()->getId(), $this->getName(), $object->getFieldname(), $object->getIndex()]);
        } elseif ($object instanceof Object\Localizedfield) {
            if (isset($params["context"])&& $params["context"]["containerType"] == "fieldcollection") {
                $context = $params["context"];
                $fieldname = $context["fieldname"];
                $index = $context["index"];
                $filter = "/fieldcollection~" . $fieldname . "/" . $index . "/%";
                $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'localizedfield'  AND position = ? AND ownername LIKE ?",
                    [$object->getObject()->getId(), $this->getName(), $params["language"], $filter]);
            } else {
                $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'localizedfield' AND ownername = 'localizedfield' AND position = ?", [$object->getObject()->getId(), $this->getName(), $params["language"]]);
            }
        } elseif ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'objectbrick' AND ownername = ? AND position = ?", [$object->getObject()->getId(), $this->getName(), $object->getFieldname(), $object->getType()]);

            // THIS IS KIND A HACK: it's necessary because of this bug PIMCORE-1454 and therefore cannot be removed
            if (count($relations) < 1) {
                $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'objectbrick' AND ownername = ? AND (position IS NULL OR position = '')", [$object->getObject()->getId(), $this->getName(), $object->getFieldname()]);
            }
            // HACK END
        }

        // using PHP sorting to order the relations, because "ORDER BY index ASC" in the queries above will cause a
        // filesort in MySQL which is extremely slow especially when there are millions of relations in the database
        usort($relations, function ($a, $b) {
            if ($a["index"] == $b["index"]) {
                return 0;
            }

            return ($a["index"] < $b["index"]) ? -1 : 1;
        });

        $data = $this->getDataFromResource($relations, $object, $params);

        return $data;
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param mixed $data
     * @param array $idMapping
     * @return array
     */
    public function rewriteIdsService($data, $idMapping)
    {
        if (is_array($data)) {
            foreach ($data as &$element) {
                $id = $element->getId();
                $type = Element\Service::getElementType($element);

                if (array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                    $element = Element\Service::getElementById($type, $idMapping[$type][$id]);
                }
            }
        }

        return $data;
    }

    /**
     * @return null|string
     */
    public function getPathFormatterClass()
    {
        return $this->pathFormatterClass;
    }

    /**
     * @param null|string $pathFormatterClass
     */
    public function setPathFormatterClass($pathFormatterClass)
    {
        $this->pathFormatterClass = $pathFormatterClass;
    }

    /**
     * @param $object
     * @param mixed $params
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return "";
    }
}
