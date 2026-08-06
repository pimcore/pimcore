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

namespace Pimcore\Tests\Model\Asset;

use Pimcore;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.asset.asset
 */
class SaveVersionParametersTest extends ModelTestCase
{
    private array $registeredListeners = [];

    protected Asset $testAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testAsset = TestHelper::createImageAsset();
    }

    protected function tearDown(): void
    {
        foreach ($this->registeredListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->registeredListeners = [];

        parent::tearDown();
    }

    private function addListener(string $eventName, callable $listener): void
    {
        Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        $this->registeredListeners[] = [$eventName, $listener];
    }

    /**
     * @param array<string, array<string, mixed>> $capturedArguments
     */
    private function captureVersioningEventArguments(array &$capturedArguments): void
    {
        foreach ([AssetEvents::PRE_UPDATE, AssetEvents::POST_UPDATE] as $eventName) {
            $this->addListener($eventName, function (AssetEvent $event) use ($eventName, &$capturedArguments): void {
                if ($event->getAsset() === $this->testAsset) {
                    $capturedArguments[$eventName] = $event->getArguments();
                }
            });
        }
    }

    public function testCustomParametersAreForwardedToVersioningEvents(): void
    {
        $capturedArguments = [];
        $this->captureVersioningEventArguments($capturedArguments);

        $this->testAsset->saveVersion(true, true, null, ['customParameter' => 'customValue']);

        foreach ([AssetEvents::PRE_UPDATE, AssetEvents::POST_UPDATE] as $eventName) {
            $this->assertArrayHasKey($eventName, $capturedArguments);
            $this->assertSame('customValue', $capturedArguments[$eventName]['customParameter']);
            $this->assertTrue($capturedArguments[$eventName]['saveVersionOnly']);
        }
    }

    public function testCoreParametersCannotBeOverriddenByCustomParameters(): void
    {
        $capturedArguments = [];
        $this->captureVersioningEventArguments($capturedArguments);

        $this->testAsset->saveVersion(true, true, null, ['saveVersionOnly' => false]);

        foreach ([AssetEvents::PRE_UPDATE, AssetEvents::POST_UPDATE] as $eventName) {
            $this->assertArrayHasKey($eventName, $capturedArguments);
            $this->assertTrue($capturedArguments[$eventName]['saveVersionOnly']);
        }
    }

    public function testListenerArgumentMutationsPropagateToPostUpdateEvent(): void
    {
        $this->addListener(AssetEvents::PRE_UPDATE, function (AssetEvent $event): void {
            if ($event->getAsset() === $this->testAsset) {
                $event->setArgument('addedByListener', 'listenerValue');
            }
        });

        $capturedArguments = [];
        $this->captureVersioningEventArguments($capturedArguments);

        $this->testAsset->saveVersion(true, true, null, ['customParameter' => 'customValue']);

        $postUpdateArguments = $capturedArguments[AssetEvents::POST_UPDATE];
        $this->assertSame('listenerValue', $postUpdateArguments['addedByListener']);
        $this->assertSame('customValue', $postUpdateArguments['customParameter']);
        $this->assertTrue($postUpdateArguments['saveVersionOnly']);
    }

    public function testSaveVersionWithoutParametersKeepsPreviousBehavior(): void
    {
        $capturedArguments = [];
        $this->captureVersioningEventArguments($capturedArguments);

        $this->testAsset->saveVersion(true, true, null);

        foreach ([AssetEvents::PRE_UPDATE, AssetEvents::POST_UPDATE] as $eventName) {
            $this->assertArrayHasKey($eventName, $capturedArguments);
            $this->assertTrue($capturedArguments[$eventName]['saveVersionOnly']);
        }
    }
}
