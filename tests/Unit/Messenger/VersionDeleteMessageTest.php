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

use Pimcore\Messenger\VersionDeleteMessage;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
class VersionDeleteMessageTest extends TestCase
{
    public function testCarriesTheMaxVersionIdBound(): void
    {
        $message = new VersionDeleteMessage('asset', 5, 42);

        $this->assertSame('asset', $message->getElementType());
        $this->assertSame(5, $message->getElementId());
        $this->assertSame(42, $message->getMaxVersionId());

        // 0 is the explicit "element had no versions at deletion time" bound and
        // must not collapse into the legacy null
        $this->assertSame(0, (new VersionDeleteMessage('asset', 5, 0))->getMaxVersionId());
    }

    /**
     * Messenger deserializes queued messages without calling the constructor, so a message
     * queued by a release that did not know the maxVersionId field yet must still be readable:
     * the class-level property default has to apply (a promoted-property default would not),
     * and null preserves the previous unbounded cleanup for exactly those messages.
     */
    public function testLegacyPayloadWithoutBoundDeserializesToNull(): void
    {
        $legacyPayload = 'O:38:"Pimcore\Messenger\VersionDeleteMessage":2:{'
            . 's:14:"' . "\0*\0" . 'elementType";s:5:"asset";'
            . 's:12:"' . "\0*\0" . 'elementId";i:5;}';

        $message = unserialize($legacyPayload, ['allowed_classes' => [VersionDeleteMessage::class]]);

        $this->assertInstanceOf(VersionDeleteMessage::class, $message);
        $this->assertSame('asset', $message->getElementType());
        $this->assertSame(5, $message->getElementId());
        $this->assertNull($message->getMaxVersionId());
    }
}
