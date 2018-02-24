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

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\FeatureToggles\FeatureContext;
use Pimcore\FeatureToggles\FeatureManager;
use Pimcore\FeatureToggles\FeatureState;
use Pimcore\FeatureToggles\FeatureStateInitializerInterface;
use Pimcore\FeatureToggles\FeatureStateInterface;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\ValidFeature;
use Pimcore\Tests\Unit\FeatureToggles\Fixtures\ValidFeatureMaximumFlags;

require_once __DIR__ . '/Fixtures/Features.php';

/**
 * @covers \Pimcore\FeatureToggles\FeatureManager
 */
class FeatureManagerTest extends TestCase
{
    /**
     * @var \ReflectionProperty
     */
    private $stateProperty;

    /**
     * @var FeatureContext
     */
    private $context;

    /**
     * @var FeatureStateInitializerInterface[]|MockObject[]
     */
    private $initializers = [];

    protected function setUp()
    {
        parent::setUp();

        $this->stateProperty = (new \ReflectionClass(FeatureManager::class))->getProperty('states');
        $this->stateProperty->setAccessible(true);

        $this->context = new FeatureContext();

        $this->initializers = [
            $this->createInitializerMock(
                ValidFeature::getType(),
                FeatureState::fromFeature(ValidFeature::FLAG_1())
            ),
            $this->createInitializerMock(
                ValidFeature::getType(),
                FeatureState::fromFeature(ValidFeature::FLAG_2())
            ),
            $this->createInitializerMock(
                ValidFeatureMaximumFlags::getType(),
                FeatureState::fromFeature(ValidFeature::NONE())
            ),
            $this->createInitializerMock(
                ValidFeatureMaximumFlags::getType(),
                FeatureState::fromFeature(ValidFeature::ALL())
            ),
        ];
    }

    public function testSetGetHasState()
    {
        $manager = new FeatureManager($this->context);

        $this->assertFalse($manager->hasState(ValidFeature::getType()));
        $this->assertNull($manager->getState(ValidFeature::getType()));

        $state = FeatureState::fromFeature(ValidFeature::FLAG_1());

        $manager->setState($state);

        $this->assertTrue($manager->hasState(ValidFeature::getType()));
        $this->assertEquals($state, $manager->getState(ValidFeature::getType()));

        // test overwrite state
        $state2 = FeatureState::fromFeature(ValidFeature::FLAG_2());

        $manager->setState($state2);

        $this->assertTrue($manager->hasState(ValidFeature::getType()));
        $this->assertEquals($state2, $manager->getState(ValidFeature::getType()));
    }

    public function testClearState()
    {
        $manager = new FeatureManager($this->context);

        $this->assertEmpty($this->stateProperty->getValue($manager));
        $this->assertStateProperty(
            $manager,
            0,
            [],
            []
        );

        $state1 = FeatureState::fromFeature(ValidFeature::FLAG_1());
        $state2 = FeatureState::fromFeature(ValidFeatureMaximumFlags::FLAG_2());

        $manager->setState($state1);
        $manager->setState($state2);

        $this->assertStateProperty(
            $manager,
            2,
            [ValidFeature::getType(), ValidFeatureMaximumFlags::getType()],
            [$state1, $state2]
        );

        $manager->clear(ValidFeature::getType());

        $this->assertStateProperty(
            $manager,
            1,
            [ValidFeatureMaximumFlags::getType()],
            [$state2]
        );

        // clear again - no change
        $manager->clear(ValidFeature::getType());

        $this->assertStateProperty(
            $manager,
            1,
            [ValidFeatureMaximumFlags::getType()],
            [$state2]
        );

        $manager->clear(ValidFeatureMaximumFlags::getType());

        $this->assertEmpty($this->stateProperty->getValue($manager));
        $this->assertStateProperty(
            $manager,
            0,
            [],
            []
        );
    }

    public function testClearAllStates()
    {
        $manager = new FeatureManager($this->context);

        $this->assertEmpty($this->stateProperty->getValue($manager));
        $this->assertStateProperty(
            $manager,
            0,
            [],
            []
        );

        $state1 = FeatureState::fromFeature(ValidFeature::FLAG_1());
        $state2 = FeatureState::fromFeature(ValidFeatureMaximumFlags::FLAG_2());

        $manager->setState($state1);
        $manager->setState($state2);

        $this->assertStateProperty(
            $manager,
            2,
            [ValidFeature::getType(), ValidFeatureMaximumFlags::getType()],
            [$state1, $state2]
        );

        $manager->clear();

        $this->assertEmpty($this->stateProperty->getValue($manager));
        $this->assertStateProperty(
            $manager,
            0,
            [],
            []
        );
    }

    private function assertStateProperty(FeatureManager $manager, int $count, array $keys, array $values)
    {
        $propertyValue = $this->stateProperty->getValue($manager);

        $this->assertCount($count, $propertyValue);
        $this->assertEquals($keys, array_keys($propertyValue));

        $values         = array_values($values);
        $propertyValues = array_values($propertyValue);

        for ($i = 0; $i < count($values); $i++) {
            $this->assertSame($values[$i], $propertyValues[$i]);
        }
    }

    /**
     * @group only
     */
    public function testIsEnabledReturnsFalseWhenNoStateIsSet()
    {
        $manager = new FeatureManager($this->context);

        $this->assertFalse($manager->isEnabled(ValidFeature::FLAG_1()));
    }

    /**
     * @group only
     */
    public function testIsEnabled()
    {
        $feature1 = ValidFeature::FLAG_1();
        $feature2 = ValidFeature::FLAG_2();

        /** @var FeatureStateInterface|MockObject $state */
        $state = $this->createMock(FeatureStateInterface::class);
        $state
            ->method('getType')
            ->willReturn(ValidFeature::getType());

        $state
            ->expects($this->exactly(2))
            ->method('isEnabled')
            ->withConsecutive(
                [
                    $this->equalTo($feature1),
                    $this->equalTo($this->context)
                ],
                [
                    $this->equalTo($feature2),
                    $this->equalTo($this->context)
                ]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $manager = new FeatureManager($this->context);
        $manager->setState($state);

        $this->assertTrue($manager->isEnabled($feature1));
        $this->assertFalse($manager->isEnabled($feature2));
    }

    public function testContextIsSet()
    {
        $manager = new FeatureManager($this->context);

        $this->assertSame($this->context, $manager->getContext());
    }

    public function testDefaultContextIsInitialized()
    {
        $manager = new FeatureManager();

        $this->assertNotNull($manager->getContext());
        $this->assertInstanceOf(FeatureContext::class, $manager->getContext());
    }

    public function testContextCanBeOverwritten()
    {
        $manager = new FeatureManager();

        $this->assertNotNull($manager->getContext());
        $this->assertNotSame($this->context, $manager->getContext());

        $manager->setContext($this->context);

        $this->assertSame($this->context, $manager->getContext());
    }

    public function testInitializersAreSet()
    {
        $manager = new FeatureManager($this->context, $this->initializers);

        $this->testDefaultInitializers($manager);
    }

    public function testInitializersAreAdded()
    {
        $manager = new FeatureManager($this->context);

        $this->assertEmpty($manager->getInitializers());
        $this->assertEmpty($manager->getInitializers(ValidFeature::getType()));
        $this->assertEmpty($manager->getInitializers(ValidFeatureMaximumFlags::getType()));

        foreach ($this->initializers as $initializer) {
            $manager->addInitializer($initializer);
        }

        $this->testDefaultInitializers($manager);
    }

    public function testInitializersAreOverwritten()
    {
        $initializers = [
            $this->createInitializerMock(
                ValidFeature::getType(),
                FeatureState::fromFeature(ValidFeature::FLAG_3())
            ),
            $this->createInitializerMock(
                ValidFeatureMaximumFlags::getType(),
                FeatureState::fromFeature(ValidFeatureMaximumFlags::FLAG_4())
            ),
        ];

        $manager = new FeatureManager($this->context, $this->initializers);
        $manager->setInitializers($initializers);

        $this->assertCount(2, $manager->getInitializers());
        $this->assertCount(1, $manager->getInitializers(ValidFeature::getType()));
        $this->assertCount(1, $manager->getInitializers(ValidFeatureMaximumFlags::getType()));

        $this->assertSame($initializers, $manager->getInitializers());
    }

    public function testInitializerIsCalledWhenNoStateIsSet()
    {
        $state = FeatureState::fromFeature(ValidFeature::FLAG_3());

        $initializer = $this->createInitializerMock(ValidFeature::getType(), $state);
        $initializer
            ->expects($this->once())
            ->method('getType');

        $initializer
            ->expects($this->once())
            ->method('getState');

        $manager = new FeatureManager($this->context, $this->initializers);
        $manager->addInitializer($initializer);

        $this->assertSame($state, $manager->getState(ValidFeature::getType()));
    }

    public function testInitializerIsNotCalledWhenAStateIsSet()
    {
        $state  = FeatureState::fromFeature(ValidFeature::FLAG_3());
        $state2 = FeatureState::fromFeature(ValidFeature::FLAG_2());

        $initializer = $this->createInitializerMock(ValidFeature::getType(), $state);
        $initializer
            ->expects($this->never())
            ->method('getType');

        $initializer
            ->expects($this->never())
            ->method('getState');

        $manager = new FeatureManager($this->context, $this->initializers);
        $manager->addInitializer($initializer);
        $manager->setState($state2);

        $this->assertSame($state2, $manager->getState(ValidFeature::getType()));
    }

    public function testInitializerIsCalledAfterStateWasCleared()
    {
        $state  = FeatureState::fromFeature(ValidFeature::FLAG_3());
        $state2 = FeatureState::fromFeature(ValidFeature::FLAG_2());

        $initializer = $this->createInitializerMock(ValidFeature::getType(), $state);

        $manager = new FeatureManager($this->context, $this->initializers);
        $manager->addInitializer($initializer);
        $manager->setState($state2);

        $this->assertSame($state2, $manager->getState(ValidFeature::getType()));

        $manager->clear();

        $initializer
            ->expects($this->once())
            ->method('getType');

        $initializer
            ->expects($this->once())
            ->method('getState');

        $this->assertSame($state, $manager->getState(ValidFeature::getType()));
    }

    private function testDefaultInitializers(FeatureManager $manager)
    {
        $this->assertCount(4, $manager->getInitializers());
        $this->assertCount(2, $manager->getInitializers(ValidFeature::getType()));
        $this->assertCount(2, $manager->getInitializers(ValidFeatureMaximumFlags::getType()));

        $byType = [
            ValidFeature::getType()             => $manager->getInitializers(ValidFeature::getType()),
            ValidFeatureMaximumFlags::getType() => $manager->getInitializers(ValidFeatureMaximumFlags::getType()),
        ];

        $this->assertSame($this->initializers[0], $byType[ValidFeature::getType()][0]);
        $this->assertSame($this->initializers[1], $byType[ValidFeature::getType()][1]);
        $this->assertSame($this->initializers[2], $byType[ValidFeatureMaximumFlags::getType()][0]);
        $this->assertSame($this->initializers[3], $byType[ValidFeatureMaximumFlags::getType()][1]);
    }

    /**
     * @param string $type
     * @param FeatureStateInterface $state
     *
     * @return FeatureStateInitializerInterface|MockObject
     */
    private function createInitializerMock(string $type, FeatureStateInterface $state): FeatureStateInitializerInterface
    {
        /** @var MockObject $mock */
        $mock = $this->createMock(FeatureStateInitializerInterface::class);
        $mock
            ->method('getType')
            ->will($this->returnValue($type));

        $mock
            ->method('getState')
            ->will($this->returnValue($state));

        return $mock;
    }
}
