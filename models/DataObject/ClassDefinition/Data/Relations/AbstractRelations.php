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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\Element;

abstract class AbstractRelations extends Data implements
    CustomResourcePersistingInterface,
    DataObject\ClassDefinition\PathFormatterAwareInterface,
    Data\LazyLoadingSupportInterface,
    Data\EqualComparisonInterface
{
    use DataObject\Traits\ContextPersistenceTrait;

    const RELATION_ID_SEPARATOR = '$$';

    /**
     * Set of allowed classes
     *
     * @var array
     */
    public $classes = [];

    /** Optional path formatter class
     * @var null|string
     */
    public $pathFormatterClass;

    /**
     * @return array[
     *  'classes' => string,
     * ]
     */
    public function getClasses()
    {
        return $this->classes ?: [];
    }

    /**
     * @param array $classes
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        $this->classes = Element\Service::fixAllowedTypes($classes, 'classes');

        return $this;
    }

    /**
     * @return bool
     */
    public function getLazyLoading()
    {
        return true;
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @throws \Exception
     */
    public function save($object, $params = [])
    {
        if (isset($params['isUntouchable']) && $params['isUntouchable']) {
            return;
        }

        if (!isset($params['context'])) {
            $params['context'] = null;
        }
        $context = $params['context'];

        if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $object instanceof Element\DirtyIndicatorInterface) {
            if (!isset($context['containerType']) || $context['containerType'] !== 'fieldcollection') {
                if ($object instanceof DataObject\Localizedfield) {
                    if ($object->getObject() instanceof Element\DirtyIndicatorInterface && !$object->hasDirtyFields()) {
                        return;
                    }
                } elseif ($this->supportsDirtyDetection() && !$object->isFieldDirty($this->getName())) {
                    return;
                }
            }
        }

        $data = $this->getDataFromObjectParam($object, $params);
        $relations = $this->prepareDataForPersistence($data, $object, $params);

        if (is_array($relations) && !empty($relations)) {
            $db = Db::get();

            foreach ($relations as $relation) {
                $this->enrichDataRow($object, $params, $classId, $relation);

                /*relation needs to be an array with src_id, dest_id, type, fieldname*/
                try {
                    $db->insert('object_relations_' . $classId, $relation);
                } catch (\Exception $e) {
                    Logger::error('It seems that the relation ' . $relation['src_id'] . ' => ' . $relation['dest_id']
                        . ' (fieldname: ' . $this->getName() . ') already exist -> please check immediately!');
                    Logger::error($e);

                    // try it again with an update if the insert fails, shouldn't be the case, but it seems that
                    // sometimes the insert throws an exception

                    throw $e;
                }
            }
        }
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return array|null
     */
    public function load($object, $params = [])
    {
        $data = null;
        $relations = [];

        if ($object instanceof DataObject\Concrete) {
            $relations = $object->retrieveRelationData(['fieldname' => $this->getName(), 'ownertype' => 'object']);
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $relations = $object->getObject()->retrieveRelationData(['fieldname' => $this->getName(), 'ownertype' => 'fieldcollection', 'ownername' => $object->getFieldname(), 'position' => $object->getIndex()]);
        } elseif ($object instanceof DataObject\Localizedfield) {
            $context = $params['context'] ?? null;
            if (isset($context['containerType']) && (($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick'))) {
                $fieldname = $context['fieldname'] ?? null;
                if ($context['containerType'] === 'fieldcollection') {
                    $index = $context['index'] ?? null;
                    $filter = '/' . $context['containerType'] . '~' . $fieldname . '/' . $index . '/%';
                } else {
                    $filter = '/' . $context['containerType'] . '~' . $fieldname . '/%';
                }
                $relations = $object->getObject()->retrieveRelationData(['fieldname' => $this->getName(), 'ownertype' => 'localizedfield', 'ownername' => $filter, 'position' => $params['language']]);
            } else {
                $relations = $object->getObject()->retrieveRelationData(['fieldname' => $this->getName(), 'ownertype' => 'localizedfield', 'position' => $params['language']]);
            }
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $relations = $object->getObject()->retrieveRelationData(['fieldname' => $this->getName(), 'ownertype' => 'objectbrick', 'ownername' => $object->getFieldname(), 'position' => $object->getType()]);
        }

        // using PHP sorting to order the relations, because "ORDER BY index ASC" in the queries above will cause a
        // filesort in MySQL which is extremely slow especially when there are millions of relations in the database
        usort($relations, function ($a, $b) {
            if ($a['index'] == $b['index']) {
                return 0;
            }

            return ($a['index'] < $b['index']) ? -1 : 1;
        });

        $data = $this->loadData($relations, $object, $params);
        if ($object instanceof Element\DirtyIndicatorInterface && $data['dirty']) {
            $object->markFieldDirty($this->getName(), true);
        }

        return $data['data'];
    }

    /**
     * @param array $data
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return mixed
     */
    abstract public function loadData($data, $object = null, $params = []);

    /**
     * @param array $data
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return mixed
     */
    abstract public function prepareDataForPersistence($data, $object = null, $params = []);

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     */
    public function delete($object, $params = [])
    {
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
     *
     * @param mixed $data
     * @param array $idMapping
     *
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
    public function getPathFormatterClass(): ?string
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
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function appendData($existingData, $additionalData)
    {
        $newData = [];
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $map = [];

        /** @var Element\ElementInterface $item */
        foreach ($existingData as $item) {
            $key = $this->buildUniqueKeyForAppending($item);
            $map[$key] = 1;
            $newData[] = $item;
        }

        if (is_array($additionalData)) {
            foreach ($additionalData as $item) {
                $key = $this->buildUniqueKeyForAppending($item);
                if (!isset($map[$key])) {
                    $newData[] = $item;
                }
            }
        }

        return $newData;
    }

    /**
     * @inheritdoc
     */
    public function removeData($existingData, $removeData)
    {
        $newData = [];
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $removeMap = [];

        /** @var Element\ElementInterface $item */
        foreach ($removeData as $item) {
            $key = $this->buildUniqueKeyForAppending($item);
            $removeMap[$key] = 1;
        }

        $newData = [];
        /** @var Element\ElementInterface $item */
        foreach ($existingData as $item) {
            $key = $this->buildUniqueKeyForAppending($item);

            if (!isset($removeMap[$key])) {
                $newData[] = $item;
            }
        }

        return $newData;
    }

    /**
     * @param Element\ElementInterface $item
     *
     * @return string
     */
    protected function buildUniqueKeyForAppending($item)
    {
        $elementType = Element\Service::getElementType($item);
        $id = $item->getId();

        return $elementType . $id;
    }

    /**
     * @param mixed $array1
     * @param mixed $array2
     *
     * @return bool
     */
    public function isEqual($array1, $array2): bool
    {
        $array1 = array_filter(is_array($array1) ? $array1 : []);
        $array2 = array_filter(is_array($array2) ? $array2 : []);
        $count1 = count($array1);
        $count2 = count($array2);
        if ($count1 != $count2) {
            return false;
        }

        $values1 = array_values($array1);
        $values2 = array_values($array2);

        for ($i = 0; $i < $count1; $i++) {
            /** @var Element\ElementInterface $el1 */
            $el1 = $values1[$i];
            /** @var Element\ElementInterface $el2 */
            $el2 = $values2[$i];

            if (! ($el1->getType() == $el2->getType() && ($el1->getId() == $el2->getId()))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function supportsDirtyDetection()
    {
        return true;
    }

    /**
     * @param DataObject\Fieldcollection\Data\AbstractData $item
     *
     * @throws \Exception
     */
    public function loadLazyFieldcollectionField(DataObject\Fieldcollection\Data\AbstractData $item)
    {
        if ($item->getObject()) {
            /** @var DataObject\Fieldcollection $container */
            $container = $item->getObject()->getObjectVar($item->getFieldname());
            if ($container) {
                $container->loadLazyField($item->getObject(), $item->getType(), $item->getFieldname(), $item->getIndex(), $this->getName());
            } else {
                // if container is not available we assume that it is a newly set item
                $item->markLazyKeyAsLoaded($this->getName());
            }
        }
    }

    /**
     * @param DataObject\Objectbrick\Data\AbstractData $item
     *
     * @throws \Exception
     */
    public function loadLazyBrickField(DataObject\Objectbrick\Data\AbstractData $item)
    {
        if ($item->getObject()) {
            /** @var DataObject\Objectbrick $container */
            $container = $item->getObject()->getObjectVar($item->getFieldname());
            if ($container) {
                $container->loadLazyField($item->getType(), $item->getFieldname(), $this->getName());
            } else {
                $item->markLazyKeyAsLoaded($this->getName());
            }
        }
    }

    /**
     * @internal trigger deprecation error when a relation is passed multiple times, remove in Pimcore 7
     *
     * @param array|null $data
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $container
     * @param array $params
     *
     * @return array
     *
     * @throws \Exception
     */
    public function filterMultipleAssignments($data, $container, $params)
    {
        if (
            (!is_array($data) || count($data) < 2)
            || !$container instanceof Element\DirtyIndicatorInterface
            || ($container instanceof DataObject\Concrete && !$container->isFieldDirty($this->getName()))
            || (($container instanceof DataObject\Fieldcollection\Data\AbstractData
                || $container instanceof DataObject\Localizedfield
                || $container instanceof DataObject\Objectbrick\Data\AbstractData)
                && !$container->isFieldDirty('_self'))
        ) {
            return $data;
        }

        if (!method_exists($this, 'getAllowMultipleAssignments') || !$this->getAllowMultipleAssignments()) {
            $relationItems = [];
            $objectId = null;
            $fieldName = $this->getName();

            if ($container instanceof DataObject\Concrete) {
                $objectId = $container->getId();
            } elseif (
                    $container instanceof DataObject\Fieldcollection\Data\AbstractData ||
                    $container instanceof DataObject\Localizedfield ||
                    $container instanceof DataObject\Objectbrick\Data\AbstractData
                ) {
                $objectFromContainer = $container->getObject();
                if ($objectFromContainer) {
                    $objectId = $objectFromContainer->getId();
                }
            }

            foreach ($data as $item) {
                $elementHash = null;
                if ($item instanceof DataObject\Data\ObjectMetadata || $item instanceof DataObject\Data\ElementMetadata) {
                    if ($item->getElement() instanceof Element\ElementInterface) {
                        $elementHash = Element\Service::getElementHash($item->getElement());
                    }
                } elseif ($item instanceof Element\ElementInterface) {
                    $elementHash = Element\Service::getElementHash($item);
                }

                if ($elementHash === null) {
                    $relationItems[] = $item; //do not filter if element hash fails
                } elseif (!isset($relationItems[$elementHash])) {
                    $relationItems[$elementHash] = $item;
                } else {
                    @trigger_error(
                            'Passing relations multiple times is deprecated since version 6.5.2 and will throw exception in 7.0.0, tried to assign ' . $elementHash
                            . ' multiple times in field' . $fieldName . ' of object id: ' . $objectId,
                            E_USER_DEPRECATED
                        );
                }
            }

            if (count($relationItems) !== count($data)) {
                $this->setDataToObject(array_values($relationItems), $container, $params);

                return array_values($relationItems);
            }
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    /**
     * @inheritDoc
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return 'array';
    }
}
