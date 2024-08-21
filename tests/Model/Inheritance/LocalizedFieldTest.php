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

namespace Pimcore\Tests\Model\Inheritance;

use Exception;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Helper\Pimcore;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool;
use Pimcore\Version;

class LocalizedFieldTest extends ModelTestCase
{
    protected array $originalConfig;

    protected SystemSettingsConfig $config;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        \Pimcore::setAdminMode();

        if (Version::getMajorVersion() >= 11) {
            $pimcoreModule = $this->getModule('\\'.Pimcore::class);
            $this->config = $pimcoreModule->grabService(SystemSettingsConfig::class);
            $this->originalConfig = $this->config->get();
        } else {
            $this->originalConfig = \Pimcore\Config::getSystemConfiguration();
        }

    }

    public function tearDown(): void
    {
        if (Version::getMajorVersion() >= 11) {
            $this->config->testSave($this->originalConfig);
        } else {
            \Pimcore\Config::setSystemConfiguration($this->originalConfig);
        }

        parent::tearDown();
    }

    public function testFallback(): void
    {
        $configuration = $this->originalConfig;
        $configuration['general']['fallback_languages']['de'] = 'en';

        if (Version::getMajorVersion() >= 11) {
            $this->config->testSave($configuration);
        } else {
            \Pimcore\Config::setSystemConfiguration($configuration);
        }
        // create root -> one -> two -> three
        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);
        $one->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);
        $two->save();

        $one->setInput('abc', 'en');
        $one->save();

        $one->save();

        $db = Db::get();

        $query = 'SELECT * FROM object_localized_query_' . $two->getClassId() . '_de where ooo_id = ' . $two->getId();
        $result = $db->fetchAssociative($query);
        $this->assertEquals($result['input'], 'abc');

        $query = 'SELECT * FROM object_localized_query_' . $two->getClassId() . '_en where ooo_id = ' . $two->getId();
        $result = $db->fetchAssociative($query);
        $this->assertEquals($result['input'], 'abc');

        $query = 'SELECT * FROM object_localized_query_' . $two->getClassId() . '_fr where ooo_id = ' . $two->getId();
        $result = $db->fetchAssociative($query);
        $this->assertNull($result['input']);
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *        |-two
     *
     * two is created after one, en fields inherited. two gets moved out and moved in again. Then one gets updated.
     */
    public function testInheritance(): void
    {
        // According to the bootstrap file en and de are valid website languages

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);

        $one->setInput('parenttextEN', 'en');
        $one->setInput('parenttextDE', 'de');
        $one->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);

        $two->setInput('childtextDE', 'de');
        $two->save();

        $three = new Inheritance();
        $three->setKey('three');
        $three->setParentId($two->getId());
        $three->setPublished(true);
        $three->save();

        $id1 = $one->getId();
        $id2 = $two->getId();
        $id3 = $three->getId();

        $one = DataObject::getById($id1);
        $two = DataObject::getById($id2);
        $three = DataObject::getById($id3);

        $three->delete();

        $this->assertEquals('parenttextEN', $one->getInput('en'));
        $this->assertEquals('parenttextEN', $two->getInput('en'));
        $this->assertEquals('parenttextEN', $three->getInput('en'));

        $three->delete();

        $this->assertEquals('parenttextDE', $one->getInput('de'));
        $this->assertEquals('childtextDE', $two->getInput('de'));

        // null it out
        $two->setInput(null, 'de');
        $two->save();

        $two = DataObject::getById($id2);
        $this->assertEquals('parenttextDE', $two->getInput('de'));

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('de');

        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), 'Expected two list items for de');

        // set it back
        $two->setInput('childtextDE', 'de');
        $two->save();
        $two = DataObject::getById($id2);

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('en');

        $listItems = $list->load();
        $this->assertEquals(2, count($listItems), 'Expected two list items for en');

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('de');

        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), 'Expected one list item for de');

        DataObject\Service::useInheritedValues(false, function () use ($id2) {
            $two = DataObject::getById($id2);
            $this->assertEquals(null, $two->getInput('en'));
            $this->assertEquals('childtextDE', $two->getInput('de'));
        });

        // now move it out

        $two->setParentId(1);
        $two->save();

        $this->assertEquals(null, $two->getInput('en'));
        $this->assertEquals('childtextDE', $two->getInput('de'));

        // and move it back in

        $two->setParentId($id1);
        $two->save();

        $this->assertEquals('parenttextEN', $two->getInput('en'));
        $this->assertEquals('childtextDE', $two->getInput('de'));

        // modify parent object
        $one->setInput('parenttextEN2', 'en');
        $one->save();

        $two = DataObject::getById($id2);
        $this->assertEquals('parenttextEN2', $two->getInput('en'));

        // now turn inheritance off
        $class = $one->getClass();
        $class->setAllowInherit(false);
        $class->save();

        $one = DataObject::getById($id2);
        $two = DataObject::getById($id2);

        // save both objects again
        $one->save();
        $two->save();

        $two = DataObject::getById($id2);
        $this->assertEquals(null, $two->getInput('en'));

        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('en');

        $listItems = $list->load();
        $this->assertEquals(1, count($listItems), 'Expected one list item for en');

        // turn it back on
        $class->setAllowInherit(true);
        $class->save();
    }

    public function testInvalidLocaleList(): void
    {
        $this->expectException(Exception::class);
        $this->markTestSkipped('TODO: the following test should fail, but no exception is thrown');

        // invalid locale
        $list = new Inheritance\Listing();
        $list->setCondition("input LIKE '%parenttext%'");
        $list->setLocale('xx');

        $listItems = $list->load();
    }

    public function testQueryTable(): void
    {
        // create root -> one -> two -> three

        $one = new Inheritance();
        $one->setKey('one');
        $one->setParentId(1);
        $one->setPublished(true);
        $one->save();

        $two = new Inheritance();
        $two->setKey('two');
        $two->setParentId($one->getId());
        $two->setPublished(true);
        $two->save();

        $three = new Inheritance();
        $three->setKey('three');
        $three->setParentId($two->getId());
        $three->setPublished(true);
        $three->save();

        $id1 = $one->getId();
        $id2 = $two->getId();
        $id3 = $three->getId();

        $db = Db::get();
        $query = 'SELECT * FROM object_localized_data_inheritance WHERE ooo_id = ' . $two->getId() . ' GROUP BY ooo_id';
        $result = $db->fetchAllAssociative($query);
        // pick the language
        $this->assertCount(1, $result);

        $groupByLanguage = $result[0]['language'];
        $validLanguages = Tool::getValidLanguages();
        $this->assertTrue(in_array($groupByLanguage, $validLanguages), 'not in valid languages');
        if (count($validLanguages) < 2) {
            $this->markTestSkipped('need at least two languages');
        }

        $otherLanguage = null;
        foreach ($validLanguages as $language) {
            if ($language != $groupByLanguage) {
                $otherLanguage = $language;

                break;
            }
        }

        $this->assertTrue(strlen($otherLanguage) > 0, 'need alternative language');

        $two->setInput('SOMEINPUT', $groupByLanguage);
        $two->save();
        // check that it is in the query table for the $groupByLanguage
        $result = $db->fetchAllAssociative('SELECT * from object_localized_query_inheritance_' . $groupByLanguage . ' WHERE ooo_id = ' . $two->getId());
        $this->assertEquals('SOMEINPUT', $result[0]['input']);

        // and null for the alternative language
        $result = $db->fetchAllAssociative('SELECT * from object_localized_query_inheritance_' . $otherLanguage . ' WHERE ooo_id = ' . $two->getId());
        $this->assertEquals(null, $result[0]['input']);

        // now update the parent for the alternative language, use the same value !!!
        $one->setInput('SOMEINPUT', $otherLanguage);
        $one->save();

        // now the alternative input value in the query table should be SOMEINPUT as well!!!
        $result = $db->fetchAllAssociative('SELECT * from object_localized_query_inheritance_' . $otherLanguage . ' WHERE ooo_id = ' . $two->getId());
        $this->assertEquals('SOMEINPUT', $result[0]['input']);

        var_dump($result);
    }
}
