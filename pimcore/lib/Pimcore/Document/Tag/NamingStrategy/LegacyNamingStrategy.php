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
use Pimcore\Document\Tag\NamingStrategy\Exception\TagNameException;

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

    /**
     * @inheritDoc
     */
    public function buildChildElementTagName(string $name, string $type, array $parentBlockNames, int $index): string
    {
        if (count($parentBlockNames) === 0) {
            throw new TagNameException(sprintf(
                'Failed to build child tag name for %s %s at index %d as no parent name was passed',
                $type, $name, $index
            ));
        }

        $id = null;
        if ('block' === $type) {
            $id = $name . implode('_', $parentBlockNames);

            foreach ($parentBlockNames as $item) {
                if (preg_match('#[^\d]{1}(?<index>[\d]+)$#i', $item, $match)) {
                    $id .= $match['index'] . '_';
                }
            }

            $id .= $index;
        } elseif (in_array($type, ['area', 'areablock'])) {
            $id = sprintf('%s%s%d', $name, array_pop($parentBlockNames), $index);
        }

        if (null === $id) {
            throw new TagNameException(sprintf('Failed to build child tag name for %s %s at index %d', $type, $name, $index));
        }

        return $id;
    }
}
