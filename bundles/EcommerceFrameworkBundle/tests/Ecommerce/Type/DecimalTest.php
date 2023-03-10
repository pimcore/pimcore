<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tests\Ecommerce\Type;

use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @covers \Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal
 */
class DecimalTest extends TestCase
{
    public function testRepresentations(): void
    {
        $value = Decimal::create(10.0, 4);

        $this->assertEquals(100000, $value->asRawValue());
        $this->assertEquals(10.0, $value->asNumeric());
        $this->assertSame('10.0000', $value->asString());
    }

    public function testAsString(): void
    {
        $value = Decimal::create(10.0, 4);

        $this->assertSame('10.0000', (string)$value);
        $this->assertSame('10.0000', $value->asString());
        $this->assertSame('10.00', $value->asString(2));
        $this->assertSame('10', $value->asString(0));

        $otherScale = Decimal::create(15.99, 6);

        $this->assertSame('15.990000', (string)$otherScale);
        $this->assertSame('15.990000', $otherScale->asString());
        $this->assertSame('15.99', $otherScale->asString(2));
        $this->assertSame('15.9', $otherScale->asString(1));
        $this->assertSame('15', $otherScale->asString(0));
    }

    public function testAsStringNoScaleRoundsToNextInteger(): void
    {
        $noScale = Decimal::create(15.99, 0);

        $this->assertSame('16', (string)$noScale);
        $this->assertSame('16', $noScale->asString());
        $this->assertSame('16.00000', $noScale->asString(5));
        $this->assertSame('16.00', $noScale->asString(2));
        $this->assertSame('16.0', $noScale->asString(1));
        $this->assertSame('16', $noScale->asString(0));
    }

    public function testInvalidScaleThrowsException(): void
    {
        $this->expectException(\DomainException::class);
        Decimal::create(10000, -1);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(float|int|string|Decimal $input, string $expected): void
    {
        $value = Decimal::create($input);

        $this->assertSame($expected, $value->asString());
    }

    /**
     * @dataProvider createZeroScaleDataProvider
     */
    public function testZeroScale(float|int|string|Decimal $input): void
    {
        $val = Decimal::create($input, 0);

        $this->assertEquals(16, $val->asRawValue());
        $this->assertEquals(16.0, $val->asNumeric());
        $this->assertEquals('16', $val->asString());
    }

    public function testCreateZero(): void
    {
        $zero = Decimal::zero();

        $this->assertEquals(0, $zero->asRawValue());
        $this->assertEquals(0, $zero->asNumeric());
        $this->assertEquals('0.0000', $zero->asString());
        $this->assertTrue($zero->equals(Decimal::create(0)));
    }

    /**
     * @dataProvider invalidValueCreateProvider
     */
    public function testErrorOnInvalidCreateArgument(mixed $value): void
    {
        $this->expectException(\TypeError::class);
        Decimal::create($value);
    }

    public function testInvalidScaleThrowsExceptionOnCreate(): void
    {
        $this->expectException(\DomainException::class);
        Decimal::create('10.0', -1);
    }

    public function testCreateRounding(): void
    {
        $this->assertEquals(16, Decimal::create('15.50', 0)->asRawValue());
        $this->assertEquals(16, Decimal::create('15.50', 0, PHP_ROUND_HALF_UP)->asRawValue());
        $this->assertEquals(15, Decimal::create('15.50', 0, PHP_ROUND_HALF_DOWN)->asRawValue());
    }

    public function testFromRawValue(): void
    {
        $simpleValue = Decimal::fromRawValue(100000, 4);

        $this->assertEquals(100000, $simpleValue->asRawValue());
        $this->assertEquals(10, $simpleValue->asNumeric());

        $decimalValue = Decimal::fromRawValue(159900, 4);

        $this->assertEquals(159900, $decimalValue->asRawValue());
        $this->assertEquals(15.99, $decimalValue->asNumeric());
    }

    public function testFromNumeric(): void
    {
        $simpleValue = Decimal::fromNumeric(10, 4);

        $this->assertEquals(100000, $simpleValue->asRawValue());
        $this->assertEquals(10, $simpleValue->asNumeric());

        $decimalValue = Decimal::fromNumeric(15.99, 4);

        $this->assertEquals(159900, $decimalValue->asRawValue());
        $this->assertEquals(15.99, $decimalValue->asNumeric());
    }

    public function testExceptionOnInvalidFromNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Decimal::fromNumeric('ABC');
    }

    public function testFromDecimal(): void
    {
        $value = Decimal::fromRawValue(100000, 4);
        $createdValue = Decimal::fromDecimal($value, 4);

        $this->assertEquals($value, $createdValue);
    }

    public function testFromDecimalWithDifferentScale(): void
    {
        $value = Decimal::fromRawValue(100000, 4);
        $createdValue = Decimal::fromDecimal($value, 8);

        $this->assertEquals($value->asNumeric(), $createdValue->asNumeric());
    }

    public function testWithScale(): void
    {
        $val = Decimal::create('10', 4);

        $this->assertSame($val, $val->withScale(4));

        $this->assertSame(100000, $val->asRawValue());
        $this->assertSame(10, $val->asNumeric());

        $val = $val->withScale(6);

        $this->assertSame(10000000, $val->asRawValue());
        $this->assertSame(10, $val->asNumeric());

        $val = $val->withScale(2);

        $this->assertSame(1000, $val->asRawValue());
        $this->assertSame(10, $val->asNumeric());

        $val = $val->withScale(4);

        $this->assertSame(100000, $val->asRawValue());
        $this->assertSame(10, $val->asNumeric());
    }

    public function testWithScaleLosesPrecision(): void
    {
        $val = Decimal::create('15.99', 4);

        $this->assertSame(159900, $val->asRawValue());
        $this->assertSame(15.99, $val->asNumeric());

        $val = $val->withScale(6);

        $this->assertSame(15990000, $val->asRawValue());
        $this->assertSame(15.99, $val->asNumeric());

        $val = $val->withScale(2);

        $this->assertSame(1599, $val->asRawValue());
        $this->assertSame(15.99, $val->asNumeric());

        $val = $val->withScale(0);

        $this->assertSame(16, $val->asRawValue());
        $this->assertSame(16, $val->asNumeric());

        $val = $val->withScale(4);

        $this->assertSame(160000, $val->asRawValue());
        $this->assertSame(16, $val->asNumeric());
    }

    public function testExceptionOnAddWithMismatchingScale(): void
    {
        $valA = Decimal::create('10', 4);
        $valB = Decimal::create('20', 8);

        $scaledB = $valB->withScale(4);
        $this->assertEquals($valB->asNumeric(), $scaledB->asNumeric());

        $this->assertEquals(30, $valA->add($scaledB)->asNumeric());

        $this->expectException(\DomainException::class);
        $valA->add($valB);
    }

    public function testExceptionOnSubWithMismatchingScale(): void
    {
        $valA = Decimal::create('10', 4);
        $valB = Decimal::create('20', 8);

        $scaledB = $valB->withScale(4);
        $this->assertEquals($valB->asNumeric(), $scaledB->asNumeric());

        $this->assertEquals(-10, $valA->sub($scaledB)->asNumeric());

        $this->expectException(\DomainException::class);
        $valA->sub($valB);
    }

    public function testCompare(): void
    {
        $a = Decimal::create(5);
        $b = Decimal::create(10);

        $this->assertTrue($a->equals($a));
        $this->assertTrue($b->equals($b));
        $this->assertTrue($a->equals(Decimal::create(5)));
        $this->assertFalse($a->equals(Decimal::create(5, 8)));
        $this->assertFalse($a->equals($b));
        $this->assertFalse($b->equals($a));

        $this->assertFalse($a->notEquals($a));
        $this->assertFalse($b->notEquals($b));
        $this->assertFalse($a->notEquals(Decimal::create(5)));
        $this->assertTrue($a->notEquals(Decimal::create(5, 8)));
        $this->assertTrue($a->notEquals($b));
        $this->assertTrue($b->notEquals($a));

        $this->assertEquals(-1, $a->compare($b));
        $this->assertEquals(1, $b->compare($a));
        $this->assertEquals(0, $a->compare($a));
        $this->assertEquals(0, $b->compare($b));

        $this->assertTrue($a->lessThan($b));
        $this->assertFalse($a->lessThan($a));
        $this->assertFalse($b->lessThan($a));
        $this->assertFalse($b->lessThan($b));

        $this->assertTrue($a->lessThanOrEqual($a));
        $this->assertTrue($a->lessThanOrEqual($b));
        $this->assertFalse($b->lessThanOrEqual($a));
        $this->assertTrue($b->lessThanOrEqual($b));

        $this->assertFalse($a->greaterThan($a));
        $this->assertFalse($a->greaterThan($b));
        $this->assertTrue($b->greaterThan($a));
        $this->assertFalse($b->greaterThan($b));

        $this->assertTrue($a->greaterThanOrEqual($a));
        $this->assertFalse($a->greaterThanOrEqual($b));
        $this->assertTrue($b->greaterThanOrEqual($a));
        $this->assertTrue($b->greaterThanOrEqual($b));
    }

    public function testIsPositive(): void
    {
        $this->assertTrue(Decimal::create(10)->isPositive());
        $this->assertTrue(Decimal::create(1)->isPositive());
        $this->assertTrue(Decimal::create(0.1)->isPositive());

        $this->assertFalse(Decimal::create(-0.1)->isPositive());
        $this->assertFalse(Decimal::create(-1)->isPositive());
        $this->assertFalse(Decimal::create(-10)->isPositive());

        $this->assertFalse(Decimal::create(0)->isPositive());
        $this->assertFalse(Decimal::create(0.00001, 4)->isPositive());
    }

    public function testIsNegative(): void
    {
        $this->assertFalse(Decimal::create(10)->isNegative());
        $this->assertFalse(Decimal::create(1)->isNegative());
        $this->assertFalse(Decimal::create(0.1)->isNegative());

        $this->assertTrue(Decimal::create(-0.1)->isNegative());
        $this->assertTrue(Decimal::create(-1)->isNegative());
        $this->assertTrue(Decimal::create(-10)->isNegative());

        $this->assertFalse(Decimal::create(0)->isNegative());
        $this->assertFalse(Decimal::create(0.00001, 4)->isNegative());
    }

    public function testIsZero(): void
    {
        $this->assertTrue(Decimal::create(0)->isZero());
        $this->assertTrue(Decimal::create(0.0)->isZero());
        $this->assertTrue(Decimal::create('0')->isZero());
        $this->assertTrue(Decimal::create('0.00')->isZero());
        $this->assertTrue(Decimal::fromRawValue(0)->isZero());
        $this->assertTrue(Decimal::create(0.00001, 4)->isZero());

        $this->assertFalse(Decimal::create(10)->isZero());
        $this->assertFalse(Decimal::create(0.1)->isZero());
        $this->assertFalse(Decimal::create(-0.1)->isZero());
        $this->assertFalse(Decimal::create(-10)->isZero());
    }

    /**
     * @dataProvider immutableOperationProvider
     *
     * @param int $input
     * @param int $expected
     * @param string $operation
     * @param array ...$arguments
     */
    public function testImmutableOperations(int $input, int $expected, string $operation, ...$arguments): void
    {
        $value = Decimal::create($input);

        /** @var Decimal $result */
        $result = call_user_func_array([$value, $operation], $arguments);

        $this->assertNotSame(
            $value,
            $result,
            sprintf(
                'Decimal::create(%d)->%s(%s) returns a new instance',
                $input,
                $operation,
                implode(', ', $arguments)
            )
        );

        $this->assertSame(
            $expected,
            $result->asNumeric(),
            sprintf(
                'Decimal::create(%d)->%s(%s)->asNumeric() returns %d',
                $input,
                $operation,
                implode(', ', $arguments),
                $expected
            )
        );
    }

    public function testAbs(): void
    {
        $a = Decimal::create(5);
        $b = Decimal::create(-5);

        $this->assertSame($a, $a->abs());
        $this->assertFalse($a->equals($b));
        $this->assertTrue($a->equals($b->abs()));
        $this->assertEquals(5, $b->abs()->asNumeric());
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd(float|int|string|Decimal $a, float|int|string|Decimal $b, float|int $expected): void
    {
        $valA = Decimal::create($a);

        $this->assertEquals($expected, $valA->add($b)->asNumeric());
    }

    /**
     * @dataProvider subDataProvider
     */
    public function testSub(float|int|string|Decimal $a, float|int|string|Decimal $b, float|int $expected): void
    {
        $valA = Decimal::create($a);

        $this->assertEquals($expected, $valA->sub($b)->asNumeric());
    }

    /**
     * @dataProvider mulDataProvider
     */
    public function testMul(float|int|string|Decimal $a, float|int|string|Decimal $b, float|int $expected): void
    {
        $valA = Decimal::create($a);

        $this->assertEquals($expected, $valA->mul($b)->asNumeric());
    }

    /**
     * @dataProvider divDataProvider
     */
    public function testDiv(float|int|string|Decimal $a, float|int|string|Decimal $b, float|int $expected): void
    {
        $val = Decimal::create($a);

        $this->assertEquals($expected, $val->div($b)->asNumeric());
    }

    /**
     * @dataProvider zeroDataProvider
     */
    public function testExceptionOnDivisionByZero(float|int|string|Decimal $val): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $valA = Decimal::fromRawValue(159900, 4);
        $valA->div(Decimal::create($val));
    }

    public function testDivisionByZeroEpsilon(): void
    {
        // test division by zero error is thrown when difference from 0 is smaller than epsilon (depending on scale)
        $val = Decimal::create('10', 4);
        $val->div(0.1);
        $val->div(0.01);
        $val->div(0.001);
        $val->div(0.0001);

        $this->expectException(\DivisionByZeroError::class);

        $val->div(0.00001);
    }

    public function testAdditiveInverse(): void
    {
        $this->assertSame('-15.5000', Decimal::create('15.50')->toAdditiveInverse()->asString());
        $this->assertSame('15.5000', Decimal::create('-15.50')->toAdditiveInverse()->asString());
        $this->assertSame(0, Decimal::create(0)->toAdditiveInverse()->asNumeric());
    }

    public function testToPercentage(): void
    {
        $this->assertEquals(80, Decimal::create(100)->toPercentage(80)->asNumeric());
        $this->assertEquals(25, Decimal::create(100)->toPercentage(25)->asNumeric());
        $this->assertEquals(25, Decimal::create(50)->toPercentage(50)->asNumeric());
        $this->assertEquals(35, Decimal::create(100)->toPercentage(35)->asNumeric());
        $this->assertEquals(200, Decimal::create(100)->toPercentage(200)->asNumeric());
    }

    public function testDiscount(): void
    {
        $this->assertEquals(85, Decimal::create(100)->discount(15)->asNumeric());
        $this->assertEquals(50, Decimal::create(100)->discount(50)->asNumeric());
        $this->assertEquals(70, Decimal::create(100)->discount(30)->asNumeric());
    }

    public function testPercentageOf(): void
    {
        $origPrice = Decimal::create('129.99');
        $discountedPrice = Decimal::create('88.00');

        $this->assertEquals(68, round($discountedPrice->percentageOf($origPrice), 0));
        $this->assertEquals(148, round($origPrice->percentageOf($discountedPrice), 0));

        $a = Decimal::create(100);
        $b = Decimal::create(50);

        $this->assertEquals(100, $a->percentageOf($a));
        $this->assertEquals(100, $b->percentageOf($b));
        $this->assertEquals(200, $a->percentageOf($b));
        $this->assertEquals(50, $b->percentageOf($a));
    }

    public function testDiscountPercentageOf(): void
    {
        $origPrice = Decimal::create('129.99');
        $discountedPrice = Decimal::create('88.00');

        $this->assertEquals(32, round($discountedPrice->discountPercentageOf($origPrice), 0));

        $a = Decimal::create(100);
        $b = Decimal::create(50);
        $c = Decimal::create(30);

        $this->assertEquals(0, $a->discountPercentageOf($a));
        $this->assertEquals(0, $b->discountPercentageOf($b));
        $this->assertEquals(-100, $a->discountPercentageOf($b));
        $this->assertEquals(50, $b->discountPercentageOf($a));
        $this->assertEquals(70, $c->discountPercentageOf($a));
        $this->assertEquals(40, $c->discountPercentageOf($b));
    }

    // TODO test overflow/underflow is checked on every possible operation
    public function testIntegerOverflow(): void
    {
        $val = Decimal::fromRawValue(1);

        // there's a threshold of 1 from the boundary, so the greatest usable int is PHP_INT_MAX - 1
        $other = Decimal::fromRawValue(PHP_INT_MAX - 2);

        $maxInt = $val->add($other);

        $this->expectException(\OverflowException::class);

        $maxInt->add(Decimal::fromRawValue(1));
    }

    public function testIntegerUnderflow(): void
    {
        $val = Decimal::fromRawValue(-1);

        // there's a threshold of 1 from the boundary, so the smallest usable int is -1 * PHP_INT_MAX + 1
        $other = Decimal::fromRawValue(~PHP_INT_MAX + 2);

        $minInt = $val->add($other);

        $this->expectException(\UnderflowException::class);

        $minInt->sub(Decimal::fromRawValue(1));
    }

    public function createDataProvider(): array
    {
        return [
            [15.99, '15.9900'],
            ['15.99', '15.9900'],
            [Decimal::fromRawValue(159900, 4), '15.9900'],
            ['1.23', '1.2300'],
            ['-1.23', '-1.2300'],
            ['-0.5', '-0.5000'],
            ['1.9999', '1.9999'],
            ['1.999999', '2.0000'],
            ['1.999900', '1.9999'],
            ['100', '100.0000'],
            ['-100', '-100.0000'],
            [100, '100.0000'],
            [-100, '-100.0000'],
            [100.00, '100.0000'],
            [-100.00, '-100.0000'],
        ];
    }

    public function invalidValueCreateProvider(): array
    {
        return [
            [new \DateTime()],
            [true],
            [false],
        ];
    }

    public function createZeroScaleDataProvider(): array
    {
        return [
            [15.99, '16.0000'],
            ['15.99', '16.0000'],
            ['15,99', '16.0000'],
            [Decimal::fromRawValue(159900, 4), '16.0000'],
        ];
    }

    public function immutableOperationProvider(): array
    {
        // operations which are expected to return a new instance
        // [input, expected, operation, ...arguments]
        return [
            [100, 100, 'withScale', 2],
            [-10, 10, 'abs'],
            [100, 110, 'add', 10],
            [100, 90, 'sub', 10],
            [100, 300, 'mul', 3],
            [100, 50, 'div', 2],
            [100, -100, 'toAdditiveInverse'],
            [100, 50, 'toPercentage', 50],
            [100, 85, 'discount', 15],
        ];
    }

    public function addDataProvider(): array
    {
        return $this->buildPriceOperationInputPairs(30);
    }

    public function subDataProvider(): array
    {
        return $this->buildPriceOperationInputPairs(1);
    }

    public function mulDataProvider(): array
    {
        return $this->buildScalarOperationInputPairs(30);
    }

    public function divDataProvider(): array
    {
        return $this->buildScalarOperationInputPairs(7.50);
    }

    public function zeroDataProvider(): array
    {
        return [
            [0],
            [0.0],
            ['0'],
            [Decimal::create(0, 4)],
        ];
    }

    /**
     * Pairs for price operations (add, sub)
     */
    private function buildPriceOperationInputPairs(int $expected): array
    {
        $input = [
            [
                15.50,
                14.50,
            ],
            [
                '15.50',
                '14.50',
            ],
            [
                Decimal::fromRawValue(155000),
                Decimal::fromRawValue(145000),
            ],
        ];

        return $this->mixPairs($input, $expected);
    }

    /**
     * Pairs for scalar operations (mul, div)
     */
    private function buildScalarOperationInputPairs(float|int $expected): array
    {
        $input = [
            [
                15,
                2,
            ],
            [
                15.00,
                2.00,
            ],
            [
                Decimal::fromRawValue(150000),
                Decimal::fromRawValue(20000),
            ],
        ];

        return $this->mixPairs($input, $expected);
    }

    /**
     * Mixes pairs (creates one pair per possible combination)
     */
    private function mixPairs(array $input, float|int $expected): array
    {
        $data = [];
        $count = count($input);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $data[] = [
                    $input[$i][0],
                    $input[$j][1],
                    $expected,
                ];
            }
        }

        return $data;
    }
}
