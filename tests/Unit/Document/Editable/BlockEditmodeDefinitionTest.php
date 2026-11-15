<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Document\Editable;

use Pimcore\Document\Editable\EditmodeEditableDefinitionCollector;
use Pimcore\Model\Document\Editable\Block;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for pimcore/platform-version#562.
 *
 * In editmode, Block::getIterator() temporarily replaces the first index with the template marker
 * 1000000 to render the hidden "add entry" template. The block used to be registered with the
 * editable definition collector before its indices were restored, so the editmode definition
 * carried the marker instead of the stored first index, and the editor saved it back.
 */
final class BlockEditmodeDefinitionTest extends TestCase
{
    private const TEMPLATE_MARKER = 1000000;

    protected bool $cleanupDbInSetup = false;

    protected function needsDb(): bool
    {
        return false;
    }

    public function testEditmodeDefinitionCarriesTheStoredIndices(): void
    {
        $definition = $this->renderBlockAndCollectDefinition([3, 5, 1]);

        $this->assertNotContains(self::TEMPLATE_MARKER, $definition['data']);
        $this->assertSame([3, 5, 1], $definition['data']);
        $this->assertIsArray($definition['config']['template'] ?? null, 'the add-entry template must still be published');
    }

    public function testEditmodeDefinitionOfAnEmptyBlockHasNoIndices(): void
    {
        $definition = $this->renderBlockAndCollectDefinition([]);

        $this->assertSame([], $definition['data']);
    }

    /**
     * @param int[] $indices
     *
     * @return array<string, mixed>
     */
    private function renderBlockAndCollectDefinition(array $indices): array
    {
        $collector = new EditmodeEditableDefinitionCollector();

        $block = new Block();
        $block->setName('content');
        $block->setRealName('content');
        $block->setConfig([]);
        $block->setEditmode(true);
        $block->setEditableDefinitionCollector($collector);
        $block->setDataFromEditmode($indices);

        ob_start();

        try {
            foreach ($block->getIterator() as $index) {
                // the block body is irrelevant here, only the bookkeeping around it matters
            }
        } finally {
            ob_end_clean();
        }

        $this->assertSame($indices, $block->getData(), 'the block must restore its indices after rendering');

        $definitions = array_values(array_filter(
            $collector->getDefinitions(),
            static fn (array $definition): bool => $definition['name'] === 'content'
        ));
        $this->assertCount(1, $definitions);

        return $definitions[0];
    }
}
