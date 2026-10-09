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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\MarkingStore\PendingMarkingStoreInterface;
use Pimcore\Workflow\Transition as PimcoreTransition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;

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
     * Regression test for https://github.com/pimcore/pimcore/issues/18653
     *
     * A transition with changePublishedState=save_version only saves a draft of
     * the subject. A marking store that persists independently of the subject
     * (like the state_table store) must therefore be told to keep the new
     * place pending on the subject, so that it is published or discarded
     * together with the draft instead of being committed right away.
     */
    public function testSaveVersionTransitionKeepsMarkingPendingOnSubject(): void
    {
        $store = $this->createPendingMarkingStore();
        $transition = $this->createSaveVersionTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->expects($this->once())->method('saveVersion');
        $subject->expects($this->never())->method('save');

        $manager = $this->buildManager($eventDispatcher, $transition);

        $marking = $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);

        $this->assertSame(['end' => 1], $marking->getPlaces());
        $this->assertTrue(
            $store->lastContext[PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION] ?? false,
            'The marking store must be told that the subject is only saved as a version.'
        );
        $this->assertSame(['end' => 1], $store->pending, 'The new place should be pending on the subject.');
        $this->assertSame(
            ['start' => 1],
            $store->persisted,
            'A save_version transition must not persist the marking independently of the draft.'
        );
    }

    /**
     * The rollback of a save_version transition must restore exactly what was pending
     * before: nothing in this case. Staging the previous place as a new pending draft
     * entry would invent a draft state the subject never had.
     */
    public function testSaveVersionTransitionRollbackRemovesThePendingMarkingAgain(): void
    {
        $store = $this->createPendingMarkingStore();
        $transition = $this->createSaveVersionTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('saveVersion')->willThrowException(new ValidationException('mandatory field missing'));

        $manager = $this->buildManager($eventDispatcher, $transition);

        $thrown = null;

        try {
            $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ValidationException::class, $thrown);
        $this->assertSame(['start' => 1], $store->persisted, 'The rollback must not commit anything to the store.');
        $this->assertNull($store->pending, 'Nothing was pending before the transition, so nothing may be pending after the rollback.');
    }

    /**
     * An earlier draft already moved the subject from "draft" (persisted) to "start"
     * (pending). A further save_version transition that fails to save must bring the
     * pending place back to "start" and must leave the persisted place alone.
     */
    public function testSaveVersionTransitionRollbackRestoresTheEarlierPendingMarking(): void
    {
        $store = $this->createPendingMarkingStore(['draft' => 1], ['start' => 1]);
        $transition = $this->createSaveVersionTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher, ['draft', 'start', 'end']);

        $subject = $this->createMock(Concrete::class);
        $subject->method('saveVersion')->willThrowException(new ValidationException('mandatory field missing'));

        $manager = $this->buildManager($eventDispatcher, $transition);

        $thrown = null;

        try {
            $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ValidationException::class, $thrown);
        $this->assertSame(['draft' => 1], $store->persisted, 'The persisted place must not change.');
        $this->assertSame(['start' => 1], $store->pending, 'The pending place of the earlier draft must be restored.');
    }

    /**
     * Same starting point ("draft" persisted, "start" pending from an earlier draft), but
     * now a publishing transition fails to save. The transition committed "end" to the
     * store and dropped the pending place; the rollback must put the *persisted* place
     * back, not the draft's place, and must restore the pending place as well.
     */
    public function testRollbackOfAPublishingTransitionRestoresThePersistedMarkingNotTheDraftPlace(): void
    {
        $store = $this->createPendingMarkingStore(['draft' => 1], ['start' => 1]);
        $transition = $this->createForcePublishedTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher, ['draft', 'start', 'end']);

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);
        $subject->method('save')->willThrowException(new ValidationException('mandatory field missing'));

        $manager = $this->buildManager($eventDispatcher, $transition);

        $thrown = null;

        try {
            $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ValidationException::class, $thrown);
        $this->assertSame(['draft' => 1], $store->persisted, 'The persisted place must be restored, not the pending draft place.');
        $this->assertSame(['start' => 1], $store->pending, 'The pending place of the earlier draft must be restored.');
    }

    public function testGlobalActionRollbackRestoresThePersistedMarkingNotTheDraftPlace(): void
    {
        $store = $this->createPendingMarkingStore(['draft' => 1], ['start' => 1]);
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $this->createForcePublishedTransition(), $eventDispatcher, ['draft', 'start', 'end']);

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
        $this->assertSame(['draft' => 1], $store->persisted, 'The persisted place must be restored, not the pending draft place.');
        $this->assertSame(['start' => 1], $store->pending, 'The pending place of the earlier draft must be restored.');
    }

    public function testOtherTransitionsPersistMarkingImmediately(): void
    {
        $store = $this->createPendingMarkingStore();
        $transition = $this->createForcePublishedTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);
        $subject->expects($this->once())->method('save');

        $manager = $this->buildManager($eventDispatcher, $transition);

        $manager->applyWithAdditionalData($workflow, $subject, 'go', [], true);

        $this->assertArrayNotHasKey(PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION, $store->lastContext);
        $this->assertSame(['end' => 1], $store->persisted);
        $this->assertNull($store->pending);
    }

    /**
     * When the caller saves the subject itself, the Manager cannot know whether
     * that is going to be a full save or a version only, so the marking store
     * keeps persisting immediately as before.
     */
    public function testSaveVersionTransitionWithoutSavingSubjectPersistsMarkingImmediately(): void
    {
        $store = $this->createPendingMarkingStore();
        $transition = $this->createSaveVersionTransition();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->expects($this->never())->method('saveVersion');
        $subject->expects($this->never())->method('save');

        $manager = $this->buildManager($eventDispatcher, $transition);

        $manager->applyWithAdditionalData($workflow, $subject, 'go', [], false);

        $this->assertArrayNotHasKey(PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION, $store->lastContext);
        $this->assertSame(['end' => 1], $store->persisted);
        $this->assertNull($store->pending);
    }

    /**
     * The context key that keeps a marking pending is reserved for the Manager. Additional
     * data is caller-provided; a caller smuggling the key in must not be able to leave a
     * place pending that no save is going to flush.
     */
    public function testCallersCannotForceTheMarkingToStayPendingOnAPublishingTransition(): void
    {
        $this->assertCallerProvidedSaveVersionFlagIsIgnored(self::createForcePublishedTransition(), true);
    }

    public function testCallersCannotForceTheMarkingToStayPendingWhenTheySaveTheSubjectThemselves(): void
    {
        $this->assertCallerProvidedSaveVersionFlagIsIgnored(self::createSaveVersionTransition(), false);
    }

    private function assertCallerProvidedSaveVersionFlagIsIgnored(PimcoreTransition $transition, bool $saveSubject): void
    {
        $store = $this->createPendingMarkingStore();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $transition, $eventDispatcher);

        $subject = $this->createMock(Concrete::class);
        $subject->method('isPublished')->willReturn(false);

        $manager = $this->buildManager($eventDispatcher, $transition);

        $manager->applyWithAdditionalData(
            $workflow,
            $subject,
            'go',
            [PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION => true, 'notes' => 'kept'],
            $saveSubject
        );

        $this->assertArrayNotHasKey(PendingMarkingStoreInterface::CONTEXT_SAVE_VERSION, $store->lastContext);
        $this->assertSame('kept', $store->lastContext['notes'] ?? null, 'Other additional data still reaches the store.');
        $this->assertSame(['end' => 1], $store->persisted);
        $this->assertNull($store->pending);
    }

    /**
     * The additional data handed to the notes subscriber is request-scoped state. It must be
     * cleared even when the transition cannot be resolved, otherwise a later apply() in the
     * same process would pick up this call's notes.
     */
    public function testNotesDataIsClearedWhenTheTransitionCannotBeResolved(): void
    {
        $store = $this->createPendingMarkingStore();
        $eventDispatcher = $this->createEventDispatcher();
        $workflow = $this->createWorkflow($store, $this->createSaveVersionTransition(), $eventDispatcher);

        $notesSubscriber = $this->createMock(NotesSubscriber::class);
        $additionalDataCalls = [];
        $notesSubscriber->method('setAdditionalData')->willReturnCallback(
            function (array $data) use (&$additionalDataCalls): void {
                $additionalDataCalls[] = $data;
            }
        );

        $manager = $this->buildManager($eventDispatcher, null, $notesSubscriber);
        $manager->method('getTransitionByName')->willThrowException(new LogicException('workflow unknown_wf not found'));

        $thrown = null;

        try {
            $manager->applyWithAdditionalData($workflow, $this->createMock(Concrete::class), 'go', ['notes' => 'x'], true);
        } catch (LogicException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(LogicException::class, $thrown);
        $this->assertSame([['notes' => 'x'], []], $additionalDataCalls, 'The notes data must be cleared again.');
    }

    /**
     * Marking store that can keep a marking pending on the subject, like StateTableMarkingStore.
     *
     * @param array<string, int> $persisted the places the store persisted
     * @param array<string, int>|null $pending the places pending on the subject, if any
     */
    private function createPendingMarkingStore(array $persisted = ['start' => 1], ?array $pending = null): PendingMarkingStoreInterface
    {
        return new class($persisted, $pending) implements PendingMarkingStoreInterface {
            public array $lastContext = [];

            public function __construct(public array $persisted, public ?array $pending)
            {
            }

            public function getMarking(object $subject): Marking
            {
                return new Marking($this->pending ?? $this->persisted);
            }

            public function setMarking(object $subject, Marking $marking, array $context = []): void
            {
                $this->lastContext = $context;

                if (!empty($context[self::CONTEXT_SAVE_VERSION])) {
                    $this->pending = $marking->getPlaces();

                    return;
                }

                $this->pending = null;
                $this->persisted = $marking->getPlaces();
            }

            public function persistPendingMarking(ElementInterface $subject): void
            {
                if ($this->pending !== null) {
                    $this->persisted = $this->pending;
                    $this->pending = null;
                }
            }

            public function getPersistedMarking(ElementInterface $subject): Marking
            {
                return new Marking($this->persisted);
            }

            public function getPendingMarking(ElementInterface $subject): ?Marking
            {
                return $this->pending === null ? null : new Marking($this->pending);
            }

            public function setPendingMarking(ElementInterface $subject, ?Marking $marking): void
            {
                $this->pending = $marking?->getPlaces();
            }
        };
    }

    private static function createSaveVersionTransition(): PimcoreTransition
    {
        return new PimcoreTransition('go', 'start', 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::SAVE_VERSION,
        ]);
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

    private static function createForcePublishedTransition(): PimcoreTransition
    {
        return new PimcoreTransition('go', 'start', 'end', [
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);
    }

    private function createEventDispatcher(): EventDispatcher
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new ChangePublishedStateSubscriber());

        return $eventDispatcher;
    }

    /**
     * @param string[] $places
     */
    private function createWorkflow(
        MarkingStoreInterface $store,
        PimcoreTransition $transition,
        EventDispatcher $eventDispatcher,
        array $places = ['start', 'end']
    ): StateMachine {
        return new StateMachine(
            new Definition($places, [$transition]),
            $store,
            $eventDispatcher,
            self::WORKFLOW_NAME
        );
    }

    private function buildManager(
        EventDispatcher $eventDispatcher,
        ?PimcoreTransition $transition = null,
        ?NotesSubscriber $notesSubscriber = null
    ): Manager&MockObject {
        $notesSubscriber ??= $this->createMock(NotesSubscriber::class);
        $expressionService = $this->createMock(ExpressionService::class);
        $registry = new Registry();

        $manager = $this->getMockBuilder(Manager::class)
            ->setConstructorArgs([$registry, $notesSubscriber, $expressionService, $eventDispatcher])
            ->onlyMethods(['getTransitionByName'])
            ->getMock();
        if ($transition !== null) {
            $manager->method('getTransitionByName')->willReturn($transition);
        }

        return $manager;
    }
}
