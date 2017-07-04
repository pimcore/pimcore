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
    public function testConstruct()
    {
        $value = new PriceAmount(100000, 4);

        $this->assertEquals(100000, $value->asInteger());
        $this->assertEquals(10, $value->asFloat());
    }

    public function testRepresentations()
    {
        $value = PriceAmount::create(10.0, 4);

        $this->assertEquals(100000, $value->asInteger());
        $this->assertEquals(10.0, $value->asFloat());
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
        new PriceAmount(10000, -1);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testZeroScale($input)
    {
        // non-integer amounts will be rounded to the next integer
        $expectedInt   = 16;
        $expectedFloat = 16.0;

        // if integer, the input value will be set directly to the given
        // amount without any conversions
        if (is_int($input)) {
            $expectedInt   = $input;
            $expectedFloat = (float)$input;
        }

        $val = PriceAmount::create($input, 0);

        $this->assertEquals($expectedInt, $val->asInteger());
        $this->assertEquals($expectedFloat, $val->asFloat());
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate($input)
    {
        $value = PriceAmount::create($input);

        $this->assertEquals(159900, $value->asInteger());
        $this->assertEquals(15.99, $value->asFloat());
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

    public function testFromInteger()
    {
        $simpleValue = PriceAmount::fromInteger(100000, 4);

        $this->assertEquals(100000, $simpleValue->asInteger());
        $this->assertEquals(10, $simpleValue->asFloat());

        $decimalValue = PriceAmount::fromInteger(159900, 4);

        $this->assertEquals(159900, $decimalValue->asInteger());
        $this->assertEquals(15.99, $decimalValue->asFloat());
    }

    public function testFromNumeric()
    {
        $simpleValue = PriceAmount::fromNumeric(10, 4);

        $this->assertEquals(100000, $simpleValue->asInteger());
        $this->assertEquals(10, $simpleValue->asFloat());

        $decimalValue = PriceAmount::fromNumeric(15.99, 4);

        $this->assertEquals(159900, $decimalValue->asInteger());
        $this->assertEquals(15.99, $decimalValue->asFloat());
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
        $value        = PriceAmount::fromInteger(100000, 4);
        $createdValue = PriceAmount::fromPriceAmount($value, 4);

        $this->assertEquals($value, $createdValue);
    }

    public function testFromPriceAmountWithDifferentScale()
    {
        $value        = PriceAmount::fromInteger(100000, 4);
        $createdValue = PriceAmount::fromPriceAmount($value, 8);

        $this->assertEquals($value->asFloat(), $createdValue->asFloat());
    }

    public function testWithScale()
    {
        $val = PriceAmount::create('10', 4);

        $this->assertEquals(100000, $val->asInteger());
        $this->assertEquals(10, $val->asFloat());

        $val = $val->withScale(6);

        $this->assertEquals(10000000, $val->asInteger());
        $this->assertEquals(10, $val->asFloat());

        $val = $val->withScale(2);

        $this->assertEquals(1000, $val->asInteger());
        $this->assertEquals(10, $val->asFloat());

        $val = $val->withScale(4);

        $this->assertEquals(100000, $val->asInteger());
        $this->assertEquals(10, $val->asFloat());
    }

    public function testWithScaleLosesPrecision()
    {
        $val = PriceAmount::create('15.99', 4);

        $this->assertEquals(159900, $val->asInteger());
        $this->assertEquals(15.99, $val->asFloat());

        $val = $val->withScale(6);

        $this->assertEquals(15990000, $val->asInteger());
        $this->assertEquals(15.99, $val->asFloat());

        $val = $val->withScale(2);

        $this->assertEquals(1599, $val->asInteger());
        $this->assertEquals(15.99, $val->asFloat());

        $val = $val->withScale(0);

        $this->assertEquals(16, $val->asInteger());
        $this->assertEquals(16, $val->asFloat());

        $val = $val->withScale(4);

        $this->assertEquals(160000, $val->asInteger());
        $this->assertEquals(16, $val->asFloat());
    }

    public function testExceptionOnAddWithMismatchingScale()
    {
        $valA = PriceAmount::create('10', 4);
        $valB = PriceAmount::create('20', 8);

        $scaledB = $valB->withScale(4);
        $this->assertEquals($valB->asFloat(), $scaledB->asFloat());

        $this->assertEquals(30, $valA->add($scaledB)->asFloat());

        $this->expectException(\DomainException::class);
        $valA->add($valB);
    }

    public function testExceptionOnSubWithMismatchingScale()
    {
        $valA = PriceAmount::create('10', 4);
        $valB = PriceAmount::create('20', 8);

        $scaledB = $valB->withScale(4);
        $this->assertEquals($valB->asFloat(), $scaledB->asFloat());

        $this->assertEquals(-10, $valA->sub($scaledB)->asFloat());

        $this->expectException(\DomainException::class);
        $valA->sub($valB);
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd($a, $b, $expected)
    {
        $valA = PriceAmount::create($a);
        $valB = PriceAmount::create($b);

        $this->assertEquals($expected, $valA->add($valB)->asFloat());
    }

    /**
     * @dataProvider subDataProvider
     */
    public function testSub($a, $b, $expected)
    {
        $valA = PriceAmount::create($a);
        $valB = PriceAmount::create($b);

        $this->assertEquals($expected, $valA->sub($valB)->asFloat());
    }

    /**
     * @dataProvider mulDataProvider
     */
    public function testMul($a, $b, $expected)
    {
        $valA = PriceAmount::create($a);

        $this->assertEquals($expected, $valA->mul($b)->asFloat());
    }

    /**
     * @dataProvider divDataProvider
     */
    public function testDiv($a, $b, $expected)
    {
        $val = PriceAmount::create($a);

        $this->assertEquals($expected, $val->div($b)->asFloat());
    }

    /**
     * @dataProvider zeroDataProvider
     * @expectedException \DivisionByZeroError
     */
    public function testExceptionOnDivisionByZero($val)
    {
        $valA = PriceAmount::create(159900, 4);
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

    public function createDataProvider(): array
    {
        return [
            [159900],
            [15.99],
            ['15.99'],
            [new PriceAmount(159900, 4)]
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
        return $this->buildScalarOperationInputPairs(31);
    }

    public function divDataProvider(): array
    {
        return $this->buildScalarOperationInputPairs(7.75);
    }

    public function zeroDataProvider(): array
    {
        return [
            [0],
            [0.0],
            ['0'],
            [new PriceAmount(0, 4)]
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
                155000,
                145000
            ],
            [
                15.50,
                14.50
            ],
            [
                '15.50',
                '14.50'
            ],
            [
                PriceAmount::create(155000),
                PriceAmount::create(145000)
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
                155000,
                2
            ],
            [
                15.50,
                2.00
            ],
            [
                '15.50',
                '2'
            ],
            [
                PriceAmount::create(155000),
                PriceAmount::create(20000),
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
