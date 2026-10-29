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
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Tests\Support\Helper\Pimcore as PimcoreModule;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * Reports what a classification store of about 300 keys costs, so that the figures of the branch
 * can be put next to the figures of its base. It measures rather than asserts: the budget is a
 * sanity bound, the output is the result.
 *
 * It is skipped unless PIMCORE_TEST_CS_BENCHMARK is set, because seeding the store takes far
 * longer than the suite's other tests and the numbers are only read when somebody asks for them.
 *
 * The harness is deliberately self contained and refers to the relation cache by service id
 * rather than by class, so that the same file also runs against a checkout that does not carry
 * the cache yet. That is what makes the before side a measurement instead of an estimate.
 *
 * @group model.datatype.classificationstore
 */
class BenchmarkTest extends AbstractClassificationStoreTest
{
    private const FIELDNAME = 'csstore';

    private const RELATION_CACHE_SERVICE = 'Pimcore\\Model\\DataObject\\Classificationstore\\KeyGroupRelationCache';

    private const GROUP_COUNT = 10;

    private const KEYS_PER_GROUP = 30;

    /**
     * Wall time of a single run is dominated by whatever else the machine is doing, so every
     * scenario is run this many times and the median and the fastest run are both reported. The
     * statement counts do not vary between runs.
     */
    private const RUNS = 9;

    private const ENVIRONMENT_FLAG = 'PIMCORE_TEST_CS_BENCHMARK';

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

        if (!getenv(self::ENVIRONMENT_FLAG)) {
            $this->markTestSkipped('Set ' . self::ENVIRONMENT_FLAG . '=1 to run the classification store benchmark.');
        }

        TestHelper::cleanUp();
        Pimcore::setAdminMode();
        Cache::disable();

        $container = $this->getModule('\\' . PimcoreModule::class)->_getContainer();
        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('The Doctrine debug data holder is only registered when the kernel runs in debug mode.');
        }

        $this->debugDataHolder = $container->get('doctrine.debug_data_holder');
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
    }

    /**
     * The four scenarios the pull request reports: what the editor pays to load the field, to
     * build the payload it renders, to build the layout around it, and what a one value edit pays
     * to save. Each is reported as the statements against a classification store table, the
     * statements altogether, and the median and fastest wall time of {@see self::RUNS} runs.
     */
    public function testReportTheCostOfAStoreOfThreeHundredKeys(): void
    {
        $languages = Tool::getValidLanguages();
        $keyCount = self::GROUP_COUNT * self::KEYS_PER_GROUP;

        $object = $this->seedObject('benchmark', $languages);
        $objectId = $object->getId();

        $this->report('Store', [
            'keys' => $keyCount,
            'groups' => self::GROUP_COUNT,
            'languages' => count($languages),
            'value rows' => $this->countStoredValueRows($objectId),
        ]);

        $load = $this->measure(
            'Editor load (Dao::load)',
            static fn (): int => $objectId,
            static function (int $id): void {
                Csstorelocalized::getById($id, ['force' => true])->getCsstore()->getItems();
            }
        );

        $this->measure(
            'Editmode payload (getDataForEditmode)',
            static fn (): Csstorelocalized => Csstorelocalized::getById($objectId, ['force' => true]),
            static function (Csstorelocalized $fresh): void {
                $fresh->getClass()
                    ->getFieldDefinition(self::FIELDNAME)
                    ->getDataForEditmode($fresh->getCsstore(), $fresh);
            }
        );

        $this->measure(
            'Layout build (enrichLayoutDefinition)',
            static fn (): Csstorelocalized => Csstorelocalized::getById($objectId, ['force' => true]),
            static function (Csstorelocalized $fresh): void {
                $fresh->getClass()->getFieldDefinition(self::FIELDNAME)->enrichLayoutDefinition($fresh);
            }
        );

        $groupId = $this->createdGroups[0]->getId();
        $keyId = $this->createdKeys[0]->getId();
        $language = $languages[0];
        $edit = 0;

        $save = $this->measure(
            'Save one changed value',
            static function () use ($objectId, $groupId, $keyId, $language, &$edit): Csstorelocalized {
                $fresh = Csstorelocalized::getById($objectId, ['force' => true]);
                $fresh->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'edited-' . ++$edit, $language);

                return $fresh;
            },
            static function (Csstorelocalized $fresh): void {
                $fresh->save();
            }
        );

        $this->assertSame(
            $keyCount * count($languages),
            $this->countStoredValueRows($objectId),
            'precondition: the seeded object holds one row per key and language'
        );
        $this->assertGreaterThan(0, $load, 'precondition: loading the store reads a classification store table');
        $this->assertGreaterThan(0, $save, 'precondition: saving the store writes a classification store table');
    }

    /**
     * Runs the scenario once to warm whatever the first call of a process pays for, then
     * {@see self::RUNS} times for the figures. Only $act is measured; $prepare builds what it
     * works on, so that the load a save needs is not counted as part of the save.
     *
     * @return int the statements the action issued against a classification store table
     */
    private function measure(string $scenario, callable $prepare, callable $act): int
    {
        $act($prepare());

        $storeStatements = 0;
        $allStatements = 0;
        $durations = [];

        for ($run = 0; $run < self::RUNS; $run++) {
            $subject = $prepare();

            $this->startMeasurement();
            $startedAt = microtime(true);
            $act($subject);
            $durations[] = (microtime(true) - $startedAt) * 1000;

            $storeStatements = $this->countStoreStatements();
            $allStatements = $this->countStatements();
        }

        sort($durations);

        $this->report($scenario, [
            'store statements' => $storeStatements,
            'all statements' => $allStatements,
            'median ms' => round($durations[intdiv(self::RUNS, 2)], 1),
            'fastest ms' => round($durations[0], 1),
        ]);

        return $storeStatements;
    }

    private function countStoredValueRows(int $objectId): int
    {
        $table = 'object_classificationstore_data_' . Csstorelocalized::getById($objectId)->getClass()->getId();

        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM `' . $table . '` WHERE `id` = ? AND `fieldname` = ?',
            [$objectId, self::FIELDNAME]
        );
    }

    /**
     * Starts counting from here, with every cache that would answer a key, group, relation or
     * field definition lookup without work dropped, so that the figures are those of a cold
     * request. Service::$definitionsCache is dropped too: it lives for the whole process and is
     * never invalidated, so leaving it would make the second run of a scenario - and therefore
     * a checkout that has only that cache - look cheaper than a request actually is.
     */
    private function startMeasurement(): void
    {
        Classificationstore\DefinitionCache::clear(null);
        Classificationstore\Service::clearDefinitionsCache();

        $container = $this->getModule('\\' . PimcoreModule::class)->_getContainer();
        if ($container->has(self::RELATION_CACHE_SERVICE)) {
            $container->get(self::RELATION_CACHE_SERVICE)->reset();
        }

        $runtimeCache = RuntimeCache::getInstance();
        foreach (array_keys($runtimeCache->getArrayCopy()) as $cacheKey) {
            if (str_starts_with((string) $cacheKey, 'cs_keyconfig_')
                || str_starts_with((string) $cacheKey, 'cs_groupconfig_')) {
                $runtimeCache->offsetUnset($cacheKey);
            }
        }

        $this->debugDataHolder->reset();
    }

    private function countStatements(): int
    {
        $count = 0;
        foreach ($this->debugDataHolder->getData() as $queries) {
            $count += count($queries);
        }

        return $count;
    }

    private function countStoreStatements(): int
    {
        $count = 0;
        foreach ($this->debugDataHolder->getData() as $queries) {
            foreach ($queries as $query) {
                if (preg_match('/classificationstore/i', (string) $query['sql'])) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * @param array<string, int|float> $figures
     */
    private function report(string $scenario, array $figures): void
    {
        $parts = [];
        foreach ($figures as $name => $figure) {
            $parts[] = $name . '=' . $figure;
        }

        codecept_debug('[benchmark] ' . $scenario . ': ' . implode(', ', $parts));
    }

    /**
     * @param string[] $languages
     */
    private function seedObject(string $key, array $languages): Csstorelocalized
    {
        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);

        $store = $object->getCsstore();
        $activeGroups = [];

        for ($groupIndex = 0; $groupIndex < self::GROUP_COUNT; $groupIndex++) {
            $group = $this->createGroup('group' . $groupIndex);
            $activeGroups[$group->getId()] = true;

            for ($keyIndex = 0; $keyIndex < self::KEYS_PER_GROUP; $keyIndex++) {
                $keyConfig = $this->createKey('key' . $groupIndex . '_' . $keyIndex);
                $this->assignKey($group, $keyConfig, $keyIndex);

                foreach ($languages as $language) {
                    $store->setLocalizedKeyValue(
                        $group->getId(),
                        $keyConfig->getId(),
                        'value-' . $groupIndex . '-' . $keyIndex . '-' . $language,
                        $language
                    );
                }
            }
        }

        $store->setActiveGroups($activeGroups);
        $object->save();

        return $object;
    }

    private function createGroup(string $label): Classificationstore\GroupConfig
    {
        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($this->getStoreId());
        $group->setName($this->uniqueName($label));
        $group->save();

        $this->createdGroups[] = $group;

        return $group;
    }

    private function createKey(string $label): Classificationstore\KeyConfig
    {
        $name = $this->uniqueName($label);

        $definition = new Input();
        $definition->setName($name);

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($this->getStoreId());
        $keyConfig->setName($name);
        $keyConfig->setDescription('description of ' . $label);
        $keyConfig->setEnabled(true);
        $keyConfig->setType($definition->getFieldType());
        $keyConfig->setDefinition(json_encode($definition));
        $keyConfig->save();

        $this->createdKeys[] = $keyConfig;

        return $keyConfig;
    }

    private function assignKey(
        Classificationstore\GroupConfig $group,
        Classificationstore\KeyConfig $keyConfig,
        int $sorter
    ): void {
        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setGroupId($group->getId());
        $relation->setKeyId($keyConfig->getId());
        $relation->setSorter($sorter);
        $relation->setMandatory(false);
        $relation->save();
    }

    private function getStoreId(): int
    {
        return Classificationstore\StoreConfig::getByName('teststore')->getId();
    }

    private function uniqueName(string $label): string
    {
        return 'benchmark_' . $label . '_' . uniqid();
    }
}
