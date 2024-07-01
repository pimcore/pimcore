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

use JsonSerializable;
use UnderflowException;

/**
 * @internal
 *
 * Keeps track of the current block nesting level and index (will be used from
 * editables to build their hierarchical editable name).
 *
 * On sub requests, a new BlockState is added to the state stack which is valid
 * for the sub request.
 */
final class BlockState implements JsonSerializable
{
    /**
     * @var BlockName[]
     */
    private array $blocks = [];

    /**
     * @var int[]
     */
    private array $indexes = [];

    /**
     * @return BlockName[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function hasBlocks(): bool
    {
        return !empty($this->blocks);
    }

    public function pushBlock(BlockName $block): void
    {
        array_push($this->blocks, $block);
    }

    public function popBlock(): BlockName
    {
        if (empty($this->blocks)) {
            throw new UnderflowException('There are no blocks to pop from as blocks list is empty');
        }

        return array_pop($this->blocks);
    }

    public function clearBlocks(): void
    {
        $this->blocks = [];
    }

    /**
     * @return int[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function hasIndexes(): bool
    {
        return !empty($this->indexes);
    }

    public function pushIndex(int $index): void
    {
        array_push($this->indexes, $index);
    }

    public function popIndex(): int
    {
        if (empty($this->indexes)) {
            throw new UnderflowException('There are no indexes to pop from as index list is empty');
        }

        return array_pop($this->indexes);
    }

    public function clearIndexes(): void
    {
        $this->indexes = [];
    }

    public function jsonSerialize(): array
    {
        return [
            'blocks' => $this->blocks,
            'indexes' => $this->indexes,
        ];
    }
}
