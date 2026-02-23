<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;

class ReverseObjectRelation extends ManyToManyObjectRelation
{
    /**
     * @internal
     */
    public ?string $ownerClassName = null;

    /**
     * @internal
     *
     */
    public ?string $ownerClassId = null;

    /**
     * @internal
     *
     */
    public string $ownerFieldName;

    /**
     * ReverseObjectRelation must be lazy loading!
     *
     * @internal
     */
    public bool $lazyLoading = true;

    public function setClasses(array $classes): static
    {
        //dummy, classes are set from owner classId
        return $this;
    }

    /**
     * @return $this
     */
    public function setOwnerClassName(string $ownerClassName): static
    {
        $this->ownerClassName = $ownerClassName;

        return $this;
    }

    public function getOwnerClassName(): ?string
    {
        //fallback for legacy data
        if (empty($this->ownerClassName) && $this->ownerClassId) {
            try {
                if (empty($this->ownerClassId)) {
                    return null;
                }
                $class = DataObject\ClassDefinition::getById($this->ownerClassId);
                if ($class instanceof DataObject\ClassDefinition) {
                    $this->ownerClassName = $class->getName();
                }
            } catch (Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        return $this->ownerClassName;
    }

    public function getOwnerClassId(): ?string
    {
        if (empty($this->ownerClassId)) {
            try {
                $class = $this->ownerClassName ? DataObject\ClassDefinition::getByName($this->ownerClassName) : null;
                if (!$class instanceof DataObject\ClassDefinition) {
                    Logger::error('Reverse relation '.$this->getName().' has no owner class assigned');

                    return null;
                }
                $this->ownerClassId = $class->getId();
            } catch (Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        return $this->ownerClassId;
    }

    public function getOwnerFieldName(): string
    {
        return $this->ownerFieldName;
    }

    public function setOwnerFieldName(string $fieldName): static
    {
        $this->ownerFieldName = $fieldName;

        return $this;
    }

    protected function allowObjectRelation(DataObject\AbstractObject $object): bool
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

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
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

    public function load(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): array
    {
        if ($this->getOwnerClassId() === null) {
            return [];
        }

        $db = Db::get();
        $relations = $db->fetchAllAssociative('SELECT * FROM object_relations_'.$this->getOwnerClassId()." WHERE dest_id = ? AND fieldname = ? AND ownertype = 'object'", [$object->getId(), $this->getOwnerFieldName()]);

        $relations = array_map(static function ($relation) {
            $relation['dest_id'] = $relation['src_id'];
            unset($relation['src_id']);

            return $relation;
        }, $relations);

        $data = $this->loadData($relations, $object, $params);
        $object->markFieldDirty($this->getName(), false);

        return $data['data'];
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        return $tags;
    }

    public function resolveDependencies(mixed $data): array
    {
        return [];
    }

    public function preGetData(mixed $container, array $params = []): array
    {
        $data = $this->load($container);

        return $this->filterUnpublishedElements($data);
    }

    /**
     * @return false
     */
    public function supportsInheritance(): bool
    {
        return false;
    }

    public function getFieldType(): string
    {
        return 'reverseObjectRelation';
    }

    public function getClasses(): array
    {
        if ($this->getOwnerClassId()) {
            return Model\Element\Service::fixAllowedTypes([$this->ownerClassName], 'classes');
        }

        return [];
    }

    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $noResult = '1 = 0';

        $tablePrefix = $params['tablePrefix'] ?? null;

        if (null === $tablePrefix) {
            throw new Exception('Function ReverseObjectRelation::getFilterConditionExt called without a table prefix.');
        }

        if ($value === null || $value === 'null') {
            return $noResult;
        }

        $db = \Pimcore\Db::get();

        if ($operator === '=') {
            $subFilter = '`' . 'src_id' . '`' . ' = ' . $db->quote((string) $value);
        } elseif ($operator === 'LIKE' || $operator === 'IN') {
            $values = explode(',', $value);
            // we treat LIKE and IN the same. UI sends LIKE
            $fieldConditions = array_map(function ($value) use ($db) {
                return '`' . 'src_id' . '`' . ' = ' . $db->quote((string) $value);
            }, array_filter($values));
            if (!empty($fieldConditions)) {
                // we use OR
                $subFilter = '(' . implode(' OR ', $fieldConditions) . ')';
            } else {
                return $noResult;
            }
        } else {
            return $noResult;
        }

        // we are looking for membership in the reverse relation
        return $tablePrefix . 'id IN ('
            . 'SELECT dest_id FROM object_relations_'. $this->getOwnerClassId()
            . ' WHERE '. $subFilter
            . ' AND fieldname = ' . $db->quote($this->getOwnerFieldName())
            . " AND ownertype = 'object'"
        . ')';
    }
}
