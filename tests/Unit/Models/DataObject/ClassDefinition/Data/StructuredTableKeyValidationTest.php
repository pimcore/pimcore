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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition\Data\StructuredTable;

/**
 * Ensures StructuredTable::setCols()/setRows() only accept valid identifier keys (GHSA-2rmm-27mv-jwg5).
 * Column/row keys are concatenated into physical database column names (calculateDbColumns()) and,
 * on removal, into raw ALTER TABLE ... DROP COLUMN/DROP INDEX DDL, so they must be restricted the
 * same way Data::setName() restricts field names.
 */
class StructuredTableKeyValidationTest extends TestCase
{
    /**
     * @dataProvider validKeyProvider
     */
    public function testValidColKeysAreAccepted(string $key): void
    {
        $field = new StructuredTable();
        $field->setCols([['key' => $key, 'type' => 'text']]);

        $this->assertSame(strtolower($key), $field->getCols()[0]['key']);
    }

    /**
     * @dataProvider validKeyProvider
     */
    public function testValidRowKeysAreAccepted(string $key): void
    {
        $field = new StructuredTable();
        $field->setRows([['key' => $key, 'label' => 'Row']]);

        $this->assertSame(strtolower($key), $field->getRows()[0]['key']);
    }

    public static function validKeyProvider(): array
    {
        return [
            'simple' => ['col1'],
            'leading underscore' => ['_internal'],
            'single char' => ['a'],
            'mixed case gets lowercased' => ['MyCol'],
            'digits and underscores' => ['col_123'],
        ];
    }

    public function testEmptyKeyIsAccepted(): void
    {
        $field = new StructuredTable();
        $field->setCols([['key' => '', 'type' => 'text']]);

        $this->assertSame('', $field->getCols()[0]['key']);
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testInvalidColKeysAreRejected(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StructuredTable())->setCols([['key' => $key, 'type' => 'text']]);
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testInvalidRowKeysAreRejected(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StructuredTable())->setRows([['key' => $key, 'label' => 'Row']]);
    }

    public static function invalidKeyProvider(): array
    {
        return [
            'DDL injection payload from the advisory PoC' => ['x`, DROP COLUMN `oo_classname'],
            'contains backtick' => ['poc`'],
            'contains comma' => ['a,b'],
            'contains space' => ['my col'],
            'contains hash' => ['a#b'],
            'leading digit' => ['1col'],
            'too long (64)' => [str_repeat('a', 64)],
        ];
    }
}
