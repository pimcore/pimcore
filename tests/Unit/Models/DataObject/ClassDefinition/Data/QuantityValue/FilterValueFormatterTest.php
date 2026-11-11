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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data\QuantityValue;

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue\FilterValueFormatter;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Direct, DB-independent unit tests for FilterValueFormatter's numeric formatting boundary
 * logic: preserving the pre-fix (float)-cast output wherever it was already correct, and
 * falling back to a quoted string wherever a float cast would lose precision or overflow.
 */
final class FilterValueFormatterTest extends TestCase
{
    /**
     * @dataProvider unquotedFloatFormatProvider
     */
    public function testValuesRepresentableByFloatKeepThePriorUnquotedFormat(string $input, string $expected): void
    {
        $this->assertSame($expected, FilterValueFormatter::format($this->quotingConnectionStub(), $input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function unquotedFloatFormatProvider(): array
    {
        return [
            'integer' => ['5', '5'],
            'decimal' => ['12.5', '12.5'],
            'negative decimal' => ['-3.75', '-3.75'],
            'leading zero' => ['0.5', '0.5'],
            'small exponent notation' => ['1e5', '100000'],
            // MySQL/MariaDB accept a bare scientific-notation literal, so this is not quoted
            'small-magnitude value renders as bare scientific notation' => ['0.000001', '1.0E-6'],
            '15 significant digits, at the safe boundary' => ['123456789012345', '1.2345678901234E+14'],
        ];
    }

    /**
     * @dataProvider quotedFallbackProvider
     */
    public function testValuesThatWouldLosePrecisionOrOverflowAreQuotedInstead(string $input): void
    {
        $condition = FilterValueFormatter::format($this->quotingConnectionStub(), $input);

        $this->assertSame("'" . $input . "'", $condition);
        $this->assertStringNotContainsString('INF', $condition);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function quotedFallbackProvider(): array
    {
        return [
            'DECIMAL(65, 30) value beyond float precision' => [
                '123456789012345678901234567890.123456789012345678901234567890',
            ],
            '16 significant digits, just past the safe boundary' => ['1234567890123456'],
            'exponent notation that overflows (float) to INF' => ['1e309'],
            'negative exponent notation that overflows (float) to -INF' => ['-1e309'],
        ];
    }

    private function quotingConnectionStub(): Connection
    {
        $db = $this->createStub(Connection::class);
        $db->method('quote')->willReturnCallback(static fn (string $value): string => "'" . $value . "'");

        return $db;
    }
}
