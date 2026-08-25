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

namespace Pimcore\Tests\Unit\Config;

use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Key enumeration has to follow the same source precedence as loadConfigByKey(): a configured
 * read target is the only source consulted, a disabled one is not read from at all, and with
 * no read target configured both the symfony-config entries and the settings store are.
 *
 * Enumeration used to treat every case other than symfony-config as settings-store, so it
 * disagreed with loading for the two remaining ones. A disabled read target listed keys that
 * loading reads from no source at all, and queried the database to produce them. An absent one
 * listed only the settings store, hiding the container entries that loading falls back through -
 * its own store query is legitimate, since loading consults the store there too. An absent
 * read_target node additionally raised an undefined array key on the way through.
 *
 * @internal
 */
final class LocationAwareConfigRepositoryTest extends TestCase
{
    private const SCOPE = 'pimcore_test_location_aware_config';

    private const CONTAINER_CONFIG = [
        'from_symfony_config_a' => ['id' => 'from_symfony_config_a'],
        'from_symfony_config_b' => ['id' => 'from_symfony_config_b'],
    ];

    private const SETTINGS_STORE_KEY = 'from_settings_store';

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        SettingsStore::set(self::SETTINGS_STORE_KEY, '{}', 'string', self::SCOPE);
    }

    protected function tearDown(): void
    {
        SettingsStore::delete(self::SETTINGS_STORE_KEY, self::SCOPE);

        parent::tearDown();
    }

    public function testReadsBothSourcesWhenNoReadTargetIsConfigured(): void
    {
        $keys = $this->createRepository(null)->fetchAllKeysByReadTargets();

        $this->assertEqualsCanonicalizing(
            ['from_symfony_config_a', 'from_symfony_config_b', self::SETTINGS_STORE_KEY],
            $keys,
            'without a read target both sources are readable, so both have to be enumerated'
        );
    }

    public function testReadsBothSourcesWhenTheReadTargetIsAbsentEntirely(): void
    {
        // A storage config that carries no read_target node at all, rather than one holding null.
        $repository = new LocationAwareConfigRepository(
            self::CONTAINER_CONFIG,
            self::SCOPE,
            [LocationAwareConfigRepository::WRITE_TARGET => $this->writeTarget()]
        );

        $this->assertEqualsCanonicalizing(
            ['from_symfony_config_a', 'from_symfony_config_b', self::SETTINGS_STORE_KEY],
            $repository->fetchAllKeysByReadTargets()
        );
    }

    public function testReadsOnlySymfonyConfigWhenItIsTheReadTarget(): void
    {
        $keys = $this->createRepository(LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG)
            ->fetchAllKeysByReadTargets();

        $this->assertEqualsCanonicalizing(['from_symfony_config_a', 'from_symfony_config_b'], $keys);
        $this->assertNotContains(
            self::SETTINGS_STORE_KEY,
            $keys,
            'a symfony-config read target must not fall back to the settings store'
        );
    }

    public function testReadsNothingWhenTheReadTargetIsDisabled(): void
    {
        $keys = $this->createRepository(LocationAwareConfigRepository::LOCATION_DISABLED)
            ->fetchAllKeysByReadTargets();

        $this->assertSame(
            [],
            $keys,
            'loadConfigByKey() reads from no source for a disabled read target, so nothing is listed'
        );
    }

    public function testReadsOnlyTheSettingsStoreWhenItIsTheReadTarget(): void
    {
        $keys = $this->createRepository(LocationAwareConfigRepository::LOCATION_SETTINGS_STORE)
            ->fetchAllKeysByReadTargets();

        $this->assertSame([self::SETTINGS_STORE_KEY], $keys);
    }

    private function createRepository(?string $readTargetType): LocationAwareConfigRepository
    {
        return new LocationAwareConfigRepository(
            self::CONTAINER_CONFIG,
            self::SCOPE,
            [
                LocationAwareConfigRepository::WRITE_TARGET => $this->writeTarget(),
                LocationAwareConfigRepository::READ_TARGET => [
                    LocationAwareConfigRepository::TYPE => $readTargetType,
                    LocationAwareConfigRepository::OPTIONS => [
                        LocationAwareConfigRepository::DIRECTORY => null,
                    ],
                ],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function writeTarget(): array
    {
        return [
            LocationAwareConfigRepository::TYPE => LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG,
            LocationAwareConfigRepository::OPTIONS => [
                LocationAwareConfigRepository::DIRECTORY => PIMCORE_PRIVATE_VAR . '/config/test',
            ],
        ];
    }
}
