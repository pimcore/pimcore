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

use Exception;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Maintenance\Tasks\CleanupClassificationstoreTablesTask;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The engine behind the value tables: the published facade still creates them, a class whose store
 * does not resolve is refused before anything is written, and a class deletion reaches every engine.
 *
 * @group model.datatype.classificationstore
 */
class EngineTest extends ModelTestCase
{
    private const DATA_TABLE_PREFIX = 'object_classificationstore_data_';

    private const GROUPS_TABLE_PREFIX = 'object_classificationstore_groups_';

    private const DOCUMENT_TABLE_PREFIX = 'object_classificationstore_doc_';

    private const FACADE_CLASS_ID = 'CsEngineFacade';

    private const DELETED_CLASS_ID = 'CsEngineDeleted';

    private const REFUSED_CLASS_ID = 'CsEngineRefused';

    private const ROUTED_CLASS_ID = 'CsEngineRouted';

    private const CONFLICT_CLASS_ID = 'CsEngineConflict';

    private const DOCUMENT_CLASS_ID = 'CsEngineDocument';

    private const NESTED_CLASS_ID = 'CsEngineNested';

    private const COLLECTION_CLASS_ID = 'CsEngineCollection';

    private const UNKNOWN_ENGINE = 'nosuchengine';

    private const DOCUMENT_ENGINE = 'document';

    private const FIRST_BRICK_KEY = 'csEngineFirstBrick';

    private const SECOND_BRICK_KEY = 'csEngineSecondBrick';

    private const COLLECTION_KEY = 'csEngineCollection';

    private const NESTED_DOCUMENT_FIELD = 'csNestedDoc';

    private const NESTED_EAV_FIELD = 'csNestedEav';

    /**
     * @see self::columnTypesOf()
     */
    private const INTEGER_TYPES = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'];

    /**
     * @var array<string, EngineInterface>|null
     */
    private ?array $registeredEngines = null;

    /**
     * @var int[]
     */
    private array $storeIds = [];

    /**
     * @var int[]
     */
    private array $groupIds = [];

    protected function tearDown(): void
    {
        if ($this->registeredEngines !== null) {
            $this->setRegisteredEngines($this->registeredEngines);
            $this->registeredEngines = null;
        }

        $db = Db::get();
        $classIds = [
            self::FACADE_CLASS_ID,
            self::DELETED_CLASS_ID,
            self::REFUSED_CLASS_ID,
            self::ROUTED_CLASS_ID,
            self::CONFLICT_CLASS_ID,
            self::DOCUMENT_CLASS_ID,
            self::NESTED_CLASS_ID,
            self::COLLECTION_CLASS_ID,
        ];

        foreach ($classIds as $classId) {
            ClassDefinition::getById($classId, true)?->delete();
        }

        foreach ($classIds as $classId) {
            $db->executeStatement('DROP TABLE IF EXISTS ' . $db->quoteIdentifier(self::DOCUMENT_TABLE_PREFIX . $classId));
            $db->executeStatement('DROP TABLE IF EXISTS ' . $db->quoteIdentifier(self::DATA_TABLE_PREFIX . $classId));
            $db->executeStatement('DROP TABLE IF EXISTS ' . $db->quoteIdentifier(self::GROUPS_TABLE_PREFIX . $classId));

            // a class carrying a brick leaves the two tables of that brick behind as well
            foreach ([self::FIRST_BRICK_KEY, self::SECOND_BRICK_KEY] as $brickKey) {
                foreach (['object_brick_store_', 'object_brick_query_'] as $prefix) {
                    $db->executeStatement(
                        'DROP TABLE IF EXISTS ' . $db->quoteIdentifier($prefix . $brickKey . '_' . $classId)
                    );
                }
            }

            // and a class carrying a field collection leaves that collection's table behind
            $db->executeStatement(
                'DROP TABLE IF EXISTS '
                . $db->quoteIdentifier('object_collection_' . self::COLLECTION_KEY . '_' . $classId)
            );
        }

        // the groups go before the stores they belong to, which they carry a foreign key on
        foreach ($this->groupIds as $groupId) {
            $db->delete('classificationstore_groups', ['id' => $groupId]);
        }
        $this->groupIds = [];

        foreach ($this->storeIds as $storeId) {
            $db->delete('classificationstore_stores', ['id' => $storeId]);
        }
        $this->storeIds = [];

        foreach ([self::FIRST_BRICK_KEY, self::SECOND_BRICK_KEY] as $brickKey) {
            RuntimeCache::getInstance()->offsetUnset('objectbrick_' . $brickKey);
        }

        RuntimeCache::getInstance()->offsetUnset('fieldcollection_' . self::COLLECTION_KEY);

        parent::tearDown();
    }

    /**
     * The published no-argument facade is what classSaved() and third-party code both call, so it
     * has to keep returning nothing and keep creating both tables.
     */
    public function testTheFacadeCreatesBothValueTablesAndReturnsNothing(): void
    {
        $class = $this->buildClass(self::FACADE_CLASS_ID, ['cs' => $this->storeField('cs', $this->createStore())]);

        $classificationStore = new Classificationstore();
        $classificationStore->setClass($class);
        $classificationStore->setFieldname('cs');

        $this->assertNull($classificationStore->createUpdateTable());

        $this->assertTrue($this->tableExists(self::GROUPS_TABLE_PREFIX . self::FACADE_CLASS_ID));
        $this->assertTrue($this->tableExists(self::DATA_TABLE_PREFIX . self::FACADE_CLASS_ID));
    }

    /**
     * One combined message for every field that does not resolve, including the ones nested in a
     * localized field, and nothing written before it is raised.
     */
    public function testAClassSaveIsRefusedForEveryUnresolvableStoreAtOnce(): void
    {
        $storeId = $this->createStore(self::UNKNOWN_ENGINE);

        $localizedFields = new Localizedfields();
        $localizedFields->setName('localizedfields');
        $localizedFields->setChildren([$this->storeField('csLocalized', $storeId)]);

        $class = $this->buildClass(self::REFUSED_CLASS_ID, [
            'csTopLevel' => $this->storeField('csTopLevel', $storeId),
            'localizedfields' => $localizedFields,
        ]);

        $message = null;

        try {
            $class->save();
        } catch (Exception $exception) {
            $message = $exception->getMessage();
        }

        $this->assertIsString($message, 'the save has to be refused');
        $this->assertStringContainsString('csTopLevel', $message);
        $this->assertStringContainsString('csLocalized', $message);
        $this->assertStringContainsString(self::UNKNOWN_ENGINE, $message);

        $this->assertFalse($this->tableExists(self::DATA_TABLE_PREFIX . self::REFUSED_CLASS_ID));
        $this->assertFalse($this->tableExists(self::GROUPS_TABLE_PREFIX . self::REFUSED_CLASS_ID));
        $this->assertSame(
            0,
            (int) Db::get()->fetchOne('SELECT COUNT(*) FROM classes WHERE id = ?', [self::REFUSED_CLASS_ID])
        );
        $this->assertFileDoesNotExist(
            PIMCORE_CLASS_DEFINITION_DIRECTORY . '/definition_' . self::REFUSED_CLASS_ID . '.php'
        );
    }

    /**
     * A class may carry classification store fields on stores with different engines, so the
     * deletion has to let every registered engine drop what it owns.
     */
    public function testClassDeletionDropsTheTablesOfEveryRegisteredEngine(): void
    {
        $class = $this->buildClass(self::DELETED_CLASS_ID, [
            'cs' => $this->storeField('cs', $this->createStore()),
            'csDoc' => $this->storeField('csDoc', $this->createStore(self::DOCUMENT_ENGINE)),
        ]);
        $class->save();

        $this->assertTrue($this->tableExists(self::DATA_TABLE_PREFIX . self::DELETED_CLASS_ID));
        $this->assertTrue($this->tableExists(self::GROUPS_TABLE_PREFIX . self::DELETED_CLASS_ID));
        $this->assertTrue($this->tableExists(self::DOCUMENT_TABLE_PREFIX . self::DELETED_CLASS_ID));

        $secondEngine = $this->createMock(EngineInterface::class);
        $secondEngine->expects($this->once())->method('dropTables');

        $this->registerSecondEngine($secondEngine);

        $class->delete();

        $this->assertFalse($this->tableExists(self::DATA_TABLE_PREFIX . self::DELETED_CLASS_ID));
        $this->assertFalse($this->tableExists(self::GROUPS_TABLE_PREFIX . self::DELETED_CLASS_ID));
        $this->assertFalse($this->tableExists(self::DOCUMENT_TABLE_PREFIX . self::DELETED_CLASS_ID));
    }

    /**
     * Everything the model publishes has to reach the engine of the field's own store, with the
     * model and the class it was called on - hard-coding any of these routes to the default engine
     * would otherwise go unnoticed.
     */
    public function testTheDaoDelegatesEveryOperationToTheEngineOfItsStore(): void
    {
        $eavEngine = $this->createMock(EngineInterface::class);
        $eavEngine->expects($this->never())->method($this->anything());

        $secondEngine = $this->createMock(EngineInterface::class);
        $this->replaceRegisteredEngines($eavEngine, $secondEngine);

        $storeId = $this->createStore('second');
        $class = $this->buildClass(self::ROUTED_CLASS_ID, ['cs' => $this->storeField('cs', $storeId)]);

        $classificationStore = new Classificationstore();
        $classificationStore->setClass($class);
        $classificationStore->setFieldname('cs');

        $secondEngine->expects($this->once())->method('load')->with($classificationStore);
        $secondEngine->expects($this->once())->method('save')->with($classificationStore);
        $secondEngine->expects($this->once())->method('delete')->with($classificationStore);
        $secondEngine->expects($this->once())->method('createUpdateTables')->with($class, $storeId);

        $classificationStore->load();
        $classificationStore->save();
        $classificationStore->delete();
        $classificationStore->createUpdateTable();
    }

    /**
     * An engine name no bundle registers has no values to read, so a load through the DAO has to
     * fail rather than come back empty from the default engine.
     */
    public function testLoadingThroughTheDaoRejectsAnUnknownEngine(): void
    {
        $storeId = $this->createStore(self::UNKNOWN_ENGINE);
        $class = $this->buildClass(self::ROUTED_CLASS_ID, ['cs' => $this->storeField('cs', $storeId)]);

        $classificationStore = new Classificationstore();
        $classificationStore->setClass($class);
        $classificationStore->setFieldname('cs');

        $this->expectException(UnknownClassificationStoreEngineException::class);

        $classificationStore->load();
    }

    /**
     * `document` used to be the engine this release knew about but did not ship. It is registered
     * now, so a store carrying it resolves to the document engine instead of being refused.
     */
    public function testADocumentStoreResolvesToTheRegisteredDocumentEngine(): void
    {
        $resolver = Pimcore::getContainer()->get(EngineResolver::class);

        $this->assertInstanceOf(DocumentEngine::class, $resolver->getEngines()[self::DOCUMENT_ENGINE] ?? null);
        $this->assertInstanceOf(
            DocumentEngine::class,
            $resolver->resolveForStore($this->createStore(self::DOCUMENT_ENGINE))
        );
    }

    /**
     * The document engine owns one table per class and nothing else: it neither creates nor needs
     * the two tables of the EAV engine, and creating it twice leaves it as it was.
     */
    public function testADocumentStoreCreatesOnlyItsOwnTableAndCreatesItIdempotently(): void
    {
        $class = $this->buildClass(
            self::DOCUMENT_CLASS_ID,
            ['cs' => $this->storeField('cs', $this->createStore(self::DOCUMENT_ENGINE))]
        );
        $class->save();

        $table = self::DOCUMENT_TABLE_PREFIX . self::DOCUMENT_CLASS_ID;

        $this->assertTrue($this->tableExists($table));
        $this->assertFalse($this->tableExists(self::DATA_TABLE_PREFIX . self::DOCUMENT_CLASS_ID));
        $this->assertFalse($this->tableExists(self::GROUPS_TABLE_PREFIX . self::DOCUMENT_CLASS_ID));

        $this->assertSame(['id', 'fieldname', 'groupId'], $this->primaryKeyOf($table));
        $this->assertSame(
            ['classificationstore_groups' => 'CASCADE', 'objects' => 'CASCADE'],
            $this->foreignKeysOf($table)
        );

        // checksum and migratedAt belong to the drain and are written by nothing in this release
        $this->assertSame(
            [
                'id' => 'int unsigned',
                'fieldname' => 'varchar(70)',
                'groupId' => 'int unsigned',
                'collectionId' => 'bigint',
                'values' => 'longtext',
                'checksum' => 'char(40)',
                'migratedAt' => 'int unsigned',
            ],
            $this->columnTypesOf($table)
        );

        $class->save();

        $this->assertTrue($this->tableExists($table));
        $this->assertSame(['id', 'fieldname', 'groupId'], $this->primaryKeyOf($table));
    }

    /**
     * The resolver memoizes which engine a store uses, so flipping a store has to drop that entry -
     * otherwise the rest of the request, or the rest of a worker's life, keeps the old engine.
     */
    public function testFlippingAStoreIsSeenAfterItHasAlreadyResolvedOnce(): void
    {
        $secondEngine = $this->createMock(EngineInterface::class);
        $this->registerSecondEngine($secondEngine);

        $resolver = Pimcore::getContainer()->get(EngineResolver::class);
        $storeId = $this->createStore();

        $this->assertNotSame($secondEngine, $resolver->resolveForStore($storeId));

        $store = StoreConfig::getById($storeId);
        $this->assertInstanceOf(StoreConfig::class, $store);
        $store->setEngine('second');
        $store->save();

        $this->assertSame($secondEngine, $resolver->resolveForStore($storeId));
    }

    /**
     * A worker keeps the same resolver across messages, so the memo is dropped at the message
     * boundary as well - for the stores another process flipped.
     */
    public function testTheMemoIsDroppedAtTheRequestAndMessageBoundary(): void
    {
        $secondEngine = $this->createMock(EngineInterface::class);
        $this->registerSecondEngine($secondEngine);

        $resolver = Pimcore::getContainer()->get(EngineResolver::class);
        $this->assertInstanceOf(ResetInterface::class, $resolver, 'the kernel.reset tag needs reset()');

        $storeId = $this->createStore();
        $this->assertNotSame($secondEngine, $resolver->resolveForStore($storeId));

        // bypasses StoreConfig::save(), the way another process or a direct DDL change would
        Db::get()->update('classificationstore_stores', ['engine' => 'second'], ['id' => $storeId]);

        $resolver->reset();

        $this->assertSame($secondEngine, $resolver->resolveForStore($storeId));
    }

    /**
     * Two bricks allowed on the same class may define a classification store under the same name.
     * The value tables key their rows by (object, fieldname), so those definitions share their rows
     * and cannot sit on two engines - and the one that is not first must not be skipped.
     */
    public function testAClassSaveIsRefusedWhenOneFieldNameSitsOnTwoEngines(): void
    {
        $this->registerSecondEngine($this->createMock(EngineInterface::class));

        $defaultStoreId = $this->createStore();
        $secondStoreId = $this->createStore('second');

        $this->registerBrick(self::FIRST_BRICK_KEY, $this->storeField('cs', $defaultStoreId));
        $this->registerBrick(self::SECOND_BRICK_KEY, $this->storeField('cs', $secondStoreId));

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::FIRST_BRICK_KEY, self::SECOND_BRICK_KEY]);

        $message = null;

        try {
            $this->buildClass(self::CONFLICT_CLASS_ID, ['bricks' => $bricks])->save();
        } catch (Exception $exception) {
            $message = $exception->getMessage();
        }

        $this->assertIsString($message, 'the save has to be refused');
        $this->assertStringContainsString('field "cs"', $message);
        $this->assertStringContainsString('objectbrick:' . self::FIRST_BRICK_KEY . '.cs uses "eav"', $message);
        $this->assertStringContainsString('objectbrick:' . self::SECOND_BRICK_KEY . '.cs uses "second"', $message);

        $this->assertSame(
            0,
            (int) Db::get()->fetchOne('SELECT COUNT(*) FROM classes WHERE id = ?', [self::CONFLICT_CLASS_ID])
        );
    }

    /**
     * A classification store may be defined inside an object brick, and its values are stored under
     * the field's own name in the table of the class the brick is allowed on - a name the class's
     * own field definitions know nothing about. Cleanup deletes every row of a name it believes
     * gone, so it has to reach into the containers before it believes that of one, and the
     * maintenance task runs it over every table of every engine on every maintenance run.
     */
    public function testCleanupKeepsTheValuesOfAFieldDefinedOnlyInsideAnObjectBrick(): void
    {
        $documentStoreId = $this->createStore(self::DOCUMENT_ENGINE);
        $eavStoreId = $this->createStore();

        $this->registerBrick(
            self::FIRST_BRICK_KEY,
            $this->storeField(self::NESTED_DOCUMENT_FIELD, $documentStoreId),
            $this->storeField(self::NESTED_EAV_FIELD, $eavStoreId)
        );

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::FIRST_BRICK_KEY]);

        $this->buildClass(self::NESTED_CLASS_ID, ['bricks' => $bricks])->save();

        $this->assertCleanupKeepsTheNestedFields(self::NESTED_CLASS_ID, $documentStoreId, $eavStoreId);
    }

    /**
     * The field collection is the other container a classification store may hide in, and it is
     * reached through a definition of its own in exactly the same way - so cleanup has to look
     * into it too, and the two containers are pinned by the same assertions.
     */
    public function testCleanupKeepsTheValuesOfAFieldDefinedOnlyInsideAFieldCollection(): void
    {
        $documentStoreId = $this->createStore(self::DOCUMENT_ENGINE);
        $eavStoreId = $this->createStore();

        $this->registerFieldCollection(
            self::COLLECTION_KEY,
            $this->storeField(self::NESTED_DOCUMENT_FIELD, $documentStoreId),
            $this->storeField(self::NESTED_EAV_FIELD, $eavStoreId)
        );

        $collections = new Fieldcollections();
        $collections->setName('items');
        $collections->setAllowedTypes([self::COLLECTION_KEY]);

        $this->buildClass(self::COLLECTION_CLASS_ID, ['items' => $collections])->save();

        $this->assertCleanupKeepsTheNestedFields(self::COLLECTION_CLASS_ID, $documentStoreId, $eavStoreId);
    }

    /**
     * Writes a value row for a field name the class only carries inside a container, one for a name
     * that really is gone, and runs the maintenance task over every engine.
     */
    private function assertCleanupKeepsTheNestedFields(
        string $classId,
        int $documentStoreId,
        int $eavStoreId
    ): void {
        $documentTable = self::DOCUMENT_TABLE_PREFIX . $classId;
        $groupsTable = self::GROUPS_TABLE_PREFIX . $classId;

        $this->assertTrue($this->tableExists($documentTable), 'the nested document field needs its table');
        $this->assertTrue($this->tableExists($groupsTable), 'the nested EAV field needs its table');

        $objectId = $this->anyObjectId();
        $db = Db::get();

        // 'csRemoved' is the name of a field that really is gone, so that the test also fails when
        // cleanup stops deleting anything at all
        foreach ([self::NESTED_DOCUMENT_FIELD, 'csRemoved'] as $fieldName) {
            // `values` is a reserved word, which Connection::insert() would leave unquoted
            $db->executeStatement(
                'INSERT INTO ' . $db->quoteIdentifier($documentTable)
                . ' (`id`, `fieldname`, `groupId`, `values`) VALUES (?, ?, ?, ?)',
                [$objectId, $fieldName, $this->createGroup($documentStoreId), '{}']
            );
        }

        foreach ([self::NESTED_EAV_FIELD, 'csRemoved'] as $fieldName) {
            $db->insert($groupsTable, [
                'id' => $objectId,
                'fieldname' => $fieldName,
                'groupId' => $this->createGroup($eavStoreId),
            ]);
        }

        (new CleanupClassificationstoreTablesTask(
            Pimcore::getContainer()->get(EngineResolver::class)
        ))->execute();

        $this->assertSame([self::NESTED_DOCUMENT_FIELD], $this->fieldNamesIn($documentTable, $objectId));
        $this->assertSame([self::NESTED_EAV_FIELD], $this->fieldNamesIn($groupsTable, $objectId));
    }

    /**
     * The engine of a store is written only when it was loaded or explicitly set, so that a store
     * config built with `new` to rename a store cannot reset a store that was flipped.
     */
    public function testTheEngineColumnIsWrittenOnlyWhenItWasSetOrLoaded(): void
    {
        $db = Db::get();
        $storeId = $this->createStore(self::UNKNOWN_ENGINE);

        $untouched = new StoreConfig();
        $untouched->setId($storeId);
        $untouched->setName('csEngineStore' . $storeId);
        $untouched->save();

        $this->assertSame(self::UNKNOWN_ENGINE, $this->storedEngine($storeId));

        $loaded = StoreConfig::getById($storeId);
        $this->assertInstanceOf(StoreConfig::class, $loaded);
        $this->assertSame(self::UNKNOWN_ENGINE, $loaded->getEngine());

        $loaded->setEngine(null);
        $loaded->save();

        $this->assertNull($this->storedEngine($storeId));
        $this->assertSame(
            1,
            (int) $db->fetchOne('SELECT COUNT(*) FROM classificationstore_stores WHERE id = ?', [$storeId])
        );
    }

    private function storedEngine(int $storeId): ?string
    {
        $engine = Db::get()->fetchOne('SELECT engine FROM classificationstore_stores WHERE id = ?', [$storeId]);

        return $engine === false || $engine === null ? null : (string) $engine;
    }

    /**
     * Adds a second engine to the resolver the DAOs read from the container. The container refuses
     * to replace an initialized service, so the registry of the instance it holds is what changes,
     * and tearDown() puts the original one back.
     */
    private function registerSecondEngine(EngineInterface $engine): void
    {
        $this->rememberRegisteredEngines();

        $this->setRegisteredEngines($this->registeredEngines + ['second' => $engine]);
    }

    /**
     * Replaces the whole registry, so that a test can assert the default engine is never reached.
     */
    private function replaceRegisteredEngines(EngineInterface $eavEngine, EngineInterface $secondEngine): void
    {
        $this->rememberRegisteredEngines();

        $this->setRegisteredEngines(['eav' => $eavEngine, 'second' => $secondEngine]);
    }

    private function rememberRegisteredEngines(): void
    {
        $this->registeredEngines ??= Pimcore::getContainer()->get(EngineResolver::class)->getEngines();
    }

    /**
     * @param array<string, EngineInterface> $engines
     */
    private function setRegisteredEngines(array $engines): void
    {
        $resolver = Pimcore::getContainer()->get(EngineResolver::class);

        $property = new ReflectionProperty(EngineResolver::class, 'registeredEngines');
        $property->setValue($resolver, $engines);

        // the per-store memo holds the engine instances that are being replaced
        $resolver->reset();
    }

    private function createStore(?string $engine = null): int
    {
        $store = new StoreConfig();
        $store->setName(uniqid('csEngineStore', false));
        if ($engine !== null) {
            $store->setEngine($engine);
        }
        $store->save();

        $storeId = (int) $store->getId();
        $this->storeIds[] = $storeId;

        return $storeId;
    }

    /**
     * Definition::getByKey() answers from the runtime cache before it looks for a definition file,
     * so the brick can be built in memory - nothing on the paths under test reads it from disk.
     */
    private function registerBrick(string $key, ClassificationstoreField ...$fieldDefinitions): void
    {
        $brick = new Definition();
        $brick->setKey($key);

        $byName = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            $byName[$fieldDefinition->getName()] = $fieldDefinition;
        }
        $brick->setFieldDefinitions($byName);

        RuntimeCache::set('objectbrick_' . $key, $brick);
    }

    /**
     * Definition::getByKey() answers from the runtime cache the same way the object brick one does,
     * so the field collection is built in memory as well.
     */
    private function registerFieldCollection(string $key, ClassificationstoreField ...$fieldDefinitions): void
    {
        $fieldCollection = new FieldcollectionDefinition();
        $fieldCollection->setKey($key);

        $byName = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            $byName[$fieldDefinition->getName()] = $fieldDefinition;
        }
        $fieldCollection->setFieldDefinitions($byName);

        RuntimeCache::set('fieldcollection_' . $key, $fieldCollection);
    }

    /**
     * The value tables carry a foreign key on `objects`, which any object row satisfies - the rows
     * written here stand for values, and never for an object of the class under test.
     */
    private function anyObjectId(): int
    {
        $objectId = Db::get()->fetchOne('SELECT id FROM objects ORDER BY id LIMIT 1');
        $this->assertNotFalse($objectId, 'the value tables need an object row to point at');

        return (int) $objectId;
    }

    private function createGroup(int $storeId): int
    {
        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($storeId);
        $group->setName(uniqid('csEngineGroup', false));
        $group->save();

        $groupId = (int) $group->getId();
        $this->groupIds[] = $groupId;

        return $groupId;
    }

    /**
     * @return string[] the field names the table holds for one object, sorted
     */
    private function fieldNamesIn(string $table, int $objectId): array
    {
        $fieldNames = Db::get()->fetchFirstColumn(
            'SELECT DISTINCT fieldname FROM ' . Db::get()->quoteIdentifier($table) . ' WHERE id = ?',
            [$objectId]
        );
        $fieldNames = array_map(strval(...), $fieldNames);
        sort($fieldNames);

        return $fieldNames;
    }

    private function storeField(string $name, int $storeId): ClassificationstoreField
    {
        $fieldDefinition = new ClassificationstoreField();
        $fieldDefinition->setName($name);
        $fieldDefinition->setTitle($name);
        $fieldDefinition->setStoreId($storeId);

        return $fieldDefinition;
    }

    /**
     * The fields go in through the layout, because that is what a saved class is rebuilt from:
     * a class whose field definitions are only set directly reloads without any of them.
     *
     * @param array<string, ClassDefinition\Data> $fieldDefinitions
     */
    private function buildClass(string $id, array $fieldDefinitions): ClassDefinition
    {
        $layout = new Panel();
        $layout->setName('root');
        $layout->setChildren(array_values($fieldDefinitions));

        $class = new ClassDefinition();
        $class->setId($id);
        $class->setName(lcfirst($id));
        $class->setLayoutDefinitions($layout);

        return $class;
    }

    private function tableExists(string $table): bool
    {
        return Db::get()->fetchOne('SHOW TABLES LIKE ?', [$table]) !== false;
    }

    /**
     * @return string[] the primary key columns, in their declared order
     */
    private function primaryKeyOf(string $table): array
    {
        return Db::get()->fetchFirstColumn(
            'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
            . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'"
            . ' ORDER BY ORDINAL_POSITION',
            [$table]
        );
    }

    /**
     * The integer display width is dropped: MariaDB reports `int(11) unsigned` where MySQL 8.0.19
     * and later report `int unsigned`, and the width says nothing about what the column holds.
     *
     * @return array<string, string> column name => declared type, in their declared order
     */
    private function columnTypesOf(string $table): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT COLUMN_NAME AS name, DATA_TYPE AS dataType, COLUMN_TYPE AS columnType'
            . ' FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table]
        );

        $columns = [];
        foreach ($rows as $row) {
            $type = (string) $row['columnType'];

            if (in_array((string) $row['dataType'], self::INTEGER_TYPES, true)) {
                $type = (string) preg_replace('/\(\d+\)/', '', $type);
            }

            $columns[(string) $row['name']] = $type;
        }

        return $columns;
    }

    /**
     * @return array<string, string> referenced table => delete rule
     */
    private function foreignKeysOf(string $table): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT k.REFERENCED_TABLE_NAME AS referenced, r.DELETE_RULE AS deleteRule'
            . ' FROM information_schema.KEY_COLUMN_USAGE k'
            . ' JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME'
            . ' AND r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA'
            . ' WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL',
            [$table]
        );

        $foreignKeys = [];
        foreach ($rows as $row) {
            $foreignKeys[(string) $row['referenced']] = (string) $row['deleteRule'];
        }

        ksort($foreignKeys);

        return $foreignKeys;
    }
}
