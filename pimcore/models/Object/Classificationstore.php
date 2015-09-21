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
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;
use Pimcore\Tool; 

class Classificationstore extends Model\AbstractModel {

    /**
     * @var array
     */
    public $items = array();

    /**
     * @var Model\Object\Concrete
     */
    public $object;

    /**
     * @var Model\Object\ClassDefinition
     */
    public $class;

    /** @var  string */
    public $fieldname;

    /** @var  array */
    public $activeGroups;


    /**
     * @param array $items
     */
    public function __construct($items = null) {
        if($items) {
            $this->setItems($items);
        }
    }

    /**
     * @param  $item
     * @return void
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    /**
     * @param  array $items
     * @return void
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Concrete $object
     * @return void
     */
    public function setObject(Concrete $object)
    {
        $this->object = $object;
        //$this->setClass($this->getObject()->getClass());
        return $this;
    }

    /**
     * @return Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Model\Object\ClassDefinition $class
     * @return void
     */
    public function setClass(ClassDefinition $class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return Model\Object\ClassDefinition
     */
    public function getClass()
    {
        if(!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }
        return $this->class;
    }

    /**
     * @throws \Exception
     * @param null $language
     * @return string
     */
    public function getLanguage ($language = null) {
        if($language) {
            return (string) $language;
        }

        return "default";
    }


    /**
     * @param $name
     * @param $value
     * @param null $language
     * @return void
     */
    public function setLocalizedKeyValue ($groupId, $keyId, $value, $language = null) {

        $language  = $this->getLanguage($language);

        if ($value) {
            $this->items[$groupId][$keyId][$language] = $value;
        } else if (isset($this->items[$groupId][$keyId][$language])) {
            unset($this->items[$groupId][$keyId][$language]);
            if (empty($this->items[$groupId][$keyId])) {
                unset($this->items[$groupId][$keyId]);
                if (empty($this->items[$groupId])) {
                    unset($this->items[$groupId]);
                }
            }
        }
        return $this;
    }

    /** Removes the group with the given id
     * @param $groupId
     */
    public function removeGroupData($groupId) {
        unset($this->items[$groupId]);
    }

    /** Returns an array of
     * @return array
     */
    public function getGroupIdsWithData() {
        return array_keys($this->items);
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @param string $fieldname
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;
    }

    /**
     * @return array
     */
    public function getActiveGroups()
    {
        return $this->activeGroups;
    }

    /**
     * @param array $activeGroups
     */
    public function setActiveGroups($activeGroups)
    {
        $this->activeGroups = $activeGroups;
    }


    protected function getFallbackValue($groupId, $keyId, $language, $fielddefinition) {
        $fallbackLanguages = Tool::getFallbackLanguagesFor($language);
        $data = null;

        foreach ($fallbackLanguages as $l) {
            if(
                array_key_exists($groupId, $this->items)
                &&  array_key_exists($keyId, $this->items[$groupId])
                &&  array_key_exists($l, $this->items[$groupId][$keyId]))
            {
                $data = $this->items[$groupId][$keyId][$l];
                if (!$fielddefinition->isEmpty($data)) {
                    return $data;
                }
            }
        }

        foreach ($fallbackLanguages as $l) {
            $data = $this->getFallbackValue($groupId, $keyId, $l, $fielddefinition);
        }

        return $data;

    }

    /**
     * @param $keyId
     * @param $groupId
     * @param string $language
     * @param bool|false $ignoreFallbackLanguage
     * @return null
     */
    public function getLocalizedKeyValue($groupId, $keyId, $language = "default", $ignoreFallbackLanguage = false) {
        $oid = $this->object->getId();
        \Logger::debug($oid);
        $keyConfig = Model\Object\Classificationstore\DefinitionCache::get($keyId);
        $fieldDefinition =  Model\Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

        $language = $this->getLanguage($language);
        $data = null;

        if(array_key_exists($groupId, $this->items)  && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($language, $this->items[$groupId][$keyId])
            )  {
            $data = $this->items[$groupId][$keyId][$language];
        }

        // check for fallback value
        if($fieldDefinition->isEmpty($data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            $data = $this->getFallbackValue($groupId, $keyId, $language, $fieldDefinition);
        }


        if ($fieldDefinition->isEmpty($data) && $language != "default") {
            $data = $this->items[$groupId][$keyId]["default"];
        }

        // check for inherited value
        $doGetInheritedValues = AbstractObject::doGetInheritedValues();
        if($fieldDefinition->isEmpty($data) && $doGetInheritedValues) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {

                if ($object->getParent() instanceof AbstractObject) {
                    $parent = $object->getParent();
                    while($parent && $parent->getType() == "folder") {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == "object" || $parent->getType() == "variant")) {
                        if ($parent->getClassId() == $object->getClassId()) {
                            $method = "getLocalizedfields";
                            if (method_exists($parent, $method)) {
                                $getter = "get" . ucfirst($this->fieldname);
                                $classificationStore = $parent->$getter();
                                if ($classificationStore instanceof Classificationstore) {
                                    if($classificationStore->object->getId() != $this->object->getId()) {
                                        $data = $classificationStore->getLocalizedKeyValue($groupId, $keyId, $language, false);
                                        \Logger::debug($data);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        if($fieldDefinition && method_exists($fieldDefinition, "preGetData")) {
            $data =  $fieldDefinition->preGetData($this, array(
                "data" => $data,
                "language" => $language,
                "name" => $groupId . "-" . $keyId
            ));
        }

        return $data;
    }

    /**
     * @return boolean
     */
    public static function doGetFallbackValues()
    {
        return true;
    }


}
