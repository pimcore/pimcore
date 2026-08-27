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

use Pimcore\Model\DataObject;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\PlaceConfig;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition as SymfonyTransition;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ChangePublishedStateTest extends TestCase
{
    private const WORKFLOW_NAME = 'product_workflow';

    private function createManager(array $placeConfigs = []): Manager
    {
        $manager = new Manager(
            $this->createStub(Registry::class),
            $this->createStub(NotesSubscriber::class),
            $this->createStub(ExpressionService::class),
            $this->createStub(EventDispatcherInterface::class)
        );

        foreach ($placeConfigs as $place => $placeConfig) {
            $manager->addPlaceConfig(self::WORKFLOW_NAME, $place, $placeConfig);
        }

        return $manager;
    }

    private function createPlaceConfig(array $placeConfigArray): PlaceConfig
    {
        return new PlaceConfig(
            'closed',
            $placeConfigArray,
            $this->createStub(ExpressionService::class),
            self::WORKFLOW_NAME
        );
    }

    public function testPlaceConfigDefaultsToNoChange(): void
    {
        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            $this->createPlaceConfig([])->getChangePublishedState()
        );
    }

    public function testPlaceConfigReturnsConfiguredState(): void
    {
        $placeConfig = $this->createPlaceConfig([
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_PUBLISHED,
            $placeConfig->getChangePublishedState()
        );
    }

    public function testTransitionDefaultsToNoChange(): void
    {
        $transition = new Transition('close', 'open', 'closed');

        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            $transition->getChangePublishedState()
        );
    }

    public function testPlaceStateIsUsedWhenTransitionDoesNotDefineOne(): void
    {
        $manager = $this->createManager([
            'open' => [],
            'closed' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
        ]);

        $transition = new Transition('close', 'open', 'closed', [
            'changePublishedState' => ChangePublishedStateSubscriber::NO_CHANGE,
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_PUBLISHED,
            $manager->getChangePublishedState(self::WORKFLOW_NAME, $transition)
        );
    }

    public function testTransitionStateOverrulesPlaceState(): void
    {
        $manager = $this->createManager([
            'closed' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
        ]);

        $transition = new Transition('close', 'open', 'closed', [
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED,
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_UNPUBLISHED,
            $manager->getChangePublishedState(self::WORKFLOW_NAME, $transition)
        );
    }

    public function testPlaceStateIsUsedForPlainSymfonyTransitions(): void
    {
        $manager = $this->createManager([
            'closed' => ['changePublishedState' => ChangePublishedStateSubscriber::SAVE_VERSION],
        ]);

        $transition = new SymfonyTransition('close', 'open', 'closed');

        $this->assertSame(
            ChangePublishedStateSubscriber::SAVE_VERSION,
            $manager->getChangePublishedState(self::WORKFLOW_NAME, $transition)
        );
    }

    public function testFirstPlaceDefiningAStateWins(): void
    {
        $manager = $this->createManager([
            'review' => [],
            'closed' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            'archived' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED],
        ]);

        $transition = new Transition('close', 'review', ['closed', 'archived']);

        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_PUBLISHED,
            $manager->getChangePublishedState(self::WORKFLOW_NAME, $transition)
        );
    }

    public function testPlacesNotTargetedByTheTransitionAreIgnored(): void
    {
        $manager = $this->createManager([
            'open' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED],
            'closed' => [],
        ]);

        $transition = new Transition('close', 'open', 'closed');

        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            $manager->getChangePublishedState(self::WORKFLOW_NAME, $transition)
        );
    }

    public function testApplyChangePublishedStatePublishesTheSubject(): void
    {
        $manager = $this->createManager();
        $subject = new DataObject\Concrete();
        $subject->setPublished(false);

        $manager->applyChangePublishedState($subject, ChangePublishedStateSubscriber::FORCE_PUBLISHED);

        $this->assertTrue($subject->isPublished());
    }

    public function testApplyChangePublishedStateUnpublishesTheSubject(): void
    {
        $manager = $this->createManager();
        $subject = new DataObject\Concrete();
        $subject->setPublished(true);

        $manager->applyChangePublishedState($subject, ChangePublishedStateSubscriber::FORCE_UNPUBLISHED);

        $this->assertFalse($subject->isPublished());
    }

    public function testApplyChangePublishedStateLeavesTheSubjectUntouchedOnNoChange(): void
    {
        $manager = $this->createManager();
        $subject = new DataObject\Concrete();
        $subject->setPublished(true);

        $manager->applyChangePublishedState($subject, ChangePublishedStateSubscriber::NO_CHANGE);

        $this->assertTrue($subject->isPublished());
    }

    public function testUnknownWorkflowResolvesToNoChange(): void
    {
        $manager = $this->createManager([
            'closed' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            $manager->getChangePublishedStateOfPlaces('another_workflow', ['closed'])
        );
    }
}
