<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Listing;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of object-classes for the specicifies parameters, returns an array of Object|Class elements
     *
     * @return array
     */
    public function load() {

        $classes = array();

        $classesRaw = $this->db->fetchCol("SELECT id FROM classes" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($classesRaw as $classRaw) {
            $classes[] = Object\ClassDefinition::getById($classRaw);

        }

        $this->model->setClasses($classes);

        return $classes;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM classes " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }
}
