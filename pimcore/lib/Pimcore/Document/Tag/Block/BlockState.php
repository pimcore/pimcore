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

final class BlockState
{
    /**
     * @var string[]
     */
    private $blocks = [];

    /**
     * @var int[]
     */
    private $indexes = [];

    /**
     * @return string[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function hasBlocks(): bool
    {
        return !empty($this->blocks);
    }

    public function pushBlock(string $block)
    {
        array_push($this->blocks, $block);
    }

    public function popBlock(): string
    {
        if (empty($this->blocks)) {
            throw new \UnderflowException('There are no blocks to pop from as blocks list is empty');
        }

        return array_pop($this->blocks);
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

    public function pushIndex(int $index)
    {
        array_push($this->indexes, $index);
    }

    public function popIndex(): int
    {
        if (empty($this->indexes)) {
            throw new \UnderflowException('There are no indexes to pop from as index list is empty');
        }

        return array_pop($this->indexes);
    }
}
