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

use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Tests\Support\Helper\Pimcore as PimcoreModule;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * Fixtures and the statement-counting harness shared by the tests of the batched key/group
 * relation lookup. The configs a test creates are its own - they carry a unique name and are
 * removed again in tearDown, so that the key count the other classification store tests assert
 * stays what their own fixture made it.
 */
abstract class AbstractBatchedRelationTest extends AbstractClassificationStoreTest
{
    /**
     * Every table a classification store reads or writes carries the name: the configuration
     * tables classificationstore_groups, _keys and _relations, and the per class value tables
     * object_classificationstore_data_<CLASS> and object_classificationstore_groups_<CLASS>.
     */
    private const STORE_TABLE_PATTERN = '/classificationstore/i';

    private const READ_STATEMENTS = ['SELECT'];

    private const WRITE_STATEMENTS = ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'];

    private DebugDataHolder $debugDataHolder;

    /**
     * @var Classificationstore\GroupConfig[]
     */
    private array $createdGroups = [];

    /**
     * @var Classificationstore\KeyConfig[]
     */
    private array $createdKeys = [];

    protected function setUp(): void
    {
        parent::setUp();

        TestHelper::cleanUp();
        Pimcore::setAdminMode();
        Cache::disable();

        $container = $this->getModule('\\' . PimcoreModule::class)->_getContainer();
        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('The Doctrine debug data holder is only registered when the kernel runs in debug mode.');
        }

        $this->debugDataHolder = $container->get('doctrine.debug_data_holder');

        $this->startMeasurement();
    }

    /**
     * Drops everything that answers a key, group or relation lookup without a statement, so that a
     * measurement counts what a request with a cold cache costs. The key and group configs are
     * also held by {@see RuntimeCache} - the fixture put them there while it created them, and
     * leaving them would hide exactly the per-key lookups that are being counted.
     */
    protected function clearConfigCaches(): void
    {
        $this->getRelationCache()->reset();
        Classificationstore\DefinitionCache::clear(null);

        $runtimeCache = RuntimeCache::getInstance();
        foreach (array_keys($runtimeCache->getArrayCopy()) as $cacheKey) {
            if (str_starts_with((string) $cacheKey, 'cs_keyconfig_')
                || str_starts_with((string) $cacheKey, 'cs_groupconfig_')) {
                $runtimeCache->offsetUnset($cacheKey);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdKeys as $keyConfig) {
            $keyConfig->delete();
        }
        foreach ($this->createdGroups as $group) {
            $group->delete();
        }

        $this->createdKeys = [];
        $this->createdGroups = [];

        TestHelper::cleanUp();

        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        parent::setUpTestClasses();

        $store = Classificationstore\StoreConfig::getByName('teststore');
        $this->tester->setupPimcoreClass_CsstoreLocalized(['storeId' => $store->getId()]);

        $this->createValueTables($store, 'csstorelocalized');
    }

    protected function getRelationCache(): Classificationstore\KeyGroupRelationCache
    {
        return Pimcore::getContainer()->get(Classificationstore\KeyGroupRelationCache::class);
    }

    protected function getStoreId(): int
    {
        return Classificationstore\StoreConfig::getByName('teststore')->getId();
    }

    protected function createGroup(string $label): Classificationstore\GroupConfig
    {
        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($this->getStoreId());
        $group->setName($this->uniqueName($label));
        $group->save();

        $this->createdGroups[] = $group;

        return $group;
    }

    protected function createKey(
        string $label,
        bool $enabled = true,
        ?Data $definition = null
    ): Classificationstore\KeyConfig {
        $name = $this->uniqueName($label);

        $definition ??= new Input();
        $definition->setName($name);

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($this->getStoreId());
        $keyConfig->setName($name);
        $keyConfig->setDescription('description of ' . $label);
        $keyConfig->setEnabled($enabled);
        $keyConfig->setType($definition->getFieldType());
        $keyConfig->setDefinition(json_encode($definition));
        $keyConfig->save();

        $this->createdKeys[] = $keyConfig;

        return $keyConfig;
    }

    protected function assignKey(
        Classificationstore\GroupConfig $group,
        Classificationstore\KeyConfig $keyConfig,
        int $sorter = 0,
        bool $mandatory = false,
        ?array $definitionOverride = null
    ): Classificationstore\KeyGroupRelation {
        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setGroupId($group->getId());
        $relation->setKeyId($keyConfig->getId());
        $relation->setSorter($sorter);
        $relation->setMandatory($mandatory);
        if ($definitionOverride !== null) {
            $relation->setDefinitionOverride($definitionOverride);
        }
        $relation->save();

        return $relation;
    }

    /**
     * Starts counting from here. Every measurement also begins with an empty cache, so that the
     * batched statements are part of what is counted rather than being answered by a previous
     * measurement.
     */
    protected function startMeasurement(): void
    {
        $this->clearConfigCaches();

        $this->debugDataHolder->reset();
    }

    /**
     * The SELECTs against a classification store table issued since the measurement started.
     */
    protected function countReadStatements(): int
    {
        return count($this->grabStoreStatements(self::READ_STATEMENTS));
    }

    /**
     * The INSERTs, UPDATEs and DELETEs against a classification store table issued since the
     * measurement started.
     */
    protected function countWriteStatements(): int
    {
        return count($this->grabStoreStatements(self::WRITE_STATEMENTS));
    }

    /**
     * Every statement issued since the measurement started, whichever table it addresses.
     *
     * This is what a bound stated in statements has to be measured against: a counter that sees the
     * classification store's own SQL alone would report a bounded cost while the elements of a
     * relation were being loaded one at a time behind it.
     */
    protected function countStatements(): int
    {
        $count = 0;
        foreach ($this->debugDataHolder->getData() as $queries) {
            $count += count($queries);
        }

        return $count;
    }

    /**
     * What one more element load costs, measured on two elements of the class the relation targets
     * have.
     *
     * Resolving a relation hands back the elements themselves, and instantiating one costs this
     * many statements however it is reached: Pimcore has no bulk element loader, and a plain
     * relation field of a class pays the same per target. A bound on resolving a key is therefore
     * stated against this number rather than as if it were zero.
     *
     * It is the second load that is measured. The first load of a class also reads its class
     * definition - {@see \Pimcore\Model\DataObject\ClassDefinition::getById()} holds it in the
     * runtime cache that every measurement here clears - and that is a cost per class, not per
     * element, so counting it in would overstate what one more target costs.
     */
    protected function measureElementLoad(int $warmUpId, int $elementId): int
    {
        RuntimeCache::clear();
        $this->startMeasurement();

        DataObject::getById($warmUpId, ['force' => true]);

        $this->startMeasurement();

        DataObject::getById($elementId, ['force' => true]);

        return $this->countStatements();
    }

    /**
     * Asserts that none of the given elements was instantiated since the runtime cache was cleared,
     * which is the precise form of "no target was read": the owning object's own load reads the
     * `objects` table as well, so counting statements against it would not tell the two apart.
     *
     * @param int[] $elementIds
     */
    protected function assertNoElementInstantiated(array $elementIds, string $message = ''): void
    {
        foreach ($elementIds as $elementId) {
            $this->assertFalse(
                RuntimeCache::isRegistered('object_' . $elementId),
                $message === '' ? 'object ' . $elementId . ' must not be instantiated' : $message
            );
        }
    }

    /**
     * The statements issued since the measurement started whose SQL matches the given pattern,
     * whichever table they address.
     *
     * For a bound on one kind of lookup rather than on the total: a round of target lookups is one
     * statement with a recognisable shape, so counting the statements of that shape says how many
     * rounds a path took.
     *
     * @return string[]
     */
    protected function grabStatementsMatching(string $pattern): array
    {
        return array_values(array_filter(
            $this->grabStatements(),
            static fn (string $sql): bool => (bool) preg_match($pattern, $sql)
        ));
    }

    /**
     * MySQL does not preserve the member order of a JSON object, so a value that came out of a JSON
     * column is compared by key, with every key and every value still matching exactly.
     *
     * @param array<array-key, mixed> $expected
     */
    protected function assertSameJsonData(array $expected, mixed $actual, string $message = ''): void
    {
        $this->assertSame(
            $this->sortedByKey($expected),
            is_array($actual) ? $this->sortedByKey($actual) : $actual,
            $message
        );
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    protected function sortedByKey(array $value): array
    {
        $sorted = [];
        foreach ($value as $key => $entry) {
            $sorted[$key] = is_array($entry) ? $this->sortedByKey($entry) : $entry;
        }
        ksort($sorted);

        return $sorted;
    }

    /**
     * The statements of the given kinds that address a classification store table. What a load or
     * a save touches besides them - the object row, its versions, the system settings - is not
     * what this field batches, and reading the system settings is uncached here anyway because
     * the measurement runs with the cache disabled.
     *
     * @param string[] $kinds
     *
     * @return string[]
     */
    private function grabStoreStatements(array $kinds): array
    {
        $statements = [];
        foreach ($this->grabStatements() as $sql) {
            if (!preg_match(self::STORE_TABLE_PATTERN, $sql)) {
                continue;
            }

            if (preg_match('/^(' . implode('|', $kinds) . ')\b/i', $sql)) {
                $statements[] = $sql;
            }
        }

        return $statements;
    }

    /**
     * Every statement issued since the measurement started, its whitespace normalised.
     *
     * @return string[]
     */
    private function grabStatements(): array
    {
        $statements = [];
        foreach ($this->debugDataHolder->getData() as $queries) {
            foreach ($queries as $query) {
                $statements[] = trim(preg_replace('/\s+/', ' ', (string) $query['sql']));
            }
        }

        return $statements;
    }

    /**
     * Underscores only: a field definition name has to pass the identifier check of
     * {@see Data::setName()}.
     */
    private function uniqueName(string $label): string
    {
        return 'batched_' . $label . '_' . uniqid();
    }
}
