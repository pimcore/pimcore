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

namespace Pimcore\Tests\Unit\Model\Asset\Image\Thumbnail;

use Pimcore\Messenger\GenerateImageThumbnailsMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\PreGenerationDispatcher;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use TypeError;

/**
 * @internal
 */
class PreGenerationDispatcherTest extends TestCase
{
    private const ASSET_ID = 987654323;

    public function testNothingIsDispatchedByDefault(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        (new PreGenerationDispatcher($messageBus, new NullLogger()))->dispatch($this->createImage());
    }

    public function testOneMessagePerConfiguredThumbnailIsDispatched(): void
    {
        $dispatched = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(function (object $message) use (&$dispatched) {
            $dispatched[] = $message;

            return new Envelope($message);
        });

        (new PreGenerationDispatcher($messageBus, new NullLogger(), ['hero', 'card', 'hero']))->dispatch($this->createImage());

        self::assertContainsOnlyInstancesOf(GenerateImageThumbnailsMessage::class, $dispatched);
        self::assertSame(
            [[self::ASSET_ID, 'hero'], [self::ASSET_ID, 'card']],
            array_map(
                static fn (GenerateImageThumbnailsMessage $message) => [$message->getId(), $message->getThumbnailName()],
                $dispatched
            )
        );
    }

    /**
     * The dispatcher runs after the save was committed: the image is stored, so a broken
     * transport must not surface as a failed save, and must not stop the other configurations.
     */
    public function testFailingDispatchIsLoggedAndDoesNotStopTheOthers(): void
    {
        $dispatched = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(function (GenerateImageThumbnailsMessage $message) use (&$dispatched) {
            if ($message->getThumbnailName() === 'hero') {
                throw new RuntimeException('transport is down');
            }
            $dispatched[] = $message->getThumbnailName();

            return new Envelope($message);
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(self::stringContains('transport is down'));

        (new PreGenerationDispatcher($messageBus, $logger, ['hero', 'card']))->dispatch($this->createImage());

        self::assertSame(['card'], $dispatched);
    }

    /**
     * A PHP error is a defect (e.g. a broken message contract), not an unavailable transport.
     */
    public function testErrorsAreNotSwallowed(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')->willThrowException(new TypeError('broken contract'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $this->expectException(TypeError::class);

        (new PreGenerationDispatcher($messageBus, $logger, ['hero']))->dispatch($this->createImage());
    }

    private function createImage(): Asset\Image
    {
        $image = $this->getMockBuilder(Asset\Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $image->method('getId')->willReturn(self::ASSET_ID);

        return $image;
    }
}
