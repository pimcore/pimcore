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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Cache;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Tests\Support\Util\TestHelper;

class PasswordTest extends AbstractClassificationStoreTest
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testPasswordKeyIsHashedOnSave(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName('testgroupPassword');
        $group->save();

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName('pw');
        $keyConfig->setDescription('pw');
        $keyConfig->setEnabled(true);
        $keyConfig->setType('password');
        $definition = new Password();
        $definition->setName('pw');
        $keyConfig->setDefinition(json_encode($definition));
        $keyConfig->save();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId($keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(1);
        $relation->save();

        $inputKey = Classificationstore\KeyConfig::getByName('input', $store->getId());
        $otherGroup = Classificationstore\GroupConfig::getByName('testgroup1');

        $o = new Csstore();
        $o->setParentId(1);
        $o->setKey('pwobject');
        $o->setPublished(true);
        $o->save();
        Cache::clearAll();

        $plain = 'Plain-Value-123';
        $cs = $o->getCsstore();
        // set another key first so the store is already dirty
        $cs->setLocalizedKeyValue($otherGroup->getId(), $inputKey->getId(), 'abc');
        $cs->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), $plain);
        $o->save();

        $table = 'object_classificationstore_data_' . $o->getClass()->getId();
        $stored = Db::get()->fetchOne(
            'SELECT `value` FROM ' . $table . ' WHERE id = :id AND keyId = :keyId',
            ['id' => $o->getId(), 'keyId' => $keyConfig->getId()]
        );

        $this->assertIsString($stored);
        $this->assertNotSame($plain, $stored);
        $this->assertNotNull(password_get_info($stored)['algo']);
        $this->assertTrue(password_verify($plain, $stored));

        $this->assertNotSame($plain, $cs->getLocalizedKeyValue($group->getId(), $keyConfig->getId()));

        // reload from the database: the persisted value is a verifiable hash
        Cache::clearAll();
        $reloaded = Csstore::getById($o->getId(), ['force' => true]);
        $reloadedValue = $reloaded->getCsstore()->getLocalizedKeyValue($group->getId(), $keyConfig->getId());
        $this->assertIsString($reloadedValue);
        $this->assertSame($stored, $reloadedValue);
        $this->assertTrue(password_verify($plain, $reloadedValue));

        // a second save keeps the very same hash
        $reloaded->setKey('pwobject2');
        $reloaded->getCsstore()->setLocalizedKeyValue($otherGroup->getId(), $inputKey->getId(), 'def');
        $reloaded->save();

        $storedAgain = Db::get()->fetchOne(
            'SELECT `value` FROM ' . $table . ' WHERE id = :id AND keyId = :keyId',
            ['id' => $o->getId(), 'keyId' => $keyConfig->getId()]
        );
        $this->assertSame($stored, $storedAgain);
        $this->assertTrue(password_verify($plain, $storedAgain));
    }
}
