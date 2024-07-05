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

use LogicException;
use Pimcore\Document\Editable\Block\BlockStateStack;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use RuntimeException;

class BlockStateStackTest extends TestCase
{
    public function testStackHasDefaultState(): void
    {
        $stack = new BlockStateStack();

        $this->assertEquals(1, $stack->count());
    }

    public function testPushAndPop(): void
    {
        $stack = new BlockStateStack();

        $this->assertEquals(1, $stack->count());

        $stack->push();

        $this->assertEquals(2, $stack->count());

        $tmpState = $stack->getCurrentState();

        $stack->push();
        $stack->push();
        $stack->push();

        $this->assertEquals(5, $stack->count());

        $stack->pop();
        $stack->pop();

        $this->assertEquals(3, $stack->count());

        $stack->pop();

        $this->assertEquals(2, $stack->count());
        $this->assertEquals($tmpState, $stack->getCurrentState());

        $stack->pop();

        $this->assertEquals(1, $stack->count());
    }

    public function testExceptionOnPopLastState(): void
    {
        $stack = new BlockStateStack();
        $stack->push();
        $stack->pop();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can\'t pop the last state off the stack');

        $stack->pop();
    }

    public function testExceptionOnCurrentStateWithoutDefaultState(): void
    {
        // this should never happen, but just to check if the current state
        // handles an empty state properly
        $reflector = new ReflectionClass(BlockStateStack::class);

        /** @var BlockStateStack $stack */
        $stack = $reflector->newInstanceWithoutConstructor();

        $this->assertInstanceOf(BlockStateStack::class, $stack);
        $this->assertEquals(0, $stack->count());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('State stack is empty');

        $stack->getCurrentState();
    }
}
