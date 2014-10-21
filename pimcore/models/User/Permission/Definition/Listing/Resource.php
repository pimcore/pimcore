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
 * @package    User
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\User\Permission\Definition\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of definitions for the specicified parameters, returns an array of User\Permission\Definition elements
     *
     * @return array
     */
    public function load() {

        $definitions = array();
        $definitionsData = $this->db->fetchAll("SELECT * FROM users_permission_definitions" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($definitionsData as $definitionData) {
            $definition = new Model\User\Permission\Definition($definitionData);
            $definitions[] = $definition;
        }

        $this->model->setDefinitions($definitions);
        return $definitions;
    }

}
