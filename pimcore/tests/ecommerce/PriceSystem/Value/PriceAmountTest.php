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

namespace Pimcore\Tests\Ecommerce\PriceSystem\Value;

use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value\PriceAmount;
use Pimcore\Tests\Test\TestCase;

class PriceAmountTest extends TestCase
{
    public function testRepresentations()
    {
        $value = PriceAmount::create(10.0, 4);

        $this->assertEquals(100000, $value->asRawValue());
        $this->assertEquals(10.0, $value->asNumeric());
        $this->assertSame('10.0000', $value->asString());
    }

    public function testAsString()
    {
        $value = PriceAmount::create(10.0, 4);

        $this->assertSame('10.0000', (string)$value);
        $this->assertSame('10.0000', $value->asString());
        $this->assertSame('10.00', $value->asString(2));
        $this->assertSame('10', $value->asString(0));

        $otherScale = PriceAmount::create(15.99, 6);

        $this->assertSame('15.990000', (string)$otherScale);
        $this->assertSame('15.990000', $otherScale->asString());
        $this->assertSame('15.99', $otherScale->asString(2));
        $this->assertSame('16.0', $otherScale->asString(1));
        $this->assertSame('16', $otherScale->asString(0));

        $noScale = PriceAmount::create(15.99, 0);

        $this->assertSame('16', (string)$noScale);
        $this->assertSame('16', $noScale->asString());
        $this->assertSame('16.00', $noScale->asString(2));
        $this->assertSame('16.0', $noScale->asString(1));
        $this->assertSame('16', $noScale->asString(0));
    }

    /**
     * @expectedException \DomainException
     */
    public function testInvalidScaleThrowsException()
    {
        PriceAmount::create(10000, -1);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testZeroScale($input)
    {
        $val = PriceAmount::create($input, 0);

        $this->assertEquals(16, $val->asRawValue());
        $this->assertEquals(16.0, $val->asNumeric());
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate($input)
    {
        $value = PriceAmount::create($input);

        $this->assertEquals(159900, $value->asRawValue());
        $this->assertEquals(15.99, $value->asNumeric());
    }

    public function testCreateZero()
    {
        $zero = PriceAmount::zero();

        $this->assertEquals(0, $zero->asRawValue());
        $this->assertEquals(0, $zero->asNumeric());
        $this->assertEquals('0.00', $zero->asString(2));
        $this->assertTrue($zero->equals(PriceAmount::create(0)));
    }

    /**
     * @expectedException \TypeError
     */
    public function testErrorOnInvalidCreateArgument()
    {
        PriceAmount::create(new \DateTime());
    }

    /**
     * @expectedException \DomainException
     */
    public function testInvalidScaleThrowsExceptionOnCreate()
    {
        PriceAmount::create('10.0', -1);
    }

    public function testCreateRounding()
    {
        $this->assertEquals(16, PriceAmount::create('15.50', 0)->asRawValue());
        $this->assertEquals(16, PriceAmount::create('15.50', 0, PHP_ROUND_HALF_UP)->asRawValue());
        $this->assertEquals(15, PriceAmount::create('15.50', 0, PHP_ROUND_HALF_DOWN)->asRawValue());
    }

    public function testFromRawValue()
    {
        $simpleValue = PriceAmount::fromRawValue(100000, 4);

        $this->assertEquals(100000, $simpleValue->asRawValue());
        $this->assertEquals(10, $simpleValue->asNumeric());

        $decimalValue = PriceAmount::fromRawValue(159900, 4);

        $this->assertEquals(159900, $decimalValue->asRawValue());
        $this->assertEquals(15.99, $decimalValue->asNumeric());
    }

    public function testFromNumeric()
    {
        $simpleValue = PriceAmount::fromNumeric(10, 4);

        $this->assertEquals(100000, $simpleValue->asRawValue());
        $this->assertEquals(10, $simpleValue->asNumeric());

        $decimalValue = PriceAmount::fromNumeric(15.99, 4);

        $this->assertEquals(159900, $decimalValue->asRawValue());
        $this->assertEquals(15.99, $decimalValue->asNumeric());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnInvalidFromNumeric()
    {
        PriceAmount::fromNumeric('ABC');
    }

    public function testFromPriceAmount()
    {
        $value        = PriceAmount::fromRawValue(100000, 4);
        $createdValue = PriceAmount::fromPriceAmount($value, 4);

        $this->assertEquals($value, $createdValue);
    }

    public function testFromPriceAmountWithDifferentScale()
    {
        $value        = PriceAmount::fromRawValue(100000, 4);
        $createdValue = PriceAmount::fromPriceAmount($value, 8);

        $this->assertEquals($value->asNumeric(), $createdValue->asNumeric());
    }

    public function testWithScale()
    {
        $val = PriceAmount::create('10', 4);

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

    public function testWithScaleLosesPrecision()
    {
        $val = PriceAmount::create('15.99', 4);

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

    public function testExceptionOnAddWithMismatchingScale()
    {
        $valA = PriceAmount::create('10', 4);
        $valB = PriceAmount::create('20', 8);

        $scaledB = $valB->withScale(4);
        $this->assertEquals($valB->asNumeric(), $scaledB->asNumeric());

        $this->assertEquals(30, $valA->add($scaledB)->asNumeric());

        $this->expectException(\DomainException::class);
        $valA->add($valB);
    }

    public function testExceptionOnSubWithMismatchingScale()
    {
        $valA = PriceAmount::create('10', 4);
        $valB = PriceAmount::create('20', 8);

        $scaledB = $valB->withScale(4);
        $this->assertEquals($valB->asNumeric(), $scaledB->asNumeric());

        $this->assertEquals(-10, $valA->sub($scaledB)->asNumeric());

        $this->expectException(\DomainException::class);
        $valA->sub($valB);
    }

    public function testCompare()
    {
        $a = PriceAmount::create(5);
        $b = PriceAmount::create(10);

        $this->assertTrue($a->equals($a));
        $this->assertTrue($b->equals($b));
        $this->assertTrue($a->equals(PriceAmount::create(5)));
        $this->assertFalse($a->equals(PriceAmount::create(5, 8)));
        $this->assertFalse($a->equals($b));
        $this->assertFalse($b->equals($a));

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

    public function testIsPositive()
    {
        $this->assertTrue(PriceAmount::create(10)->isPositive());
        $this->assertTrue(PriceAmount::create(1)->isPositive());
        $this->assertTrue(PriceAmount::create(0.1)->isPositive());

        $this->assertFalse(PriceAmount::create(-0.1)->isPositive());
        $this->assertFalse(PriceAmount::create(-1)->isPositive());
        $this->assertFalse(PriceAmount::create(-10)->isPositive());

        $this->assertFalse(PriceAmount::create(0)->isPositive());
        $this->assertFalse(PriceAmount::create(0.00001, 4)->isPositive());
    }

    public function testIsNegative()
    {
        $this->assertFalse(PriceAmount::create(10)->isNegative());
        $this->assertFalse(PriceAmount::create(1)->isNegative());
        $this->assertFalse(PriceAmount::create(0.1)->isNegative());

        $this->assertTrue(PriceAmount::create(-0.1)->isNegative());
        $this->assertTrue(PriceAmount::create(-1)->isNegative());
        $this->assertTrue(PriceAmount::create(-10)->isNegative());

        $this->assertFalse(PriceAmount::create(0)->isNegative());
        $this->assertFalse(PriceAmount::create(0.00001, 4)->isNegative());
    }

    public function testIsZero()
    {
        $this->assertTrue(PriceAmount::create(0)->isZero());
        $this->assertTrue(PriceAmount::create(0.0)->isZero());
        $this->assertTrue(PriceAmount::create('0')->isZero());
        $this->assertTrue(PriceAmount::create('0.00')->isZero());
        $this->assertTrue(PriceAmount::fromRawValue(0)->isZero());
        $this->assertTrue(PriceAmount::create(0.00001, 4)->isZero());

        $this->assertFalse(PriceAmount::create(10)->isZero());
        $this->assertFalse(PriceAmount::create(0.1)->isZero());
        $this->assertFalse(PriceAmount::create(-0.1)->isZero());
        $this->assertFalse(PriceAmount::create(-10)->isZero());
    }

    public function testAbs()
    {
        $a = PriceAmount::create(5);
        $b = PriceAmount::create(-5);

        $this->assertFalse($a->equals($b));
        $this->assertTrue($a->equals($b->abs()));
        $this->assertEquals(5, $b->abs()->asNumeric());
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd($a, $b, $expected)
    {
        $valA = PriceAmount::create($a);

        $this->assertEquals($expected, $valA->add($b)->asNumeric());
    }

    /**
     * @dataProvider subDataProvider
     */
    public function testSub($a, $b, $expected)
    {
        $valA = PriceAmount::create($a);

        $this->assertEquals($expected, $valA->sub($b)->asNumeric());
    }

    /**
     * @dataProvider mulDataProvider
     */
    public function testMul($a, $b, $expected)
    {
        $valA = PriceAmount::create($a);

        $this->assertEquals($expected, $valA->mul($b)->asNumeric());
    }

    /**
     * @dataProvider divDataProvider
     */
    public function testDiv($a, $b, $expected)
    {
        $val = PriceAmount::create($a);

        $this->assertEquals($expected, $val->div($b)->asNumeric());
    }

    /**
     * @dataProvider zeroDataProvider
     * @expectedException \DivisionByZeroError
     */
    public function testExceptionOnDivisionByZero($val)
    {
        $valA = PriceAmount::fromRawValue(159900, 4);
        $valA->div(PriceAmount::create($val));
    }

    public function testDivisionByZeroEpsilon()
    {
        // test division by zero error is thrown when difference from 0 is smaller than epsilon (depending on scale)
        $val = PriceAmount::create('10', 4);
        $val->div(0.1);
        $val->div(0.01);
        $val->div(0.001);
        $val->div(0.0001);

        $this->expectException(\DivisionByZeroError::class);

        $val->div(0.00001);
    }

    public function testAdditiveInverse()
    {
        $this->assertSame('-15.50', PriceAmount::create('15.50')->toAdditiveInverse()->asString(2));
        $this->assertSame('15.50', PriceAmount::create('-15.50')->toAdditiveInverse()->asString(2));
        $this->assertSame(0, PriceAmount::create(0)->toAdditiveInverse()->asNumeric());
    }

    public function testToPercentage()
    {
        $this->assertEquals(80, PriceAmount::create(100)->toPercentage(80)->asNumeric());
        $this->assertEquals(35, PriceAmount::create(100)->toPercentage(35)->asNumeric());
        $this->assertEquals(200, PriceAmount::create(100)->toPercentage(200)->asNumeric());
    }

    public function testDiscount()
    {
        $this->assertEquals(85, PriceAmount::create(100)->discount(15)->asNumeric());
        $this->assertEquals(50, PriceAmount::create(100)->discount(50)->asNumeric());
        $this->assertEquals(70, PriceAmount::create(100)->discount(30)->asNumeric());
    }

    public function testPercentageOf()
    {
        $origPrice       = PriceAmount::create('129.99');
        $discountedPrice = PriceAmount::create('88.00');

        $this->assertEquals(68, round($discountedPrice->percentageOf($origPrice), 0));
        $this->assertEquals(148, round($origPrice->percentageOf($discountedPrice), 0));

        $a = PriceAmount::create(100);
        $b = PriceAmount::create(50);

        $this->assertEquals(100, $a->percentageOf($a));
        $this->assertEquals(100, $b->percentageOf($b));
        $this->assertEquals(200, $a->percentageOf($b));
        $this->assertEquals(50, $b->percentageOf($a));
    }

    public function testDiscountPercentageOf()
    {
        $origPrice       = PriceAmount::create('129.99');
        $discountedPrice = PriceAmount::create('88.00');

        $this->assertEquals(32, round($discountedPrice->discountPercentageOf($origPrice), 0));

        $a = PriceAmount::create(100);
        $b = PriceAmount::create(50);
        $c = PriceAmount::create(30);

        $this->assertEquals(0, $a->discountPercentageOf($a));
        $this->assertEquals(0, $b->discountPercentageOf($b));
        $this->assertEquals(-100, $a->discountPercentageOf($b));
        $this->assertEquals(50, $b->discountPercentageOf($a));
        $this->assertEquals(70, $c->discountPercentageOf($a));
        $this->assertEquals(40, $c->discountPercentageOf($b));
    }

    // TODO test overflow/underflow is checked on every possible operation
    public function testIntegerOverflow()
    {
        $val = PriceAmount::fromRawValue(1);

        // there's a threshold of 1 from the boundary, so the greatest usable int is PHP_INT_MAX - 1
        $other = PriceAmount::fromRawValue(PHP_INT_MAX - 2);

        $maxInt = $val->add($other);

        $this->expectException(\OverflowException::class);

        $maxInt->add(PriceAmount::fromRawValue(1));
    }

    public function testIntegerUnderflow()
    {
        $val = PriceAmount::fromRawValue(-1);

        // there's a threshold of 1 from the boundary, so the smallest usable int is -1 * PHP_INT_MAX + 1
        $other = PriceAmount::fromRawValue(~PHP_INT_MAX + 2);

        $minInt = $val->add($other);

        $this->expectException(\UnderflowException::class);

        $minInt->sub(PriceAmount::fromRawValue(1));
    }

    public function createDataProvider(): array
    {
        return [
            [15.99],
            ['15.99'],
            [PriceAmount::fromRawValue(159900, 4)],
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
            [PriceAmount::create(0, 4)]
        ];
    }

    /**
     * Pairs for price operations (add, sub)
     *
     * @param $expected
     *
     * @return array
     */
    private function buildPriceOperationInputPairs($expected): array
    {
        $input = [
            [
                15.50,
                14.50
            ],
            [
                '15.50',
                '14.50'
            ],
            [
                PriceAmount::fromRawValue(155000),
                PriceAmount::fromRawValue(145000)
            ]
        ];

        return $this->mixPairs($input, $expected);
    }

    /**
     * Pairs for scalar operations (mul, div)
     *
     * @param $expected
     *
     * @return array
     */
    private function buildScalarOperationInputPairs($expected): array
    {
        $input = [
            [
                15,
                2
            ],
            [
                15.00,
                2.00
            ],
            [
                '15.00',
                '2'
            ],
            [
                PriceAmount::fromRawValue(150000),
                PriceAmount::fromRawValue(20000),
            ]
        ];

        return $this->mixPairs($input, $expected);
    }

    /**
     * Mixes pairs (creates one pair per possible combination)
     *
     * @param array $input
     * @param $expected
     *
     * @return array
     */
    private function mixPairs(array $input, $expected): array
    {
        $data  = [];
        $count = count($input);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $data[] = [
                    $input[$i][0],
                    $input[$j][1],
                    $expected
                ];
            }
        }

        return $data;
    }
}
