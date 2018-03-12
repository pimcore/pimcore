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

namespace Pimcore\Tests\Unit\FeatureToggles\Initializers;

use Pimcore\FeatureToggles\FeatureContext;
use Pimcore\FeatureToggles\FeatureState;
use Pimcore\FeatureToggles\FeatureStateInterface;
use Pimcore\FeatureToggles\Initializers\ClosureInitializer;
use Pimcore\Tests\Test\TestCase;

/**
 * @covers \Pimcore\FeatureToggles\Initializers\ClosureInitializer
 */
class ClosureInitializerTest extends TestCase
{
    public function testTypeIsSet()
    {
        $initializer = new ClosureInitializer('foo', function () {
        });

        $this->assertEquals('foo', $initializer->getType());
    }

    public function testClosureIsCalled()
    {
        // TODO find a more concise way of testing if a closure was called
        $state         = new \stdClass();
        $state->called = false;

        $initializer = new ClosureInitializer('foo', function () use ($state) {
            $state->called = true;
        });

        $this->assertFalse($state->called);

        $initializer->getState(new FeatureContext());

        $this->assertTrue($state->called);
    }

    public function testContextIsPassed()
    {
        $state         = new \stdClass();
        $state->called = false;

        $context = new FeatureContext();

        $initializer = new ClosureInitializer('foo', function (FeatureContext $givenContext) use ($state, $context) {
            $state->called = true;

            $this->assertSame($context, $givenContext);
        });

        $this->assertFalse($state->called);

        $initializer->getState($context);

        $this->assertTrue($state->called);
    }

    public function testPreviousStateCanBeNull()
    {
        $state         = new \stdClass();
        $state->called = false;

        $initializer = new ClosureInitializer('foo', function (FeatureContext $givenContext, FeatureStateInterface $givenPreviousState = null) use ($state) {
            $state->called = true;

            $this->assertNull($givenPreviousState);
        });

        $this->assertFalse($state->called);

        $initializer->getState(new FeatureContext());

        $this->assertTrue($state->called);
    }

    public function testPreviousStateIsPassed()
    {
        $state         = new \stdClass();
        $state->called = false;

        $previousState = new FeatureState('foo', 0);

        $initializer = new ClosureInitializer('foo', function (FeatureContext $givenContext, FeatureStateInterface $givenPreviousState = null) use ($state, $previousState) {
            $state->called = true;

            $this->assertSame($previousState, $givenPreviousState);
        });

        $this->assertFalse($state->called);

        $initializer->getState(new FeatureContext(), $previousState);

        $this->assertTrue($state->called);
    }
}
