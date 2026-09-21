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
use Pimcore\Event\Model\Asset\ResolveMimeTypeEvent;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.asset.resolve-mime-type
 */
class ResolveMimeTypeEventTest extends ModelTestCase
{
    private array $registeredListeners = [];

    protected function tearDown(): void
    {
        foreach ($this->registeredListeners as $listener) {
            Pimcore::getEventDispatcher()->removeListener(AssetEvents::RESOLVE_MIME_TYPE, $listener);
        }
        $this->registeredListeners = [];

        parent::tearDown();
    }

    private function addListener(callable $listener): void
    {
        Pimcore::getEventDispatcher()->addListener(AssetEvents::RESOLVE_MIME_TYPE, $listener);
        $this->registeredListeners[] = $listener;
    }

    // ── Event class unit tests ────────────────────────────────────────────────

    public function testGettersReturnConstructorValues(): void
    {
        $asset = $this->createMock(Asset::class);
        $event = new ResolveMimeTypeEvent('test.jpg', 'image/jpeg', $asset, false);

        $this->assertSame('test.jpg', $event->getFilename());
        $this->assertSame('image/jpeg', $event->getMimeType());
        $this->assertSame($asset, $event->getAsset());
        $this->assertFalse($event->isNewAsset());
    }

    public function testMimeTypeIsMutable(): void
    {
        $event = new ResolveMimeTypeEvent('test.jpg', 'image/jpeg');
        $event->setMimeType('image/gif');

        $this->assertSame('image/gif', $event->getMimeType());
    }

    public function testAssetDefaultsToNull(): void
    {
        $event = new ResolveMimeTypeEvent('test.jpg', 'image/jpeg');

        $this->assertNull($event->getAsset());
    }

    public function testIsNewAssetDefaultsToTrue(): void
    {
        $event = new ResolveMimeTypeEvent('test.jpg', 'image/jpeg');

        $this->assertTrue($event->isNewAsset());
    }

    // ── Integration tests – create ────────────────────────────────────────────

    public function testEventFiredOnCreate(): void
    {
        $fired = false;
        $this->addListener(function (ResolveMimeTypeEvent $event) use (&$fired): void {
            $fired = true;
        });

        TestHelper::createImageAsset();

        $this->assertTrue($fired, 'RESOLVE_MIME_TYPE event was not dispatched during asset creation');
    }

    public function testIsNewAssetTrueOnCreate(): void
    {
        /** @var ResolveMimeTypeEvent[] $captured */
        $captured = [];
        $this->addListener(function (ResolveMimeTypeEvent $event) use (&$captured): void {
            $captured[] = $event;
        });

        TestHelper::createImageAsset();

        $this->assertNotEmpty($captured);
        foreach ($captured as $event) {
            $this->assertTrue($event->isNewAsset(), 'isNewAsset must be true for a new asset');
        }
    }

    public function testListenerCanOverrideMimeTypeOnCreate(): void
    {
        $this->addListener(function (ResolveMimeTypeEvent $event): void {
            $event->setMimeType('image/gif');
        });

        $asset = TestHelper::createImageAsset();

        $this->assertSame('image/gif', $asset->getMimeType());
    }

    public function testFilenameCarriedInEventOnCreate(): void
    {
        $capturedFilename = null;
        $this->addListener(function (ResolveMimeTypeEvent $event) use (&$capturedFilename): void {
            $capturedFilename = $event->getFilename();
        });

        $asset = TestHelper::createImageAsset();

        $this->assertSame($asset->getFilename(), $capturedFilename);
    }

    // ── Integration tests – update ────────────────────────────────────────────

    public function testEventFiredOnUpdate(): void
    {
        $asset = TestHelper::createImageAsset();

        $fired = false;
        $this->addListener(function (ResolveMimeTypeEvent $event) use (&$fired): void {
            $fired = true;
        });

        $asset->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image1.jpg')));
        $asset->save();

        $this->assertTrue($fired, 'RESOLVE_MIME_TYPE event was not dispatched during asset update');
    }

    public function testIsNewAssetFalseOnUpdate(): void
    {
        $asset = TestHelper::createImageAsset();

        $capturedEvent = null;
        $this->addListener(function (ResolveMimeTypeEvent $event) use (&$capturedEvent): void {
            $capturedEvent = $event;
        });

        $asset->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image1.jpg')));
        $asset->save();

        $this->assertNotNull($capturedEvent);
        $this->assertFalse($capturedEvent->isNewAsset(), 'isNewAsset must be false when updating an existing asset');
    }

    public function testAssetInstanceAvailableOnUpdate(): void
    {
        $asset = TestHelper::createImageAsset();

        $capturedEvent = null;
        $this->addListener(function (ResolveMimeTypeEvent $event) use (&$capturedEvent): void {
            $capturedEvent = $event;
        });

        $asset->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image1.jpg')));
        $asset->save();

        $this->assertNotNull($capturedEvent);
        $this->assertSame($asset, $capturedEvent->getAsset());
    }

    public function testListenerCanOverrideMimeTypeOnUpdate(): void
    {
        $asset = TestHelper::createImageAsset();

        $this->addListener(function (ResolveMimeTypeEvent $event): void {
            $event->setMimeType('image/gif');
        });

        $asset->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image1.jpg')));
        $asset->save();

        $reloaded = Asset::getById($asset->getId(), ['force' => true]);
        $this->assertSame('image/gif', $reloaded->getMimeType());
    }
}
