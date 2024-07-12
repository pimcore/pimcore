<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Unit\Document\Tag\Block;

use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Document\Editable\Block\BlockState;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @covers BlockState
 */
class BlockStateTest extends TestCase
{
    public function testBlocks(): void
    {
        $state = new BlockState();

        $this->assertEquals(0, count($state->getBlocks()));
        $this->assertFalse($state->hasBlocks());

        $blockNames = [
            new BlockName('A', 'realA'),
            new BlockName('B', 'realB'),
        ];

        $state->pushBlock($blockNames[0]);
        $state->pushBlock($blockNames[1]);

        $this->assertEquals(2, count($state->getBlocks()));
        $this->assertTrue($state->hasBlocks());
        $this->assertEquals($blockNames, $state->getBlocks());

        $state->popBlock();

        $this->assertEquals(1, count($state->getBlocks()));
        $this->assertTrue($state->hasBlocks());
        $this->assertEquals([$blockNames[0]], $state->getBlocks());

        $state->popBlock();

        $this->assertEquals(0, count($state->getBlocks()));
        $this->assertFalse($state->hasBlocks());
    }

    public function testPopBlocksThrowsExceptionIfEmpty(): void
    {
        $this->expectException(\UnderflowException::class);
        $state = new BlockState();
        $state->popBlock();
    }

    public function testClearBlocks(): void
    {
        $state = new BlockState();

        $state->pushBlock(new BlockName('A', 'realA'));
        $state->pushBlock(new BlockName('B', 'realB'));

        $this->assertTrue($state->hasBlocks());
        $this->assertCount(2, $state->getBlocks());

        $state->clearBlocks();

        $this->assertFalse($state->hasBlocks());
        $this->assertCount(0, $state->getBlocks());
    }

    public function testIndexes(): void
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

    public function testPopIndexesThrowsExceptionIfEmpty(): void
    {
        $this->expectException(\UnderflowException::class);
        $state = new BlockState();
        $state->popIndex();
    }

    public function testClearIndexes(): void
    {
        $state = new BlockState();

        $state->pushIndex(1);
        $state->pushIndex(2);

        $this->assertTrue($state->hasIndexes());
        $this->assertCount(2, $state->getIndexes());

        $state->clearIndexes();

        $this->assertFalse($state->hasIndexes());
        $this->assertCount(0, $state->getIndexes());
    }
}
