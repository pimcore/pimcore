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

namespace Pimcore\Tests\Unit\Model\DataObject\Data;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\RgbaColor;

/**
 * Regression test for #17894: BlockElement::renewReferences() deep-copied the owner chain
 * (BlockElement -> Localizedfield -> all items) of every owner-aware data object, which made
 * loading versions of localized blocks explode in time and memory.
 *
 * @group model.datatype.block
 */
class BlockElementTest extends TestCase
{
    /**
     * @return iterable<string, array{0: callable(): object}>
     */
    public static function ownerAwareDataProvider(): iterable
    {
        yield 'link' => [static fn (): object => (new Link())->setDirect('/foo')->setText('foo')];
        yield 'geopoint' => [static fn (): object => new GeoCoordinates(48.2, 16.3)];
        yield 'rgbaColor' => [static fn (): object => new RgbaColor(1, 2, 3, 4)];
        yield 'consent' => [static fn (): object => new Consent(true, 1)];
    }

    public function testRenewReferencesDoesNotCopyOwnerGraph(): void
    {
        foreach (self::ownerAwareDataProvider() as $name => [$createData]) {
            $this->assertOwnerGraphIsNotCopied($createData, $name);
        }
    }

    private function assertOwnerGraphIsNotCopied(callable $createData, string $name): void
    {
        $owner = new OwnerGraphStub();
        foreach (range(1, 3) as $i) {
            $element = new BlockElement('field', 'type', $createData());
            $element->getData()->_setOwner($element);
            $owner->items[] = $element;
            $element->_setOwner($owner);
        }

        /** @var OwnerGraphStub $restored */
        $restored = unserialize(serialize($owner));
        OwnerGraphProbe::$clones = 0;

        foreach ($restored->items as $element) {
            $data = $element->getData();

            $this->assertSame(
                $element,
                $data->_getOwner(),
                $name . ': the renewed data must be owned by the block element it belongs to, not by a copy of it'
            );
            $this->assertSame('field', $data->_getOwnerFieldname());
        }

        $this->assertSame(0, OwnerGraphProbe::$clones, $name . ': the owner graph must not be deep-copied');
    }

    public function testRenewReferencesStillCopiesTheData(): void
    {
        $element = new BlockElement('field', 'link', (new Link())->setDirect('/foo'));
        $restored = unserialize(serialize($element));
        $this->assertTrue($restored->getNeedsRenewReferences());

        $dataBefore = (fn () => $this->data)->call($restored);
        $dataAfter = $restored->getData();

        $this->assertNotSame($dataBefore, $dataAfter);
        $this->assertSame('/foo', $dataAfter->getPath());
        $this->assertFalse($restored->getNeedsRenewReferences());
    }
}

/**
 * @internal test double standing in for the Localizedfield that owns the block elements
 */
class OwnerGraphStub
{
    /** @var BlockElement[] */
    public array $items = [];

    public OwnerGraphProbe $probe;

    public function __construct()
    {
        $this->probe = new OwnerGraphProbe();
    }
}

/**
 * @internal counts how often it is cloned, which is what the deep copy does to every object it visits
 */
class OwnerGraphProbe
{
    public static int $clones = 0;

    public function __clone()
    {
        self::$clones++;
    }
}
