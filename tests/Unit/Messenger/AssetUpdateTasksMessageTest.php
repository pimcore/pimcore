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

namespace Pimcore\Tests\Unit\Messenger;

use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Tests\Support\Test\TestCase;

class AssetUpdateTasksMessageTest extends TestCase
{
    /**
     * Messages queued before the data generation was introduced don't contain it. They must still be handled
     * (as tasks processing the asset in any case) after upgrading, instead of failing on the missing properties.
     */
    public function testMessageQueuedWithoutDataGenerationIsUnserialized(): void
    {
        $serializedLegacyMessage = 'O:41:"Pimcore\Messenger\AssetUpdateTasksMessage":1:{s:5:"' . "\0*\0" . 'id";i:42;}';

        $message = unserialize($serializedLegacyMessage);
        $this->assertInstanceOf(AssetUpdateTasksMessage::class, $message);
        $this->assertSame(42, $message->getId());
        $this->assertNull($message->getDataGeneration());
        $this->assertFalse($message->isPreviewsOnly());
    }

    public function testDataGenerationSurvivesSerialization(): void
    {
        $message = unserialize(serialize(new AssetUpdateTasksMessage(42, 'abcdef0123456789', true)));
        $this->assertInstanceOf(AssetUpdateTasksMessage::class, $message);
        $this->assertSame(42, $message->getId());
        $this->assertSame('abcdef0123456789', $message->getDataGeneration());
        $this->assertTrue($message->isPreviewsOnly());

        $message = new AssetUpdateTasksMessage(42);
        $this->assertNull($message->getDataGeneration());
        $this->assertFalse($message->isPreviewsOnly());
    }
}
