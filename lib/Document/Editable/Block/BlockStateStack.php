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

namespace Pimcore\Document\Editable\Block;

use Countable;
use JsonSerializable;
use LogicException;
use RuntimeException;

/**
 * @internal
 *
 * Handles block state (current block level, current block index)
 */
final class BlockStateStack implements Countable, JsonSerializable
{
    /**
     * @var BlockState[]
     */
    private array $states = [];

    public function __construct()
    {
        // we need to make sure there's a default state on the stack
        $this->push();
    }

    /**
     * Adds a new state to the stack
     *
     */
    public function push(BlockState $blockState = null): void
    {
        if (null === $blockState) {
            $blockState = new BlockState();
        }

        array_push($this->states, $blockState);
    }

    /**
     * Removes current state from the stack
     *
     */
    public function pop(): BlockState
    {
        if (count($this->states) <= 1) {
            throw new LogicException('Can\'t pop the last state off the stack');
        }

        return array_pop($this->states);
    }

    /**
     * Returns current state
     *
     */
    public function getCurrentState(): BlockState
    {
        if (empty($this->states)) {
            // this should never happen
            throw new RuntimeException('State stack is empty');
        }

        return array_slice($this->states, -1)[0];
    }

    public function count(): int
    {
        return count($this->states);
    }

    public function jsonSerialize(): array
    {
        return $this->states;
    }

    public function loadArray(array $array): void
    {
        $this->states = [];

        foreach ($array as $blockStateData) {
            $blockState = new BlockState();

            foreach ($blockStateData['blocks'] as $blockData) {
                $blockState->pushBlock(new BlockName($blockData['name'], $blockData['realName']));
            }

            foreach ($blockStateData['indexes'] as $indexData) {
                $blockState->pushIndex($indexData);
            }

            $this->states[] = $blockState;
        }
    }
}
