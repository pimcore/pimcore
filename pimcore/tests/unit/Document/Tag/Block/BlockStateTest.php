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

namespace Pimcore\Tests\Unit\Document\Tag\Block;

use Pimcore\Document\Tag\Block\BlockState;
use Pimcore\Tests\Test\TestCase;

/**
 * @covers BlockState
 */
class BlockStateTest extends TestCase
{
    public function testBlocks()
    {
        $state = new BlockState();

        $this->assertEquals(0, count($state->getBlocks()));
        $this->assertFalse($state->hasBlocks());

        $state->pushBlock('A');
        $state->pushBlock('B');

        $this->assertEquals(2, count($state->getBlocks()));
        $this->assertTrue($state->hasBlocks());
        $this->assertEquals(['A', 'B'], $state->getBlocks());

        $state->popBlock();

        $this->assertEquals(1, count($state->getBlocks()));
        $this->assertTrue($state->hasBlocks());
        $this->assertEquals(['A'], $state->getBlocks());

        $state->popBlock();

        $this->assertEquals(0, count($state->getBlocks()));
        $this->assertFalse($state->hasBlocks());
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testPopBlocksThrowsExceptionIfEmpty()
    {
        $state = new BlockState();
        $state->popBlock();
    }

    public function testIndexes()
    {
        $state = new BlockState();

        $this->assertEquals(0, count($state->getIndexes()));
        $this->assertFalse($state->hasIndexes());

        $state->pushIndex(1);
        $state->pushIndex(2);

        $this->assertEquals(2, count($state->getIndexes()));
        $this->assertTrue($state->hasIndexes());
        $this->assertEquals([1, 2], $state->getIndexes());

        $state->popIndex();

        $this->assertEquals(1, count($state->getIndexes()));
        $this->assertTrue($state->hasIndexes());
        $this->assertEquals([1], $state->getIndexes());

        $state->popIndex();

        $this->assertEquals(0, count($state->getIndexes()));
        $this->assertFalse($state->hasIndexes());
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testPopIndexesThrowsExceptionIfEmpty()
    {
        $state = new BlockState();
        $state->popIndex();
    }
}
