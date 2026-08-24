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

namespace Pimcore\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Pimcore\Helper\ParameterBagHelper;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Unit tests for ParameterBagHelper
 *
 * @internal
 */
class ParameterBagHelperTest extends TestCase
{
    // ========================================
    // Tests for getInt()
    // ========================================

    public function testGetIntWithValidIntegerString(): void
    {
        $bag = new ParameterBag(['id' => '123']);
        $result = ParameterBagHelper::getInt($bag, 'id');

        $this->assertSame(123, $result);
    }

    public function testGetIntWithValidInteger(): void
    {
        $bag = new ParameterBag(['id' => 456]);
        $result = ParameterBagHelper::getInt($bag, 'id');

        $this->assertSame(456, $result);
    }

    public function testGetIntWithZero(): void
    {
        $bag = new ParameterBag(['id' => 0]);
        $result = ParameterBagHelper::getInt($bag, 'id');

        $this->assertSame(0, $result);
    }

    public function testGetIntWithNegativeNumber(): void
    {
        $bag = new ParameterBag(['id' => '-42']);
        $result = ParameterBagHelper::getInt($bag, 'id');

        $this->assertSame(-42, $result);
    }

    public function testGetIntWithMissingKey(): void
    {
        $bag = new ParameterBag([]);
        $result = ParameterBagHelper::getInt($bag, 'id');

        $this->assertSame(0, $result);
    }

    public function testGetIntWithDefaultValue(): void
    {
        $bag = new ParameterBag([]);
        $result = ParameterBagHelper::getInt($bag, 'id', 42);

        $this->assertSame(42, $result);
    }

    public function testGetIntWithInvalidString(): void
    {
        $bag = new ParameterBag(['id' => 'invalid']);
        $result = ParameterBagHelper::getInt($bag, 'id', 99);

        $this->assertSame(99, $result, 'Should return default value for invalid input');
    }

    public function testGetIntWithFloat(): void
    {
        $bag = new ParameterBag(['id' => '123.45']);
        $result = ParameterBagHelper::getInt($bag, 'id', 100);

        $this->assertSame(100, $result, 'Should return default value for float input');
    }

    public function testGetIntWithEmptyString(): void
    {
        $bag = new ParameterBag(['id' => '']);
        $result = ParameterBagHelper::getInt($bag, 'id', 50);

        $this->assertSame(50, $result, 'Should return default value for empty string');
    }

    public function testGetIntWithNull(): void
    {
        $bag = new ParameterBag(['id' => null]);
        $result = ParameterBagHelper::getInt($bag, 'id', 25);

        $this->assertSame(25, $result, 'Should return default value for null');
    }

    public function testGetIntWithArray(): void
    {
        $bag = new ParameterBag(['id' => ['not', 'a', 'number']]);
        $result = ParameterBagHelper::getInt($bag, 'id', 75);

        $this->assertSame(75, $result, 'Should return default value for array input');
    }

    // ========================================
    // Tests for getBool()
    // ========================================

    public function testGetBoolWithTrueString(): void
    {
        $bag = new ParameterBag(['active' => '1']);
        $result = ParameterBagHelper::getBool($bag, 'active');

        $this->assertTrue($result);
    }

    public function testGetBoolWithTrueBoolean(): void
    {
        $bag = new ParameterBag(['active' => true]);
        $result = ParameterBagHelper::getBool($bag, 'active');

        $this->assertTrue($result);
    }

    public function testGetBoolWithFalseString(): void
    {
        $bag = new ParameterBag(['active' => '0']);
        $result = ParameterBagHelper::getBool($bag, 'active');

        $this->assertFalse($result);
    }

    public function testGetBoolWithFalseBoolean(): void
    {
        $bag = new ParameterBag(['active' => false]);
        $result = ParameterBagHelper::getBool($bag, 'active');

        $this->assertFalse($result);
    }

    public function testGetBoolWithTrueWord(): void
    {
        $bag = new ParameterBag(['active' => 'true']);
        $result = ParameterBagHelper::getBool($bag, 'active');

        $this->assertTrue($result);
    }

    public function testGetBoolWithFalseWord(): void
    {
        $bag = new ParameterBag(['active' => 'false']);
        $result = ParameterBagHelper::getBool($bag, 'active');

        $this->assertFalse($result);
    }

    public function testGetBoolWithOnOff(): void
    {
        $bag = new ParameterBag(['enabled' => 'on', 'disabled' => 'off']);

        $this->assertTrue(ParameterBagHelper::getBool($bag, 'enabled'));
        $this->assertFalse(ParameterBagHelper::getBool($bag, 'disabled'));
    }

    public function testGetBoolWithYesNo(): void
    {
        $bag = new ParameterBag(['yes_field' => 'yes', 'no_field' => 'no']);

        $this->assertTrue(ParameterBagHelper::getBool($bag, 'yes_field'));
        $this->assertFalse(ParameterBagHelper::getBool($bag, 'no_field'));
    }

    public function testGetBoolWithMissingKey(): void
    {
        $bag = new ParameterBag([]);
        $result = ParameterBagHelper::getBool($bag, 'active');

        $this->assertFalse($result);
    }

    public function testGetBoolWithDefaultTrue(): void
    {
        $bag = new ParameterBag([]);
        $result = ParameterBagHelper::getBool($bag, 'active', true);

        $this->assertTrue($result);
    }

    public function testGetBoolWithDefaultFalse(): void
    {
        $bag = new ParameterBag([]);
        $result = ParameterBagHelper::getBool($bag, 'active', false);

        $this->assertFalse($result);
    }

    public function testGetBoolWithInvalidString(): void
    {
        $bag = new ParameterBag(['active' => 'invalid']);
        $result = ParameterBagHelper::getBool($bag, 'active', true);

        $this->assertTrue($result, 'Should return default value for invalid input');
    }

    public function testGetBoolWithEmptyString(): void
    {
        $bag = new ParameterBag(['active' => '']);
        $result = ParameterBagHelper::getBool($bag, 'active');

        // Empty string is considered false for FILTER_VALIDATE_BOOLEAN
        $this->assertFalse($result);
    }

    public function testGetBoolWithNull(): void
    {
        $bag = new ParameterBag(['active' => null]);
        $result = ParameterBagHelper::getBool($bag, 'active', true);

        $this->assertTrue($result, 'Should return default value for null');
    }

    public function testGetBoolWithArray(): void
    {
        $bag = new ParameterBag(['active' => ['not', 'a', 'boolean']]);
        $result = ParameterBagHelper::getBool($bag, 'active', true);

        $this->assertTrue($result, 'Should return default value for array input');
    }

    // ========================================
    // Integration/Real-world scenarios
    // ========================================

    public function testRealWorldPaginationScenario(): void
    {
        // Simulating query parameters: ?page=2&limit=25
        $bag = new ParameterBag(['page' => '2', 'limit' => '25']);

        $page = ParameterBagHelper::getInt($bag, 'page', 1);
        $limit = ParameterBagHelper::getInt($bag, 'limit', 50);

        $this->assertSame(2, $page);
        $this->assertSame(25, $limit);
    }

    public function testRealWorldPaginationWithMissingParams(): void
    {
        // Simulating query parameters: (none)
        $bag = new ParameterBag([]);

        $page = ParameterBagHelper::getInt($bag, 'page', 1);
        $limit = ParameterBagHelper::getInt($bag, 'limit', 50);

        $this->assertSame(1, $page, 'Should use default page 1');
        $this->assertSame(50, $limit, 'Should use default limit 50');
    }

    public function testRealWorldIdFromMultipleSources(): void
    {
        // Simulating: id not in attributes, but in query
        $attributes = new ParameterBag([]);
        $query = new ParameterBag(['id' => '123']);

        $id = ParameterBagHelper::getInt($attributes, 'id')
            ?: ParameterBagHelper::getInt($query, 'id');

        $this->assertSame(123, $id);
    }

    public function testRealWorldFormSubmission(): void
    {
        // Simulating form submission with checkboxes
        $bag = new ParameterBag([
            'newsletter' => '1',
            'profiling' => '0',
            'terms_accepted' => 'on',
        ]);

        $newsletter = ParameterBagHelper::getBool($bag, 'newsletter');
        $profiling = ParameterBagHelper::getBool($bag, 'profiling');
        $termsAccepted = ParameterBagHelper::getBool($bag, 'terms_accepted');
        $marketing = ParameterBagHelper::getBool($bag, 'marketing', false);

        $this->assertTrue($newsletter);
        $this->assertFalse($profiling);
        $this->assertTrue($termsAccepted);
        $this->assertFalse($marketing, 'Unchecked checkbox should use default false');
    }

    public function testRealWorldInvalidUserInput(): void
    {
        // Simulating malicious/invalid user input
        $bag = new ParameterBag([
            'id' => 'DROP TABLE users',
            'active' => '<script>alert("xss")</script>',
            'limit' => '999999999999999999999',
        ]);

        $id = ParameterBagHelper::getInt($bag, 'id', 0);
        $active = ParameterBagHelper::getBool($bag, 'active', false);
        $limit = ParameterBagHelper::getInt($bag, 'limit', 50);

        $this->assertSame(0, $id, 'Invalid int should return default');
        $this->assertFalse($active, 'Invalid bool should return default');
        // Note: Very large numbers may overflow, so default is returned
        $this->assertIsInt($limit);
    }
}
