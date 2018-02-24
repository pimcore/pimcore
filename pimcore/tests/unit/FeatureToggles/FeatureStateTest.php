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

use Pimcore\FeatureToggles\FeatureContext;
use Pimcore\FeatureToggles\FeatureState;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\ValidFeature;

require_once __DIR__ . '/Fixtures/Features.php';

/**
 * @covers \Pimcore\FeatureToggles\FeatureState
 */
class FeatureStateTest extends TestCase
{
    /**
     * @var FeatureContext
     */
    private $context;

    protected function setUp()
    {
        parent::setUp();

        $this->context = new FeatureContext();
    }

    public function testTypeAndValue()
    {
        $state = new FeatureState('foo', 2);

        $this->assertEquals('foo', $state->getType());
        $this->assertEquals(2, $state->getValue());
    }

    public function testInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('State must be >= 0');

        $state = new FeatureState('foo', -1);
    }

    public function testFromFeature()
    {
        $state = FeatureState::fromFeature(ValidFeature::FLAG_1());

        $this->assertEquals(ValidFeature::getType(), $state->getType());
        $this->assertEquals(ValidFeature::FLAG_1, $state->getValue());
    }

    public function testNoMatchIfTypeDoesntMatch()
    {
        $state = new FeatureState('foo', ValidFeature::FLAG_1);

        $this->assertFalse($state->isEnabled(ValidFeature::NONE(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_0(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_1(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_2(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_3(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::ALL(), $this->context));
    }

    public function testNoneIsCorrectlyHandled()
    {
        $state = FeatureState::fromFeature(ValidFeature::NONE());

        $this->assertTrue($state->isEnabled(ValidFeature::NONE(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_0(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_1(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_2(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_3(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::ALL(), $this->context));
    }

    public function testSingleFlag()
    {
        $state = new FeatureState(ValidFeature::getType(), ValidFeature::FLAG_2);

        $this->assertFalse($state->isEnabled(ValidFeature::NONE(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_0(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_1(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_2(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_3(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::ALL(), $this->context));
    }

    public function testMultipleFlags()
    {
        $state = new FeatureState(ValidFeature::getType(), ValidFeature::FLAG_2 | ValidFeature::FLAG_3);

        $this->assertFalse($state->isEnabled(ValidFeature::NONE(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_0(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::FLAG_1(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_2(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_3(), $this->context));
        $this->assertFalse($state->isEnabled(ValidFeature::ALL(), $this->context));
    }

    public function testAllFlags()
    {
        $state = new FeatureState(ValidFeature::getType(), ValidFeature::FLAG_0 | ValidFeature::FLAG_1 | ValidFeature::FLAG_2 | ValidFeature::FLAG_3);

        $this->assertFalse($state->isEnabled(ValidFeature::NONE(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_0(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_1(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_2(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_3(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::ALL(), $this->context));
    }

    public function testAllFlag()
    {
        $state = FeatureState::fromFeature(ValidFeature::ALL());

        $this->assertFalse($state->isEnabled(ValidFeature::NONE(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_0(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_1(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_2(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::FLAG_3(), $this->context));
        $this->assertTrue($state->isEnabled(ValidFeature::ALL(), $this->context));
    }
}
