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

namespace Pimcore\Tests\Model\Config;

use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Db;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tests\Support\Test\ModelTestCase;

class LocationAwareConfigRepositoryTest extends ModelTestCase
{
    private const SCOPE = 'location_aware_config_test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteScope();
    }

    protected function tearDown(): void
    {
        $this->deleteScope();
        parent::tearDown();
    }

    /**
     * The settings store is not part of the per-test database cleanup.
     */
    private function deleteScope(): void
    {
        foreach (SettingsStore::getIdsByScope(self::SCOPE) as $id) {
            SettingsStore::delete($id, self::SCOPE);
        }
    }

    private function createRepository(?string $readTarget, array $containerConfig = []): LocationAwareConfigRepository
    {
        return new LocationAwareConfigRepository($containerConfig, self::SCOPE, [
            LocationAwareConfigRepository::WRITE_TARGET => [
                LocationAwareConfigRepository::TYPE => LocationAwareConfigRepository::LOCATION_SETTINGS_STORE,
                LocationAwareConfigRepository::OPTIONS => [LocationAwareConfigRepository::DIRECTORY => null],
            ],
            LocationAwareConfigRepository::READ_TARGET => [
                LocationAwareConfigRepository::TYPE => $readTarget,
                LocationAwareConfigRepository::OPTIONS => [LocationAwareConfigRepository::DIRECTORY => null],
            ],
        ]);
    }

    /**
     * Changes a stored config without going through the model, so that a later read shows whether it queried the
     * database or was answered from memory.
     */
    private function changeBehindTheBack(string $key, array $data): void
    {
        Db::get()->update(SettingsStore\Dao::TABLE_NAME, ['data' => json_encode($data)], ['id' => $key, 'scope' => self::SCOPE]);
    }

    public function testListingLoadsTheSettingsStoreEntriesAtOnce(): void
    {
        $repository = $this->createRepository(null, ['from-container' => ['a' => 'container']]);
        $repository->saveConfig('config-1', ['a' => 1]);
        $repository->saveConfig('config-2', ['a' => 2]);
        $repository->saveConfig('123', ['a' => 3]);

        $keys = $repository->fetchAllKeys();
        $this->assertEqualsCanonicalizing(['config-1', 'config-2', '123', 'from-container'], $keys);
        foreach ($keys as $key) {
            $this->assertIsString($key, 'numeric ids stay strings');
        }

        $this->changeBehindTheBack('config-1', ['a' => 'changed']);

        // the listing loaded the entries: no query, so the change is not seen
        $this->assertSame([['a' => 1], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $repository->loadConfigByKey('config-1'));
        $this->assertSame([['a' => 3], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $repository->loadConfigByKey('123'));
        // a new repository of the same scope, as every config model creates one, shares them
        $this->assertSame([['a' => 1], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $this->createRepository(null)->loadConfigByKey('config-1'));

        // the container config still takes precedence, and missing keys behave as before
        $this->assertSame([['a' => 'container'], LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG], $repository->loadConfigByKey('from-container'));
        $this->assertSame([null, null], $repository->loadConfigByKey('missing'));

        // the next listing reads the store again
        $repository->fetchAllKeys();
        $this->assertSame([['a' => 'changed'], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $repository->loadConfigByKey('config-1'));
    }

    public function testKeysAddedAfterTheListingAreStillFound(): void
    {
        $repository = $this->createRepository(LocationAwareConfigRepository::LOCATION_SETTINGS_STORE);
        $repository->saveConfig('config-1', ['a' => 1]);
        $this->assertSame(['config-1'], $repository->fetchAllKeysByReadTargets());

        // e.g. by another process
        Db::get()->insert(SettingsStore\Dao::TABLE_NAME, [
            'id' => 'config-2',
            'scope' => self::SCOPE,
            'data' => json_encode(['a' => 2]),
            'type' => SettingsStore::TYPE_STRING,
        ]);

        $this->assertSame([['a' => 2], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $repository->loadConfigByKey('config-2'));
    }

    public function testWritesDropTheLoadedEntries(): void
    {
        $repository = $this->createRepository(null);
        $repository->saveConfig('config-1', ['a' => 1]);
        $repository->saveConfig('config-2', ['a' => 2]);

        $repository->fetchAllKeys();
        $this->changeBehindTheBack('config-1', ['a' => 'changed']);
        $repository->saveConfig('config-2', ['a' => 'saved']);
        $this->assertSame([['a' => 'changed'], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $repository->loadConfigByKey('config-1'));
        $this->assertSame([['a' => 'saved'], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $repository->loadConfigByKey('config-2'));

        $repository->fetchAllKeys();
        $repository->deleteData('config-2', LocationAwareConfigRepository::LOCATION_SETTINGS_STORE);
        $this->assertSame([null, null], $repository->loadConfigByKey('config-2'));

        // a direct write to the scope counts as well
        $repository->fetchAllKeys();
        $this->changeBehindTheBack('config-1', ['a' => 'changed again']);
        SettingsStore::set('unrelated', 'x', SettingsStore::TYPE_STRING, self::SCOPE);
        $this->assertSame([['a' => 'changed again'], LocationAwareConfigRepository::LOCATION_SETTINGS_STORE], $repository->loadConfigByKey('config-1'));
    }

    public function testSymfonyConfigReadTargetDoesNotLoadTheEntries(): void
    {
        $repository = $this->createRepository(LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG, ['from-container' => ['a' => 'container']]);
        SettingsStore::set('config-1', json_encode(['a' => 1]), SettingsStore::TYPE_STRING, self::SCOPE);

        $this->assertEqualsCanonicalizing(['config-1', 'from-container'], $repository->fetchAllKeys());
        $this->assertNull(SettingsStore::getFromScopeSnapshot('config-1', self::SCOPE));
        $this->assertSame([['a' => 'container'], LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG], $repository->loadConfigByKey('from-container'));
    }
}
