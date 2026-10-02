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

namespace Pimcore\Tests\Unit\Maintenance;

use Doctrine\DBAL\Connection;
use Pimcore\Maintenance\Tasks\DataObject\DataObjectTaskHelper;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Guards the orphan-table detection used by the brick / fieldcollection maintenance cleanup tasks.
 *
 * The dangerous case is a definition key that contains an underscore (keys are validated as
 * /^[a-zA-Z]\w*$/): a naive "split at the first underscore" would resolve a live table to the wrong
 * key, fail the existence check, and drop a table that still holds data. matchCollectionKeys() must
 * therefore return every key that could own a table - a table may only be treated as orphaned when
 * no candidate parse resolves to a live class.
 */
class DataObjectTaskHelperTest extends TestCase
{
    private function createHelper(): DataObjectTaskHelper
    {
        // matchCollectionKeys() is pure logic and touches neither the logger nor the connection.
        return new DataObjectTaskHelper(
            $this->createMock(LoggerInterface::class),
            $this->createMock(Connection::class)
        );
    }

    /**
     * A key without an underscore resolves to itself; a non-matching identifier is an orphan.
     */
    public function testMatchesAndMissesSimpleKeys(): void
    {
        $helper = $this->createHelper();
        $names = ['foo' => 'Foo'];

        $this->assertSame(['Foo'], $helper->matchCollectionKeys('Foo_5', $names));
        $this->assertSame([], $helper->matchCollectionKeys('Bar_5', $names));
    }

    /**
     * Regression guard: a live table whose key contains an underscore must be recognised as owned,
     * never treated as an orphan (which previously would have dropped it).
     */
    public function testUnderscoreKeyTableIsNotAnOrphan(): void
    {
        $helper = $this->createHelper();

        $this->assertSame(
            ['Foo_Bar'],
            $helper->matchCollectionKeys('Foo_Bar_5', ['foo_bar' => 'Foo_Bar'])
        );
    }

    /**
     * When several keys share a prefix, every one of them is a candidate owner, ordered longest
     * (most specific) first so the caller probes the most likely class-id split before the others.
     */
    public function testAllCandidateKeysAreReturnedLongestFirst(): void
    {
        $helper = $this->createHelper();
        $names = ['foo' => 'Foo', 'foo_bar' => 'Foo_Bar'];

        $this->assertSame(['Foo_Bar', 'Foo'], $helper->matchCollectionKeys('Foo_Bar_5', $names));
        $this->assertSame(['Foo'], $helper->matchCollectionKeys('Foo_5', $names));
    }

    /**
     * A removed definition whose name is a substring of a surviving key is still an orphan - the
     * match requires a full underscore-delimited segment, not a raw string prefix.
     */
    public function testPartialStringPrefixIsNotAMatch(): void
    {
        $helper = $this->createHelper();

        // "Foo" was removed, "Foobar" survives: the "Foo_5" table is a genuine orphan.
        $this->assertSame([], $helper->matchCollectionKeys('Foo_5', ['foobar' => 'Foobar']));
        // "Foo" was removed, "Foo_Bar" survives: "Foo_5" does not belong to "Foo_Bar".
        $this->assertSame([], $helper->matchCollectionKeys('Foo_5', ['foo_bar' => 'Foo_Bar']));
    }

    /**
     * Matching is case-insensitive but returns the actual (case-preserved) key from disk.
     */
    public function testMatchIsCaseInsensitive(): void
    {
        $helper = $this->createHelper();

        $this->assertSame(['Foo'], $helper->matchCollectionKeys('foo_5', ['foo' => 'Foo']));
    }

    /**
     * With no definitions left (the last one was removed), every table is an orphan.
     */
    public function testEmptyDefinitionsMeansEverythingIsOrphan(): void
    {
        $helper = $this->createHelper();

        $this->assertSame([], $helper->matchCollectionKeys('Foo_5', []));
    }

    /**
     * Localized fieldcollection tables (object_collection_<key>_localized_<id>) resolve to their key
     * even when the key itself contains an underscore.
     */
    public function testLocalizedFieldcollectionDescriptorResolvesToKey(): void
    {
        $helper = $this->createHelper();

        $this->assertSame(['Foo'], $helper->matchCollectionKeys('Foo_localized_5', ['foo' => 'Foo']));
        $this->assertSame(
            ['Foo_Bar'],
            $helper->matchCollectionKeys('Foo_Bar_localized_5', ['foo_bar' => 'Foo_Bar'])
        );
    }
}
