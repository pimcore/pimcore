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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\Video;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Probe class used to detect whether __wakeup is invoked during a restricted unserialize.
 */
class VideoObjectInjectionProbe
{
    public static bool $wakeupCalled = false;

    public function __wakeup(): void
    {
        self::$wakeupCalled = true;
    }
}

/**
 * @group unit.model.datatype.video
 */
class VideoTest extends TestCase
{
    /**
     * Verifies that a serialized payload embedding an arbitrary object is not instantiated
     * (no __wakeup) when Video::getDataFromResource() deserializes the stored column.
     *
     * Regression test for: Serialize::unserialize($data, false)
     */
    public function testGetDataFromResourceDoesNotInstantiateArbitraryObjects(): void
    {
        VideoObjectInjectionProbe::$wakeupCalled = false;

        // Simulates attacker-controlled bytes in the video column: a plain array (as the
        // field legitimately stores) that smuggles an object in an otherwise-ignored member.
        // The legitimate members keep valid scalar values so getDataFromResource() reaches
        // completion; the test fails only if the smuggled object's __wakeup() is invoked.
        $payload = serialize([
            'type' => 'other',
            'data' => 'https://example.com/video.mp4',
            'poster' => null,
            'injected' => new VideoObjectInjectionProbe(),
        ]);

        (new Video())->getDataFromResource($payload);

        $this->assertFalse(
            VideoObjectInjectionProbe::$wakeupCalled,
            '__wakeup() must not be called: the video field must deserialize with allowed_classes => false'
        );
    }
}
