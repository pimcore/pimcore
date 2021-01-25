<?php

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Test\ModelTestCase;

class CompositeIndexTest extends ModelTestCase
{
    public function testAddIndex()
    {
        $classId = Unittest::classId();
        $db = Db::get();
        try {
            $db->query('ALTER TABLE `object_query_' . $classId . '` DROP INDEX `mycomposite`');
            $this->fail('expected that the index does not exist yet');
        } catch (\Exception $e) {
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
        $db->query('ALTER TABLE `object_query_' . $classId . '` DROP INDEX `c_mycomposite`');
    }
}
