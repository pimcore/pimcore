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

use Pimcore\Document\Tag\Block\BlockState;

interface NamingStrategyInterface
{
    /**
     * Get the name of the naming strategy (used in frontend)
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Builds a tag name, taking current block state into account
     *
     * @param string $name
     * @param string $type
     * @param BlockState $blockState
     *
     * @return string
     */
    public function buildTagName(string $name, string $type, BlockState $blockState): string;

    /**
     * Builds a tag name for a child element
     *
     * @param string $name
     * @param string $type
     * @param array $parentBlockNames
     * @param int $index
     *
     * @return string
     */
    public function buildChildElementTagName(string $name, string $type, array $parentBlockNames, int $index): string;
}
