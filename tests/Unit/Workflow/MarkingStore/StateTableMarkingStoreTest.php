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

use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\MarkingStore\PendingMarkingStoreInterface;
use Pimcore\Workflow\MarkingStore\StateTableMarkingStore;
use Symfony\Component\Workflow\Marking;

/**
 * Covers the part of the state_table marking store that does not touch the
 * database: keeping a marking pending on the subject for a draft
 * (https://github.com/pimcore/pimcore/issues/18653).
 */
class StateTableMarkingStoreTest extends TestCase
{
    private const WORKFLOW_NAME = 'test_wf';

    public function testSaveVersionContextKeepsMarkingPendingOnSubject(): void
    {
        $store = new StateTableMarkingStore(self::WORKFLOW_NAME);
        $subject = $this->createSubject();

        $store->setMarking(
            $subject,
            new Marking(['review' => 1, 'translation' => 1]),
            [PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION => true]
        );

        $this->assertSame(['review', 'translation'], $subject->getPendingWorkflowMarking(self::WORKFLOW_NAME));
        $this->assertSame(
            ['review' => 1, 'translation' => 1],
            $store->getMarking($subject)->getPlaces(),
            'The pending marking must be reported as the current marking of the draft.'
        );
    }

    public function testPendingMarkingsAreScopedToTheirWorkflow(): void
    {
        $subject = $this->createSubject();
        $subject->setPendingWorkflowMarking('other_wf', ['done']);

        $store = new StateTableMarkingStore(self::WORKFLOW_NAME);
        $store->setMarking($subject, new Marking(['review' => 1]), [PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION => true]);

        $this->assertSame(
            ['other_wf' => ['done'], self::WORKFLOW_NAME => ['review']],
            $subject->getPendingWorkflowMarkings()
        );
    }

    public function testPendingMarkingIsPartOfVersionDumpButNotOfCache(): void
    {
        $subject = $this->createSubject();
        $subject->setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);

        $subject->setInDumpState(true);
        $this->assertContains('pendingWorkflowMarkings', $subject->__sleep(), 'A version dump must carry the pending marking.');

        $subject->setInDumpState(false);
        $this->assertNotContains('pendingWorkflowMarkings', $subject->__sleep(), 'The cache must not carry the pending marking.');
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
