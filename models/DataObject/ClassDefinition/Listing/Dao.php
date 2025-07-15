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
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\ClassDefinition\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of object-classes for the specicifies parameters, returns an array of DataObject\ClassDefinition elements
     *
     */
    public function load(): array
    {
        $classes = [];

        $classesRaw = $this->db->fetchFirstColumn('SELECT id FROM classes' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($classesRaw as $classRaw) {
            if ($class = DataObject\ClassDefinition::getById($classRaw)) {
                $classes[] = $class;
            }
        }

        $this->model->setClasses($classes);

        return $classes;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM classes ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
