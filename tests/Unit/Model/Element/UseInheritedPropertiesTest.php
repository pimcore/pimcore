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

namespace Pimcore\Tests\Unit\Model\Element;

use Codeception\Test\Unit;
use Pimcore\Model\Document\Dao;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Hardlink\Wrapper\Page as WrapperPage;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Property;
use RuntimeException;

/**
 * @group model.element.properties
 *
 * @internal
 */
class UseInheritedPropertiesTest extends Unit
{
    private bool $backup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backup = AbstractElement::getGetInheritedProperties();
        AbstractElement::setGetInheritedProperties(true);
    }

    protected function tearDown(): void
    {
        AbstractElement::setGetInheritedProperties($this->backup);
        parent::tearDown();
    }

    private function createPage(): Page
    {
        $own = (new Property())->setName('own')->setType('text')->setData('a')->setInherited(false);
        $inherited = (new Property())->setName('inherited')->setType('text')->setData('b')->setInherited(true);

        $page = new Page();
        $page->setProperties(['own' => $own, 'inherited' => $inherited]);

        return $page;
    }

    public function testDisablingExcludesInheritedProperties(): void
    {
        $page = $this->createPage();

        $names = Service::useInheritedProperties(false, static fn (Page $p) => array_keys($p->getProperties()), [$page]);

        $this->assertSame(['own'], $names);
        $this->assertTrue(AbstractElement::getGetInheritedProperties());
    }

    public function testEnablingIncludesInheritedPropertiesAndRestoresFlag(): void
    {
        AbstractElement::setGetInheritedProperties(false);
        $page = $this->createPage();

        $names = Service::useInheritedProperties(true, static fn () => array_keys($page->getProperties()));

        $this->assertSame(['own', 'inherited'], $names);
        $this->assertFalse(AbstractElement::getGetInheritedProperties());
    }

    public function testFlagIsRestoredWhenCallableThrows(): void
    {
        try {
            Service::useInheritedProperties(false, static function (): void {
                throw new RuntimeException('boom');
            });
            $this->fail('Exception expected');
        } catch (RuntimeException) {
            $this->assertTrue(AbstractElement::getGetInheritedProperties());
        }
    }

    public function testNestedCallsRestoreTheOuterSetting(): void
    {
        $page = $this->createPage();

        $result = Service::useInheritedProperties(false, function () use ($page): array {
            $inner = Service::useInheritedProperties(true, static fn () => array_keys($page->getProperties()));

            return [$inner, AbstractElement::getGetInheritedProperties(), array_keys($page->getProperties())];
        });

        $this->assertSame([['own', 'inherited'], false, ['own']], $result);
        $this->assertTrue(AbstractElement::getGetInheritedProperties());
    }

    public function testReturnValueAndArgumentsArePassedThrough(): void
    {
        $this->assertSame(5, Service::useInheritedProperties(false, static fn (int $a, int $b) => $a + $b, [2, 3]));
    }

    public function testSetAndRemovePropertyKeepInheritedPropertiesInsideTheWrapper(): void
    {
        $page = $this->createPage();

        Service::useInheritedProperties(false, static function () use ($page): void {
            $page->setProperty('added', 'text', 'c');
            $page->removeProperty('own');
        });

        $this->assertSame(['inherited', 'added'], array_keys($page->getProperties()));
        $this->assertSame('b', $page->getProperty('inherited'));
    }

    /**
     * @return array<string, Property>
     */
    private function createProperties(): array
    {
        return [
            'own' => (new Property())->setName('own')->setType('text')->setData('a')->setInherited(false),
            'inherited' => (new Property())->setName('inherited')->setType('text')->setData('b')->setInherited(true),
        ];
    }

    private function createHardlink(): Hardlink
    {
        // the properties are left unset, so that getProperties() builds them from the dao stub
        $dao = $this->createMock(Dao::class);
        $dao->method('getProperties')->willReturn($this->createProperties());

        $hardlink = new Hardlink();
        $hardlink->setId(999999991);
        $hardlink->setDao($dao);

        return $hardlink;
    }

    public function testHardlinkHonoursTheFlagWithoutStoringTheFilteredList(): void
    {
        $hardlink = $this->createHardlink();

        $filtered = Service::useInheritedProperties(false, static fn () => array_keys($hardlink->getProperties()));

        $this->assertSame(['own'], $filtered);
        $this->assertSame(['own', 'inherited'], array_keys($hardlink->getProperties()));
    }

    public function testHardlinkWrapperHonoursTheFlagWithoutStoringTheFilteredList(): void
    {
        $hardlink = new Hardlink();
        $hardlink->setProperties($this->createProperties());

        $wrapper = new WrapperPage();
        $wrapper->setHardLinkSource($hardlink);

        // built while the inheritance is off: the wrapper marks every property of the hardlink as inherited, so none
        // is returned, but the stored list must still be complete
        $filtered = Service::useInheritedProperties(false, static fn () => array_keys($wrapper->getProperties()));

        $this->assertSame([], $filtered);
        $this->assertSame(['own', 'inherited'], array_keys($wrapper->getProperties()));
    }

    private function createSourcePage(): Page
    {
        $page = new Page();
        $page->setProperties([
            'src_own' => (new Property())->setName('src_own')->setType('text')->setData('s')->setInherited(false),
        ]);

        return $page;
    }

    private function createHardlinkWithSource(Page $source, bool $propertiesFromSource): Hardlink
    {
        $dao = $this->createMock(Dao::class);
        $dao->method('getProperties')->willReturn($this->createProperties());

        $hardlink = new class ($source) extends Hardlink {
            public function __construct(private readonly Page $source)
            {
            }

            public function getSourceDocument(): Page
            {
                return $this->source;
            }
        };
        $hardlink->setId(999999992);
        $hardlink->setDao($dao);
        $hardlink->setPropertiesFromSource($propertiesFromSource);

        return $hardlink;
    }

    public function testHardlinkWithPropertiesFromSourceMergesTheSourcePropertiesAsInherited(): void
    {
        $hardlink = $this->createHardlinkWithSource($this->createSourcePage(), true);

        $all = $hardlink->getProperties();
        $this->assertSame(['src_own', 'own', 'inherited'], array_keys($all));
        $this->assertTrue($all['src_own']->isInherited());

        $filtered = Service::useInheritedProperties(false, static fn () => array_keys($hardlink->getProperties()));
        $this->assertSame(['own'], $filtered);
        $this->assertSame(['src_own', 'own', 'inherited'], array_keys($hardlink->getProperties()));
        $this->assertTrue(AbstractElement::getGetInheritedProperties());
    }

    public function testHardlinkWithSourceDocumentReadsTheSourceDaoWithoutPropertiesFromSource(): void
    {
        $sourceDao = $this->createMock(Dao::class);
        $sourceDao->method('getProperties')->willReturn([
            'dao_prop' => (new Property())->setName('dao_prop')->setType('text')->setData('d')->setInherited(false),
        ]);
        $source = new Page();
        $source->setDao($sourceDao);

        $hardlink = $this->createHardlinkWithSource($source, false);

        $filtered = Service::useInheritedProperties(false, static fn () => array_keys($hardlink->getProperties()));

        $this->assertSame(['own'], $filtered);
        $all = $hardlink->getProperties();
        $this->assertSame(['dao_prop', 'own', 'inherited'], array_keys($all));
        $this->assertTrue($all['dao_prop']->isInherited());
    }

    public function testHardlinkWrapperWithSourceDocumentUsesTheSourcePropertiesAndKeepsTheStoredListComplete(): void
    {
        $hardlink = new Hardlink();
        $hardlink->setPropertiesFromSource(true);
        $hardlink->setProperties([
            'hl_prop' => (new Property())->setName('hl_prop')->setType('text')->setData('h')->setInherited(false),
        ]);

        $wrapper = new WrapperPage();
        $wrapper->setHardLinkSource($hardlink);
        $wrapper->setSourceDocument($this->createSourcePage());

        $filtered = Service::useInheritedProperties(false, static fn () => array_keys($wrapper->getProperties()));

        // the source document's own property survives, the hardlink's property is marked inherited and dropped
        $this->assertSame(['src_own'], $filtered);
        $this->assertSame(['src_own', 'hl_prop'], array_keys($wrapper->getProperties()));
        $this->assertTrue(AbstractElement::getGetInheritedProperties());
    }
}
