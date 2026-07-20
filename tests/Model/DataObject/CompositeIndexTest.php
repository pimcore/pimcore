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

use Exception;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Test\ModelTestCase;

class CompositeIndexTest extends ModelTestCase
{
    public function testAddIndex(): void
    {
        $classId = Unittest::classId();
        $db = Db::get();

        try {
            $db->executeQuery('ALTER TABLE `object_query_' . $classId . '` DROP INDEX `mycomposite`');
            $this->fail('expected that the index does not exist yet');
        } catch (Exception $e) {
        }

        $definition = ClassDefinition::getById($classId);
        $definition->setCompositeIndices([
           [
               'index_key' => 'mycomposite',
               'index_type' => 'query',
               'index_columns' => [
                   'slider', 'number',
               ],
           ],
        ]);

        $definition->save();

        // this will throw an exception if the index does not exist
        $db->executeQuery('ALTER TABLE `object_query_' . $classId . '` DROP INDEX `c_mycomposite`');
    }
}
