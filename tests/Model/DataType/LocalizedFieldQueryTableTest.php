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

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Support\Helper\Pimcore;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * Covers the number of statements the localized field DAO issues against the per-language query
 * tables. Every additional statement in there is executed once per language, so it directly
 * multiplies the cost of saving an object on installations with many languages.
 *
 * @see https://github.com/pimcore/pimcore/issues/11757
 */
class LocalizedFieldQueryTableTest extends ModelTestCase
{
    private DebugDataHolder $debugDataHolder;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $container = $this->getModule('\\'.Pimcore::class)->_getContainer();
        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('The Doctrine debug data holder is only registered when the kernel runs in debug mode.');
        }

        $this->debugDataHolder = $container->get('doctrine.debug_data_holder');
    }

    /**
     * The stored query table data is only used to decide which fields have to be propagated to the
     * child objects, so it must not be read at all when the class does not allow inheritance.
     */
    public function testQueryTableIsNotReadWhenInheritanceIsDisabled(): void
    {
        $object = TestHelper::createEmptyObject();
        $this->assertFalse($object->getClass()->getAllowInherit(), 'precondition: test class must not allow inheritance');
        $object->save();

        $languages = Tool::getValidLanguages();
        $this->assertGreaterThan(1, count($languages), 'precondition: more than one language has to be configured');

        foreach ($languages as $language) {
            $object->setLinput('value-'.$language, $language);
        }

        $this->debugDataHolder->reset();
        $object->save();

        $this->assertSame(
            [],
            $this->grabQueryTableReads(),
            'saving an object of a class without inheritance must not read the localized query tables'
        );

        // the data still has to end up in the query tables
        $db = Db::get();
        foreach ($languages as $language) {
            $value = $db->fetchOne(
                'SELECT `linput` FROM object_localized_query_'.$object->getClassId().'_'.$language.' WHERE ooo_id = ?',
                [$object->getId()]
            );
            $this->assertSame('value-'.$language, $value, 'query table of language '.$language);
        }
    }

    /**
     * Counterpart of the test above: with inheritance enabled the stored data is needed to detect
     * the changed fields, so it still has to be read.
     */
    public function testQueryTableIsReadWhenInheritanceIsEnabled(): void
    {
        $object = new Inheritance();
        $object->setKey('inheritance-query-table');
        $object->setParentId(1);
        $object->setPublished(true);
        $this->assertTrue($object->getClass()->getAllowInherit(), 'precondition: test class must allow inheritance');
        $object->save();

        $object->setInput('some value', 'en');

        $this->debugDataHolder->reset();
        $object->save();

        $this->assertNotEmpty(
            $this->grabQueryTableReads(),
            'saving an object of a class with inheritance still has to read the localized query tables'
        );
    }

    /**
     * The skipped read used to be the first statement touching the per-language query table and
     * therefore triggered the deferred creation of a missing language table. With inheritance
     * disabled that role is taken over by the upsert, so a language table that does not exist yet
     * still has to be created and the save has to be retried.
     */
    public function testMissingLanguageQueryTableIsRecreatedWhenInheritanceIsDisabled(): void
    {
        $object = TestHelper::createEmptyObject();
        $this->assertFalse($object->getClass()->getAllowInherit(), 'precondition: test class must not allow inheritance');
        $object->save();

        $languages = Tool::getValidLanguages();
        $language = (string)end($languages);
        $queryTable = 'object_localized_query_'.$object->getClassId().'_'.$language;

        $db = Db::get();
        $db->executeStatement('DROP TABLE '.$queryTable);

        // a language table that was never written to also has no cached column information, so drop it
        // here as well to end up in the same state as after adding a language to the system settings
        RuntimeCache::getInstance()->offsetUnset(Model\Dao\AbstractDao::CACHEKEY.$queryTable);
        Cache::clearTags(['system', 'resource']);

        $this->assertFalse($this->tableExists($queryTable), 'precondition: the language query table has to be gone');

        $object->setLinput('recreated-'.$language, $language);
        $object->save();

        $this->assertTrue($this->tableExists($queryTable), 'the missing language query table has to be created again');
        $this->assertSame(
            'recreated-'.$language,
            $db->fetchOne('SELECT `linput` FROM '.$queryTable.' WHERE ooo_id = ?', [$object->getId()]),
            'the retried save has to persist the value into the recreated table'
        );
    }

    private function tableExists(string $table): bool
    {
        return (bool)Db::get()->fetchOne('SHOW TABLES LIKE '.Db::get()->quote($table));
    }

    /**
     * @return string[]
     */
    private function grabQueryTableReads(): array
    {
        $reads = [];
        foreach ($this->debugDataHolder->getData() as $queries) {
            foreach ($queries as $query) {
                $sql = preg_replace('/\s+/', ' ', (string)$query['sql']);
                if (preg_match('/^SELECT \* FROM object_localized_query_/i', $sql)) {
                    $reads[] = $sql;
                }
            }
        }

        return $reads;
    }
}
