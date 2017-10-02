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

namespace Pimcore\Document\Tag\NamingStrategy;

use Pimcore\Document\Tag\Block\BlockName;
use Pimcore\Document\Tag\Block\BlockState;

abstract class AbstractNamingStrategy implements NamingStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function buildTagName(string $name, string $type, BlockState $blockState): string
    {
        if (!$blockState->hasBlocks()) {
            return $name;
        }

        $blocks = $blockState->getBlocks();
        $indexes = $blockState->getIndexes();

        // check if the previous block is the name we're about to build
        // TODO: can this be avoided at the block level?
        if ($type === 'block') {
            $tmpBlocks = $blocks;
            $tmpIndexes = $indexes;

            array_pop($tmpBlocks);
            array_pop($tmpIndexes);

            $tmpName = $name;
            if (is_array($tmpBlocks)) {
                $tmpName = $this->buildHierarchicalName($name, $tmpBlocks, $tmpIndexes);
            }

            $previousBlockName = $blocks[count($blocks) - 1]->getName();
            if ($previousBlockName === $tmpName) {
                array_pop($blocks);
                array_pop($indexes);
            }
        }

        $result = $this->buildHierarchicalName($name, $blocks, $indexes);

        return $result;
    }

    /**
     * Builds hierarchical name from given state information
     *
     * @param string $name
     * @param BlockName[] $blocks
     * @param int[] $indexes
     *
     * @return string
     */
    abstract protected function buildHierarchicalName(string $name, array $blocks, array $indexes): string;
}
