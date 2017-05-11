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

final class LegacyNamingStrategy extends AbstractNamingStrategy
{
    const STRATEGY_NAME = 'legacy';

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::STRATEGY_NAME;
    }

    /**
     * @param string $name
     * @param BlockName[] $blocks
     * @param int[] $indexes
     *
     * @return string
     */
    protected function buildHierarchicalName(string $name, array $blocks, array $indexes): string
    {
        $blockNames = array_map(function (BlockName $block) {
            return $block->getName();
        }, $blocks);

        return $name . implode('_', $blockNames) . implode('_', $indexes);
    }
}
