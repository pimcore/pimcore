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

namespace Pimcore\Tests\Unit\Document\Tag\NamingStrategy;

use Pimcore\Document\Tag\NamingStrategy\LegacyNamingStrategy;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;

class LegacyNamingStrategyTest extends AbstractNamingStrategyTest
{
    /**
     * @inheritdoc
     */
    protected function buildNamingStrategy(): NamingStrategyInterface
    {
        return new LegacyNamingStrategy();
    }

    /**
     * @inheritdoc
     */
    protected function getExpectedNames(): array
    {
        return [
            // top level elements
            'title' => 'title',
            'content' => 'content',
            'B_card' => 'B_card',

            // first card
            'B_card[1].BI_card_header' => 'BI_card_headerB_card1',
            'B_card[1].BB_card_block' => 'BB_card_blockB_card1',
            'B_card[1].BB_card_block[1].BBI_card_block_text' => 'BBI_card_block_textB_card_BB_card_blockB_card11_1',
            'B_card[1].BB_card_block[2].BBI_card_block_text' => 'BBI_card_block_textB_card_BB_card_blockB_card11_2',

            // second card
            'B_card[2].BI_card_header' => 'BI_card_headerB_card2',
            'B_card[2].BB_card_block' => 'BB_card_blockB_card2',
            'B_card[2].BB_card_block[1].BBI_card_block_text' => 'BBI_card_block_textB_card_BB_card_blockB_card22_1',
            'B_card[2].BB_card_block[2].BBI_card_block_text' => 'BBI_card_block_textB_card_BB_card_blockB_card22_2',
        ];
    }
}
