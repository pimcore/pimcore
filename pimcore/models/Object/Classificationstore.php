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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Object\Classificationstore\Dao getDao()
 */
class Classificationstore extends Model\AbstractModel
{
    /**
     * @var array
     */
    public $items = [];

    /**
     * @var Model\Object\Concrete
     */
    public $object;

    /**
     * @var Model\Object\ClassDefinition
     */
    public $class;

    /** @var string */
    public $fieldname;

    /** @var array */
    public $activeGroups = [];

    /** @var array */
    public $groupCollectionMapping;

    /**
     * @param array $items
     */
    public function __construct($items = null)
    {
        if ($items) {
            $this->setItems($items);
        }
    }

    /**
     * @param  $item
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    /**
     * @param  array $items
     *
     * @return $this
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
     *
     * @return $this
     */
    public function setObject($object)
    {
        if (!$object instanceof Concrete) {
            throw new \Exception('not instance of Concrete');
        }
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
     *
     * @return $this
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
        if (!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }

        return $this->class;
    }

    /**
     * @throws \Exception
     *
     * @param null $language
     *
     * @return string
     */
    public function getLanguage($language = null)
    {
        if ($language) {
            return (string) $language;
        }

        return 'default';
    }

    /**
     * @param $groupId
     * @param $keyId
     * @param $value
     * @param null $language
     *
     * @return $this
     */
    public function setLocalizedKeyValue($groupId, $keyId, $value, $language = null)
    {
        if (!$groupId) {
            throw new \Exception('groupId not valid');
        }

        if (!$keyId) {
            throw new \Exception('keyId not valid');
        }

        $language  = $this->getLanguage($language);

        // treat value "0" nonempty
        $nonEmpty = (is_string($value) || is_numeric($value)) && strlen($value) > 0;

        if ($nonEmpty || $value) {
            $this->items[$groupId][$keyId][$language] = $value;
        } elseif (isset($this->items[$groupId][$keyId][$language])) {
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
    public function removeGroupData($groupId)
    {
        unset($this->items[$groupId]);
    }

    /** Returns an array of
     * @return array
     */
    public function getGroupIdsWithData()
    {
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

    /**
     * @param $groupId
     * @param $keyId
     * @param $language
     * @param $fielddefinition
     *
     * @return null
     */
    protected function getFallbackValue($groupId, $keyId, $language, $fielddefinition)
    {
        $fallbackLanguages = Tool::getFallbackLanguagesFor($language);
        $data = null;

        foreach ($fallbackLanguages as $l) {
            if (
                array_key_exists($groupId, $this->items)
                && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($l, $this->items[$groupId][$keyId])) {
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
     * @param bool|false $ignoreDefaultLanguage
     *
     * @return null
     *
     * @todo: not sure if bool|false is actually allowed in phpdoc?
     */
    public function getLocalizedKeyValue($groupId, $keyId, $language = 'default', $ignoreFallbackLanguage = false, $ignoreDefaultLanguage = false)
    {
        $oid = $this->object->getId();

        $keyConfig = Model\Object\Classificationstore\DefinitionCache::get($keyId);

        if ($keyConfig->getType() == 'calculatedValue') {
            $data = new Model\Object\Data\CalculatedValue($this->getFieldname());
            $childDef = Model\Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
            $data->setContextualData('classificationstore', $this->getFieldname(), null, $language, $groupId, $keyId, $childDef);
            $data = Model\Object\Service::getCalculatedFieldValueForEditMode($this->getObject(), [], $data);

            return $data;
        }

        $fieldDefinition =  Model\Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

        $language = $this->getLanguage($language);
        $data = null;

        if (array_key_exists($groupId, $this->items) && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($language, $this->items[$groupId][$keyId])
            ) {
            $data = $this->items[$groupId][$keyId][$language];
        }

        // check for fallback value
        if ($fieldDefinition->isEmpty($data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            $data = $this->getFallbackValue($groupId, $keyId, $language, $fieldDefinition);
        }

        if ($fieldDefinition->isEmpty($data) && !$ignoreDefaultLanguage && $language != 'default') {
            $data = $this->items[$groupId][$keyId]['default'];
        }

        // check for inherited value
        $doGetInheritedValues = AbstractObject::doGetInheritedValues();
        if ($fieldDefinition->isEmpty($data) && $doGetInheritedValues) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {
                if ($object->getParent() instanceof AbstractObject) {
                    $parent = $object->getParent();
                    while ($parent && $parent->getType() == 'folder') {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == 'object' || $parent->getType() == 'variant')) {
                        if ($parent->getClassId() == $object->getClassId()) {
                            $method = 'getLocalizedfields';
                            if (method_exists($parent, $method)) {
                                $getter = 'get' . ucfirst($this->fieldname);
                                $classificationStore = $parent->$getter();
                                if ($classificationStore instanceof Classificationstore) {
                                    if ($classificationStore->object->getId() != $this->object->getId()) {
                                        $data = $classificationStore->getLocalizedKeyValue($groupId, $keyId, $language, false);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($fieldDefinition && method_exists($fieldDefinition, 'preGetData')) {
            $data =  $fieldDefinition->preGetData($this, [
                'data' => $data,
                'language' => $language,
                'name' => $groupId . '-' . $keyId
            ]);
        }

        return $data;
    }

    /**
     * @return bool
     */
    public static function doGetFallbackValues()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getGroupCollectionMappings()
    {
        return $this->groupCollectionMapping;
    }

    /**
     * @param array $groupCollectionMapping
     */
    public function setGroupCollectionMappings($groupCollectionMapping)
    {
        $this->groupCollectionMapping = $groupCollectionMapping;
    }

    /**
     * @param $groupId
     * @param $collectionId
     */
    public function setGroupCollectionMapping($groupId, $collectionId)
    {
        if (!is_array($this->groupCollectionMapping)) {
            $this->groupCollectionMapping[$groupId] = $collectionId;
        }
    }

    /**
     * @param $groupId
     *
     * @return mixed
     */
    public function getGroupCollectionMapping($groupId)
    {
        if ($this->groupCollectionMapping) {
            return $this->groupCollectionMapping[$groupId];
        }
    }
}
