<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Listing;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\ClassDefinition\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of object-classes for the specicifies parameters, returns an array of DataObject\ClassDefinition elements
     *
     * @return array
     */
    public function load()
    {
        $classes = [];

        $classesRaw = $this->db->fetchCol('SELECT id FROM classes' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($classesRaw as $classRaw) {
            $classes[] = DataObject\ClassDefinition::getById($classRaw);
        }

        $this->model->setClasses($classes);

        return $classes;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM classes ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
