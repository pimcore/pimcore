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

use Pimcore\Model\DataObject\Classificationstore;

/**
 * GeneralTest against a store on the document engine: the values of every key type, their languages and their fallbacks
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * @group model.datatype.classificationstore
 */
class DocumentGeneralTest extends GeneralTest
{
    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    /**
     * The store is shared across the suite and testClassificationStoreQuantityValue adds a key to it;
     * left there, it would change the key count GeneralTest::testBasics expects of the store.
     */
    public function tearDown(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $group = Classificationstore\GroupConfig::getByName('testgroupQvalue');
        $key = $store ? Classificationstore\KeyConfig::getByName('qValue', $store->getId()) : null;

        $key?->delete();
        $group?->delete();

        parent::tearDown();
    }
}
