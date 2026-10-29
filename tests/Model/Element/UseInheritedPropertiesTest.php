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

namespace Pimcore\Tests\Model\Element;

use Codeception\Test\Unit;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Property;
use RuntimeException;

/**
 * @group model.element.properties
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
}
