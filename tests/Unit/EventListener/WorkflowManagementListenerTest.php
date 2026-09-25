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

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\CoreBundle\EventListener\WorkflowManagementListener;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\MarkingStore\PendingMarkingStoreInterface;
use Pimcore\Workflow\SupportStrategy\ExpressionSupportStrategy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowManagementListenerTest extends TestCase
{
    private const WORKFLOW_NAME = 'test_wf';

    /**
     * A marking kept pending on an element (changePublishedState "save_version")
     * belongs to the draft: a version-only save must leave it pending, a full
     * save must persist it.
     */
    public function testPendingMarkingsArePersistedOnFullSaveOnly(): void
    {
        $element = $this->createElementWithPendingMarking();
        $store = $this->createPendingMarkingStore($element, 1);

        $manager = $this->createMock(Manager::class);
        $manager->method('getWorkflowByName')->with(self::WORKFLOW_NAME)->willReturn($this->createWorkflow($store));

        $listener = new WorkflowManagementListener($manager);

        $listener->onElementPostUpdate(new AssetEvent($element, ['saveVersionOnly' => true]));
        $listener->onElementPostUpdate(new AssetEvent($element));
    }

    /**
     * The pending place was set while the workflow applied to the element. Publishing the
     * draft must not re-evaluate the workflow's support strategy against the content being
     * published: a subject-dependent strategy (e.g. an expression) that turns false for the
     * draft would otherwise silently drop the place. The workflow is therefore resolved by
     * name, not via the registry.
     */
    public function testPendingMarkingIsPersistedEvenIfSupportStrategyRejectsTheDraft(): void
    {
        $element = $this->createElementWithPendingMarking();
        $store = $this->createPendingMarkingStore($element, 1);
        $workflow = $this->createWorkflow($store);

        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->method('evaluateExpression')->willReturn(false);

        $registry = new Registry();
        $registry->addWorkflow(
            $workflow,
            new ExpressionSupportStrategy($expressionService, Asset::class, 'subject.getKey() == "supported"')
        );

        $manager = $this->getMockBuilder(Manager::class)
            ->setConstructorArgs([$registry, $this->createMock(NotesSubscriber::class), $expressionService, new EventDispatcher()])
            ->onlyMethods(['getWorkflowByName'])
            ->getMock();
        $manager->registerWorkflow(self::WORKFLOW_NAME, ['type' => 'workflow']);
        $manager->method('getWorkflowByName')->with(self::WORKFLOW_NAME)->willReturn($workflow);

        $this->assertSame(
            [],
            $manager->getAllWorkflowsForSubject($element),
            'Precondition: the support strategy rejects the element, so a registry lookup would drop the workflow.'
        );

        (new WorkflowManagementListener($manager))->onElementPostUpdate(new AssetEvent($element));
    }

    public function testPendingMarkingOfAnUnknownWorkflowIsSkipped(): void
    {
        $element = $this->createElementWithPendingMarking();

        $manager = $this->createMock(Manager::class);
        $manager->method('getWorkflowByName')->willThrowException(new LogicException('workflow test_wf not found'));

        (new WorkflowManagementListener($manager))->onElementPostUpdate(new AssetEvent($element));

        $this->assertSame([self::WORKFLOW_NAME => ['review']], $element->getPendingWorkflowMarkings());
    }

    public function testElementsWithoutPendingMarkingsAreLeftAlone(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->never())->method('getWorkflowByName');

        $listener = new WorkflowManagementListener($manager);

        $element = new Asset();
        $element->setId(42);

        $listener->onElementPostUpdate(new AssetEvent($element));
    }

    private function createElementWithPendingMarking(): Asset
    {
        $element = new Asset();
        $element->setId(42);
        $element->setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);

        return $element;
    }

    private function createPendingMarkingStore(Asset $element, int $expectedPersistCalls): PendingMarkingStoreInterface&MockObject
    {
        $store = $this->createMock(PendingMarkingStoreInterface::class);
        $store->expects($this->exactly($expectedPersistCalls))->method('persistPendingMarking')->with($element);

        return $store;
    }

    private function createWorkflow(PendingMarkingStoreInterface $store): WorkflowInterface&MockObject
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('getName')->willReturn(self::WORKFLOW_NAME);
        $workflow->method('getMarkingStore')->willReturn($store);

        return $workflow;
    }
}
