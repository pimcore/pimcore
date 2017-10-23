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

namespace Pimcore\Tests\Unit\Analytics\Code;

use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Tests\Test\TestCase;

class CodeBlockTest extends TestCase
{
    /**
     * @var array
     */
    private $defaultParts = [
        'foo;',
        'bar?',
        'bazinga!' . "\n" . '!!!'
    ];

    /**
     * @var string
     */
    private $defaultResult = <<<'EOL'
foo;
bar?
bazinga!
!!!
EOL;

    public function testToString()
    {
        $block = new CodeBlock($this->defaultParts);

        $this->assertEquals($this->defaultResult, $block->asString());
        $this->assertEquals($this->defaultResult, (string)$block);
        $this->assertEquals($this->defaultResult, $block->__toString());
    }

    public function testSetParts()
    {
        $block = new CodeBlock();

        $this->assertEmpty($block->getParts());

        $block->setParts($this->defaultParts);

        $this->assertEquals($this->defaultParts, $block->getParts());
        $this->assertEquals($this->defaultResult, $block->asString());
    }

    public function testAppend()
    {
        $block = new CodeBlock($this->defaultParts);

        $block->append('foofoo');

        $expected = $this->defaultResult . "\nfoofoo";

        $this->assertEquals($expected, $block->asString());

        $block->append(["123", "456"]);

        $expected = $expected. "\n123\n456";

        $this->assertEquals($expected, $block->asString());
    }

    public function testPrepend()
    {
        $block = new CodeBlock($this->defaultParts);

        $block->prepend('barbar');

        $expected = "barbar\n" . $this->defaultResult;

        $this->assertEquals($expected, $block->asString());

        $block->prepend(["654", "321"]);

        $expected = "654\n321\n" . $expected;

        $this->assertEquals($expected, $block->asString());
    }
}
