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

namespace Pimcore\Tests\Unit\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\WorkflowManagementListener;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\MarkingStore\PendingMarkingStoreInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowManagementListenerTest extends TestCase
{
    /**
     * A marking kept pending on an element (changePublishedState "save_version")
     * belongs to the draft: a version-only save must leave it pending, a full
     * save must persist it.
     */
    public function testPendingMarkingsArePersistedOnFullSaveOnly(): void
    {
        $element = new Asset();
        $element->setId(42);
        $element->setPendingWorkflowMarking('test_wf', ['review']);

        $store = $this->createMock(PendingMarkingStoreInterface::class);
        $store->expects($this->once())->method('persistPendingMarking')->with($element);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('getMarkingStore')->willReturn($store);

        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflowsForSubject')->with($element)->willReturn([$workflow]);

        $listener = new WorkflowManagementListener($manager);

        $listener->onElementPostUpdate(new AssetEvent($element, ['saveVersionOnly' => true]));
        $listener->onElementPostUpdate(new AssetEvent($element));
    }

    public function testElementsWithoutPendingMarkingsAreLeftAlone(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->never())->method('getAllWorkflowsForSubject');

        $listener = new WorkflowManagementListener($manager);

        $element = new Asset();
        $element->setId(42);

        $listener->onElementPostUpdate(new AssetEvent($element));
    }
}
