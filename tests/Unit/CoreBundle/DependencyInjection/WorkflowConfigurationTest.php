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

namespace Pimcore\Tests\Unit\CoreBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\EventSubscriber\NotificationSubscriber;
use Pimcore\Workflow\Transition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class WorkflowConfigurationTest extends TestCase
{
    /**
     * @param array<string, mixed> $globalAction
     *
     * @return array<string, mixed>
     */
    private function processGlobalAction(array $globalAction): array
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'workflows' => [
                'myWorkflow' => [
                    'supports' => ['Pimcore\Model\DataObject\Concrete'],
                    'places' => ['open' => []],
                    'transitions' => [
                        'close' => ['from' => 'open', 'to' => 'open'],
                    ],
                    'globalActions' => [
                        'myGlobalAction' => $globalAction,
                    ],
                ],
            ],
        ]]);

        return $config['workflows']['myWorkflow']['globalActions']['myGlobalAction'];
    }

    public function testGlobalActionDefaults(): void
    {
        $globalAction = $this->processGlobalAction([]);

        $this->assertSame(ChangePublishedStateSubscriber::NO_CHANGE, $globalAction['changePublishedState']);
        $this->assertSame(Transition::UNSAVED_CHANGES_BEHAVIOUR_WARN, $globalAction['unsavedChangesBehaviour']);
        $this->assertSame([], $globalAction['notificationSettings']);
    }

    public function testGlobalActionAcceptsChangePublishedState(): void
    {
        $globalAction = $this->processGlobalAction([
            'changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
        ]);

        $this->assertSame(ChangePublishedStateSubscriber::FORCE_PUBLISHED, $globalAction['changePublishedState']);
    }

    public function testGlobalActionRejectsAnUnknownChangePublishedState(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processGlobalAction(['changePublishedState' => 'no_such_state']);
    }

    public function testGlobalActionAcceptsUnsavedChangesBehaviour(): void
    {
        $globalAction = $this->processGlobalAction([
            'unsavedChangesBehaviour' => Transition::UNSAVED_CHANGES_BEHAVIOUR_SAVE,
        ]);

        $this->assertSame(Transition::UNSAVED_CHANGES_BEHAVIOUR_SAVE, $globalAction['unsavedChangesBehaviour']);
    }

    public function testGlobalActionRejectsAnUnknownUnsavedChangesBehaviour(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processGlobalAction(['unsavedChangesBehaviour' => 'no_such_behaviour']);
    }

    public function testGlobalActionNotificationSettingsUseTheSameDefaultsAsTransitions(): void
    {
        $globalAction = $this->processGlobalAction([
            'notificationSettings' => [
                [
                    'notifyUsers' => ['admin'],
                    'notifyRoles' => ['projectmanagers'],
                ],
            ],
        ]);

        $this->assertCount(1, $globalAction['notificationSettings']);

        $notificationSetting = $globalAction['notificationSettings'][0];

        $this->assertSame(['admin'], $notificationSetting['notifyUsers']);
        $this->assertSame(['projectmanagers'], $notificationSetting['notifyRoles']);
        $this->assertSame(
            [NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL],
            $notificationSetting['channelType']
        );
        $this->assertSame(NotificationSubscriber::MAIL_TYPE_TEMPLATE, $notificationSetting['mailType']);
        $this->assertSame(
            NotificationSubscriber::DEFAULT_MAIL_TEMPLATE_PATH,
            $notificationSetting['mailPath']
        );
    }

    /**
     * Transitions and global actions share one `notificationSettings` node definition, so the two
     * must normalise identically - defaults included.
     */
    public function testTransitionAndGlobalActionNotificationSettingsAreIdentical(): void
    {
        $notificationSettings = [
            [
                'notifyUsers' => ['admin'],
                'notifyRoles' => ['projectmanagers'],
            ],
        ];

        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'workflows' => [
                'myWorkflow' => [
                    'supports' => ['Pimcore\Model\DataObject\Concrete'],
                    'places' => ['open' => []],
                    'transitions' => [
                        'close' => [
                            'from' => 'open',
                            'to' => 'open',
                            'options' => ['notificationSettings' => $notificationSettings],
                        ],
                    ],
                    'globalActions' => [
                        'myGlobalAction' => ['notificationSettings' => $notificationSettings],
                    ],
                ],
            ],
        ]]);

        $workflow = $config['workflows']['myWorkflow'];

        $this->assertSame(
            $workflow['transitions']['close']['options']['notificationSettings'],
            $workflow['globalActions']['myGlobalAction']['notificationSettings']
        );
    }

    public function testGlobalActionAcceptsThePimcoreNotificationChannel(): void
    {
        $globalAction = $this->processGlobalAction([
            'notificationSettings' => [
                [
                    'notifyUsers' => ['admin'],
                    'channelType' => [NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION],
                ],
            ],
        ]);

        $this->assertSame(
            [NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION],
            $globalAction['notificationSettings'][0]['channelType']
        );
    }
}
