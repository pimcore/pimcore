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
 * @package    DataObject\ClassDefinition
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Cache;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

class ReverseManyToManyObjectRelation extends ManyToManyObjectRelation
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'reverseManyToManyObjectRelation';

    /**
     * @var bool
     */
    public static $remoteOwner = true;

    /**
     * @var string
     */
    public $ownerClassName;

    /**
     * @var number
     */
    public $ownerClassId;

    /**
     * @var string
     */
    public $ownerFieldName;

    /**
     * NonOwnerObjects must be lazy loading!
     *
     * @var bool
     */
    public $lazyLoading = true;

    /**
     * @param array $classes
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        //dummy, classes are set from owner classId
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
     * @param  $lazyLoading
     *
     * @return $this
     */
    public function setLazyLoading($lazyLoading)
    {
        //dummy, non owner objects must be lazy loading
        return $this;
    }

    /**
     * @param string $ownerClassName
     *
     * @return $this
     */
    public function setOwnerClassName($ownerClassName)
    {
        $this->ownerClassName = $ownerClassName;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerClassName()
    {
        //fallback for legacy data
        if (empty($this->ownerClassName)) {
            try {
                $class = DataObject\ClassDefinition::getById($this->ownerClassId);
                $this->ownerClassName = $class->getName();
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        return $this->ownerClassName;
    }

    /**
     * @return number
     */
    public function getOwnerClassId()
    {
        if (empty($this->ownerClassId)) {
            try {
                $class = DataObject\ClassDefinition::getByName($this->ownerClassName);
                $this->ownerClassId = $class->getId();
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        return $this->ownerClassId;
    }

    /**
     * @return string
     */
    public function getOwnerFieldName()
    {
        return $this->ownerFieldName;
    }

    /**
     * @param  string $fieldName
     *
     * @return $this
     */
    public function setOwnerFieldName($fieldName)
    {
        $this->ownerFieldName = $fieldName;

        return $this;
    }

    /**
     *
     * Checks if an object is an allowed relation
     *
     * @param DataObject\Concrete $object
     *
     * @return bool
     */
    protected function allowObjectRelation($object)
    {
        //only relations of owner type are allowed
        $ownerClass = DataObject\ClassDefinition::getByName($this->getOwnerClassName());
        if ($ownerClass->getId() > 0 && $ownerClass->getId() === $object->getClassId()) {
            $fd = $ownerClass->getFieldDefinition($this->getOwnerFieldName());
            if ($fd instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (is_array($data)) {
            foreach ($data as $o) {
                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass || !($o instanceof DataObject\Concrete)) {
                    throw new Model\Element\ValidationException('Invalid non owner object relation to object ['.$o->getId().']', null, null);
                }

                $fd = $o->getClass()->getFieldDefinition($this->getOwnerFieldName());
                $fd->checkValidity($o->get($this->getOwnerFieldName()), $omitMandatoryCheck);
            }
        }
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @abstract
     *
     * @param string $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return DataObject\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return null;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        return $tags;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        return [];
    }

    /**
     * @param DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        return null;
    }

    /**
     * converts data to be imported via webservices
     *
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param $idMapper
     *
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isOptimizedAdminLoading(): bool
    {
        return true;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return null
     */
    public function load($object, $params = [])
    {
        $db = Db::get();
        $data = null;

        if (!method_exists($this, 'getLazyLoading') or !$this->getLazyLoading() or (array_key_exists('force', $params) && $params['force'])) {
            $relations = $db->fetchAll('SELECT * FROM object_relations_' . $this->getOwnerClassId() . " WHERE dest_id = ? AND fieldname = ? AND ownertype = 'object'", [$object->getId(), $this->getOwnerFieldName()]);
        } else {
            return null;
        }

        $relations = array_map(function ($relation) {
            $relation['dest_id'] = $relation['src_id'];
            unset($relation['src_id']);
            return $relation;
        }, $relations);

        // using PHP sorting to order the relations, because "ORDER BY index ASC" in the queries above will cause a
        // filesort in MySQL which is extremely slow especially when there are millions of relations in the database
        usort($relations, static function ($a, $b) {
            if ($a['index'] == $b['index']) {
                return 0;
            }

            return ($a['index'] < $b['index']) ? -1 : 1;
        });

        $data = $this->loadData($relations, $object, $params);
        if ($object instanceof DataObject\DirtyIndicatorInterface) {
            $object->markFieldDirty($this->getName(), false);
        }

        return $data['data'];
    }

    /**
     * @param DataObject\Concrete $object
     * @param                     $data
     * @param array               $params
     *
     * @return array|null
     * @throws \InvalidArgumentException
     */
    public function preSetData($object, $data, $params = [])
    {
        $oldRelations = (array)$object->get($this->getName());

        $ownerFieldName = $this->getOwnerFieldName();
        /** @var DataObject\Concrete $item */
        foreach ((array)$data as $item) {
            if(!$this->allowObjectRelation($item)) {
                throw new \InvalidArgumentException('Object is not an instance of an allowed class in field "'.$this->getName().'"');
            }

            $reverseObjects = $item->get($ownerFieldName);
            foreach((array)$reverseObjects as $reverseObject) {
                if($reverseObject->getId() === $object->getId()) {
                    continue 2;
                }
            }
            $reverseObjects[] = $object;

            $item->set($ownerFieldName, $reverseObjects);
        }

        $oldRelationIds = array_map(function(DataObject\Concrete $newRelation) {
            return $newRelation->getId();
        }, $oldRelations);
        $newRelationIds = array_map(function(DataObject\Concrete $newRelation) {
            return $newRelation->getId();
        }, (array)$data);
        $deletedRelationIds = array_diff($oldRelationIds, $newRelationIds);

        foreach($deletedRelationIds as $deletedRelationId) {
            $deletedRelation = DataObject\Concrete::getById($deletedRelationId);
            $reverseObjects = $deletedRelation->get($ownerFieldName);

            foreach($reverseObjects as $index => $reverseObject) {
                if($reverseObject->getId() === $object->getId()) {
                    unset($reverseObjects[$index]);
                }
            }
            $deletedRelation->set($ownerFieldName, array_values($reverseObjects));
        }

        return parent::preSetData($object, $data, $params);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataForPersistence($data, $object = null, $params = [])
    {
        $db = Db::get();

        $oldRelations = $db->fetchAll('SELECT src_id FROM object_relations_' . $this->getOwnerClassId().' WHERE dest_id=? AND fieldname=? AND ownertype = \'object\'', [$object->getId(), $this->getOwnerFieldName()]);
        $oldRelations = array_map(static function($oldRelation) {
            return $oldRelation['src_id'];
        }, $oldRelations);

        $deletedRelationIds = $oldRelations;
        $newRelationIds = array_map(static function(DataObject\Concrete $newRelation) {
            return $newRelation->getId();
        }, (array)$data);

        $deletedRelationIds = array_diff($deletedRelationIds, $newRelationIds);

        foreach($deletedRelationIds as $deletedRelationId) {
            $deletedRelation = DataObject\Concrete::getById($deletedRelationId);

            $reverseObjects = $deletedRelation->get($this->getOwnerFieldName());

            foreach($reverseObjects as $index => $reverseObject) {
                if($reverseObject->getId() === $object->getId()) {
                    unset($reverseObjects[$index]);
                }
            }

            $deletedRelation->set($this->getOwnerFieldName(), array_values($reverseObjects));

            \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::PRE_UPDATE, new DataObjectEvent($deletedRelation));

            $version = $deletedRelation->saveVersion(true, true, $params['versionNote'] ?? null);
            $db->update('objects', ['o_versionCount' => $version->getVersionCount(), 'o_modificationDate' => $version->getDate()], ['o_id' => $deletedRelation->getId()]);

            $db->deleteWhere('dependencies', 'sourceid='.$db->quote($deletedRelationId).' AND sourcetype=\'object\' AND targetid='.$db->quote($object->getId()).' AND targettype=\'object\'');

            $latestVersion = $deletedRelation->getLatestVersion();
            if(!$latestVersion || $deletedRelation->getVersionCount() === $latestVersion->getVersionCount()) {
                DataObject\AbstractObject::clearDependentCacheByObjectId($deletedRelation->getId());
            }
        }

        $db->deleteWhere('object_relations_' . $this->getOwnerClassId(), 'dest_id='.$db->quote($object->getId()).' AND fieldname='.$db->quote($this->getOwnerFieldName()).' AND ownertype = \'object\'');

        if(count($deletedRelationIds) > 0) {
            $db->executeUpdate('UPDATE object_query_'.$this->getOwnerClassId().' SET `'.$this->getOwnerFieldName().'`=IF(`'.$this->getOwnerFieldName().'`=\','.$object->getId().',\', NULL, REPLACE(`'.$this->getOwnerFieldName().'`, \','.$object->getId().',\', \',\')) WHERE oo_id IN ('.implode(',', $deletedRelationIds).')');
        }

        foreach($deletedRelationIds as $deletedRelationId) {
            $deletedRelation = DataObject\Concrete::getById($deletedRelationId);

            \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE, new DataObjectEvent($deletedRelation));
        }

        $return = [];
        if (is_array($data) && count($data) > 0) {
            $counter = 1;

            foreach ($data as $reverseObject) {
                if ($reverseObject instanceof DataObject\Concrete) {
                    $return[] = [
                        'src_id' => $reverseObject->getId(),
                        'type' => 'object',
                        'fieldname' => $this->getOwnerFieldName(),
                        'index' => $counter
                    ];

                    if(!in_array($reverseObject->getId(), $oldRelations)) {
                        \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::PRE_UPDATE, new DataObjectEvent($reverseObject));

                        $version = $reverseObject->saveVersion(true, true, $params['versionNote'] ?? null);
                        $db->update('objects', ['o_versionCount' => $version->getVersionCount(), 'o_modificationDate' => $version->getDate()], ['o_id' => $reverseObject->getId()]);
                        $db->insert('dependencies', [
                            'sourceid' => $reverseObject->getId(),
                            'sourcetype' => 'object',
                            'targetid' => $object->getId(),
                            'targettype' => 'object'
                        ]);

                        $latestVersion = $reverseObject->getLatestVersion();
                        if(!$latestVersion || $reverseObject->getVersionCount() === $latestVersion->getVersionCount()) {
                            DataObject\AbstractObject::clearDependentCacheByObjectId($reverseObject->getId());
                        }

                        \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE, new DataObjectEvent($reverseObject));
                    }
                }
                $counter++;
            }

            if(count($newRelationIds) > 0) {
                $db->executeUpdate('UPDATE object_query_'.$this->getOwnerClassId().' SET `'.$this->getOwnerFieldName().'`=CONCAT(IFNULL(`'.$this->getOwnerFieldName().'`, \',\'), \''.$object->getId().',\') WHERE oo_id IN ('.implode(',', $newRelationIds).')');

                $inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper($this->getOwnerClassId());
                $inheritanceHelper->addRelationToCheck($this->getOwnerFieldName(), DataObject\ClassDefinition::getById($this->getOwnerClassId())->getFieldDefinition($this->getOwnerFieldName()));

                foreach($newRelationIds as $newRelationId) {
                    $inheritanceHelper->doUpdate($newRelationId);
                }
            }

            return $return;
        }

        if (is_array($data) && count($data) === 0) {
            //give empty array if data was not null
            return [];
        }

        //return null if data was null - this indicates data was not loaded
        return null;
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
            $relation['dest_id'] = $object->getId();
            $relation['ownertype'] = 'object';

            $classId = $this->getOwnerClassId();
        }
    }
}

class_alias(ReverseManyToManyObjectRelation::class, 'Pimcore\Model\DataObject\ClassDefinition\Data\Nonownerobjects');
