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

namespace Pimcore\Model\DataObject;

use Pimcore\Model;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\Dao createUpdateTable()
 * @method \Pimcore\Model\DataObject\Classificationstore\Dao getDao()
 * @method void delete()
 * @method Classifictionstore load()
 * @method void save()
 */
class Classificationstore extends Model\AbstractModel implements DirtyIndicatorInterface
{
    use Model\Element\Traits\DirtyIndicatorTrait;
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var Model\DataObject\Concrete
     */
    protected $object;

    /**
     * @var Model\DataObject\ClassDefinition
     */
    protected $class;

    /** @var string */
    protected $fieldname;

    /** @var array */
    protected $activeGroups = [];

    /** @var array */
    protected $groupCollectionMapping;

    /**
     * @param array $items
     */
    public function __construct($items = null)
    {
        if ($items) {
            $this->setItems($items);
            $this->markFieldDirty('_self');
        }
    }

    /**
     * @param array $item
     */
    public function addItem($item)
    {
        $this->items[] = $item;
        $this->markFieldDirty('_self');
    }

    /**
     * @param  array $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        $this->markFieldDirty('_self');

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
        if ($this->object) {
            if ($this->object->getId() != $object->getId()) {
                $this->markFieldDirty('_self');
            }
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
     * @param Model\DataObject\ClassDefinition $class
     *
     * @return $this
     */
    public function setClass(?ClassDefinition $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return Model\DataObject\ClassDefinition
     */
    public function getClass()
    {
        if (!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }

        return $this->class;
    }

    /**
     * @param string|null $language
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
     * @param int $groupId
     * @param int $keyId
     * @param mixed $value
     * @param string|null $language
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setLocalizedKeyValue($groupId, $keyId, $value, $language = null)
    {
        if (!$groupId) {
            throw new \Exception('groupId not valid');
        }

        if (!$keyId) {
            throw new \Exception('keyId not valid');
        }

        $language = $this->getLanguage($language);

        // treat value "0" nonempty
        $nonEmpty = (is_string($value) || is_numeric($value)) && strlen($value) > 0;

        // Workaround for booleanSelect
        // @TODO Find a better solution for using isEmpty() in all ClassDefintion DataTypes

        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        /** @var Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface $dataDefinition */
        $dataDefinition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

        // set the given group to active groups
        $this->setActiveGroups($this->activeGroups + [$groupId => true]);

        if (!$this->isFieldDirty('_self')) {
            if ($this->object) {
                $oldData = $this->items[$groupId][$keyId][$language] ?? null;
                $oldData = $dataDefinition->getDataForResource($oldData, $this->object);
                $oldData = serialize($oldData);

                $newData = $dataDefinition->getDataForResource($value, $this->object);
                $newData = serialize($newData);

                if ($newData != $oldData) {
                    $this->markFieldDirty('_self');
                }
            } else {
                $this->markFieldDirty('_self');
            }
        }

        if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\BooleanSelect) {
            $nonEmpty = true;
        }

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

    /**
     * Removes the group with the given id
     *
     * @param int $groupId
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

    protected function sanitizeActiveGroups($activeGroups)
    {
        $newList = [];

        if ($activeGroups) {
            foreach ($activeGroups as $key => $value) {
                if ($value) {
                    $newList[$key] = true;
                }
            }
        }

        return $newList;
    }

    /**
     * @param array $activeGroups
     */
    public function setActiveGroups($activeGroups)
    {
        $activeGroups = $this->sanitizeActiveGroups($activeGroups);
        $diff1 = array_diff(array_keys($activeGroups), array_keys($this->activeGroups));
        $diff2 = array_diff(array_keys($this->activeGroups), array_keys($activeGroups));
        if ($diff1 || $diff2) {
            $this->markFieldDirty('_self');
        }
        $this->activeGroups = $activeGroups;
    }

    /**
     * @param int $groupId
     * @param int $keyId
     * @param string $language
     * @param Model\DataObject\ClassDefinition\Data $fielddefinition
     *
     * @return mixed
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
     * @param int $keyId
     * @param int $groupId
     * @param string $language
     * @param bool $ignoreFallbackLanguage
     * @param bool $ignoreDefaultLanguage
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getLocalizedKeyValue($groupId, $keyId, $language = 'default', $ignoreFallbackLanguage = false, $ignoreDefaultLanguage = false)
    {
        $oid = $this->object->getId();

        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);

        if ($keyConfig->getType() == 'calculatedValue') {
            $data = new Model\DataObject\Data\CalculatedValue($this->getFieldname());
            $childDef = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
            $data->setContextualData('classificationstore', $this->getFieldname(), null, $language, $groupId, $keyId, $childDef);
            $data = Model\DataObject\Service::getCalculatedFieldValueForEditMode($this->getObject(), [], $data);

            return $data;
        }

        $fieldDefinition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

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
            $data = $this->items[$groupId][$keyId]['default'] ?? null;
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
                        /** @var Concrete $parent */
                        if ($parent->getClassId() == $object->getClassId()) {
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

        if ($fieldDefinition && method_exists($fieldDefinition, 'preGetData')) {
            $data = $fieldDefinition->preGetData($this, [
                'data' => $data,
                'language' => $language,
                'name' => $groupId . '-' . $keyId,
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
     * @param int $groupId
     * @param int $collectionId
     */
    public function setGroupCollectionMapping($groupId = null, $collectionId = null)
    {
        if (!is_array($this->groupCollectionMapping) && $groupId) {
            $this->groupCollectionMapping[$groupId] = $collectionId;
        }
    }

    /**
     * @param int $groupId
     *
     * @return int|null
     */
    public function getGroupCollectionMapping($groupId)
    {
        if ($this->groupCollectionMapping) {
            return $this->groupCollectionMapping[$groupId];
        }

        return null;
    }
}
