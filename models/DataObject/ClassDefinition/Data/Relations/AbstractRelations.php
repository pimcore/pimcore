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

abstract class AbstractRelations extends Data implements CustomResourcePersistingInterface, DataObject\ClassDefinition\PathFormatterAwareInterface
{
    const RELATION_ID_SEPARATOR = '$$';

    /**
     * @var bool
     */
    public static $remoteOwner = false;

    /**
     * @var bool
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
        return $this->lazyLoading;
    }

    /**
     * @param  $lazyLoading
     *
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

        if ($object instanceof DataObject\Concrete) {
            $relation['src_id'] = $object->getId();
            $relation['ownertype'] = 'object';

            $classId = $object->getClassId();
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $relation['src_id'] = $object->getObject()->getId(); // use the id from the object, not from the field collection
            $relation['ownertype'] = 'fieldcollection';
            $relation['ownername'] = $object->getFieldname();
            $relation['position'] = $object->getIndex();

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof DataObject\Localizedfield) {
            $relation['src_id'] = $object->getObject()->getId();
            $relation['ownertype'] = 'localizedfield';
            $relation['ownername'] = 'localizedfield';
            $context = $object->getContext();
            if (isset($context['containerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
                $fieldname = $context['fieldname'];
                $index = $context['index'] ?? null;
                $relation['ownername'] = '/' . $context['containerType'] . '~' . $fieldname . '/' . $index . '/localizedfield~' . $relation['ownername'];
            }

            $relation['position'] = $params['language'];

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $relation['src_id'] = $object->getObject()->getId();
            $relation['ownertype'] = 'objectbrick';
            $relation['ownername'] = $object->getFieldname();
            $relation['position'] = $object->getType();

            $classId = $object->getObject()->getClassId();
        }
    }

    /**
     * @param $object
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

        if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $object instanceof DataObject\DirtyIndicatorInterface) {
            if (!isset($context['containerType']) || $context['containerType'] !== 'fieldcollection') {
                if ($object instanceof DataObject\Localizedfield) {
                    if ($object->getObject() instanceof DataObject\DirtyIndicatorInterface && !$object->hasDirtyFields()) {
                        return;
                    }
                } elseif ($this->supportsDirtyDetection() && !$object->isFieldDirty($this->getName())) {
                    return;
                }
            }
        }

        $db = Db::get();

        $data = $this->getDataFromObjectParam($object, $params);
        $relations = $this->prepareDataForPersistence($data, $object, $params);

        if (is_array($relations) && !empty($relations)) {
            foreach ($relations as $relation) {
                $this->enrichRelation($object, $params, $classId, $relation);

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
     * @param $object
     * @param array $params
     *
     * @return null
     */
    public function load($object, $params = [])
    {
        $db = Db::get();
        $data = null;
        $relations = [];

        if ($object instanceof DataObject\Concrete) {
            if (!method_exists($this, 'getLazyLoading') or !$this->getLazyLoading() or (array_key_exists('force', $params) && $params['force'])) {
                $relations = $db->fetchAll('SELECT * FROM object_relations_' . $object->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'object'", [$object->getId(), $this->getName()]);
            } else {
                return null;
            }
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $relations = $db->fetchAll('SELECT * FROM object_relations_' . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'fieldcollection' AND ownername = ? AND position = ?", [$object->getObject()->getId(), $this->getName(), $object->getFieldname(), $object->getIndex()]);
        } elseif ($object instanceof DataObject\Localizedfield) {
            $context = $params['context'] ?? null;
            if (isset($context['containerType']) && (($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick'))) {
                $fieldname = $context['fieldname'] ?? null;
                if ($context['containerType'] === 'fieldcollection') {
                    $index = $context['index'] ?? null;
                    $filter = '/' . $context['containerType'] . '~' . $fieldname . '/' . $index . '/%';
                } else {
                    $filter = '/' . $context['containerType'] .'~' . $fieldname . '/%';
                }
                $relations = $db->fetchAll(
                    'SELECT * FROM object_relations_' . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'localizedfield'  AND position = ? AND ownername LIKE ?",
                    [$object->getObject()->getId(), $this->getName(), $params['language'], $filter]
                );
            } else {
                $relations = $db->fetchAll('SELECT * FROM object_relations_' . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'localizedfield' AND ownername = 'localizedfield' AND position = ?", [$object->getObject()->getId(), $this->getName(), $params['language']]);
            }
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $relations = $db->fetchAll('SELECT * FROM object_relations_' . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'objectbrick' AND ownername = ? AND position = ?", [$object->getObject()->getId(), $this->getName(), $object->getFieldname(), $object->getType()]);

            // THIS IS KIND A HACK: it's necessary because of this bug PIMCORE-1454 and therefore cannot be removed
            if (count($relations) < 1) {
                $relations = $db->fetchAll('SELECT * FROM object_relations_' . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'objectbrick' AND ownername = ? AND (position IS NULL OR position = '')", [$object->getObject()->getId(), $this->getName(), $object->getFieldname()]);
            }
            // HACK END
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
        if ($object instanceof DataObject\DirtyIndicatorInterface && $data['dirty']) {
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
     * @param $object
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
     * @param $object
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

        /** @var $item Element\ElementInterface */
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
     * @param $item
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
     * @param $array1
     * @param $array2
     *
     * @return bool
     */
    public function isEqual($array1, $array2)
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
            /** @var $el1 Element\ElementInterface */
            $el1 = $values1[$i];
            /** @var $el2 Element\ElementInterface */
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
     * @param DataObject\Fieldcollection\Data\AbstractData $object
     *
     * @throws \Exception
     */
    public function loadLazyFieldcollectionField(DataObject\Fieldcollection\Data\AbstractData $item)
    {
        if ($this->getLazyLoading() && $item->getObject()) {
            /** @var $container DataObject\Fieldcollection */
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
     * @param DataObject\Objectbrick\Data\AbstractData $object
     *
     * @throws \Exception
     */
    public function loadLazyBrickField(DataObject\Objectbrick\Data\AbstractData $item)
    {
        if ($this->getLazyLoading() && $item->getObject()) {
            /** @var $container DataObject\Objectbrick */
            $container = $item->getObject()->getObjectVar($item->getFieldname());
            if ($container) {
                $container->loadLazyField($item->getType(), $item->getFieldname(), $this->getName());
            } else {
                $item->markLazyKeyAsLoaded($this->getName());
            }
        }
    }
}
