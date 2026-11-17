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
use Pimcore\Messenger\GenerateImageThumbnailsMessage;
use Pimcore\Model\Asset\Image\Thumbnail\PreGenerationDispatcher;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionProperty;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Thumbnail pre-generation (`pimcore.assets.image.thumbnails.pre_generate`) is queued by the save
 * of an image itself, for a changed binary only.
 *
 * @group model.asset.asset
 */
final class ImageThumbnailPreGenerationTest extends ModelTestCase
{
    /**
     * @var array<int, array{int, string}>
     */
    private array $dispatched = [];

    /**
     * @var array<string, mixed>
     */
    private array $originalState = [];

    /**
     * The container does not allow replacing an initialized service and the test configuration lists
     * no thumbnail, so the bus and the names of the real dispatcher are swapped for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(function (GenerateImageThumbnailsMessage $message) {
            $this->dispatched[] = [$message->getId(), $message->getThumbnailName()];

            return new Envelope($message);
        });

        $this->originalState = $this->setDispatcherState(['messageBus' => $messageBus, 'thumbnailNames' => ['hero']]);
    }

    protected function tearDown(): void
    {
        $this->setDispatcherState($this->originalState);

        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed> the values that were replaced
     */
    private function setDispatcherState(array $state): array
    {
        $dispatcher = Pimcore::getContainer()->get(PreGenerationDispatcher::class);
        $previous = [];
        foreach ($state as $name => $value) {
            $property = new ReflectionProperty(PreGenerationDispatcher::class, $name);
            $previous[$name] = $property->getValue($dispatcher);
            $property->setValue($dispatcher, $value);
        }

        return $previous;
    }

    public function testOnlyASaveWithAChangedBinaryQueuesThePreGeneration(): void
    {
        $image = TestHelper::createImageAsset();
        self::assertSame([[$image->getId(), 'hero']], $this->dispatched, 'The upload queues the pre-generation');

        $this->dispatched = [];
        $image->setProperty('foo', 'text', 'bar');
        $image->save();
        self::assertSame([], $this->dispatched, 'A save without a changed binary queues nothing');

        $image->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image2.jpg')));
        $image->save();
        self::assertSame([[$image->getId(), 'hero']], $this->dispatched, 'A replaced binary queues the pre-generation');
    }

    public function testNonImageAssetsQueueNothing(): void
    {
        TestHelper::createDocumentAsset();

        self::assertSame([], $this->dispatched);
    }
}
