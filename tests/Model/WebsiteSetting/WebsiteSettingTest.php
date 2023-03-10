<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\WebsiteSetting;

use Pimcore\Model\WebsiteSetting;
use Pimcore\Tests\Support\Test\ModelTestCase;

class WebsiteSettingTest extends ModelTestCase
{
    public function test(): void
    {
        $a = new WebsiteSetting();
        $a->setType('text');
        $a->setName('test');
        $a->setData('a');
        $a->save();

        $b = new WebsiteSetting();
        $b->setType('text');
        $b->setName('test');
        $b->setData('b');
        $b->setSiteId(1);
        $b->save();

        $c = new WebsiteSetting();
        $c->setType('text');
        $c->setName('test');
        $c->setData('c');
        $c->setLanguage('en');
        $c->save();

        $d = new WebsiteSetting();
        $d->setType('text');
        $d->setName('test');
        $d->setData('d');
        $d->setLanguage('en');
        $d->setSiteId(1);
        $d->save();

        $this->assertEquals('a', WebsiteSetting::getByName('test')->getData());
        $this->assertEquals('b', WebsiteSetting::getByName('test', 1)->getData());
        $this->assertEquals('d', WebsiteSetting::getByName('test', 1, 'en')->getData());
        $this->assertEquals('b', WebsiteSetting::getByName('test', 1, 'de')->getData());
        $this->assertEquals('a', WebsiteSetting::getByName('test', 2)->getData());
        $this->assertEquals('c', WebsiteSetting::getByName('test', 2, 'en')->getData());
        $this->assertEquals('a', WebsiteSetting::getByName('test', 2, 'de')->getData());
        $this->assertEquals('c', WebsiteSetting::getByName('test', null, 'en')->getData());
        $this->assertEquals('a', WebsiteSetting::getByName('test', null, 'de')->getData());
        $this->assertEquals(null, WebsiteSetting::getByName('test2'));
    }
}
