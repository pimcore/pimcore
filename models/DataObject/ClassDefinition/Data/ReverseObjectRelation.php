<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

class ReverseObjectRelation extends ManyToManyObjectRelation
{
    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'reverseObjectRelation';

    /**
     * @internal
     *
     * @var string
     */
    public $ownerClassName;

    /**
     * @internal
     *
     * @var string|null
     */
    public $ownerClassId;

    /**
     * @internal
     *
     * @var string
     */
    public $ownerFieldName;

    /**
     * ReverseObjectRelation must be lazy loading!
     *
     * @internal
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
     * @return string|null
     */
    public function getOwnerClassName()
    {
        //fallback for legacy data
        if (empty($this->ownerClassName) && $this->ownerClassId) {
            try {
                if (empty($this->ownerClassId)) {
                    return null;
                }
                $class = DataObject\ClassDefinition::getById($this->ownerClassId);
                $this->ownerClassName = $class->getName();
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        return $this->ownerClassName;
    }

    /**
     * @return string|null
     */
    public function getOwnerClassId()
    {
        if (empty($this->ownerClassId)) {
            try {
                $class = DataObject\ClassDefinition::getByName($this->ownerClassName);
                if (!$class instanceof DataObject\ClassDefinition) {
                    Logger::error('Reverse relation '.$this->getName().' has no owner class assigned');

                    return null;
                }
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
     * {@inheritdoc}
     */
    protected function allowObjectRelation($object)
    {
        //only relations of owner type are allowed
        $ownerClass = DataObject\ClassDefinition::getByName($this->getOwnerClassName());
        if ($ownerClass instanceof DataObject\ClassDefinition && $object instanceof DataObject\Concrete && $ownerClass->getId() == $object->getClassId()) {
            $fd = $ownerClass->getFieldDefinition($this->getOwnerFieldName());
            if ($fd instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        //TODO
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (is_array($data)) {
            foreach ($data as $o) {
                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass || !($o instanceof DataObject\Concrete)) {
                    throw new Model\Element\ValidationException('Invalid non owner object relation to object ['.$o->getId().']');
                }
            }
        }
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     */
    public function load($object, $params = [])
    {
        if ($this->getOwnerClassId() === null) {
            return [];
        }

        $db = Db::get();
        $relations = $db->fetchAll('SELECT * FROM object_relations_'.$this->getOwnerClassId()." WHERE dest_id = ? AND fieldname = ? AND ownertype = 'object'", [$object->getId(), $this->getOwnerFieldName()]);

        $relations = array_map(static function ($relation) {
            $relation['dest_id'] = $relation['src_id'];
            unset($relation['src_id']);

            return $relation;
        }, $relations);

        $data = $this->loadData($relations, $object, $params);
        if ($object instanceof Model\Element\DirtyIndicatorInterface) {
            $object->markFieldDirty($this->getName(), false);
        }

        return $data['data'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
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
     * {@inheritdoc}
     */
    public function isOptimizedAdminLoading(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function preGetData($container, $params = [])
    {
        return $this->load($container);
    }

    /**
     * @return false
     */
    public function supportsInheritance()
    {
        return false;
    }
}

//TODO remove in Pimcore 11
class_alias(ReverseObjectRelation::class, 'Pimcore\Model\DataObject\ClassDefinition\Data\ReverseManyToManyObjectRelation');
