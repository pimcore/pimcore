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
use Pimcore\Workflow\WorkflowConfig;
use RuntimeException;

class WorkflowUsageProviderTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $executedSql = [];

    /**
     * @var list<array<int|string, mixed>>
     */
    private array $executedParams = [];

    public function testReportsUnderTheWorkflowKey(): void
    {
        $provider = $this->provider();

        $this->assertInstanceOf(BundleUsageProviderInterface::class, $provider);
        $this->assertSame('workflow', $provider->getBundleKey());
    }

    /**
     * The reason this provider exists in this form: a workflow defined in config that no element has
     * ever entered is exactly the shelfware `usage.*` is meant to expose.
     */
    public function testAConfiguredButNeverRunWorkflowIsNotUsed(): void
    {
        $this->assertFalse($this->provider(['product_approval' => 'state_table'], 0)->isUsed());
    }

    public function testAWorkflowWithElementsInItIsUsed(): void
    {
        $this->assertTrue($this->provider(['product_approval' => 'state_table'], 51)->isUsed());
    }

    public function testNoWorkflowsConfiguredIsNotUsed(): void
    {
        $this->assertFalse($this->provider([], 0)->isUsed());
    }

    /**
     * With nothing configured there is nothing to exercise, so the state table must not be queried -
     * the majority of installs run no workflows and should pay nothing.
     */
    public function testTheStateTableIsNotQueriedWhenNothingIsConfigured(): void
    {
        $this->provider([], 0)->isUsed();

        $this->assertSame([], $this->executedSql);
    }

    /**
     * Only the `state_table` marking store persists into element_workflow_state. `single_state`,
     * `multiple_state` and both data-object stores keep the marking on the subject, where no aggregate
     * query can see it - so reporting `false` there would invent an adoption gap for a workflow that
     * may be in heavy use. Unknown is the only honest answer.
     *
     * @dataProvider unobservableMarkingStores
     */
    public function testAWorkflowWhoseMarkingIsNotInTheStateTableIsUnknownRatherThanUnused(
        string $markingStore
    ): void {
        $used = $this->provider(['product_approval' => $markingStore], 0)->isUsed();

        $this->assertNull($used, sprintf('%s keeps its marking on the subject', $markingStore));
        $this->assertNotFalse($used);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function unobservableMarkingStores(): array
    {
        return [
            'single_state' => ['single_state'],
            'multiple_state' => ['multiple_state'],
            'data_object_multiple_state' => ['data_object_multiple_state'],
            'data_object_splitted_state' => ['data_object_splitted_state'],
            'unset marking store' => [''],
        ];
    }

    /**
     * A mixed install: the observable workflow proves use, so the unobservable one cannot make the
     * answer worse. Positive evidence wins over ignorance.
     */
    public function testPositiveEvidenceFromAnObservableWorkflowWins(): void
    {
        $provider = $this->provider(
            ['product_approval' => 'state_table', 'asset_review' => 'single_state'],
            7
        );

        $this->assertTrue($provider->isUsed());
    }

    /**
     * Same mix with an empty state table: the observable one is genuinely unused, but the other cannot
     * be seen at all, so the capability as a whole is unknown - not unused.
     */
    public function testAMixedInstallWithNoEvidenceIsUnknown(): void
    {
        $provider = $this->provider(
            ['product_approval' => 'state_table', 'asset_review' => 'single_state'],
            0
        );

        $this->assertNull($provider->isUsed());
    }

    /**
     * Rows can outlive the workflow that wrote them. An unscoped count would report use that is no
     * longer possible, so the query is bound to the currently configured workflows.
     */
    public function testStateRowsAreScopedToConfiguredWorkflows(): void
    {
        $this->provider(['product_approval' => 'state_table', 'asset_review' => 'state_table'], 3)->isUsed();

        $this->assertCount(1, $this->executedSql);
        $this->assertStringContainsString('WHERE workflow IN (?, ?)', $this->executedSql[0]);
        $this->assertSame(['product_approval', 'asset_review'], $this->executedParams[0]);
    }

    /**
     * Workflow names are customer-chosen. They may be bound as query parameters but must never be
     * interpolated into SQL, where they could surface in a log or an error message.
     */
    public function testWorkflowNamesAreBoundNotInterpolated(): void
    {
        $this->provider(['secret_project_gate' => 'state_table'], 1)->isUsed();

        foreach ($this->executedSql as $sql) {
            $this->assertStringNotContainsString('secret_project_gate', $sql);
        }
    }

    /**
     * The reason `isUsed()` returns `?bool`. A manager that cannot be consulted tells us nothing about
     * adoption, and `false` there is indistinguishable from a genuine "installed but not used".
     */
    public function testAnUnavailableWorkflowManagerIsUnknownRatherThanUnused(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflows')->willThrowException(new RuntimeException('container not booted'));

        $used = (new WorkflowUsageProvider($manager, $this->queryRunner(0)))->isUsed();

        $this->assertNull($used);
        $this->assertNotFalse($used);
    }

    public function testAnUnreadableStateTableIsUnknownRatherThanUnused(): void
    {
        $used = $this->provider(['product_approval' => 'state_table'], 0, failStateQuery: true)->isUsed();

        $this->assertNull($used);
        $this->assertNotFalse($used);
    }

    /**
     * A workflow the manager cannot describe cannot be claimed as observed either.
     */
    public function testAnUnreadableWorkflowConfigIsTreatedAsUnobservable(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflows')->willReturn(['product_approval']);
        $manager->method('getWorkflowConfig')->willThrowException(new RuntimeException('workflow not found'));

        $used = (new WorkflowUsageProvider($manager, $this->queryRunner(0)))->isUsed();

        $this->assertNull($used);
        $this->assertSame([], $this->executedSql, 'nothing observable, so no query');
    }

    /**
     * @param array<string, string> $workflows name => marking store type
     */
    private function provider(
        array $workflows = ['product_approval' => 'state_table'],
        int $elementsInWorkflow = 0,
        bool $failStateQuery = false,
    ): WorkflowUsageProvider {
        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflows')->willReturn(array_keys($workflows));
        $manager->method('getWorkflowConfig')->willReturnCallback(
            static function (string $name) use ($workflows): WorkflowConfig {
                $store = $workflows[$name];
                $config = $store === '' ? [] : ['marking_store' => ['type' => $store]];

                return new WorkflowConfig($name, $config);
            }
        );

        return new WorkflowUsageProvider($manager, $this->queryRunner($elementsInWorkflow, $failStateQuery));
    }

    private function queryRunner(int $count, bool $fail = false): SnapshotQueryRunner
    {
        $this->executedSql = [];
        $this->executedParams = [];

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            function (string $sql, array $params = []) use ($count, $fail): int {
                $this->executedSql[] = $sql;
                $this->executedParams[] = $params;

                if ($fail) {
                    throw new RuntimeException('max_statement_time exceeded');
                }

                return $count;
            }
        );

        return new SnapshotQueryRunner($connection, 0);
    }
}
