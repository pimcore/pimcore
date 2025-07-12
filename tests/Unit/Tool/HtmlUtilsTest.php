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

namespace Pimcore\Tests\Unit\Tool;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\HtmlUtils;

class HtmlUtilsTest extends TestCase
{
    private array $attributes = [
        'foo' => 'bar',
        'baz' => 'inga',
        'noop' => null,
        'quux' => true,
        'john' => 1,
        'doe' => 2,
    ];

    public function testAssembleAttributeString(): void
    {
        $this->assertEquals(
            'foo="bar" baz="inga" noop quux="1" john="1" doe="2"',
            HtmlUtils::assembleAttributeString($this->attributes)
        );
    }

    public function testAssembleAttributeStringOmitsNullValuesWhenConfigured(): void
    {
        $this->assertEquals(
            'foo="bar" baz="inga" quux="1" john="1" doe="2"',
            HtmlUtils::assembleAttributeString($this->attributes, true)
        );
    }
}
