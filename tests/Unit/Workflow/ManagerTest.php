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
use Symfony\Component\Workflow\Transition as SymfonyTransition;

class ManagerTest extends TestCase
{
    private const WORKFLOW_NAME = 'test_wf';

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
        $store = $this->createImmediateMarkingStore();
        $transition = $this->createForcePublishedTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

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

        $manager = $this->buildManager($eventDispatcher, $transition);

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
        $store = $this->createImmediateMarkingStore();
        $transition = $this->createForcePublishedTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);
        $subject->expects($this->once())->method('save');

        $manager = $this->buildManager($eventDispatcher, $transition);

        $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);

        $this->assertSame(['end' => 1], $store->persisted);
    }

    /**
     * A global action can move the subject to a new place directly, so it is
     * exposed to the same inconsistency as a transition when the subsequent
     * save() fails.
     */
    public function testGlobalActionRollsBackMarkingWhenSaveFails(): void
    {
        $store = $this->createImmediateMarkingStore();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $this->createForcePublishedTransition(), $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('save')->willThrowException(new ValidationException('mandatory field missing'));

        $manager = $this->buildManager($eventDispatcher);
        $manager->addGlobalAction(self::WORKFLOW_NAME, 'finish', ['to' => ['end']]);

        $thrown = null;

        try {
            $manager->applyGlobalAction($workflow, $subject, 'finish', [], true);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ValidationException::class, $thrown);
        $this->assertSame(
            ['start' => 1],
            $store->persisted,
            'Marking should be rolled back when the save after a global action fails.'
        );
    }

    public function testGlobalActionHappyPathPersistsMarking(): void
    {
        $store = $this->createImmediateMarkingStore();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $this->createForcePublishedTransition(), $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->expects($this->once())->method('save');

        $manager = $this->buildManager($eventDispatcher);
        $manager->addGlobalAction(self::WORKFLOW_NAME, 'finish', ['to' => ['end']]);

        $marking = $manager->applyGlobalAction($workflow, $subject, 'finish', [], true);

        $this->assertSame(['end' => 1], $store->persisted);
        $this->assertSame(['end' => 1], $marking->getPlaces());
    }

    /**
     * A place-level changePublishedState is applied when the transition does not define one - also
     * when the transition is a plain Symfony transition which carries no Pimcore options at all.
     */
    public function testPlaceLevelStateIsAppliedForAPlainSymfonyTransition(): void
    {
        $store = $this->createImmediateMarkingStore();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, new SymfonyTransition('go', 'start', 'end'), $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);

        $publishedStates = [];
        $subject->method('setPublished')->willReturnCallback(
            function (bool $value) use (&$publishedStates, $subject): Concrete {
                $publishedStates[] = $value;

                return $subject;
            }
        );

        $manager = $this->buildManager($eventDispatcher);
        $manager->addPlaceConfig(self::WORKFLOW_NAME, 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);

        $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);

        $this->assertSame(['end' => 1], $store->persisted);
        $this->assertSame(
            [true],
            $publishedStates,
            'The force_published of the target place should be applied by the subscriber.'
        );
    }

    /**
     * A place-level save_version has to reach the save branch, not just the resolver.
     */
    public function testPlaceLevelSaveVersionSavesAVersionInsteadOfTheSubject(): void
    {
        $store = $this->createImmediateMarkingStore();
        $transition = new PimcoreTransition('go', 'start', 'end');
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);
        $subject->expects($this->once())->method('saveVersion');
        $subject->expects($this->never())->method('save');

        $manager = $this->buildManager($eventDispatcher, $transition);
        $manager->addPlaceConfig(self::WORKFLOW_NAME, 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::SAVE_VERSION,
        ]);

        $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);

        $this->assertSame(['end' => 1], $store->persisted);
    }

    /**
     * A global action which sets a place applies that place's changePublishedState too, so the
     * published state does not depend on how the element reached the place.
     */
    public function testGlobalActionAppliesThePlaceLevelPublishedState(): void
    {
        $store = $this->createImmediateMarkingStore();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $this->createForcePublishedTransition(), $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);
        $subject->expects($this->once())->method('save');

        $publishedStates = [];
        $subject->method('setPublished')->willReturnCallback(
            function (bool $value) use (&$publishedStates, $subject): Concrete {
                $publishedStates[] = $value;

                return $subject;
            }
        );

        $manager = $this->buildManager($eventDispatcher);
        $manager->addPlaceConfig(self::WORKFLOW_NAME, 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);
        $manager->addGlobalAction(self::WORKFLOW_NAME, 'finish', ['to' => ['end']]);

        $manager->applyGlobalAction($workflow, $subject, 'finish', [], true);

        $this->assertSame(['end' => 1], $store->persisted);
        $this->assertSame(
            [true],
            $publishedStates,
            'The force_published of the place the global action moves to should be applied.'
        );
    }

    /**
     * Marking store that persists immediately, like StateTableMarkingStore.
     */
    private function createImmediateMarkingStore(): MarkingStoreInterface
    {
        return new class() implements MarkingStoreInterface {
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
    }

    private function createForcePublishedTransition(): PimcoreTransition
    {
        return new PimcoreTransition('go', 'start', 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);
    }

    private function createEventDispatcher(): EventDispatcher
    {
        // The ChangePublishedStateSubscriber is registered in buildManager(),
        // since it resolves the effective changePublishedState through the Manager.
        return new EventDispatcher();
    }

    private function createWorkflow(
        MarkingStoreInterface $store,
        SymfonyTransition $transition,
        EventDispatcher $eventDispatcher
    ): StateMachine {
        return new StateMachine(
            new Definition(['start', 'end'], [$transition]),
            $store,
            $eventDispatcher,
            self::WORKFLOW_NAME
        );
    }

    private function buildManager(
        EventDispatcher $eventDispatcher,
        ?PimcoreTransition $transition = null
    ): Manager&MockObject {
        $notesSubscriber = $this->createMock(NotesSubscriber::class);
        $expressionService = $this->createMock(ExpressionService::class);
        $registry = new Registry();

        $manager = $this->getMockBuilder(Manager::class)
            ->setConstructorArgs([$registry, $notesSubscriber, $expressionService, $eventDispatcher])
            ->onlyMethods(['getTransitionByName'])
            ->getMock();
        $manager->method('getTransitionByName')->willReturn($transition);

        $eventDispatcher->addSubscriber(new ChangePublishedStateSubscriber($manager));

        return $manager;
    }
}
