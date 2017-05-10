<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\Mail;

use Pimcore\Tests\Test\TestCase;
use Pimcore\Tool\HtmlUtils;

class HtmlUtilsTest extends TestCase
{
    private $attributes = [
        'foo' => 'bar',
        'baz' => 'inga',
        'noop' => null,
        'quux' => true,
        'john' => 1,
        'doe'  => 2,
    ];

    public function testAssembleAttributeString()
    {
        $this->assertEquals(
            'foo="bar" baz="inga" noop quux="1" john="1" doe="2"',
            HtmlUtils::assembleAttributeString($this->attributes)
        );
    }

    public function testAssembleAttributeStringOmitsNullValuesWhenConfigured()
    {
        $this->assertEquals(
            'foo="bar" baz="inga" quux="1" john="1" doe="2"',
            HtmlUtils::assembleAttributeString($this->attributes, true)
        );
    }
}
