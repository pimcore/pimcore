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

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Localizedfield\Dao;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool;

/**
 * Saving localized fields relies on the statically known key columns of the localized tables
 * instead of looking the primary key up from the database schema for every language. These
 * tests verify the static columns stay in sync with the primary keys of the actually
 * created tables.
 *
 * @group model.datatype.localizedfield
 */
class LocalizedfieldDaoTest extends ModelTestCase
{
    public function testTableKeyColumnsMatchPrimaryKeyInClassContext(): void
    {
        $object = TestHelper::createEmptyObject();
        $object->setLinput('some value', 'en');
        $object->save();

        $localizedfield = new Localizedfield();
        $localizedfield->setObject($object);

        /** @var Dao $dao */
        $dao = $localizedfield->getDao();

        $this->assertEqualsCanonicalizing(
            $dao->getPrimaryKey($dao->getTableName(), false),
            $dao->getTableKeyColumns()
        );
    }

    public function testQueryTableKeyColumnsMatchPrimaryKeyForEveryLanguage(): void
    {
        $object = TestHelper::createEmptyObject();

        $localizedfield = new Localizedfield();
        $localizedfield->setObject($object);

        /** @var Dao $dao */
        $dao = $localizedfield->getDao();

        $validLanguages = Tool::getValidLanguages();
        $this->assertNotEmpty($validLanguages);

        foreach ($validLanguages as $language) {
            $this->assertEqualsCanonicalizing(
                $dao->getPrimaryKey($dao->getQueryTableName() . '_' . $language, false),
                $dao->getQueryTableKeyColumns(),
                sprintf('key columns of the query table for language "%s"', $language)
            );
        }
    }

    public function testTableKeyColumnsMatchPrimaryKeyInFieldcollectionContext(): void
    {
        $object = TestHelper::createEmptyObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\Unittestfieldcollection();
        $item->setLinput('some value', 'en');
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();

        $localizedfield = new Localizedfield();
        $localizedfield->setObject($object);
        $localizedfield->setContext([
            'containerType' => 'fieldcollection',
            'containerKey' => 'unittestfieldcollection',
            'fieldname' => 'fieldcollection',
        ]);

        /** @var Dao $dao */
        $dao = $localizedfield->getDao();

        $this->assertEqualsCanonicalizing(
            $dao->getPrimaryKey($dao->getTableName(), false),
            $dao->getTableKeyColumns()
        );
    }

    /**
     * Regression test: a missing language table is created on the fly and the save is retried. The first, failed
     * run must restore the fallback values flag, otherwise the retry takes the leaked value as the one to restore
     * and it stays switched on for the rest of the process.
     */
    public function testRetryAfterMissingLanguageTableRestoresFallbackValues(): void
    {
        $object = TestHelper::createEmptyObject();

        $localizedfield = new Localizedfield();
        $localizedfield->setObject($object);

        /** @var Dao $dao */
        $dao = $localizedfield->getDao();
        $db = Db::get();
        $db->executeStatement('DROP TABLE ' . $db->quoteIdentifier($dao->getQueryTableName() . '_en'));

        // the CLI bootstrap enables the fallback values, Studio requests run without them
        $originalFallbackValues = Localizedfield::getGetFallbackValues();
        Localizedfield::setGetFallbackValues(false);

        try {
            $object->setLinput('some value', 'en');
            $object->save();
        } finally {
            $fallbackValues = Localizedfield::getGetFallbackValues();
            Localizedfield::setGetFallbackValues($originalFallbackValues);
        }

        $this->assertFalse($fallbackValues);
        $this->assertSame('some value', Concrete::getById($object->getId(), ['force' => true])->getLinput('en'));
    }

    /**
     * Regression test: the localized views fall back to the next language when a value is NULL or an empty
     * string. A date column cannot hold an empty string and MySQL 8 rejects comparing it with one
     * ("1525 Incorrect DATE value"), so temporal columns may only be checked for NULL.
     */
    public function testViewDoesNotCompareTemporalColumnsWithEmptyString(): void
    {
        $className = 'LfDateView';

        $date = new Data\Date();
        $date->setName('day');
        $date->setTitle('day');
        $date->setColumnType('date');

        $datetime = new Data\Datetime();
        $datetime->setName('moment');
        $datetime->setTitle('moment');
        $datetime->setColumnType('datetime');

        $title = new Data\Input();
        $title->setName('title');
        $title->setTitle('title');

        $localizedFields = new Localizedfields();
        $localizedFields->setName('localizedfields');
        $localizedFields->setChildren([$title, $date, $datetime]);

        $panel = new Panel();
        $panel->setName('pimcore_root');
        $panel->setChildren([$localizedFields]);

        $class = ClassDefinition::getByName($className) ?? new ClassDefinition();
        $class->setName($className);
        $class->setLayoutDefinitions($panel);
        $class->save();

        try {
            $db = Db::get();
            $view = 'object_localized_' . $class->getId() . '_de';
            $definition = (string) $db->fetchOne(
                'SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$view]
            );

            $this->assertNotSame('', $definition, 'the localized view must exist');
            $this->assertStringContainsString('`title`', $definition);
            $this->assertMatchesRegularExpression('/strcmp\(`?[a-z_]+`?\.`title`/i', $definition, 'text columns keep the empty check');
            $this->assertDoesNotMatchRegularExpression('/strcmp\(`?[a-z_]+`?\.`(day|moment)`/i', $definition, 'temporal columns must not be compared with an empty string');
        } finally {
            $class->delete();
        }
    }
}
