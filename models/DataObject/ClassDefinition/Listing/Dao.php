<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Listing;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\ClassDefinition\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of object-classes for the specific parameters, returns an array of DataObject\ClassDefinition elements
     *
     * @return DataObject\ClassDefinition[]
     *
     * @throws \Doctrine\DBAL\Exception     *
     */
    public function load(): array
    {
        $classes = [];

        $classesData = $this->db->fetchAllAssociative(
            sprintf(
                'SELECT %s, %s FROM classes',
                $this->db->quoteIdentifier('id'),
                $this->db->quoteIdentifier('name')
            ) .
            $this->getCondition() .
            $this->getOrder() .
            $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );

        foreach ($classesData as $classData) {
            $class = $this->buildModel(
                $classData['id'],
                $classData['name'],
                $this->model->getForce()
            );
            if ($class) {
                $classes[] = $class;
            }
        }

        $this->model->setClasses($classes);

        return $classes;
    }

    public function buildModel(string $id, string $name, bool $force = false): ?ClassDefinition
    {
        $cacheKey = 'class_' . $id;

        try {
            if ($force) {
                throw new Exception('Forced load');
            }
            $class = RuntimeCache::get($cacheKey);
            if (!$class) {
                throw new Exception('Class in registry is null');
            }
        } catch (Exception $e) {
            try {
                $class = new ClassDefinition();
                if (!$name) {
                    throw new Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
                }

                $definitionFile = $class->getDefinitionFile($name);
                $class = @include $definitionFile;

                if (!$class instanceof ClassDefinition) {
                    throw new Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
                }

                $class->setId($id);

                RuntimeCache::set($cacheKey, $class);
            } catch (Exception $e) {
                Logger::info($e->getMessage());

                return null;
            }
        }

        return $class;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM classes ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
