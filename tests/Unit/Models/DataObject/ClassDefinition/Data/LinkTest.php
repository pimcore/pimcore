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
use Pimcore\Model\DataObject\Data\Link as LinkData;
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

    private function buildLink(string $direct): LinkData
    {
        $link = new LinkData();
        $link->setLinktype('direct');
        $link->setDirect($direct);

        return $link;
    }

    /**
     * Regression test for https://github.com/pimcore/platform-version/issues/318.
     *
     * The version-comparison template (diff_versions.html.twig) falls back to an empty
     * string for a localized value that is not set in a given language. That empty string
     * used to be passed straight into isEqualArray(?array $array1, ...), which threw a
     * TypeError instead of the intended "no diff" / "diff" result.
     */
    public function testIsEqualDoesNotThrowForEmptyStringOperands(): void
    {
        $definition = new Link();

        $this->assertTrue($definition->isEqual('', null), 'empty string vs null must be treated as equal (both "no link")');
        $this->assertTrue($definition->isEqual(null, ''), 'null vs empty string must be treated as equal (both "no link")');
        $this->assertTrue($definition->isEqual('', ''), 'empty string vs empty string must be treated as equal');
    }

    public function testIsEqualDetectsDiffBetweenEmptyAndRealLink(): void
    {
        $definition = new Link();
        $link = $this->buildLink('https://www.pimcore.com');

        $this->assertFalse($definition->isEqual('', $link), 'empty string vs a real link must be reported as a diff');
        $this->assertFalse($definition->isEqual($link, ''), 'a real link vs empty string must be reported as a diff');
        $this->assertFalse($definition->isEqual(null, $link), 'null vs a real link must be reported as a diff');
    }

    public function testIsEqualStillComparesTwoRealLinksCorrectly(): void
    {
        $definition = new Link();

        $linkA = $this->buildLink('https://www.pimcore.com');
        $linkB = $this->buildLink('https://www.pimcore.com');
        $linkC = $this->buildLink('https://www.pimcore.org');

        $this->assertTrue($definition->isEqual($linkA, $linkB), 'two links with identical data must be equal');
        $this->assertFalse($definition->isEqual($linkA, $linkC), 'two links with different data must not be equal');
    }
}
