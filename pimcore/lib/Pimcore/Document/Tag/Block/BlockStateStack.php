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

namespace Pimcore\Document\Tag\Block;

/**
 * Handles block state (current block level, current block index). This is the
 * data which previously was handled in Registry pimcore_tag_block_current and
 * pimcore_tag_block_numeration.
 *
 * @internal
 */
final class BlockStateStack implements \Countable
{
    /**
     * @var BlockState[]
     */
    private $states = [];

    public function __construct()
    {
        // we need to make sure there's a default state on the stack
        $this->push();
    }

    /**
     * Adds a new state to the stack
     */
    public function push()
    {
        array_push($this->states, new BlockState());
    }

    /**
     * Removes current state from the stack
     *
     * @return BlockState
     */
    public function pop(): BlockState
    {
        if (count($this->states) <= 1) {
            throw new \LogicException('Can\'t pop the last state off the stack');
        }

        return array_pop($this->states);
    }

    /**
     * Returns current state
     *
     * @return BlockState
     */
    public function getCurrentState(): BlockState
    {
        if (empty($this->states)) {
            // this should never happen
            throw new \RuntimeException('State stack is empty');
        }

        return array_slice($this->states, -1)[0];
    }

    public function count(): int
    {
        return count($this->states);
    }
}
