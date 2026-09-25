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

use PHPUnit\Framework\TestCase;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Notification\NotificationInterface;
use Pimcore\Workflow\Transition;

final class GlobalActionTest extends TestCase
{
    private function createGlobalAction(array $options): GlobalAction
    {
        return new GlobalAction(
            'myGlobalAction',
            $options,
            $this->createStub(ExpressionService::class),
            'myWorkflow'
        );
    }

    public function testChangePublishedStateDefaultsToNoChange(): void
    {
        $globalAction = $this->createGlobalAction([]);

        $this->assertSame(ChangePublishedStateSubscriber::NO_CHANGE, $globalAction->getChangePublishedState());
    }

    public function testChangePublishedStateIsReadFromTheConfig(): void
    {
        $globalAction = $this->createGlobalAction([
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED,
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_UNPUBLISHED,
            $globalAction->getChangePublishedState()
        );
    }

    public function testUnsavedChangesBehaviourDefaultsToWarn(): void
    {
        $globalAction = $this->createGlobalAction([]);

        $this->assertSame(
            Transition::UNSAVED_CHANGES_BEHAVIOUR_WARN,
            $globalAction->getUnsavedChangesBehaviour()
        );
    }

    public function testUnsavedChangesBehaviourIsReadFromTheConfig(): void
    {
        $globalAction = $this->createGlobalAction([
            'unsavedChangesBehaviour' => Transition::UNSAVED_CHANGES_BEHAVIOUR_IGNORE,
        ]);

        $this->assertSame(
            Transition::UNSAVED_CHANGES_BEHAVIOUR_IGNORE,
            $globalAction->getUnsavedChangesBehaviour()
        );
    }

    public function testNotificationSettingsDefaultToAnEmptyArray(): void
    {
        $globalAction = $this->createGlobalAction([]);

        $this->assertInstanceOf(NotificationInterface::class, $globalAction);
        $this->assertSame([], $globalAction->getNotificationSettings());
    }

    public function testNotificationSettingsAreReadFromTheConfig(): void
    {
        $notificationSettings = [
            [
                'notifyUsers' => ['admin'],
                'notifyRoles' => ['projectmanagers'],
                'channelType' => ['mail'],
                'mailType' => 'template',
                'mailPath' => 'some/path.html.twig',
            ],
        ];

        $globalAction = $this->createGlobalAction(['notificationSettings' => $notificationSettings]);

        $this->assertSame($notificationSettings, $globalAction->getNotificationSettings());
    }
}
