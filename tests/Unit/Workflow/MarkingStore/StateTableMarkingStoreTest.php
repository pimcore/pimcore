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

namespace Pimcore\Tests\Unit\Workflow\MarkingStore;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\MarkingStore\PendingMarkingStoreInterface;
use Pimcore\Workflow\MarkingStore\StateTableMarkingStore;
use RuntimeException;
use Symfony\Component\Workflow\Marking;

/**
 * Covers the part of the state_table marking store that does not touch the
 * database: keeping a marking pending on the subject for a draft
 * (https://github.com/pimcore/pimcore/issues/18653). The table access is
 * stubbed where a test needs it.
 */
class StateTableMarkingStoreTest extends TestCase
{
    private const WORKFLOW_NAME = 'test_wf';

    public function testSaveVersionContextKeepsMarkingPendingOnSubject(): void
    {
        $store = $this->createStoreWithStubbedTable(['draft' => 1]);
        $subject = $this->createSubject();

        $store->setMarking(
            $subject,
            new Marking(['review' => 1, 'translation' => 1]),
            [PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION => true]
        );

        $this->assertSame(['review', 'translation'], $subject->__getPendingWorkflowMarking(self::WORKFLOW_NAME));
        $this->assertSame(
            ['review' => 1, 'translation' => 1],
            $store->getMarking($subject)->getPlaces(),
            'The pending marking must be reported as the current marking of the draft.'
        );
    }

    public function testPendingMarkingsAreScopedToTheirWorkflow(): void
    {
        $subject = $this->createSubject();
        $subject->__setPendingWorkflowMarking('other_wf', ['done']);

        $store = $this->createStoreWithStubbedTable(['draft' => 1]);
        $store->setMarking($subject, new Marking(['review' => 1]), [PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION => true]);

        $this->assertSame(
            ['other_wf' => ['done'], self::WORKFLOW_NAME => ['review']],
            $subject->__getPendingWorkflowMarkings()
        );
    }

    public function testPendingMarkingIsPartOfVersionDumpButNotOfCache(): void
    {
        $subject = $this->createSubject();
        $subject->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);

        $subject->setInDumpState(true);
        $this->assertContains('__pendingWorkflowMarkings', $subject->__sleep(), 'A version dump must carry the pending marking.');
        $this->assertContains('__pendingWorkflowMarkingBases', $subject->__sleep(), 'A version dump must carry the base of the pending marking.');

        $subject->setInDumpState(false);
        $this->assertNotContains('__pendingWorkflowMarkings', $subject->__sleep(), 'The cache must not carry the pending marking.');
        $this->assertNotContains('__pendingWorkflowMarkingBases', $subject->__sleep(), 'The cache must not carry the base of the pending marking.');
    }

    /**
     * The persisted and the pending marking are reported separately, so that a
     * rollback can restore each of them; getMarking() prefers the pending one.
     */
    public function testPersistedAndPendingMarkingAreReportedSeparately(): void
    {
        $store = $this->createStoreWithStubbedTable(['published' => 1]);
        $subject = $this->createSubject();

        $this->assertNull($store->getPendingMarking($subject));
        $this->assertSame(['published' => 1], $store->getMarking($subject)->getPlaces());

        $store->setPendingMarking($subject, new Marking(['review' => 1]));

        $this->assertSame(['review' => 1], $store->getPendingMarking($subject)?->getPlaces());
        $this->assertSame(['published' => 1], $store->getPersistedMarking($subject)->getPlaces());
        $this->assertSame(['review' => 1], $store->getMarking($subject)->getPlaces());

        $store->setPendingMarking($subject, null);

        $this->assertNull($store->getPendingMarking($subject));
        $this->assertSame(['published' => 1], $store->getMarking($subject)->getPlaces());
    }

    public function testPersistingClearsThePendingMarkingOnlyAfterTheWriteSucceeded(): void
    {
        $store = $this->createStoreWithStubbedTable();
        $store->expects($this->exactly(2))->method('persistPlaces');
        $subject = $this->createSubject();

        $subject->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);
        $store->persistPendingMarking($subject);
        $this->assertNull($subject->__getPendingWorkflowMarking(self::WORKFLOW_NAME), 'Publishing the draft persists and clears the pending marking.');

        $subject->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);
        $store->setMarking($subject, new Marking(['done' => 1]));
        $this->assertNull($subject->__getPendingWorkflowMarking(self::WORKFLOW_NAME), 'A directly persisted marking supersedes the pending one.');
    }

    /**
     * If the table cannot be written, the draft's place must stay in memory: it is
     * still the state of the draft, and clearing it would lose it silently.
     */
    public function testFailedWriteKeepsThePendingMarking(): void
    {
        $store = $this->createStoreWithStubbedTable();
        $store->method('persistPlaces')->willThrowException(new RuntimeException('element_workflow_state unavailable'));
        $subject = $this->createSubject();
        $subject->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);

        foreach (['persistPendingMarking', 'setMarking'] as $method) {
            try {
                $method === 'persistPendingMarking'
                    ? $store->persistPendingMarking($subject)
                    : $store->setMarking($subject, new Marking(['done' => 1]));
                $this->fail('The failure of the table write must propagate.');
            } catch (RuntimeException) {
                $this->assertSame(
                    ['review'],
                    $subject->__getPendingWorkflowMarking(self::WORKFLOW_NAME),
                    sprintf('%s() must not clear the pending marking when the write fails.', $method)
                );
            }
        }
    }

    /**
     * The base is the persisted marking at the time the draft's workflow run started. Further
     * version-only transitions replace the pending places but must keep that base.
     */
    public function testBaseIsRecordedWhenTheMarkingFirstBecomesPendingAndKeptAfterwards(): void
    {
        $store = $this->getMockBuilder(StateTableMarkingStore::class)
            ->setConstructorArgs([self::WORKFLOW_NAME])
            ->onlyMethods(['persistPlaces', 'getPersistedMarking'])
            ->getMock();
        $store->expects($this->once())->method('getPersistedMarking')->willReturn(new Marking(['draft' => 1]));
        $subject = $this->createSubject();
        $context = [PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION => true];

        $store->setMarking($subject, new Marking(['review' => 1]), $context);
        $store->setMarking($subject, new Marking(['approval' => 1]), $context);

        $this->assertSame(['approval'], $subject->__getPendingWorkflowMarking(self::WORKFLOW_NAME));
        $this->assertSame(['draft'], $subject->__getPendingWorkflowMarkingBase(self::WORKFLOW_NAME));
    }

    public function testRemovingThePendingMarkingRemovesItsBase(): void
    {
        $store = $this->createStoreWithStubbedTable(['draft' => 1]);
        $subject = $this->createSubject();

        $store->setPendingMarking($subject, new Marking(['review' => 1]));
        $this->assertSame(['draft'], $subject->__getPendingWorkflowMarkingBase(self::WORKFLOW_NAME));

        $store->setPendingMarking($subject, null);
        $this->assertNull($subject->__getPendingWorkflowMarkingBase(self::WORKFLOW_NAME));
    }

    public function testPendingMarkingIsPersistedWhileTheElementIsStillInItsBasePlace(): void
    {
        $store = $this->createStoreWithStubbedTable(['draft' => 1]);
        $store->expects($this->once())->method('persistPlaces')->with($this->anything(), ['review']);
        $subject = $this->createSubject();
        $subject->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review'], ['draft']);

        $store->persistPendingMarking($subject);

        $this->assertNull($subject->__getPendingWorkflowMarking(self::WORKFLOW_NAME));
    }

    /**
     * The element was moved to another place after the draft's workflow run started (for example
     * by a transition applied to the published element). Publishing the draft must not move it back.
     */
    public function testStalePendingMarkingIsDroppedInsteadOfOverwritingANewerPlace(): void
    {
        $store = $this->createStoreWithStubbedTable(['archived' => 1]);
        $store->expects($this->never())->method('persistPlaces');
        $subject = $this->createSubject();
        $subject->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review'], ['draft']);

        $store->persistPendingMarking($subject);

        $this->assertNull($subject->__getPendingWorkflowMarking(self::WORKFLOW_NAME), 'A stale pending marking is dropped.');
        $this->assertNull($subject->__getPendingWorkflowMarkingBase(self::WORKFLOW_NAME));
    }

    public function testBaseComparisonIgnoresThePlaceOrder(): void
    {
        $store = $this->createStoreWithStubbedTable(['translation' => 1, 'review' => 1]);
        $store->expects($this->once())->method('persistPlaces')->with($this->anything(), ['done']);
        $subject = $this->createSubject();
        $subject->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['done'], ['review', 'translation']);

        $store->persistPendingMarking($subject);
    }

    /**
     * @param array<string, int> $persistedPlaces what the stubbed table reports as persisted marking
     */
    private function createStoreWithStubbedTable(array $persistedPlaces = []): StateTableMarkingStore&MockObject
    {
        $store = $this->getMockBuilder(StateTableMarkingStore::class)
            ->setConstructorArgs([self::WORKFLOW_NAME])
            ->onlyMethods(['persistPlaces', 'getPersistedMarking'])
            ->getMock();
        $store->method('getPersistedMarking')->willReturn(new Marking($persistedPlaces));

        return $store;
    }

    private function createSubject(): Asset
    {
        $asset = new Asset();
        $asset->setId(42);
        // avoid lazy loading properties from the database when serializing
        $asset->setProperties([]);

        return $asset;
    }
}
