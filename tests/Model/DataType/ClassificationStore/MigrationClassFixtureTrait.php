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

use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;

/**
 * A class of its own, carrying classification store fields on more than one store and in more than
 * one container - which neither shipped test class does, and which is what the scope of the drain
 * and the refusal of the cleanup are about.
 *
 * The containers are built in memory and registered in the runtime cache, the way
 * {@see EngineTest} builds them: `Definition::getByKey()` answers from there before it looks for a
 * definition file, and nothing on the paths under test reads one from disk.
 */
trait MigrationClassFixtureTrait
{
    private const BRICK_KEY = 'csMigrationBrick';

    private const COLLECTION_KEY = 'csMigrationCollection';

    /**
     * @var string[] every brick key this fixture registered, which is what its tables are named
     *               after and what the runtime cache holds it under
     */
    private array $registeredBrickKeys = [];

    /**
     * @var string[]
     */
    private array $createdClassIds = [];

    /**
     * @var int[]
     */
    private array $createdStoreIds = [];

    /**
     * @var int[]
     */
    private array $createdGroupIds = [];

    /**
     * @var int[]
     */
    private array $createdKeyIds = [];

    protected function createMigrationStore(): int
    {
        $store = new Classificationstore\StoreConfig();
        $store->setName(uniqid('csMigrationStore', false));
        $store->save();

        $storeId = (int) $store->getId();
        $this->createdStoreIds[] = $storeId;

        return $storeId;
    }

    /**
     * Flips one of the stores this fixture created, the way the flip command does.
     */
    protected function flipMigrationStore(int $storeId): void
    {
        $store = Classificationstore\StoreConfig::getById($storeId);
        $this->assertInstanceOf(Classificationstore\StoreConfig::class, $store);

        $this->getMigrationService()->flip($store);
    }

    /**
     * @param string|null $name a name of the caller's own, for the tests that create many groups
     *                          and cannot rely on the clock telling them apart
     */
    protected function createMigrationGroup(int $storeId, ?string $name = null): int
    {
        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($storeId);
        $group->setName($name ?? uniqid('csMigrationGroup', false));
        $group->save();

        $groupId = (int) $group->getId();
        $this->createdGroupIds[] = $groupId;

        return $groupId;
    }

    protected function createMigrationKey(int $storeId, int $groupId): int
    {
        $name = 'csMigrationKey_' . uniqid();

        $definition = new Input();
        $definition->setName($name);

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($storeId);
        $keyConfig->setName($name);
        $keyConfig->setEnabled(true);
        $keyConfig->setType($definition->getFieldType());
        $keyConfig->setDefinition(json_encode($definition));
        $keyConfig->save();

        $keyId = (int) $keyConfig->getId();
        $this->createdKeyIds[] = $keyId;

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setGroupId($groupId);
        $relation->setKeyId($keyId);
        $relation->setSorter(0);
        $relation->save();

        return $keyId;
    }

    protected function migrationStoreField(string $name, int $storeId): ClassificationstoreField
    {
        $fieldDefinition = new ClassificationstoreField();
        $fieldDefinition->setName($name);
        $fieldDefinition->setTitle($name);
        $fieldDefinition->setStoreId($storeId);

        return $fieldDefinition;
    }

    /**
     * @param ClassDefinition\Data[] $fieldDefinitions
     * @param string|null            $name the class name, for an id a name cannot be derived from -
     *                                     a numeric id, which is what a class gets by default
     */
    protected function buildMigrationClass(
        string $classId,
        array $fieldDefinitions,
        ?string $name = null
    ): ClassDefinition {
        // the fields go in through the layout, because that is what a saved class is rebuilt from
        $layout = new Panel();
        $layout->setName('root');
        $layout->setChildren(array_values($fieldDefinitions));

        $class = new ClassDefinition();
        $class->setId($classId);
        $class->setName($name ?? lcfirst($classId));
        $class->setLayoutDefinitions($layout);

        return $class;
    }

    /**
     * @param ClassDefinition\Data[] $fieldDefinitions
     */
    protected function saveMigrationClass(
        string $classId,
        array $fieldDefinitions,
        ?string $name = null
    ): ClassDefinition {
        $class = $this->buildMigrationClass($classId, $fieldDefinitions, $name);
        $class->save();

        $this->createdClassIds[] = $classId;

        return $class;
    }

    protected function registerMigrationBrick(ClassificationstoreField ...$fieldDefinitions): Objectbricks
    {
        return $this->registerMigrationBricks([self::BRICK_KEY => $fieldDefinitions]);
    }

    /**
     * One object bricks field allowing more than one brick, each brick carrying its own
     * classification store fields - which is how one field name comes to be defined twice in one
     * class, on two stores, without either definition shadowing the other.
     *
     * @param array<string, ClassificationstoreField[]> $fieldsByBrickKey
     */
    protected function registerMigrationBricks(array $fieldsByBrickKey): Objectbricks
    {
        foreach ($fieldsByBrickKey as $brickKey => $fieldDefinitions) {
            $brick = new ObjectbrickDefinition();
            $brick->setKey($brickKey);
            $brick->setFieldDefinitions($this->byName(...$fieldDefinitions));

            RuntimeCache::set('objectbrick_' . $brickKey, $brick);
            $this->registeredBrickKeys[] = $brickKey;
        }

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes(array_keys($fieldsByBrickKey));

        return $bricks;
    }

    protected function registerMigrationFieldCollection(ClassificationstoreField ...$fieldDefinitions): Fieldcollections
    {
        $fieldCollection = new FieldcollectionDefinition();
        $fieldCollection->setKey(self::COLLECTION_KEY);
        $fieldCollection->setFieldDefinitions($this->byName(...$fieldDefinitions));

        RuntimeCache::set('fieldcollection_' . self::COLLECTION_KEY, $fieldCollection);

        $collections = new Fieldcollections();
        $collections->setName('items');
        $collections->setAllowedTypes([self::COLLECTION_KEY]);

        return $collections;
    }

    protected function wrapInLocalizedFields(ClassificationstoreField ...$fieldDefinitions): Localizedfields
    {
        $localizedFields = new Localizedfields();
        $localizedFields->setName('localizedfields');
        $localizedFields->setChildren(array_values($fieldDefinitions));

        return $localizedFields;
    }

    /**
     * One group activation row and one value row for a (object, fieldname) pair, the way the EAV
     * engine wrote them.
     */
    protected function seedMigrationEavRows(
        string $classId,
        int $objectId,
        string $fieldname,
        int $groupId,
        int $keyId,
        string $value
    ): void {
        $db = Db::get();

        $db->insert(self::GROUPS_TABLE_PREFIX . $classId, [
            'id' => $objectId,
            'groupId' => $groupId,
            'fieldname' => $fieldname,
        ]);
        $db->insert(self::DATA_TABLE_PREFIX . $classId, [
            'id' => $objectId,
            'groupId' => $groupId,
            'keyId' => $keyId,
            'fieldname' => $fieldname,
            'language' => 'default',
            'type' => 'input',
            'value' => $value,
        ]);
    }

    /**
     * Removes the classes, their value tables and the configurations this fixture created, in the
     * order the foreign keys between them ask for.
     */
    protected function cleanUpMigrationFixture(): void
    {
        $db = Db::get();

        foreach ($this->createdClassIds as $classId) {
            ClassDefinition::getById($classId, true)?->delete();

            foreach ($this->valueTablesOf($classId) as $table) {
                $db->executeStatement('DROP TABLE IF EXISTS ' . $db->quoteIdentifier($table));
            }
        }
        $this->createdClassIds = [];

        foreach ($this->createdKeyIds as $keyId) {
            $db->delete('classificationstore_relations', ['keyId' => $keyId]);
            $db->delete('classificationstore_keys', ['id' => $keyId]);
        }
        $this->createdKeyIds = [];

        foreach ($this->createdGroupIds as $groupId) {
            $db->delete('classificationstore_groups', ['id' => $groupId]);
        }
        $this->createdGroupIds = [];

        foreach ($this->createdStoreIds as $storeId) {
            $db->delete('classificationstore_stores', ['id' => $storeId]);
        }
        $this->createdStoreIds = [];

        foreach ($this->registeredBrickKeys as $brickKey) {
            RuntimeCache::getInstance()->offsetUnset('objectbrick_' . $brickKey);
        }
        $this->registeredBrickKeys = [];

        RuntimeCache::getInstance()->offsetUnset('fieldcollection_' . self::COLLECTION_KEY);
    }

    /**
     * @return string[]
     */
    private function valueTablesOf(string $classId): array
    {
        $tables = [
            self::DATA_TABLE_PREFIX . $classId,
            self::GROUPS_TABLE_PREFIX . $classId,
            self::DOCUMENT_TABLE_PREFIX . $classId,
            'object_collection_' . self::COLLECTION_KEY . '_' . $classId,
        ];

        foreach ($this->registeredBrickKeys as $brickKey) {
            $tables[] = 'object_brick_store_' . $brickKey . '_' . $classId;
            $tables[] = 'object_brick_query_' . $brickKey . '_' . $classId;
        }

        return $tables;
    }

    /**
     * @return array<string, Data>
     */
    private function byName(ClassificationstoreField ...$fieldDefinitions): array
    {
        $byName = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            $byName[$fieldDefinition->getName()] = $fieldDefinition;
        }

        return $byName;
    }
}
