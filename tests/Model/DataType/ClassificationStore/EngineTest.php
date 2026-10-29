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
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;

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

    private const FACADE_CLASS_ID = 'CsEngineFacade';

    private const DELETED_CLASS_ID = 'CsEngineDeleted';

    private const REFUSED_CLASS_ID = 'CsEngineRefused';

    private const UNKNOWN_ENGINE = 'nosuchengine';

    /**
     * @var array<string, EngineInterface>|null
     */
    private ?array $registeredEngines = null;

    /**
     * @var int[]
     */
    private array $storeIds = [];

    protected function tearDown(): void
    {
        if ($this->registeredEngines !== null) {
            $this->setRegisteredEngines($this->registeredEngines);
            $this->registeredEngines = null;
        }

        $db = Db::get();
        foreach ([self::FACADE_CLASS_ID, self::DELETED_CLASS_ID, self::REFUSED_CLASS_ID] as $classId) {
            $db->executeStatement('DROP TABLE IF EXISTS ' . $db->quoteIdentifier(self::DATA_TABLE_PREFIX . $classId));
            $db->executeStatement('DROP TABLE IF EXISTS ' . $db->quoteIdentifier(self::GROUPS_TABLE_PREFIX . $classId));
        }

        foreach ($this->storeIds as $storeId) {
            $db->delete('classificationstore_stores', ['id' => $storeId]);
        }
        $this->storeIds = [];

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
        $class = $this->buildClass(self::DELETED_CLASS_ID, ['cs' => $this->storeField('cs', $this->createStore())]);
        $class->save();

        $this->assertTrue($this->tableExists(self::DATA_TABLE_PREFIX . self::DELETED_CLASS_ID));
        $this->assertTrue($this->tableExists(self::GROUPS_TABLE_PREFIX . self::DELETED_CLASS_ID));

        $secondEngine = $this->createMock(EngineInterface::class);
        $secondEngine->expects($this->once())->method('dropTables');

        $this->registerSecondEngine($secondEngine);

        $class->delete();

        $this->assertFalse($this->tableExists(self::DATA_TABLE_PREFIX . self::DELETED_CLASS_ID));
        $this->assertFalse($this->tableExists(self::GROUPS_TABLE_PREFIX . self::DELETED_CLASS_ID));
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
        $this->registeredEngines = Pimcore::getContainer()->get(EngineResolver::class)->getEngines();

        $this->setRegisteredEngines($this->registeredEngines + ['second' => $engine]);
    }

    /**
     * @param array<string, EngineInterface> $engines
     */
    private function setRegisteredEngines(array $engines): void
    {
        $resolver = Pimcore::getContainer()->get(EngineResolver::class);

        $property = new ReflectionProperty(EngineResolver::class, 'registeredEngines');
        $property->setValue($resolver, $engines);
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

    private function storeField(string $name, int $storeId): ClassificationstoreField
    {
        $fieldDefinition = new ClassificationstoreField();
        $fieldDefinition->setName($name);
        $fieldDefinition->setTitle($name);
        $fieldDefinition->setStoreId($storeId);

        return $fieldDefinition;
    }

    /**
     * @param array<string, ClassDefinition\Data> $fieldDefinitions
     */
    private function buildClass(string $id, array $fieldDefinitions): ClassDefinition
    {
        $class = new ClassDefinition();
        $class->setId($id);
        $class->setName(lcfirst($id));
        $class->setFieldDefinitions($fieldDefinitions);

        return $class;
    }

    private function tableExists(string $table): bool
    {
        return Db::get()->fetchOne('SHOW TABLES LIKE ?', [$table]) !== false;
    }
}
