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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore;
use Pimcore\Loader\ImplementationLoader\ClassMapLoader;
use Pimcore\Loader\ImplementationLoader\ImplementationLoader;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionProperty;

/**
 * Object brick data classes can be replaced through pimcore.models.class_overrides.
 *
 * @group model.dataobject.objectbrick
 */
class ObjectbrickClassOverrideTest extends ModelTestCase
{
    private const BRICK = 'Pimcore\\Model\\DataObject\\Objectbrick\\Data\\UnittestBrick';

    private const OVERRIDE = 'Pimcore\\Tests\\Model\\DataObject\\OverriddenUnittestBrick';

    private mixed $originalFactory = null;

    private ?array $originalLoaders = null;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
        // the generated brick class only exists after the class import of the test setup
        if (!class_exists(self::OVERRIDE, false)) {
            eval('namespace Pimcore\\Tests\\Model\\DataObject;
class OverriddenUnittestBrick extends \\' . self::BRICK . ' {
    public ?int $objectIdSeenInConstructor = null;
    public function __construct(\\Pimcore\\Model\\DataObject\\Concrete $object)
    {
        parent::__construct($object);
        $this->objectIdSeenInConstructor = $object->getId();
    }
    public function overriddenValue(): string
    {
        return "overridden:" . $this->getBrickinput();
    }
}');
        }
        $this->originalFactory = Pimcore::getContainer()->get('pimcore.model.factory');
    }

    public function tearDown(): void
    {
        if ($this->originalLoaders !== null) {
            (new ReflectionProperty(ImplementationLoader::class, 'loaders'))->setValue($this->originalFactory, $this->originalLoaders);
            $this->resetLoaderCache($this->originalFactory);
            $this->originalLoaders = null;
        }
        parent::tearDown();
    }

    private function enableOverride(): void
    {
        // the container's factory is already initialised: append a class map loader (the last matching loader wins) and restore it in tearDown()
        $factory = $this->originalFactory;
        $loaders = new ReflectionProperty(ImplementationLoader::class, 'loaders');
        $this->originalLoaders = $loaders->getValue($factory);
        $loaders->setValue($factory, [...$this->originalLoaders, new ClassMapLoader([self::BRICK => self::OVERRIDE])]);
        $this->resetLoaderCache($factory);
    }

    private function resetLoaderCache(object $factory): void
    {
        (new ReflectionProperty(ImplementationLoader::class, 'loaderCache'))->setValue($factory, []);
    }

    private function createParentWithBrick(string $key = 'one'): Inheritance
    {
        $one = new Inheritance();
        $one->setKey($key);
        $one->setParentId(1);
        $one->setPublished(true);
        $brick = new (self::BRICK)($one);
        $brick->setBrickinput('parenttext');
        $one->getMybricks()->setUnittestBrick($brick);
        $one->save();

        return $one;
    }

    public function testWithoutOverrideBaseClassIsUsed(): void
    {
        $one = $this->createParentWithBrick();
        $loaded = DataObject::getById($one->getId(), ['force' => true]);

        $brick = $loaded->getMybricks()->getUnittestBrick();
        $this->assertSame(self::BRICK, $brick::class);
        $this->assertSame('parenttext', $brick->getBrickinput());
    }

    public function testLoadedBrickUsesOverride(): void
    {
        $one = $this->createParentWithBrick();
        $this->enableOverride();

        $loaded = DataObject::getById($one->getId(), ['force' => true]);
        $brick = $loaded->getMybricks()->getUnittestBrick();

        $this->assertInstanceOf(self::OVERRIDE, $brick);
        $this->assertSame('parenttext', $brick->getBrickinput());
        $this->assertSame($loaded->getId(), $brick->getObject()->getId());
        $this->assertSame('mybricks', $brick->getFieldname());
        $this->assertSame('overridden:parenttext', $brick->overriddenValue());
    }

    public function testConstructorReceivesObjectOnLoad(): void
    {
        $one = $this->createParentWithBrick();
        $this->enableOverride();

        $loaded = DataObject::getById($one->getId(), ['force' => true]);
        $brick = $loaded->getMybricks()->getUnittestBrick();

        $this->assertSame($loaded->getId(), $brick->objectIdSeenInConstructor);
    }

    public function testOverriddenBrickIsSavedAndReloaded(): void
    {
        $one = $this->createParentWithBrick();
        $this->enableOverride();

        $loaded = DataObject::getById($one->getId(), ['force' => true]);
        $loaded->getMybricks()->getUnittestBrick()->setBrickinput('changed');
        $loaded->save();

        $reloaded = DataObject::getById($one->getId(), ['force' => true]);
        $brick = $reloaded->getMybricks()->getUnittestBrick();
        $this->assertInstanceOf(self::OVERRIDE, $brick);
        $this->assertSame('changed', $brick->getBrickinput());
    }

    public function testInheritedBrickCreatedOnChildUsesOverride(): void
    {
        $one = $this->createParentWithBrick();
        $this->enableOverride();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);
        $two->save();

        $reloaded = DataObject::getById($two->getId(), ['force' => true]);
        $brick = $reloaded->getMybricks()->getUnittestBrick();

        $this->assertInstanceOf(self::OVERRIDE, $brick);
        // inherited value from the parent
        $this->assertSame('parenttext', $brick->getBrickinput());
        $this->assertSame($reloaded->getId(), $brick->getObject()->getId());
    }

    public function testEditmodeCreatesOverriddenBrick(): void
    {
        $this->enableOverride();

        $object = new Inheritance();
        $object->setKey('editmode');
        $object->setParentId(1);
        $object->setPublished(true);
        $object->save();

        /** @var DataObject\ClassDefinition\Data\Objectbricks $fd */
        $fd = $object->getClass()->getFieldDefinition('mybricks');
        $data = [
            [
                'type' => 'unittestBrick',
                'data' => ['brickinput' => 'fromeditmode'],
            ],
        ];
        $container = $fd->getDataFromEditmode($data, $object);
        $brick = $container->getUnittestBrick();

        $this->assertInstanceOf(self::OVERRIDE, $brick);
        $this->assertSame('fromeditmode', $brick->getBrickinput());
        $this->assertSame($object->getId(), $brick->getObject()->getId());
        $this->assertSame($object->getId(), $brick->objectIdSeenInConstructor);
    }
}
