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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Telemetry\Snapshot\ActiveBundles;
use Pimcore\Tests\Support\Test\TestCase;
use stdClass;

// Stands in for a first-party bundle: this test namespace is under `Pimcore\`, which is exactly what
// ActiveBundles keys off. `stdClass` stands in for anything outside Pimcore's namespaces - a
// customer or agency bundle - since it is in the global namespace.
class PimcoreDataHubBundle
{
}

class ActiveBundlesTest extends TestCase
{
    public function testOnlyFirstPartyBundlesAreNamed(): void
    {
        $this->assertSame(['PimcoreDataHubBundle'], $this->activeBundles()->firstPartyNames());
    }

    /**
     * A customer bundle's class name is customer-authored content: it is counted so the fleet data
     * still shows how customised an install is, but it must never be transmitted.
     */
    public function testNonFirstPartyBundlesAreCountedButNeverNamed(): void
    {
        $bundles = $this->activeBundles();

        $this->assertSame(1, $bundles->thirdPartyCount());
        $this->assertSame(2, $bundles->count(), 'the total still reflects every active bundle');
        $this->assertNotContains('stdClass', $bundles->firstPartyNames());
    }

    /**
     * Capability flags are matched against first-party names only, so a bundle outside Pimcore's
     * namespaces can never flip one - even if its class name contains a Pimcore product name.
     */
    public function testCapabilityFlagsIgnoreNonFirstPartyBundles(): void
    {
        $this->assertTrue($this->activeBundles()->has('DataHub'));

        $onlyForeign = $this->activeBundles(withFirstParty: false);
        $this->assertFalse($onlyForeign->has('std'), 'a non-first-party bundle must not match');
        $this->assertSame([], $onlyForeign->firstPartyNames());
        $this->assertSame(1, $onlyForeign->thirdPartyCount());
    }

    public function testTheBundleListIsResolvedOnlyOnce(): void
    {
        $bundleManager = $this->createMock(PimcoreBundleManager::class);
        $bundleManager->expects($this->once())
            ->method('getActiveBundles')
            ->willReturn([new PimcoreDataHubBundle()]);

        $bundles = new ActiveBundles($bundleManager);
        $bundles->firstPartyNames();
        $bundles->thirdPartyCount();
        $bundles->count();
        $bundles->has('DataHub');
    }

    private function activeBundles(bool $withFirstParty = true): ActiveBundles
    {
        $active = $withFirstParty
            ? [new PimcoreDataHubBundle(), new stdClass()]
            : [new stdClass()];

        $bundleManager = $this->createMock(PimcoreBundleManager::class);
        $bundleManager->method('getActiveBundles')->willReturn($active);

        return new ActiveBundles($bundleManager);
    }
}
