<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Model\DataObject\Data;

use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionProperty;

/**
 * Regression test for PEES-1217: legacy DB rows can contain `null` for properties that are
 * now typed as non-nullable `string` (e.g. `text`, `parameters`). Loading such a row goes
 * through native PHP unserialize(), which restores typed properties directly and bypasses any
 * setter, so the fix must be verified against unserialize() itself rather than against setValues().
 *
 * @see Link::__unserialize()
 */
class LinkTest extends TestCase
{
    /**
     * Builds a serialized blob that mimics a legacy DB row: same protected property layout as
     * Link, but produced from an untyped double so the given overrides (e.g. null for a
     * now-non-nullable property) can actually be stored. The double's class name is then
     * rewritten to Link::class so it unserializes as a real Link instance.
     */
    private function buildLegacyLinkBlob(array $overrides): string
    {
        $double = new LegacyLinkDouble();
        foreach ($overrides as $property => $value) {
            $reflectionProperty = new ReflectionProperty($double, $property);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($double, $value);
        }

        $blob = serialize($double);
        $source = LegacyLinkDouble::class;
        $target = Link::class;

        return preg_replace(
            '/^O:' . strlen($source) . ':"' . preg_quote($source, '/') . '"/',
            'O:' . strlen($target) . ':"' . $target . '"',
            $blob
        );
    }

    public function testUnserializeCoercesLegacyNullStringPropertiesToEmptyString(): void
    {
        $blob = $this->buildLegacyLinkBlob([
            'text' => null,
            'parameters' => null,
            'anchor' => null,
            'title' => null,
            'accesskey' => null,
            'rel' => null,
            'tabindex' => null,
            'class' => null,
            'attributes' => null,
        ]);

        $link = unserialize($blob, ['allowed_classes' => true]);

        $this->assertInstanceOf(Link::class, $link);
        $this->assertSame('', $link->getText());
        $this->assertSame('', $link->getParameters());
        $this->assertSame('', $link->getAnchor());
        $this->assertSame('', $link->getTitle());
        $this->assertSame('', $link->getAccesskey());
        $this->assertSame('', $link->getRel());
        $this->assertSame('', $link->getTabindex());
        $this->assertSame('', $link->getClass());
        $this->assertSame('', $link->getAttributes());
    }

    public function testUnserializeKeepsNullForAlreadyNullableProperty(): void
    {
        $blob = $this->buildLegacyLinkBlob([
            'internal' => null,
            'internalType' => null,
        ]);

        $link = unserialize($blob, ['allowed_classes' => true]);

        $this->assertInstanceOf(Link::class, $link);
        $this->assertNull($link->getInternal());
        $this->assertNull($link->getInternalType());
    }

    public function testSerializeUnserializeRoundTripPreservesValues(): void
    {
        $link = new Link();
        $link->setText('hello');
        $link->setParameters('a=b');

        $restored = unserialize(serialize($link));

        $this->assertInstanceOf(Link::class, $restored);
        $this->assertSame('hello', $restored->getText());
        $this->assertSame('a=b', $restored->getParameters());
    }
}

/**
 * Same protected property layout as Link, without type hints, so a test fixture can store
 * `null` for properties that Link itself no longer allows to be null.
 */
class LegacyLinkDouble
{
    protected $text = '';

    protected $internalType = null;

    protected $internal = null;

    protected $parameters = '';

    protected $anchor = '';

    protected $title = '';

    protected $accesskey = '';

    protected $rel = '';

    protected $tabindex = '';

    protected $class = '';

    protected $attributes = '';
}
