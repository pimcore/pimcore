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
 * @package    Object
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Folder_Resource extends Object_Abstract_Resource {


    public function getClasses() {

        $classIds = $this->db->fetchAll("SELECT o_classId FROM objects WHERE o_path LIKE ? AND o_type = 'object' GROUP BY o_classId", $this->model->getFullPath() . "%");

        $classes = array();
        foreach ($classIds as $classId) {
            $classes[] = Object_Class::getById($classId["o_classId"]);
        }

        return $classes;
    }

}
