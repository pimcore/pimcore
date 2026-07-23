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

use Pimcore\Model\DataObject\ClassDefinition\Data\Link;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Probe class used to detect whether __wakeup is invoked during a restricted unserialize.
 * It must NOT be in the allowlist passed to getDataFromResource.
 */
class ObjectInjectionProbe
{
    public static bool $wakeupCalled = false;

    public function __wakeup(): void
    {
        self::$wakeupCalled = true;
    }
}

/**
 * @group unit.model.datatype.link
 */
class LinkTest extends TestCase
{
    /**
     * Verifies that a serialized payload containing a class outside the allowlist is not
     * instantiated and does not trigger __wakeup — preventing PHP Object Injection via the DB.
     *
     * Regression test for the allowlist added to getDataFromResource():
     *   Serialize::unserialize($data, [DataObject\Data\Link::class])
     */
    public function testGetDataFromResourceRejectsNonAllowlistedClass(): void
    {
        ObjectInjectionProbe::$wakeupCalled = false;

        // Build a payload whose class is NOT in the allowlist used by getDataFromResource.
        $payload = serialize(new ObjectInjectionProbe());

        $result = (new Link())->getDataFromResource($payload);

        $this->assertNull($result, 'getDataFromResource() must return null for a non-allowlisted class');
        $this->assertFalse(
            ObjectInjectionProbe::$wakeupCalled,
            '__wakeup() must not be called when the class is excluded by the deserialization allowlist'
        );
    }
}

