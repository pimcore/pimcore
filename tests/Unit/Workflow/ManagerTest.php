<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Unit\Workflow;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition as PimcoreTransition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;

class ManagerTest extends TestCase
{
    /**
     * Regression test for https://github.com/pimcore/pimcore/issues/18178
     *
     * When a transition with changePublishedState=force_published is applied
     * to a Concrete object with empty mandatory fields, the post-transition
     * save() throws a ValidationException. Marking stores that persist
     * immediately (e.g. the state_table store) would otherwise leave the
     * subject in an inconsistent state where the workflow place advanced but
     * the subject itself was not updated. The Manager must roll back both
     * the marking and the published state.
     */
    public function testRollsBackMarkingAndPublishedStateWhenSaveFails(): void
    {
        // Marking store that persists immediately, like StateTableMarkingStore.
        $store = new class() implements MarkingStoreInterface {
            public array $persisted = ['start' => 1];

            public function getMarking(object $subject): Marking
            {
                return new Marking($this->persisted);
            }

            public function setMarking(object $subject, Marking $marking, array $context = []): void
            {
                $this->persisted = $marking->getPlaces();
            }
        };

        $transition = new PimcoreTransition('go', 'start', 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);
        $definition = new Definition(['start', 'end'], [$transition]);

        $workflowEventDispatcher = new EventDispatcher();
        $workflowEventDispatcher->addSubscriber(new ChangePublishedStateSubscriber());

        $workflow = new StateMachine($definition, $store, $workflowEventDispatcher, 'test_wf');

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);

        $publishedStates = [];
        $subject->method('setPublished')->willReturnCallback(
            function (bool $value) use (&$publishedStates, $subject): Concrete {
                $publishedStates[] = $value;

                return $subject;
            }
        );
        $subject->method('save')->willThrowException(new ValidationException('mandatory field missing'));

        $manager = $this->buildManager($workflowEventDispatcher, $transition);

        $this->assertSame(['start' => 1], $store->persisted);

        $thrown = null;
        try {
            $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ValidationException::class, $thrown);
        $this->assertSame(
            ['start' => 1],
            $store->persisted,
            'Marking should be rolled back when the post-transition save fails.'
        );
        $this->assertSame(
            [true, false],
            $publishedStates,
            'Published state should be forced to true by the event listener and then rolled back to false.'
        );
    }

    public function testHappyPathPersistsMarkingAndDoesNotRollBack(): void
    {
        $store = new class() implements MarkingStoreInterface {
            public array $persisted = ['start' => 1];

            public function getMarking(object $subject): Marking
            {
                return new Marking($this->persisted);
            }

            public function setMarking(object $subject, Marking $marking, array $context = []): void
            {
                $this->persisted = $marking->getPlaces();
            }
        };

        $transition = new PimcoreTransition('go', 'start', 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);
        $definition = new Definition(['start', 'end'], [$transition]);

        $workflowEventDispatcher = new EventDispatcher();
        $workflowEventDispatcher->addSubscriber(new ChangePublishedStateSubscriber());

        $workflow = new StateMachine($definition, $store, $workflowEventDispatcher, 'test_wf');

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);
        $subject->expects($this->once())->method('save');

        $manager = $this->buildManager($workflowEventDispatcher, $transition);

        $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);

        $this->assertSame(['end' => 1], $store->persisted);
    }

    private function buildManager(EventDispatcher $eventDispatcher, PimcoreTransition $transition): Manager&MockObject
    {
        $notesSubscriber = $this->createMock(NotesSubscriber::class);
        $expressionService = $this->createMock(ExpressionService::class);
        $registry = new Registry();

        $manager = $this->getMockBuilder(Manager::class)
            ->setConstructorArgs([$registry, $notesSubscriber, $expressionService, $eventDispatcher])
            ->onlyMethods(['getTransitionByName'])
            ->getMock();
        $manager->method('getTransitionByName')->willReturn($transition);

        return $manager;
    }
}
