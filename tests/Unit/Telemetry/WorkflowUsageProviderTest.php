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

use Pimcore\Telemetry\Usage\BundleUsageProviderInterface;
use Pimcore\Telemetry\Usage\WorkflowUsageProvider;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\Manager;
use RuntimeException;

class WorkflowUsageProviderTest extends TestCase
{
    public function testReportsUnderTheWorkflowKey(): void
    {
        $provider = new WorkflowUsageProvider($this->manager([]));

        $this->assertInstanceOf(BundleUsageProviderInterface::class, $provider);
        $this->assertSame('workflow', $provider->getBundleKey());
    }

    public function testWorkflowsConfiguredCountsAsUsed(): void
    {
        $this->assertTrue((new WorkflowUsageProvider($this->manager(['product_approval'])))->isUsed());
    }

    /**
     * Self-resetting: the capability owns the definition of "used", and removing the last workflow
     * flips it back rather than leaving a sticky true.
     */
    public function testNoWorkflowsCountsAsNotUsed(): void
    {
        $this->assertFalse((new WorkflowUsageProvider($this->manager([])))->isUsed());
    }

    /**
     * The reason `isUsed()` returns `?bool`. A manager that cannot be consulted tells us nothing about
     * adoption, and `false` there is indistinguishable from a genuine "installed but not used" - the
     * one signal the `usage.*` namespace exists to produce. Null omits the key instead.
     */
    public function testAnUnavailableWorkflowManagerIsUnknownRatherThanUnused(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflows')->willThrowException(new RuntimeException('container not booted'));

        $used = (new WorkflowUsageProvider($manager))->isUsed();

        $this->assertNull($used, 'a failure must not be reported as "not used"');
        $this->assertNotFalse($used);
    }

    /**
     * @param list<string> $workflows
     */
    private function manager(array $workflows): Manager
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflows')->willReturn($workflows);

        return $manager;
    }
}
