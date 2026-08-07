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

use Pimcore;
use Pimcore\Extension\Document\Areabrick\AreabrickInterface;
use Pimcore\Extension\Document\Areabrick\AreabrickManagerInterface;
use Pimcore\Model\Document\Editable\Areablock;
use Pimcore\Model\Document\Editable\Block;
use Pimcore\Model\Document\Editable\Scheduledblock;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionProperty;

/**
 * Regression test for pimcore/pimcore#19319, covering the scenario reported in
 * pimcore/platform-version#248.
 *
 * A document created in Pimcore v12 stores areablock items under identifiers that are neither
 * contiguous nor ascending (`content:13`, `content:15`, …, `content:8`, `content:2`), and the
 * editables inside an item repeat that identifier in their own names (`content:13.title`).
 * Opening such a document in the Studio editor and saving it — without adding, removing or
 * reordering anything — renumbered the parent items into a fresh `1..N` sequence while the child
 * editables kept the old identifiers, so parent and children became disconnected and the stored
 * values vanished from the editor.
 *
 * The item key travels to the editor in a `key` attribute on the entry `<div>`. `key` is a
 * reserved React prop and a non-standard HTML attribute, so it can be stripped between the server
 * render and the point where the editor reads it — the reporter confirmed `key: null` in their
 * DOM. With no key to read, the editor invented the sequence, which is exactly the renumbering.
 *
 * The fix mirrors the key into `data-key`, which survives that processing. These tests pin down
 * that for every entry of an areablock, block and scheduledblock the editmode markup carries
 * `data-key` next to `key`, and that both hold the *stored* identifier rather than the entry's
 * position.
 */
class EditmodeDataKeyAttributeTest extends TestCase
{
    protected bool $cleanupDbInSetup = false;

    /**
     * The six areablock entries from the report, with their v12 identifiers: not contiguous, not
     * ascending, and — as they arrive from the editmode payload — strings rather than integers.
     * One entry is hidden, since a hidden item must keep its identifier just like a visible one.
     */
    private const LEGACY_AREABLOCK_ENTRIES = [
        ['key' => '13', 'type' => 'row', 'hidden' => false],
        ['key' => '15', 'type' => 'spacer', 'hidden' => false],
        ['key' => '17', 'type' => 'category-carousel', 'hidden' => false],
        ['key' => '18', 'type' => 'brand-carousel', 'hidden' => true],
        ['key' => '8', 'type' => 'product-carousel', 'hidden' => false],
        ['key' => '2', 'type' => 'row', 'hidden' => false],
    ];

    /**
     * The contiguous sequence the editor fabricated for those six entries — what must never come
     * out of the renderer.
     */
    private const RENUMBERED_SEQUENCE = ['1', '2', '3', '4', '5', '6'];

    private ?AreabrickManagerInterface $originalAreabrickManager = null;

    protected function needsDb(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->stubAreabrickManager();
    }

    protected function tearDown(): void
    {
        if ($this->originalAreabrickManager !== null) {
            Pimcore::getContainer()->set(AreabrickManagerInterface::class, $this->originalAreabrickManager);
            $this->originalAreabrickManager = null;
        }

        parent::tearDown();
    }

    /**
     * The reported case: every item of a six-item areablock keeps its v12 identifier, in both
     * attributes, and the set of identifiers is not the fabricated 1..6 sequence.
     */
    public function testAreablockKeepsLegacyItemKeysAndMirrorsThemIntoDataKey(): void
    {
        $areablock = $this->createAreablock('content', 'content', self::LEGACY_AREABLOCK_ENTRIES);

        $emittedKeys = [];

        foreach (self::LEGACY_AREABLOCK_ENTRIES as $position => $entry) {
            $this->setCurrentEntry($areablock, $position);

            $rendered = $areablock->blockStart()['editmodeOuterAttributes'];
            $context = sprintf('areablock entry #%d (%s)', $position, $entry['type']);

            $this->assertMirroredKey($rendered, $entry['key'], $context);

            // the item type and the hidden flag must stay paired with that same identifier
            $this->assertSame($entry['type'], $this->attributeValue($rendered, 'type'), $context);
            $this->assertSame(
                $entry['hidden'] ? 'true' : 'false',
                $this->attributeValue($rendered, 'data-hidden'),
                $context
            );

            $emittedKeys[] = $this->attributeValue($rendered, 'data-key');
        }

        $this->assertSame(
            array_column(self::LEGACY_AREABLOCK_ENTRIES, 'key'),
            $emittedKeys,
            'every item must advertise its stored v12 identifier'
        );
        $this->assertNotSame(
            self::RENUMBERED_SEQUENCE,
            $emittedKeys,
            'items must not be renumbered into a fresh contiguous sequence — that is platform-version#248'
        );
    }

    /**
     * The report nests an areablock inside an areablock item (`content:13.content1`), and a block
     * inside that (`content:13.content1:1.homepage_carousel`). Nesting must not change how the
     * identifier is emitted, and the entry has to stay addressable under its qualified name.
     */
    public function testNestedAreablockInsideAreablockItemEmitsDataKey(): void
    {
        $nested = $this->createAreablock(
            'content:13.content1',
            'content1',
            [['key' => '1', 'type' => 'mega-slider', 'hidden' => false]],
            ['content']
        );

        $templateParams = $nested->blockStart();
        $outerAttributes = $templateParams['editmodeOuterAttributes'];

        $this->assertMirroredKey($outerAttributes, '1', 'nested areablock entry');
        $this->assertSame(
            'content:13.content1',
            $this->attributeValue($templateParams['editmodeGenericAttributes'], 'data-name'),
            'the nested areablock must stay addressable under its qualified name'
        );
    }

    /**
     * `0` is a valid identifier and a falsy value — the mirror must not skip it.
     */
    public function testAreablockEmitsDataKeyForAZeroItemKey(): void
    {
        $areablock = $this->createAreablock('content', 'content', [
            ['key' => '0', 'type' => 'row', 'hidden' => false],
        ]);

        $this->assertMirroredKey(
            $areablock->blockStart()['editmodeOuterAttributes'],
            '0',
            'areablock entry with key 0'
        );
    }

    /**
     * The `homepage_carousel` block from the report, nested inside an areablock item. A block
     * stores bare indices rather than entry arrays, and a v12 document leaves them out of order.
     */
    public function testBlockKeepsLegacyIndicesAndMirrorsThemIntoDataKey(): void
    {
        $legacyIndices = [4, 1, 3, 2];

        $block = new Block();
        $block->setName('content:13.content1:1.homepage_carousel');
        $block->setRealName('homepage_carousel');
        $block->setParentBlockNames(['content', 'content1']);
        $block->setEditmode(true);
        $block->setDataFromEditmode($legacyIndices);

        $emittedKeys = [];

        foreach ($legacyIndices as $position => $legacyIndex) {
            $this->setCurrentEntry($block, $position);

            $rendered = $block->blockStart(showControls: false, return: true);

            $this->assertMirroredKey($rendered, (string) $legacyIndex, sprintf('block entry #%d', $position));

            $emittedKeys[] = $this->attributeValue($rendered, 'data-key');
        }

        $this->assertSame(
            ['4', '1', '3', '2'],
            $emittedKeys,
            'block entries must keep their stored indices, in their stored order'
        );
    }

    /**
     * Scheduledblock carries the same `key` attribute and was renumbered the same way.
     */
    public function testScheduledblockKeepsLegacyItemKeysAndMirrorsThemIntoDataKey(): void
    {
        $legacyEntries = [
            ['key' => '13', 'date' => 1000],
            ['key' => '8', 'date' => 2000],
            ['key' => '2', 'date' => 3000],
        ];

        $scheduledblock = new Scheduledblock();
        $scheduledblock->setName('content:13.schedule');
        $scheduledblock->setRealName('schedule');
        // blockStart() only writes to the output buffer while in editmode
        $scheduledblock->setEditmode(true);
        $scheduledblock->setDataFromEditmode($legacyEntries);

        $emittedKeys = [];

        foreach ($legacyEntries as $position => $entry) {
            // blockStart() advances the internal pointer itself
            ob_start();
            $scheduledblock->blockStart();
            $rendered = (string) ob_get_clean();

            $context = sprintf('scheduledblock entry #%d', $position);

            $this->assertMirroredKey($rendered, $entry['key'], $context);
            $this->assertSame((string) $entry['date'], $this->attributeValue($rendered, 'date'), $context);

            $emittedKeys[] = $this->attributeValue($rendered, 'data-key');
        }

        $this->assertSame(
            ['13', '8', '2'],
            $emittedKeys,
            'scheduledblock entries must keep their stored identifiers'
        );
    }

    /**
     * Asserts that the rendered markup carries the stored identifier in both attributes: `key` for
     * backward compatibility, and `data-key` as the mirror that survives downstream processing.
     */
    private function assertMirroredKey(string $rendered, string $expectedKey, string $context): void
    {
        $this->assertSame(
            $expectedKey,
            $this->attributeValue($rendered, 'key'),
            sprintf('%s must keep the legacy key attribute with its stored key. Rendered: %s', $context, $rendered)
        );

        $this->assertSame(
            $expectedKey,
            $this->attributeValue($rendered, 'data-key'),
            sprintf('%s must emit data-key with its stored key. Rendered: %s', $context, $rendered)
        );
    }

    /**
     * Reads a single attribute out of a rendered attribute string. The negative lookbehind keeps a
     * lookup of `key` from being satisfied by the `data-key` that the fix adds.
     */
    private function attributeValue(string $rendered, string $attribute): ?string
    {
        $pattern = sprintf('/(?<![-\w])%s="([^"]*)"/', preg_quote($attribute, '/'));

        if (preg_match($pattern, $rendered, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<int, string>               $parentBlockNames
     */
    private function createAreablock(
        string $name,
        string $realName,
        array $entries,
        array $parentBlockNames = []
    ): Areablock {
        $areablock = new Areablock();
        $areablock->setName($name);
        $areablock->setRealName($realName);
        $areablock->setParentBlockNames($parentBlockNames);
        $areablock->setEditmode(true);
        $areablock->setDataFromEditmode($entries);

        return $areablock;
    }

    /**
     * Positions the block on the nth entry. There is no usable public seam for this: Areablock has
     * no setter at all, and Block::setCurrent() offsets by one because it is meant to be called
     * from the iteration helpers rather than to address an entry directly.
     */
    private function setCurrentEntry(Areablock|Block $editable, int $position): void
    {
        $current = new ReflectionProperty($editable, 'current');
        $current->setValue($editable, $position);
    }

    /**
     * Areablock::blockStart() resolves the brick for the current entry through the container.
     *
     * The real manager is a shared service that lives for the whole test run and exposes no
     * unregister operation, so registering test bricks on it would leak into every later test and
     * make a future register() of the same ids order-dependent. Swap in a stub for the duration of
     * the test and put the original service back in tearDown() instead.
     */
    private function stubAreabrickManager(): void
    {
        $container = Pimcore::getContainer();
        $this->originalAreabrickManager = $container->get(AreabrickManagerInterface::class);

        $areabrickManager = $this->createMock(AreabrickManagerInterface::class);
        $areabrickManager
            ->method('getBrick')
            ->willReturn($this->createMock(AreabrickInterface::class));

        $container->set(AreabrickManagerInterface::class, $areabrickManager);
    }
}
