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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * A group of relation-typed keys in the shared test store, created per test and removed again in
 * tearDown: the store is shared across the suite and GeneralTest::testBasics counts its keys, so a
 * key left behind would change what the other tests expect of it.
 */
trait RelationFixtureTrait
{
    protected const RELATION_STORE_NAME = 'teststore';

    protected const RELATION_GROUP_NAME = 'csRelationGroup';

    protected const RELATION_COLUMN = 'quantity';

    /**
     * The class of the targets the fixture's keys are given, as
     * {@see \Pimcore\Model\DataObject\Concrete::getClassName()} reports it: the advanced object
     * relation validates every item against exactly that name.
     */
    protected const RELATION_TARGET_CLASS = 'unittest';

    /**
     * The five relation types a Classification Store key may use, by the name of the key that
     * carries them.
     */
    protected const RELATION_TYPES = [
        'csManyToOne' => ManyToOneRelation::class,
        'csManyToMany' => ManyToManyRelation::class,
        'csManyToManyObject' => ManyToManyObjectRelation::class,
        'csAdvancedManyToMany' => AdvancedManyToManyRelation::class,
        'csAdvancedManyToManyObject' => AdvancedManyToManyObjectRelation::class,
    ];

    /**
     * The two types whose items carry per-relation column values of their own.
     */
    protected const ADVANCED_RELATION_TYPES = ['csAdvancedManyToMany', 'csAdvancedManyToManyObject'];

    private ?GroupConfig $relationGroup = null;

    /**
     * @var KeyConfig[]
     */
    private array $relationKeys = [];

    /**
     * Creates the group and one key per relation type, assigned to it.
     *
     * @param string[]|null $keyNames the keys to create, or null for every relation type
     */
    protected function createRelationFixture(?array $keyNames = null): GroupConfig
    {
        $storeId = $this->getRelationStore()->getId();
        $suffix = uniqid();

        $this->relationGroup = new GroupConfig();
        $this->relationGroup->setStoreId($storeId);
        $this->relationGroup->setName(self::RELATION_GROUP_NAME . $suffix);
        $this->relationGroup->save();

        $sorter = 0;

        foreach ($keyNames ?? array_keys(self::RELATION_TYPES) as $keyName) {
            $definition = $this->buildRelationDefinition($keyName);

            $keyConfig = new KeyConfig();
            $keyConfig->setStoreId($storeId);
            $keyConfig->setName($keyName . $suffix);
            $keyConfig->setEnabled(true);
            $keyConfig->setType($definition->getFieldType());
            $keyConfig->setDefinition(json_encode($definition));
            $keyConfig->save();

            $relation = new KeyGroupRelation();
            $relation->setGroupId($this->relationGroup->getId());
            $relation->setKeyId($keyConfig->getId());
            $relation->setSorter($sorter++);
            $relation->save();

            $this->relationKeys[$keyName] = $keyConfig;
        }

        return $this->relationGroup;
    }

    protected function deleteRelationFixture(): void
    {
        foreach ($this->relationKeys as $keyConfig) {
            $keyConfig->delete();
        }

        $this->relationKeys = [];

        $this->relationGroup?->delete();
        $this->relationGroup = null;
    }

    protected function getRelationGroupId(): int
    {
        return (int) $this->relationGroup->getId();
    }

    protected function getRelationKeyId(string $keyName): int
    {
        return (int) $this->relationKeys[$keyName]->getId();
    }

    protected function getRelationStore(): StoreConfig
    {
        return StoreConfig::getByName(self::RELATION_STORE_NAME);
    }

    /**
     * Every target kind the key may hold is allowed, and the advanced pair carries one column, so
     * that a stored item has a per-relation value to round-trip.
     */
    private function buildRelationDefinition(string $keyName): Data
    {
        $className = self::RELATION_TYPES[$keyName];
        $definition = new $className();
        $definition->setName($keyName);

        if ($definition instanceof ManyToOneRelation || $definition instanceof ManyToManyRelation) {
            $definition->setObjectsAllowed(true);
            $definition->setAssetsAllowed(true);
            $definition->setDocumentsAllowed(true);
        }

        if ($definition instanceof AdvancedManyToManyObjectRelation) {
            $definition->setAllowedClassId(self::RELATION_TARGET_CLASS);
        }

        if ($definition instanceof AdvancedManyToManyRelation
            || $definition instanceof AdvancedManyToManyObjectRelation
        ) {
            $definition->setColumns([[
                'key' => self::RELATION_COLUMN,
                'type' => 'text',
                'label' => 'Quantity',
                'position' => 1,
            ]]);
        }

        return $definition;
    }
}
