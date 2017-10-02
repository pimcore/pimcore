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

use Pimcore\Document\Tag\Block\BlockName;
use Pimcore\Document\Tag\Block\BlockState;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Tests\Test\TestCase;

/**
 * Tests an nested block structure building cards (bootstrap 4) with headers and one or more
 * text elements:
 *
 * input title => title
 *
 * block B_card => B_card
 *     <first card block>
 *     + input BI_card_header => B_card:1.BI_card_header
 *     + block BB_card_block => B_card:1.BB_card_block
 *         <first card text block>
 *         + input BB_card_block_text => B_card:1.BB_card_block:1.BBI_card_block_text
 *
 *         <second card text block>
 *         + input BB_card_block_text => B_card:1.BB_card_block:2.BBI_card_block_text
 *
 *     <second card block>
 *     + input BI_card => B_card:2.BI_card
 *     + block BB_card_block => B_card:2.BB_card_block
 *         <first card text block>
 *         + input BB_card_block_text => B_card:2.BB_card_block:1.BBI_card_block_text
 *
 *         <second card text block>
 *         + input BB_card_block_text => B_card:2.BB_card_block:2.BBI_card_block_text
 *
 * input content => content
 */
abstract class AbstractNamingStrategyTest extends TestCase
{
    /**
     * @var NamingStrategyInterface
     */
    private $strategy;

    /**
     * @var BlockState
     */
    private $blockState;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->blockState = new BlockState();
        $this->strategy   = $this->buildNamingStrategy();
    }

    /**
     * Builds the naming strategy to test
     *
     * @return NamingStrategyInterface
     */
    abstract protected function buildNamingStrategy(): NamingStrategyInterface;

    /**
     * Get expected names for the elements we're dealing with. See already implemented
     * tests and/or sprintf calls below for the expected names.
     *
     * @return array
     */
    abstract protected function getExpectedNames(): array;

    public function testStrategyBuildsExpectedNestedNames()
    {
        $expected = $this->getExpectedNames();

        $this->assertEquals(
            $expected['title'],
            $this->strategy->buildTagName('title', 'input', $this->blockState)
        );

        $blockName = $this->strategy->buildTagName('B_card', 'block', $this->blockState);
        $this->assertEquals(
            $expected['B_card'],
            $blockName
        );

        $this->blockState->pushBlock(BlockName::createFromNames($blockName, $blockName));

        $this->testCardBlock($expected, 1);
        $this->testCardBlock($expected, 2);

        $this->blockState->popBlock();

        $this->assertEquals(
            'content',
            $this->strategy->buildTagName('content', 'input', $this->blockState)
        );
    }

    protected function testCardBlock(array $expected, int $cardIndex)
    {
        // outer index
        $this->blockState->pushIndex($cardIndex);

        // header element
        $expectedHeaderKey  = sprintf('B_card[%d].BI_card_header', $cardIndex);
        $expectedHeaderName = $expected[$expectedHeaderKey];

        $this->assertEquals(
            $expectedHeaderName,
            $this->strategy->buildTagName('BI_card_header', 'input', $this->blockState),
            sprintf('Item "%s" matches expected name "%s"', $expectedHeaderKey, $expectedHeaderName)
        );

        // add sub-block
        $expectedSubBlockKey  = sprintf('B_card[%d].BB_card_block', $cardIndex);
        $expectedSubBlockName = $expected[$expectedSubBlockKey];

        $subBlockName = $this->strategy->buildTagName('BB_card_block', 'block', $this->blockState);
        $this->assertEquals(
            $expectedSubBlockName,
            $subBlockName,
            sprintf('Item "%s" matches expected name "%s"', $expectedSubBlockKey, $expectedSubBlockName)
        );

        // add inner block
        $this->blockState->pushBlock(BlockName::createFromNames($subBlockName, 'BB_card_block'));

        // test inner block elements
        $this->testCardSubBlock($expected, $cardIndex, 1);
        $this->testCardSubBlock($expected, $cardIndex, 2);

        $this->blockState->popBlock();

        // outer index
        $this->blockState->popIndex();
    }

    protected function testCardSubBlock(array $expected, int $cardIndex, int $subIndex)
    {
        // inner index
        $this->blockState->pushIndex($subIndex);

        $expectedTextKey  = sprintf('B_card[%d].BB_card_block[%d].BBI_card_block_text', $cardIndex, $subIndex);
        $expectedTextName = $expected[$expectedTextKey];

        $this->assertEquals(
            $expected[sprintf('B_card[%d].BB_card_block[%d].BBI_card_block_text', $cardIndex, $subIndex)],
            $this->strategy->buildTagName('BBI_card_block_text', 'input', $this->blockState),
            sprintf('Item "%s" matches expected name "%s"', $expectedTextKey, $expectedTextName)
        );

        // inner index
        $this->blockState->popIndex();
    }
}
