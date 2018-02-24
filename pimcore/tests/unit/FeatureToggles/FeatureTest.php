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

namespace Pimcore\Tests\Unit\FeatureToggles;

use Pimcore\FeatureToggles\Feature;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\InvalidFeatureDuplicateValue;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\InvalidFeatureExceedMaximumFlags;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\InvalidFeatureInvalidValue;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\InvalidFeatureRedefined0;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\InvalidFeatureRedefinedNone;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\ValidFeature;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\ValidFeatureMaximumFlags;

require_once __DIR__ . '/Fixtures/Features.php';

/**
 * @covers \Pimcore\FeatureToggles\Feature
 */
class FeatureTest extends TestCase
{
    public function testFromName()
    {
        $flag = ValidFeature::fromName('FLAG_1');

        $this->assertEquals('FLAG_1', $flag->getKey());
        $this->assertEquals(2, $flag->getValue());
    }

    public function testValidFeature()
    {
        $values = ValidFeature::values();

        $this->assertCount(6, $values);

        $this->assertTrue(array_key_exists('NONE', $values));
        $this->assertTrue(array_key_exists('FLAG_0', $values));
        $this->assertTrue(array_key_exists('FLAG_1', $values));
        $this->assertTrue(array_key_exists('FLAG_2', $values));
        $this->assertTrue(array_key_exists('FLAG_3', $values));
        $this->assertTrue(array_key_exists('ALL', $values));

        $this->assertEquals(0, $values['NONE']->getValue());
        $this->assertEquals(1, $values['FLAG_0']->getValue());
        $this->assertEquals(2, $values['FLAG_1']->getValue());
        $this->assertEquals(4, $values['FLAG_2']->getValue());
        $this->assertEquals(8, $values['FLAG_3']->getValue());
        $this->assertEquals(15, $values['ALL']->getValue());
    }

    public function testMaximumFlags()
    {
        $values = ValidFeatureMaximumFlags::values();

        $this->assertCount(33, $values);
        $this->assertEquals(2147483647, $values['ALL']->getValue());
    }

    /**
     * @dataProvider invalidFeatureProvider
     *
     * @param Feature $featureClass
     * @param string $exceptionMessage
     */
    public function testInvalidFeatures($featureClass, string $exceptionMessage)
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $featureClass::toArray();
    }

    public function invalidFeatureProvider()
    {
        return [
            [
                InvalidFeatureRedefined0::class,
                'The constant INVALID tries to re-define the bit mask 0 which is reserved for the NONE value.',
            ],
            [
                InvalidFeatureRedefinedNone::class,
                'The constant NONE is overwritten with value 4, but NONE needs to be 0.',
            ],
            [
                InvalidFeatureInvalidValue::class,
                'The mask 5 for constant FLAG_3 is not a power of 2.',
            ],
            [
                InvalidFeatureDuplicateValue::class,
                'The bit value 4 for constant FLAG_3 is already defined by FLAG_2. Please use distinct values for every feature.',
            ],
            [
                InvalidFeatureExceedMaximumFlags::class,
                'A feature can have a maximum of 31 flags excluding NONE and ALL.',
            ],
        ];
    }
}
