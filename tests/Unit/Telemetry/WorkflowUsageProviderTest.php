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

use Doctrine\DBAL\Connection;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Telemetry\Usage\BundleUsageProviderInterface;
use Pimcore\Telemetry\Usage\WorkflowUsageProvider;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\Manager;
use RuntimeException;

class WorkflowUsageProviderTest extends TestCase
{
    public function testReportsUnderTheWorkflowKey(): void
    {
        $provider = $this->provider();

        $this->assertInstanceOf(BundleUsageProviderInterface::class, $provider);
        $this->assertSame('workflow', $provider->getBundleKey());
    }

    /**
     * The reason this provider changed: a workflow defined in config that no element has ever entered
     * is exactly the shelfware `usage.*` exists to expose. Counting it as used would report an adoption
     * success where there is none.
     */
    public function testAConfiguredButNeverRunWorkflowIsNotUsed(): void
    {
        $this->assertFalse($this->provider(workflows: ['product_approval'], elementsInWorkflow: 0)->isUsed());
    }

    public function testAWorkflowWithElementsInItIsUsed(): void
    {
        $this->assertTrue($this->provider(workflows: ['product_approval'], elementsInWorkflow: 51)->isUsed());
    }

    /**
     * Self-resetting: archiving the last in-flight element flips it back rather than leaving a
     * sticky true.
     */
    public function testNoWorkflowsConfiguredIsNotUsed(): void
    {
        $this->assertFalse($this->provider(workflows: [], elementsInWorkflow: 0)->isUsed());
    }

    /**
     * With nothing configured there is nothing to exercise, so the state table must not be queried -
     * the cheap path stays cheap on the majority of installs that use no workflows at all.
     */
    public function testTheStateTableIsNotQueriedWhenNothingIsConfigured(): void
    {
        $executed = [];
        $this->provider(workflows: [], executedSql: $executed)->isUsed();

        $this->assertSame([], $executed);
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

        $used = (new WorkflowUsageProvider($manager, $this->queryRunner(0)))->isUsed();

        $this->assertNull($used, 'a failure must not be reported as "not used"');
        $this->assertNotFalse($used);
    }

    /**
     * Same rule one level down: workflows are configured but the state table cannot be read, so
     * adoption is unknown rather than absent.
     */
    public function testAnUnreadableStateTableIsUnknownRatherThanUnused(): void
    {
        $used = $this->provider(workflows: ['product_approval'], failStateQuery: true)->isUsed();

        $this->assertNull($used, 'an unreadable state table must not be reported as "not used"');
        $this->assertNotFalse($used);
    }

    /**
     * @param list<string>  $workflows
     * @param list<string>  $executedSql captured by reference
     */
    private function provider(
        array $workflows = ['product_approval'],
        int $elementsInWorkflow = 0,
        bool $failStateQuery = false,
        array &$executedSql = [],
    ): WorkflowUsageProvider {
        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflows')->willReturn($workflows);

        return new WorkflowUsageProvider(
            $manager,
            $this->queryRunner($elementsInWorkflow, $failStateQuery, $executedSql)
        );
    }

    /**
     * @param list<string> $executedSql captured by reference
     */
    private function queryRunner(
        int $count,
        bool $fail = false,
        array &$executedSql = [],
    ): SnapshotQueryRunner {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            function (string $sql) use ($count, $fail, &$executedSql): int {
                $executedSql[] = $sql;

                if ($fail) {
                    throw new RuntimeException('max_statement_time exceeded');
                }

                return $count;
            }
        );

        return new SnapshotQueryRunner($connection, 0);
    }
}
