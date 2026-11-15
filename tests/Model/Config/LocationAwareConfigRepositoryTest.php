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
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tests\Support\Test\ModelTestCase;

class LocationAwareConfigRepositoryTest extends ModelTestCase
{
    private const SCOPE = 'pimcore_test_location_aware';

    protected function tearDown(): void
    {
        foreach (SettingsStore::getIdsByScope(self::SCOPE) as $id) {
            SettingsStore::delete($id, self::SCOPE);
        }

        parent::tearDown();
    }

    private function createRepository(?string $readTarget): LocationAwareConfigRepository
    {
        return new LocationAwareConfigRepository(
            ['FromContainer' => ['id' => 'FromContainer']],
            self::SCOPE,
            [
                'write_target' => ['type' => 'settings-store', 'options' => ['directory' => '/tmp/unused']],
                'read_target' => ['type' => $readTarget, 'options' => ['directory' => '/tmp/unused']],
            ]
        );
    }

    public function testWithoutReadTargetKeysOfContainerAndSettingsStoreAreListed(): void
    {
        $repository = $this->createRepository(null);
        $repository->saveConfig('FromStore', ['id' => 'FromStore']);

        $keys = $repository->fetchAllKeysByReadTargets();
        sort($keys);

        $this->assertSame(['FromContainer', 'FromStore'], $keys);
        $this->assertSame(['id' => 'FromStore'], $repository->loadConfigByKey('FromStore')[0]);
        $this->assertSame(['id' => 'FromContainer'], $repository->loadConfigByKey('FromContainer')[0]);
    }

    public function testExplicitReadTargetsLimitTheListedKeys(): void
    {
        $this->createRepository(null)->saveConfig('FromStore', ['id' => 'FromStore']);

        $this->assertSame(['FromContainer'], $this->createRepository('symfony-config')->fetchAllKeysByReadTargets());
        $this->assertSame(['FromStore'], $this->createRepository('settings-store')->fetchAllKeysByReadTargets());
    }
}
